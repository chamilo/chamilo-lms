<?php

namespace Knp\DoctrineBehaviors\Bundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DoctrineBehaviorsExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../../config'));
        $loader->load('orm-services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Don't rename in Configuration for BC reasons
        $config['softdeletable'] = $config['soft_deletable'];
        unset($config['soft_deletable']);

        foreach ($config as $behavior => $enabled) {
            if (!$enabled) {
                $container->removeDefinition(sprintf('knp.doctrine_behaviors.%s_subscriber', $behavior));
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return 'knp_doctrine_behaviors';
    }
}
