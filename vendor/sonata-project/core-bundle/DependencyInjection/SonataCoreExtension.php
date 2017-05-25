<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\DependencyInjection;

use Sonata\CoreBundle\Form\FormHelper;
use Sonata\CoreBundle\Serializer\BaseSerializerHandler;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class SonataCoreExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig('sonata_admin');

        foreach ($configs as $config) {
            if (isset($config['options']['form_type'])) {
                $container->prependExtensionConfig(
                    $this->getAlias(),
                    array('form_type' => $config['options']['form_type'])
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();

        // NEXT_MAJOR : remove this if block
        if (!interface_exists('JMS\Serializer\Handler\SubscribingHandlerInterface')) {
            /* Let's check for config values before the configuration is processed,
             * otherwise we won't be able to tell,
             * since there is a default value for this option. */
            foreach ($configs as $config) {
                if (isset($config['serializer'])) {
                    @trigger_error(<<<'EOT'
Setting the sonata_core -> serializer -> formats option
without having the jms/serializer library installed is deprecated since 3.1,
and will not be supported in 4.0,
because the configuration option will not be added in that case.
EOT
                    , E_USER_DEPRECATED);
                }
            }
        }
        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('date.xml');
        $loader->load('flash.xml');
        $loader->load('form_types.xml');
        $loader->load('validator.xml');
        $loader->load('twig.xml');
        $loader->load('model_adapter.xml');
        $loader->load('core.xml');

        $this->registerFlashTypes($container, $config);
        $container->setParameter('sonata.core.form_type', $config['form_type']);

        $this->configureFormFactory($container, $config);
        $this->configureClassesToCompile();

        $this->deprecateSlugify($container);

        $this->configureSerializerFormats($config);
    }

    public function configureClassesToCompile()
    {
        $this->addClassesToCompile(array(
            'Sonata\\CoreBundle\\Form\\Type\\BooleanType',
            'Sonata\\CoreBundle\\Form\\Type\\CollectionType',
            'Sonata\\CoreBundle\\Form\\Type\\DateRangeType',
            'Sonata\\CoreBundle\\Form\\Type\\DateTimeRangeType',
            'Sonata\\CoreBundle\\Form\\Type\\EqualType',
            'Sonata\\CoreBundle\\Form\\Type\\ImmutableArrayType',
            'Sonata\\CoreBundle\\Form\\Type\\TranslatableChoiceType',
        ));
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    public function configureFormFactory(ContainerBuilder $container, array $config)
    {
        if (!$config['form']['mapping']['enabled'] || version_compare(Kernel::VERSION, '2.8', '<')) {
            $container->removeDefinition('sonata.core.form.extension.dependency');

            return;
        }

        $container->setParameter('sonata.core.form.mapping.type', $config['form']['mapping']['type']);
        $container->setParameter('sonata.core.form.mapping.extension', $config['form']['mapping']['extension']);

        FormHelper::registerFormTypeMapping($config['form']['mapping']['type']);
        foreach ($config['form']['mapping']['extension'] as $ext => $idx) {
            FormHelper::registerFormExtensionMapping($ext, $idx);
        }

        $definition = $container->getDefinition('sonata.core.form.extension.dependency');
        $definition->replaceArgument(4, FormHelper::getFormTypeMapping());

        $definition = $container->getDefinition('sonata.core.form.extension.dependency');
        $definition->replaceArgument(5, FormHelper::getFormExtensionMapping());
    }

    /**
     * Registers flash message types defined in configuration to flash manager.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param array                                                   $config
     */
    public function registerFlashTypes(ContainerBuilder $container, array $config)
    {
        $mergedConfig = array_merge_recursive($config['flashmessage'], array(
            'success' => array('types' => array(
                'success' => array('domain' => 'SonataCoreBundle'),
                'sonata_flash_success' => array('domain' => 'SonataAdminBundle'),
                'sonata_user_success' => array('domain' => 'SonataUserBundle'),
                'fos_user_success' => array('domain' => 'FOSUserBundle'),
            )),
            'warning' => array('types' => array(
                'warning' => array('domain' => 'SonataCoreBundle'),
                'sonata_flash_info' => array('domain' => 'SonataAdminBundle'),
            )),
            'danger' => array('types' => array(
                'error' => array('domain' => 'SonataCoreBundle'),
                'sonata_flash_error' => array('domain' => 'SonataAdminBundle'),
                'sonata_user_error' => array('domain' => 'SonataUserBundle'),
            )),
        ));

        $types = $cssClasses = array();

        foreach ($mergedConfig as $typeKey => $typeConfig) {
            $types[$typeKey] = $typeConfig['types'];
            $cssClasses[$typeKey] = array_key_exists('css_class', $typeConfig) ? $typeConfig['css_class'] : $typeKey;
        }

        $identifier = 'sonata.core.flashmessage.manager';

        $definition = $container->getDefinition($identifier);
        $definition->replaceArgument(2, $types);
        $definition->replaceArgument(3, $cssClasses);

        $container->setDefinition($identifier, $definition);
    }

    /**
     * @param array $config
     */
    public function configureSerializerFormats($config)
    {
        if (interface_exists('JMS\Serializer\Handler\SubscribingHandlerInterface')) {
            BaseSerializerHandler::setFormats($config['serializer']['formats']);
        }
    }

    protected function deprecateSlugify(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('sonata.core.slugify.cocur');
        if (method_exists($definition, 'setDeprecated')) {
            $definition->setDeprecated(true);

            $definition = $container->getDefinition('sonata.core.slugify.native');
            $definition->setDeprecated(true);
        }
    }
}
