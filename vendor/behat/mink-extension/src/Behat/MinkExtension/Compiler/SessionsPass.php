<?php

namespace Behat\MinkExtension\Compiler;

use Symfony\Component\DependencyInjection\Reference,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/*
 * This file is part of the Behat\MinkExtension
 *
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Behat\Mink container compilation pass.
 * Registers all available in controller Mink sessions.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class SessionsPass implements CompilerPassInterface
{
    /**
     * Registers Mink sessions.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('behat.mink')) {
            return;
        }
        $minkDefinition = $container->getDefinition('behat.mink');

        foreach ($container->findTaggedServiceIds('behat.mink.session') as $id => $attributes) {
            foreach ($attributes as $attribute) {
                if (isset($attribute['alias']) && $name = $attribute['alias']) {
                    $minkDefinition->addMethodCall(
                        'registerSession', array($name, new Reference($id))
                    );
                }
            }
        }

        $minkDefinition->addMethodCall(
            'setDefaultSessionName', array($container->getParameter('behat.mink.default_session'))
        );
    }
}
