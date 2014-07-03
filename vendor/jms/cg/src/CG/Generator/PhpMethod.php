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

use CG\Core\ReflectionUtils;

/**
 * Represents a PHP method.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PhpMethod extends AbstractPhpMember
{
    private $final = false;
    private $abstract = false;
    private $parameters = array();
    private $referenceReturned = false;
    private $body = '';

    public static function create($name = null)
    {
        return new static($name);
    }

    public static function fromReflection(\ReflectionMethod $ref)
    {
        $method = new static();
        $method
            ->setFinal($ref->isFinal())
            ->setAbstract($ref->isAbstract())
            ->setStatic($ref->isStatic())
            ->setVisibility($ref->isPublic() ? self::VISIBILITY_PUBLIC : ($ref->isProtected() ? self::VISIBILITY_PROTECTED : self::VISIBILITY_PRIVATE))
            ->setReferenceReturned($ref->returnsReference())
            ->setName($ref->name)
        ;

        if ($docComment = $ref->getDocComment()) {
            $method->setDocblock(ReflectionUtils::getUnindentedDocComment($docComment));
        }

        foreach ($ref->getParameters() as $param) {
            $method->addParameter(static::createParameter($param));
        }

        // FIXME: Extract body?

        return $method;
    }

    protected static function createParameter(\ReflectionParameter $parameter)
    {
        return PhpParameter::fromReflection($parameter);
    }

    public function setFinal($bool)
    {
        $this->final = (Boolean) $bool;

        return $this;
    }

    public function setAbstract($bool)
    {
        $this->abstract = $bool;

        return $this;
    }

    public function setReferenceReturned($bool)
    {
        $this->referenceReturned = (Boolean) $bool;

        return $this;
    }

    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = array_values($parameters);

        return $this;
    }

    public function addParameter(PhpParameter $parameter)
    {
        $this->parameters[] = $parameter;

        return $this;
    }

    public function replaceParameter($position, PhpParameter $parameter)
    {
        if ($position < 0 || $position > strlen($this->parameters)) {
            throw new \InvalidArgumentException(sprintf('The position must be in the range [0, %d].', strlen($this->parameters)));
        }
        $this->parameters[$position] = $parameter;

        return $this;
    }

    public function removeParameter($position)
    {
        if (!isset($this->parameters[$position])) {
            throw new \InvalidArgumentException(sprintf('There is no parameter at position "%d" does not exist.', $position));
        }
        unset($this->parameters[$position]);
        $this->parameters = array_values($this->parameters);

        return $this;
    }

    public function isFinal()
    {
        return $this->final;
    }

    public function isAbstract()
    {
        return $this->abstract;
    }

    public function isReferenceReturned()
    {
        return $this->referenceReturned;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}