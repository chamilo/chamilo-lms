<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle;

use Sonata\CoreBundle\DependencyInjection\Compiler\AdapterCompilerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Sonata\CoreBundle\DependencyInjection\Compiler\StatusRendererCompilerPass;

class SonataCoreBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new StatusRendererCompilerPass());
        $container->addCompilerPass(new AdapterCompilerPass());
    }
}
