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
 * Represents a PHP function.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PhpFunction
{
    private $name;
    private $namespace;
    private $parameters = array();
    private $body = '';
    private $referenceReturned = false;
    private $docblock;

    public static function create($name = null)
    {
        return new static($name);
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

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function setReferenceReturned($bool)
    {
        $this->referenceReturned = (Boolean) $bool;

        return $this;
    }

    public function replaceParameter($position, PhpParameter $parameter)
    {
        if ($position < 0 || $position > count($this->parameters)) {
            throw new \InvalidArgumentException(sprintf('$position must be in the range [0, %d].', count($this->parameters)));
        }

        $this->parameters[$position] = $parameter;

        return $this;
    }

    public function addParameter(PhpParameter $parameter)
    {
        $this->parameters[] = $parameter;

        return $this;
    }

    public function removeParameter($position)
    {
        if (!isset($this->parameters[$position])) {
            throw new \InvalidArgumentException(sprintf('There is not parameter at position %d.', $position));
        }

        unset($this->parameters[$position]);
        $this->parameters = array_values($this->parameters);

        return $this;
    }

    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    public function setDocblock($docBlock)
    {
        $this->docblock = $docBlock;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getDocblock()
    {
        return $this->docblock;
    }

    public function isReferenceReturned()
    {
        return $this->referenceReturned;
    }
}