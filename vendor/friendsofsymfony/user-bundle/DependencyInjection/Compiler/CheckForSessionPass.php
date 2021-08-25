<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Flex\Recipe;

/**
 * Checks to see if the session service exists.
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
class CheckForSessionPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has('fos_user.session') && !$container->has('session')) {
            $message = 'FOSUserBundle requires the "session" service to be available.';

            if (class_exists(Recipe::class)) {
                $message .= ' Uncomment the "session" section in "config/packages/framework.yaml" to activate it.';
            }

            throw new \LogicException($message);
        }
    }
}
