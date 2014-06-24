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

namespace CG\Core;

use CG\Generator\PhpClass;

/**
 * Abstract base class for all class generators.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class AbstractClassGenerator implements ClassGeneratorInterface
{
    private $namingStrategy;
    private $generatorStrategy;

    public function setNamingStrategy(NamingStrategyInterface $namingStrategy = null)
    {
        $this->namingStrategy = $namingStrategy;
    }

    public function setGeneratorStrategy(GeneratorStrategyInterface $generatorStrategy = null)
    {
        $this->generatorStrategy = $generatorStrategy;
    }

    public function getClassName(\ReflectionClass $class)
    {
        if (null === $this->namingStrategy) {
            $this->namingStrategy = new DefaultNamingStrategy();
        }

        return $this->namingStrategy->getClassName($class);
    }

    protected function generateCode(PhpClass $class)
    {
        if (null === $this->generatorStrategy) {
            $this->generatorStrategy = new DefaultGeneratorStrategy();
        }

        return $this->generatorStrategy->generate($class);
    }
}