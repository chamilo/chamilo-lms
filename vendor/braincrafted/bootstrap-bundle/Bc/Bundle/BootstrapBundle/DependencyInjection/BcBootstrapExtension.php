<?php
/**
 * This file is part of BcBootstrapBundle.
 *
 * (c) 2012-2013 by Florian Eckerstorfer
 */

namespace Bc\Bundle\BootstrapBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * BcBootstrapExtension
 *
 * @package    BcBootstrapBundle
 * @subpackage DependencyInjection
 * @author     Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright  2012-2013 Florian Eckerstorfer
 * @license    http://opensource.org/licenses/MIT The MIT License
 * @link       http://bootstrap.braincrafted.com Bootstrap for Symfony2
 */
class BcBootstrapExtension extends Extension implements PrependExtensionInterface
{
    /** @var string */
    private $formTemplate = 'BcBootstrapBundle:Form:form_div_layout.html.twig';

    /** @var string */
    private $menuTemplate = 'BcBootstrapBundle:Menu:menu.html.twig';

    /** @var string */
    private $paginationTemplate = 'BcBootstrapBundle:Pagination:pagination.html.twig';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        // Configure AsseticBundle
        if (isset($bundles['AsseticBundle'])) {
            $this->configureAsseticBundle($container);
        }

        // Configure TwigBundle
        if (isset($bundles['TwigBundle'])) {
            $this->configureTwigBundle($container);
        }

        // Configure KnpMenuBundle
        if (isset($bundles['TwigBundle']) && isset($bundles['KnpMenuBundle'])) {
            $this->configureKnpMenuBundle($container);
        }

        if (isset($bundles['TwigBundle']) && isset($bundles['KnpPaginatorBundle'])) {
            $this->configureKnpPaginatorBundle($container);
        }
    }

    /**
     * Configures the AsseticBundle.
     *
     * @param ContainerBuilder $container The service container
     *
     * @return void
     */
    private function configureAsseticBundle(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        foreach ($container->getExtensions() as $name => $extension) {
            switch ($name) {
                case 'assetic':
                    $container->prependExtensionConfig(
                        $name,
                        array(
                            'assets'    => $this->buildAsseticConfig($config)
                        )
                    );
                    break;
            }
        }
    }

    /**
     * Configures the TwigBundle.
     *
     * @param ContainerBuilder $container The service container
     *
     * @return void
     */
    private function configureTwigBundle(ContainerBuilder $container)
    {
        foreach ($container->getExtensions() as $name => $extension) {
            switch ($name) {
                case 'twig':
                    $container->prependExtensionConfig(
                        $name,
                        array('form'  => array('resources' => array($this->formTemplate)))
                    );
                    break;
            }
        }
    }

    /**
     * Configures the KnpMenuBundle.
     *
     * @param ContainerBuilder $container The service container
     *
     * @return void
     */
    private function configureKnpMenuBundle(ContainerBuilder $container)
    {
        foreach ($container->getExtensions() as $name => $extension) {
            switch ($name) {
                case 'knp_menu':
                    $container->prependExtensionConfig(
                        $name,
                        array('twig' => array('template'  => $this->menuTemplate))
                    );
                    break;
            }
        }
    }

    /**
     * Configures the KnpPaginatorBundle.
     *
     * @param ContainerBuilder $container The service container
     *
     * @return void
     */
    private function configureKnpPaginatorBundle(ContainerBuilder $container)
    {
        foreach ($container->getExtensions() as $name => $extension) {
            switch ($name) {
                case 'knp_paginator':
                    $container->prependExtensionConfig(
                        $name,
                        array('template' => array('pagination' => $this->paginationTemplate))
                    );
                    break;
            }
        }
    }

    private function buildAsseticConfig(array $config)
    {
        return array(
            'bootstrap_css' => $this->buildAsseticBootstrapCssConfig($config),
            'bootstrap_js'  => $this->buildAsseticBootstrapJsConfig($config),
            'jquery'        => $this->buildAsseticJqueryConfig($config)
        );
    }

    private function buildAsseticBootstrapCssConfig(array $config)
    {
        return array(
            'inputs'        => array(
                $config['assets_dir'].'/less/bootstrap.less',
                $config['assets_dir'].'/less/responsive.less'
            ),
            'filters'       => array($config['less_filter'], 'cssrewrite'),
            'output'        => $config['output_dir'].'/css/bootstrap.css'
        );
    }

    private function buildAsseticBootstrapJsConfig(array $config)
    {
        return array(
            'inputs'        => array(
                $config['assets_dir'].'/js/bootstrap-transition.js',
                $config['assets_dir'].'/js/bootstrap-alert.js',
                $config['assets_dir'].'/js/bootstrap-button.js',
                $config['assets_dir'].'/js/bootstrap-carousel.js',
                $config['assets_dir'].'/js/bootstrap-collapse.js',
                $config['assets_dir'].'/js/bootstrap-dropdown.js',
                $config['assets_dir'].'/js/bootstrap-modal.js',
                $config['assets_dir'].'/js/bootstrap-tooltip.js',
                $config['assets_dir'].'/js/bootstrap-popover.js',
                $config['assets_dir'].'/js/bootstrap-scrollspy.js',
                $config['assets_dir'].'/js/bootstrap-tab.js',
                $config['assets_dir'].'/js/bootstrap-typeahead.js',
                $config['assets_dir'].'/js/bootstrap-affix.js'
            ),
            'output'        => $config['output_dir'].'/js/bootstrap.js'
        );
    }

    private function buildAsseticJqueryConfig(array $config)
    {
        return array(
            'inputs'        => array($config['jquery_path']),
            'output'        => $config['output_dir'].'/js/jquery.js'
        );
    }
}
