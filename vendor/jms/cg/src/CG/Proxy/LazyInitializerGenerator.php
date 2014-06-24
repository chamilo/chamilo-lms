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

namespace CG\Proxy;

use CG\Generator\Writer;
use CG\Core\ReflectionUtils;
use CG\Generator\GeneratorUtils;
use CG\Generator\PhpParameter;
use CG\Generator\PhpMethod;
use CG\Generator\PhpProperty;
use CG\Generator\PhpClass;

/**
 * Generator for creating lazy-initializing instances.
 *
 * This generator enhances concrete classes to allow for them to be lazily
 * initialized upon first access.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class LazyInitializerGenerator implements GeneratorInterface
{
    private $writer;
    private $prefix = '__CG__';
    private $markerInterface;

    public function __construct()
    {
        $this->writer = new Writer();
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Sets the marker interface which should be implemented by the
     * generated classes.
     *
     * @param string $interface The FQCN of the interface
     */
    public function setMarkerInterface($interface)
    {
        $this->markerInterface = $interface;
    }

    /**
     * Generates the necessary methods in the class.
     *
     * @param \ReflectionClass $originalClass
     * @param PhpClass $class
     * @return void
     */
    public function generate(\ReflectionClass $originalClass, PhpClass $class)
    {
        $methods = ReflectionUtils::getOverrideableMethods($originalClass, true);

        // no public, non final methods
        if (empty($methods)) {
            return;
        }

        if (null !== $this->markerInterface) {
            $class->setImplementedInterfaces(array_merge(
                $class->getImplementedInterfaces(),
                array($this->markerInterface)
            ));
        }

        $initializer = new PhpProperty();
        $initializer->setName($this->prefix.'lazyInitializer');
        $initializer->setVisibility(PhpProperty::VISIBILITY_PRIVATE);
        $class->setProperty($initializer);

        $initialized = new PhpProperty();
        $initialized->setName($this->prefix.'initialized');
        $initialized->setDefaultValue(false);
        $initialized->setVisibility(PhpProperty::VISIBILITY_PRIVATE);
        $class->setProperty($initialized);

        $initializerSetter = new PhpMethod();
        $initializerSetter->setName($this->prefix.'setLazyInitializer');
        $initializerSetter->setBody('$this->'.$this->prefix.'lazyInitializer = $initializer;');

        $parameter = new PhpParameter();
        $parameter->setName('initializer');
        $parameter->setType('\CG\Proxy\LazyInitializerInterface');
        $initializerSetter->addParameter($parameter);
        $class->setMethod($initializerSetter);

        $this->addMethods($class, $methods);

        $initializingMethod = new PhpMethod();
        $initializingMethod->setName($this->prefix.'initialize');
        $initializingMethod->setVisibility(PhpMethod::VISIBILITY_PRIVATE);
        $initializingMethod->setBody(
            $this->writer
                ->reset()
                ->writeln('if (null === $this->'.$this->prefix.'lazyInitializer) {')
                    ->indent()
                    ->writeln('throw new \RuntimeException("'.$this->prefix.'setLazyInitializer() must be called prior to any other public method on this object.");')
                    ->outdent()
                ->write("}\n\n")
                ->writeln('$this->'.$this->prefix.'lazyInitializer->initializeObject($this);')
                ->writeln('$this->'.$this->prefix.'initialized = true;')
                ->getContent()
        );
        $class->setMethod($initializingMethod);
    }

    private function addMethods(PhpClass $class, array $methods)
    {
        foreach ($methods as $method) {
            $initializingCode = 'if (false === $this->'.$this->prefix.'initialized) {'."\n"
            .'    $this->'.$this->prefix.'initialize();'."\n"
            .'}';

            if ($class->hasMethod($method->name)) {
                $genMethod = $class->getMethod($method->name);
                $genMethod->setBody(
                $initializingCode."\n"
                .$genMethod->getBody()
                );

                continue;
            }

            $genMethod = PhpMethod::fromReflection($method);
            $genMethod->setBody(
            $initializingCode."\n\n"
            .'return '.GeneratorUtils::callMethod($method).';'
            );
            $class->setMethod($genMethod);
        }
    }
}