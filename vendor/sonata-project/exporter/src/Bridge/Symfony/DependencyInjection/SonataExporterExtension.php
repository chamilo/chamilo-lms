<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Grégoire Paris <postmaster@greg0ire.fr>
 */
final class SonataExporterExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $this->configureExporter($container, $config['exporter']);
        $this->configureWriters($container, $config['writers']);
    }

    private function configureExporter(ContainerBuilder $container, array $config)
    {
        foreach (['csv', 'json', 'xls', 'xml'] as $format) {
            if (in_array($format, $config['default_writers'])) {
                $container->getDefinition('sonata.exporter.writer.'.$format)->addTag(
                    'sonata.exporter.writer'
                );
            }
        }
    }

    private function configureWriters(ContainerBuilder $container, array $config)
    {
        foreach ($config as $format => $settings) {
            foreach ($settings as $key => $value) {
                $container->setParameter(sprintf(
                    'sonata.exporter.writer.%s.%s',
                    $format,
                    $key
                ), $value);
            }
        }
    }
}
