<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\DependencyInjection;

use Ivory\CKEditorBundle\Exception\DependencyInjectionException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * Ivory CKEditor extension.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class IvoryCKEditorExtension extends ConfigurableExtension
{
    /**
     * {@inheritdoc}
     */
    protected function loadInternal(array $config, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        foreach (array('helper', 'form', 'twig') as $service) {
            $loader->load($service.'.xml');
        }

        $container->setParameter('ivory_ck_editor.form.type.enable', $config['enable']);
        $container->setParameter('ivory_ck_editor.form.type.base_path', $config['base_path']);
        $container->setParameter('ivory_ck_editor.form.type.js_path', $config['js_path']);

        $this->registerResources($container);

        if ($config['enable']) {
            $this->registerConfigs($config, $container);
            $this->registerPlugins($config, $container);
            $this->registerStylesSet($config, $container);
            $this->registerTemplates($config, $container);
        }
    }

    /**
     * Registers the form resources for the PHP/Twig templating engines.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container The container.
     */
    protected function registerResources(ContainerBuilder $container)
    {
        $templatingEngines = $container->getParameter('templating.engines');

        if (in_array('php', $templatingEngines)) {
            $container->setParameter(
                'templating.helper.form.resources',
                array_merge(
                    array('IvoryCKEditorBundle:Form'),
                    $container->getParameter('templating.helper.form.resources')
                )
            );
        }

        if (in_array('twig', $templatingEngines)) {
            $container->setParameter(
                'twig.form.resources',
                array_merge(
                    array('IvoryCKEditorBundle:Form:ckeditor_widget.html.twig'),
                    $container->getParameter('twig.form.resources')
                )
            );
        }
    }

    /**
     * Registers the CKEditor configs.
     *
     * @param array                                                   $config    The CKEditor configuration.
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container The container.
     *
     * @throws \Ivory\CKEditorBundle\Exception\DependencyInjectionException If the default config does not exist.
     */
    protected function registerConfigs(array $config, ContainerBuilder $container)
    {
        if (empty($config['configs'])) {
            return;
        }

        $config = $this->mergeToolbars($config);

        $definition = $container->getDefinition('ivory_ck_editor.config_manager');
        foreach ($config['configs'] as $name => $configuration) {
            $definition->addMethodCall('setConfig', array($name, $configuration));
        }

        if (isset($config['default_config'])) {
            if (!isset($config['configs'][$config['default_config']])) {
                throw DependencyInjectionException::invalidDefaultConfig($config['default_config']);
            }

            $definition->addMethodCall('setDefaultConfig', array($config['default_config']));
        }
    }

    /**
     * Registers the CKEditor plugins.
     *
     * @param array                                                   $config    The CKEditor configuration.
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container The container.
     */
    protected function registerPlugins(array $config, ContainerBuilder $container)
    {
        if (empty($config['plugins'])) {
            return;
        }

        $definition = $container->getDefinition('ivory_ck_editor.plugin_manager');

        foreach ($config['plugins'] as $name => $plugin) {
            $definition->addMethodCall('setPlugin', array($name, $plugin));
        }
    }

    /**
     * Registers the CKEditor styles set.
     *
     * @param array                                                   $config    The CKEditor configuration.
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container The container.
     */
    protected function registerStylesSet(array $config, ContainerBuilder $container)
    {
        if (empty($config['styles'])) {
            return;
        }

        $definition = $container->getDefinition('ivory_ck_editor.styles_set_manager');

        foreach ($config['styles'] as $name => $stylesSet) {
            $definition->addMethodCall('setStylesSet', array($name, $this->fixStylesSet($stylesSet)));
        }
    }

    /**
     * Registers the CKEditor templates.
     *
     * @param array                                                   $config    The CKEditor configuration.
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container The container.
     */
    protected function registerTemplates(array $config, ContainerBuilder $container)
    {
        if (empty($config['templates'])) {
            return;
        }

        $definition = $container->getDefinition('ivory_ck_editor.template_manager');

        foreach ($config['templates'] as $name => $template) {
            $definition->addMethodCall('setTemplate', array($name, $template));
        }
    }

    /**
     * Merges the toolbars into the CKEditor configs.
     *
     * @param array $config The CKEditor configuration.
     *
     * @throws \Ivory\CKEditorBundle\Exception\DependencyInjectionException If a toolbar does not exist.
     *
     * @return array The CKEditor configuration with merged toolbars.
     */
    protected function mergeToolbars(array $config)
    {
        $resolvedToolbars = $this->resolveToolbars($config);
        unset($config['toolbars']);

        foreach ($config['configs'] as $name => $configuration) {
            if (!isset($configuration['toolbar']) || !is_string($configuration['toolbar'])) {
                continue;
            }

            if (!isset($resolvedToolbars[$configuration['toolbar']])) {
                throw DependencyInjectionException::invalidToolbar($configuration['toolbar']);
            }

            $config['configs'][$name]['toolbar'] = $resolvedToolbars[$configuration['toolbar']];
        }

        return $config;
    }

    /**
     * Resolves the CKEditor toolbars.
     *
     * @param array $config The CKEditor configuration.
     *
     * @return array The resolved CKEditor toolbars.
     */
    protected function resolveToolbars(array $config)
    {
        $resolvedToolbars = array();

        foreach ($config['toolbars']['configs'] as $name => $toolbar) {
            $resolvedToolbars[$name] = array();

            foreach ($toolbar as $item) {
                $resolvedToolbars[$name][] = $this->resolveToolbarItem($item, $config['toolbars']['items']);
            }
        }

        return array_merge($this->getDefaultToolbars(), $resolvedToolbars);
    }

    /**
     * Resolves a CKEditor toolbar item.
     *
     * @param string|array $item  The CKEditor item.
     * @param array        $items The CKEditor items.
     *
     * @throws \Ivory\CKEditorBundle\Exception\DependencyInjectionException If the toolbar item does not exist.
     *
     * @return array The resolved CKEditor toolbar item.
     */
    protected function resolveToolbarItem($item, array $items)
    {
        if (is_string($item) && ($item[0] === '@')) {
            $itemName = substr($item, 1);

            if (!isset($items[$itemName])) {
                throw DependencyInjectionException::invalidToolbarItem($itemName);
            }

            return $items[$itemName];
        }

        return $item;
    }

    /**
     * Fixes the CKEditor styles set.
     *
     * @param array $stylesSet The CKEditor styles set.
     *
     * @return array The fixed CKEditor styles set.
     */
    protected function fixStylesSet(array $stylesSet)
    {
        foreach ($stylesSet as &$value) {
            if (empty($value['styles'])) {
                unset($value['styles']);
            }

            if (empty($value['attributes'])) {
                unset($value['attributes']);
            }
        }

        return $stylesSet;
    }

    /**
     * Gets the default CKEditor toolbars.
     *
     * @return array The default CKEditor toolbars.
     */
    protected function getDefaultToolbars()
    {
        return array(
            'full'     => $this->getFullToolbar(),
            'standard' => $this->getStandardToolbar(),
            'basic'    => $this->getBasicToolbar(),
        );
    }

    /**
     * Gets the full CKEditor toolbar.
     *
     * @return array The full CKEditor toolbar.
     */
    protected function getFullToolbar()
    {
        return array(
            array('Source', '-', 'NewPage', 'Preview', 'Print', '-', 'Templates'),
            array('Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'),
            array('Find', 'Replace', '-', 'SelectAll', '-', 'Scayt'),
            array(
                'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'SelectField', 'Button', 'ImageButton',
                'HiddenField',
            ),
            '/',
            array('Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'),
            array(
                'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-',
                'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl',
            ),
            array('Link', 'Unlink', 'Anchor'),
            array('Image', 'FLash', 'Table', 'HorizontalRule', 'SpecialChar', 'Smiley', 'PageBreak', 'Iframe'),
            '/',
            array('Styles', 'Format', 'Font', 'FontSize', 'TextColor', 'BGColor'),
            array('Maximize', 'ShowBlocks'),
            array('About'),
        );
    }

    /**
     * Gets the standard CKEditor toolbar.
     *
     * @return array The standard CKEditor toolbar.
     */
    protected function getStandardToolbar()
    {
        return array(
            array('Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'),
            array('Scayt'),
            array('Link', 'Unlink', 'Anchor'),
            array('Image', 'Table', 'HorizontalRule', 'SpecialChar'),
            array('Maximize'),
            array('Source'),
            '/',
            array('Bold', 'Italic', 'Strike', '-', 'RemoveFormat'),
            array('NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote'),
            array('Styles', 'Format', 'About'),
        );
    }

    /**
     * Gets the basic CKEditor toolbar.
     *
     * @return array The basic CKEditor toolbar.
     */
    protected function getBasicToolbar()
    {
        return array(
            array('Bold', 'Italic'),
            array('NumberedList', 'BulletedList', '-', 'Outdent', 'Indent'),
            array('Link', 'Unlink'),
            array('About'),
        );
    }
}
