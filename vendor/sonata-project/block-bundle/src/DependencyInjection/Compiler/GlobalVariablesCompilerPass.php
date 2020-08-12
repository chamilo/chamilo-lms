<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * GlobalVariablesCompilerPass.
 *
 * @final since sonata-project/block-bundle 3.0
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class GlobalVariablesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('twig')
            ->addMethodCall('addGlobal', ['sonata_block', new Reference('sonata.block.twig.global')]);
    }
}
