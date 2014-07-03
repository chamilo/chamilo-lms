<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\SecurityExtraBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class DisableVotersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->getParameter('security.role_voter.disabled')) {
            $container->removeDefinition('security.access.role_hierarchy_voter');
            $container->removeDefinition('security.access.simple_role_voter');
        }

        if ($container->getParameter('security.authenticated_voter.disabled')) {
            $container->removeDefinition('security.access.authenticated_voter');
        }

        if ($container->hasDefinition('security.acl.voter.basic_permissions')) {
            if ($container->getParameter('security.acl_voter.disabled')) {
                $container->removeDefinition('security.acl.voter.basic_permissions');
            } else {
                $container->getDefinition('security.acl.voter.basic_permissions')
                    ->setClass('JMS\SecurityExtraBundle\Security\Acl\Voter\AclVoter');
            }
        }
    }
}