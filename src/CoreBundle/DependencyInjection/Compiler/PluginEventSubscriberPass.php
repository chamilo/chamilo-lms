<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DependencyInjection\Compiler;

use SplFileInfo;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Finder;

use const PATHINFO_FILENAME;

class PluginEventSubscriberPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $pluginSubscriberDir = __DIR__.'/../../../../public/plugin';

        $finder = new Finder();
        $finder->files()->in($pluginSubscriberDir)->name('*EventSubscriber.php');

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            if (class_exists($className) && is_subclass_of($className, EventSubscriberInterface::class)) {
                $definition = new Definition($className);
                $definition->setAutowired(true);
                $definition->addTag('kernel.event_subscriber');
                $container->setDefinition($className, $definition);
            }
        }
    }
}
