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

namespace JMS\DiExtraBundle\Metadata;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Definition;
use Metadata\ClassHierarchyMetadata;

class MetadataConverter
{
    /**
     * Converts class hierarchy metadata to definition instances.
     *
     * @param ClassHierarchyMetadata $metadata
     * @return array an array of Definition instances
     */
    public function convert(ClassHierarchyMetadata $metadata)
    {
        static $count = 0;
        $definitions = array();

        $previous = null;
        foreach ($metadata->classMetadata as $classMetadata) {
            if (null === $previous && null === $classMetadata->parent) {
                $definition = new Definition();
            } else {
                $definition = new DefinitionDecorator(
                    $classMetadata->parent ?: $previous->id
                );
            }

            $definition->setClass($classMetadata->name);
            if (null !== $classMetadata->scope) {
                $definition->setScope($classMetadata->scope);
            }
            if (null !== $classMetadata->public) {
                $definition->setPublic($classMetadata->public);
            }
            if (null !== $classMetadata->abstract) {
                $definition->setAbstract($classMetadata->abstract);
            }
            if (null !== $classMetadata->arguments) {
                $definition->setArguments($classMetadata->arguments);
            }

            $definition->setMethodCalls($classMetadata->methodCalls);
            $definition->setTags($classMetadata->tags);
            $definition->setProperties($classMetadata->properties);

            if (null === $classMetadata->id) {
                $classMetadata->id = '_jms_di_extra.unnamed.service_'.$count++;
            }

            if ($classMetadata->initMethod) {
                if (!method_exists($definition, 'setInitMethod')) {
                    throw new \RuntimeException(sprintf('@AfterSetup is not available on your Symfony version.'));
                }

                $definition->setInitMethod($classMetadata->initMethod);
            }

            $definitions[$classMetadata->id] = $definition;
            $previous = $classMetadata;
        }

        return $definitions;
    }
}
