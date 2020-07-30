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

namespace Sonata\Doctrine\Bridge\Symfony\Bundle;

use Sonata\Doctrine\Bridge\Symfony\DependencyInjection\Compiler\AdapterCompilerPass;
use Sonata\Doctrine\Bridge\Symfony\DependencyInjection\Compiler\MapperCompilerPass;
use Sonata\Doctrine\Bridge\Symfony\DependencyInjection\SonataDoctrineExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @deprecated Since sonata-project/doctrine-extensions 1.x, to be removed in 2.0. Use Sonata\Doctrine\Bridge\Symfony\SonataDoctrineBundle instead.
 */
final class SonataDoctrineBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AdapterCompilerPass());
        $container->addCompilerPass(new MapperCompilerPass());
    }

    public function getPath()
    {
        return __DIR__.'/..';
    }

    protected function getContainerExtensionClass()
    {
        return SonataDoctrineExtension::class;
    }
}
