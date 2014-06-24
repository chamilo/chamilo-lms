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

namespace JMS\DiExtraBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Integrates the bundle with external code.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class IntegrationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // replace Symfony2's default controller resolver
        $container->setAlias('controller_resolver', new Alias('jms_di_extra.controller_resolver', false));

        if (true === $container->getParameter('jms_di_extra.doctrine_integration')) {
            $this->integrateWithDoctrine($container);
        }
    }

    /**
     * Integrates the DiAwareObjectManager with Doctrine.
     *
     * This is a bit trickier... mostly because Doctrine uses many factories,
     * and we cannot directly inject the EntityManager. We circumvent this
     * problem by renaming the original entity manager definition, and then
     * placing our definition in its place.
     *
     * Note that this also currently only supports the ORM, for the ODM flavors
     * a similar integration should be possible.
     *
     * @param ContainerBuilder $container
     */
    private function integrateWithDoctrine($container)
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            if (!$definition instanceof DefinitionDecorator) {
                continue;
            }

            if ('doctrine.orm.entity_manager.abstract' !== $definition->getParent()) {
                continue;
            }

            $definition->setPublic(false);
            $container->setDefinition($id.'.delegate', $definition);
            $container->register($id, $container->getParameter('jms_di_extra.doctrine_integration.entity_manager.class'))
                ->setFile($container->getParameter('jms_di_extra.doctrine_integration.entity_manager.file'))
                ->addArgument(new Reference($id.'.delegate'))
                ->addArgument(new Reference('service_container'));
        }
    }
}
