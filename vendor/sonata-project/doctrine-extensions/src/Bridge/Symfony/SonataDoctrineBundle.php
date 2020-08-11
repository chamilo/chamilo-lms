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

namespace Sonata\Doctrine\Bridge\Symfony;

use Sonata\Doctrine\Bridge\Symfony\DependencyInjection\Compiler\AdapterCompilerPass;
use Sonata\Doctrine\Bridge\Symfony\DependencyInjection\Compiler\MapperCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SonataDoctrineBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AdapterCompilerPass());
        $container->addCompilerPass(new MapperCompilerPass());
    }
}
