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

namespace JMS\DiExtraBundle\Generator;

use Metadata\ClassHierarchyMetadata;
use CG\Generator\PhpParameter;
use CG\Generator\PhpMethod;
use CG\Generator\PhpProperty;
use CG\Generator\PhpClass;
use CG\Proxy\GeneratorInterface;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

class LookupMethodClassGenerator implements GeneratorInterface
{
    const PREFIX = '__jmsDiExtra_';

    private $metadata;
    private $requiredFile;

    public function __construct(ClassHierarchyMetadata $metadata)
    {
        $this->metadata = $metadata;
    }

    public function setRequiredFile($file)
    {
        $this->requiredFile = $file;
    }

    public function generate(\ReflectionClass $class, PhpClass $genClass)
    {
        if (!empty($this->requiredFile)) {
            $genClass->addRequiredFile($this->requiredFile);
        }

        $genClass->setProperty(PhpProperty::create()
            ->setName(self::PREFIX.'container')
            ->setVisibility('private')
        );

        $genClass->setMethod(PhpMethod::create()
            ->setName(self::PREFIX.'setContainer')
            ->addParameter(PhpParameter::create()
                ->setName('container')
                ->setType('Symfony\Component\DependencyInjection\ContainerInterface')
            )
            ->setBody('$this->'.self::PREFIX.'container = $container;')
        );

        $genClass->addInterfaceName('JMS\DiExtraBundle\DependencyInjection\LookupMethodClassInterface');
        $genClass->setMethod(PhpMethod::create()
            ->setName(self::PREFIX.'getOriginalClassName')
            ->setFinal(true)
            ->setBody('return '.var_export($class->name, true).';')
        );

        foreach ($this->getLookupMethods() as $name => $value) {
            $genClass->setMethod(PhpMethod::fromReflection($class->getMethod($name))
                ->setAbstract(false)
                ->setBody('return '.$this->dumpValue($value).';')
                ->setDocblock(null)
            );
        }
    }

    private function getLookupMethods()
    {
        $outerClass = $this->metadata->getOutsideClassMetadata()->reflection;
        $lookupMethods = array();
        foreach ($this->metadata->classMetadata as $classMetadata) {
            if (!$classMetadata->lookupMethods) {
                continue;
            }

            foreach ($classMetadata->lookupMethods as $name => $value) {
                // check if method has been overridden
                if ($outerClass->getMethod($name)->class !== $classMetadata->reflection->name) {
                    continue;
                }

                $lookupMethods[$name] = $value;
            }
        }

        return $lookupMethods;
    }

    private function dumpValue($value)
    {
        if ($value instanceof Parameter) {
            return '$this->'.self::PREFIX.'container->getParameter('.var_export((string) $value, true).')';
        } else if ($value instanceof Reference) {
            return '$this->'.self::PREFIX.'container->get('.var_export((string) $value, true).', '.var_export($value->getInvalidBehavior(), true).')';
        } else if (is_string($value) && '%' === $value[0]) {
            return '$this->'.self::PREFIX.'container->getParameter('.var_export(substr($value, 1, -1), true).')';
        } else if (is_array($value) || is_scalar($value) || null === $value) {
            return var_export($value, true);
        }

        throw new \RuntimeException(sprintf('Invalid value for lookup method: %s', json_encode($value)));
    }
}
