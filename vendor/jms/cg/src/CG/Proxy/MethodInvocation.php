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

/**
 * Represents a method invocation.
 *
 * This object contains information for the method invocation, such as the object
 * on which the method is invoked, and the arguments that are passed to the method.
 *
 * Before the actual method is called, first all the interceptors must call the
 * proceed() method on this class.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class MethodInvocation
{
    public $reflection;
    public $object;
    public $arguments;

    private $interceptors;
    private $pointer;

    public function __construct(\ReflectionMethod $reflection, $object, array $arguments, array $interceptors)
    {
        $this->reflection = $reflection;
        $this->object = $object;
        $this->arguments = $arguments;
        $this->interceptors = $interceptors;
        $this->pointer = 0;
    }

    /**
     * Proceeds down the call-chain and eventually calls the original method.
     *
     * @return mixed
     */
    public function proceed()
    {
        if (isset($this->interceptors[$this->pointer])) {
            return $this->interceptors[$this->pointer++]->intercept($this);
        }

        $this->reflection->setAccessible(true);

        return $this->reflection->invokeArgs($this->object, $this->arguments);
    }

    /**
     * Returns a string representation of the method.
     *
     * This is intended for debugging purposes only.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s::%s', $this->reflection->class, $this->reflection->name);
    }
}