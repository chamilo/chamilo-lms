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
 * Injects firewall's UserChecker into LoginManager.
 *
 * @author Gocha Ossinkine <ossinkine@ya.ru>
 */
class InjectUserCheckerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $firewallName = $container->getParameter('fos_user.firewall_name');
        $loginManager = $container->findDefinition('fos_user.security.login_manager');

        if ($container->has('security.user_checker.'.$firewallName)) {
            $loginManager->replaceArgument(1, new Reference('security.user_checker.'.$firewallName));
        }
    }
}
