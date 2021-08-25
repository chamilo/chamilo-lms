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
use Symfony\Component\DependencyInjection\Reference;

/**
 * Inject RememberMeServices into LoginManager.
 *
 * @author Vasily Khayrulin <sirianru@gmail.com>
 */
class InjectRememberMeServicesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $firewallName = $container->getParameter('fos_user.firewall_name');
        $loginManager = $container->getDefinition('fos_user.security.login_manager');

        if ($container->hasDefinition('security.authentication.rememberme.services.persistent.'.$firewallName)) {
            $loginManager->replaceArgument(4, new Reference('security.authentication.rememberme.services.persistent.'.$firewallName));
        } elseif ($container->hasDefinition('security.authentication.rememberme.services.simplehash.'.$firewallName)) {
            $loginManager->replaceArgument(4, new Reference('security.authentication.rememberme.services.simplehash.'.$firewallName));
        }
    }
}
