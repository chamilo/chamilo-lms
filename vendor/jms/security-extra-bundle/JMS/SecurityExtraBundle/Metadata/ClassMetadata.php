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

namespace JMS\SecurityExtraBundle\Metadata;

use JMS\SecurityExtraBundle\Exception\RuntimeException;
use JMS\SecurityExtraBundle\Exception\InvalidArgumentException;
use Metadata\MethodMetadata;
use Metadata\MergeableInterface;
use Metadata\MergeableClassMetadata;

/**
 * Contains class metadata information
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ClassMetadata extends MergeableClassMetadata
{
    public function addMethodMetadata(MethodMetadata $metadata)
    {
        if ($this->reflection->isFinal()) {
            throw new RuntimeException(sprintf('Class "%s" is declared final, and cannot be secured.', $reflection->name));
        }

        if ($metadata->reflection->isStatic()) {
            throw new RuntimeException(sprintf('Method "%s::%s" is declared static and cannot be secured.', $metadata->reflection->class, $metadata->reflection->name));
        }

        if ($metadata->reflection->isFinal()) {
            throw new RuntimeException(sprintf('Method "%s::%s" is declared final and cannot be secured.', $metadata->reflection->class, $metadata->reflection->name));
        }

        parent::addMethodMetadata($metadata);
    }

    public function merge(MergeableInterface $metadata)
    {
        if (!$metadata instanceof ClassMetadata) {
            throw new InvalidArgumentException('$metadata must be an instance of ClassMetadata.');
        }

        foreach ($this->methodMetadata as $name => $methodMetadata) {
            // check if metadata was declared on an interface
            if (!$metadata->reflection->hasMethod($name)) {
                continue;
            }

            if ($metadata->reflection->getMethod($name)->getDeclaringClass()->name
                !== $methodMetadata->class) {
                if (!isset($metadata->methodMetadata[$name])) {
                    if ($methodMetadata->reflection->isAbstract()) {
                        continue;
                    }

                    throw new RuntimeException(sprintf(
                         'You have overridden a secured method "%s::%s" in "%s". '
                        .'Please copy over the applicable security metadata, and '
                        .'also add @SatisfiesParentSecurityPolicy.',
                        $methodMetadata->reflection->class,
                        $name,
                        $metadata->reflection->name
                    ));
                }

                if (!$metadata->methodMetadata[$name]->satisfiesParentSecurityPolicy) {
                    throw new RuntimeException(sprintf('Unresolved security metadata conflict for method "%s::%s" in "%s". Please copy the respective annotations, and add @SatisfiesParentSecurityPolicy to the child method.', $metadata->reflection->name, $name, $methodMetadata->reflection->getDeclaringClass()->getFilename()));
                }
            }
        }

        parent::merge($metadata);
    }

    public function isProxyRequired()
    {
        return !empty($this->methodMetadata);
    }
}
