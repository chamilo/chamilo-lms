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

use CG\Core\NamingStrategyInterface;

use CG\Generator\Writer;
use CG\Generator\PhpMethod;
use CG\Generator\PhpDocblock;
use CG\Generator\PhpClass;
use CG\Core\AbstractClassGenerator;

/**
 * Class enhancing generator implementation.
 *
 * This class enhances existing classes by generating a proxy and leveraging
 * different generator implementation.
 *
 * There are several built-in generator such as lazy-initializing objects, or
 * a generator for creating AOP joinpoints.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class Enhancer extends AbstractClassGenerator
{
    private $generatedClass;
    private $class;
    private $interfaces;
    private $generators;

    public function __construct(\ReflectionClass $class, array $interfaces = array(), array $generators = array())
    {
        if (empty($generators) && empty($interfaces)) {
            throw new \RuntimeException('Either generators, or interfaces must be given.');
        }

        $this->class = $class;
        $this->interfaces = $interfaces;
        $this->generators = $generators;
    }

    /**
     * Creates a new instance  of the enhanced class.
     *
     * @param array $args
     * @return object
     */
    public function createInstance(array $args = array())
    {
        $generatedClass = $this->getClassName($this->class);

        if (!class_exists($generatedClass, false)) {
            eval($this->generateClass());
        }

        $ref = new \ReflectionClass($generatedClass);

        return $ref->newInstanceArgs($args);
    }

    public function writeClass($filename)
    {
        if (!is_dir($dir = dirname($filename))) {
            if (false === @mkdir($dir, 0777, true)) {
                throw new \RuntimeException(sprintf('Could not create directory "%s".', $dir));
            }
        }

        if (!is_writable($dir)) {
            throw new \RuntimeException(sprintf('The directory "%s" is not writable.', $dir));
        }

        file_put_contents($filename, "<?php\n\n".$this->generateClass());
    }

    /**
     * Creates a new enhanced class
     *
     * @return string
     */
    public final function generateClass()
    {
        static $docBlock;
        if (empty($docBlock)) {
            $writer = new Writer();
            $writer
                ->writeln('/**')
                ->writeln(' * CG library enhanced proxy class.')
                ->writeln(' *')
                ->writeln(' * This code was generated automatically by the CG library, manual changes to it')
                ->writeln(' * will be lost upon next generation.')
                ->writeln(' */')
            ;
            $docBlock = $writer->getContent();
        }

        $this->generatedClass = PhpClass::create()
            ->setDocblock($docBlock)
            ->setParentClassName($this->class->name)
        ;

        $proxyClassName = $this->getClassName($this->class);
        if (false === strpos($proxyClassName, NamingStrategyInterface::SEPARATOR)) {
            throw new \RuntimeException(sprintf('The proxy class name must be suffixed with "%s" and an optional string, but got "%s".', NamingStrategyInterface::SEPARATOR, $proxyClassName));
        }
        $this->generatedClass->setName($proxyClassName);

        if (!empty($this->interfaces)) {
            $this->generatedClass->setInterfaceNames(array_map(function($v) { return '\\'.$v; }, $this->interfaces));

            foreach ($this->getInterfaceMethods() as $method) {
                $method = PhpMethod::fromReflection($method);
                $method->setAbstract(false);

                $this->generatedClass->setMethod($method);
            }
        }

        if (!empty($this->generators)) {
            foreach ($this->generators as $generator) {
                $generator->generate($this->class, $this->generatedClass);
            }
        }

        return $this->generateCode($this->generatedClass);
    }

    /**
     * Adds stub methods for the interfaces that have been implemented.
     */
    protected function getInterfaceMethods()
    {
        $methods = array();

        foreach ($this->interfaces as $interface) {
            $ref = new \ReflectionClass($interface);
            $methods = array_merge($methods, $ref->getMethods());
        }

        return $methods;
    }
}