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

namespace CG\Generator;

/**
 * Represents a PHP parameter.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PhpParameter
{
    private $name;
    private $defaultValue;
    private $hasDefaultValue = false;
    private $passedByReference = false;
    private $type;

    public static function create($name = null)
    {
        return new static($name);
    }

    public static function fromReflection(\ReflectionParameter $ref)
    {
        $parameter = new static();
        $parameter
            ->setName($ref->name)
            ->setPassedByReference($ref->isPassedByReference())
        ;

        if ($ref->isDefaultValueAvailable()) {
            $parameter->setDefaultValue($ref->getDefaultValue());
        }

        if ($ref->isArray()) {
            $parameter->setType('array');
        } else if ($class = $ref->getClass()) {
            $parameter->setType($class->name);
        }

        return $parameter;
    }

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setDefaultValue($value)
    {
        $this->defaultValue = $value;
        $this->hasDefaultValue = true;

        return $this;
    }

    public function unsetDefaultValue()
    {
        $this->defaultValue = null;
        $this->hasDefaultValue = false;

        return $this;
    }

    public function setPassedByReference($bool)
    {
        $this->passedByReference = (Boolean) $bool;

        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function hasDefaultValue()
    {
        return $this->hasDefaultValue;
    }

    public function isPassedByReference()
    {
        return $this->passedByReference;
    }

    public function getType()
    {
        return $this->type;
    }
}