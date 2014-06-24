<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Changes the Router implementation.
 *
 */
class SetRouterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // only replace the default router by overwriting the 'router' alias if config tells us to
        if ($container->hasParameter('cmf_routing.replace_symfony_router') && true === $container->getParameter('cmf_routing.replace_symfony_router')) {
            $container->setAlias('router', 'cmf_routing.router');
        }

    }
}
