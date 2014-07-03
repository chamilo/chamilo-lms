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

use JMS\DiExtraBundle\Config\FastDirectoriesResource;

use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class ResourceOptimizationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $resources = $directories = array();

        $ref = new \ReflectionProperty('Symfony\Component\Config\Resource\DirectoryResource', 'pattern');
        $ref->setAccessible(true);

        foreach ($container->getResources() as $resource) {
            if ($resource instanceof DirectoryResource) {
                if (null === $pattern = $ref->getValue($resource)) {
                    $pattern = '*';
                }

                $directories[$pattern][] = $resource->getResource();

                continue;
            }

            $resources[] = $resource;
        }

        $sortFunc = function($a, $b) {
            return strlen($a) - strlen($b);
        };

        foreach ($directories as $pattern => $pDirectories) {
            $newResources = array();

            usort($pDirectories, $sortFunc);
            foreach ($pDirectories as $a) {
                foreach ($newResources as $b) {
                    if (0 === strpos($a, $b)) {
                        continue 2;
                    }
                }

                $newResources[] = $a;
            }

            $directories[$pattern] = $newResources;
        }

        $disableGrep = $container->getParameter('jms_di_extra.disable_grep');

        foreach ($directories as $pattern => $pDirectories) {
            $newResource = new FastDirectoriesResource($pDirectories, $pattern, $disableGrep);
            $newResource->update();
            $resources[] = $newResource;
        }

        $ref = new \ReflectionProperty('Symfony\Component\DependencyInjection\ContainerBuilder', 'resources');
        $ref->setAccessible(true);
        $ref->setValue($container, $resources);
    }
}
