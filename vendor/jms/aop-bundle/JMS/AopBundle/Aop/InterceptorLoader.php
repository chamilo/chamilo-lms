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

namespace JMS\AopBundle\Aop;

use CG\Proxy\InterceptorLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lazy-loading interceptor loader implementation.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class InterceptorLoader implements InterceptorLoaderInterface
{
    private $container;
    private $interceptors;
    private $loadedInterceptors = array();

    /**
     * @param ContainerInterface $container
     * @param array<array<string>> $interceptors
     */
    public function __construct(ContainerInterface $container, array $interceptors)
    {
        $this->container = $container;
        $this->interceptors = $interceptors;
    }

    public function loadInterceptors(\ReflectionMethod $method)
    {
        if (!isset($this->interceptors[$method->class][$method->name])) {
            return array();
        }

        if (isset($this->loadedInterceptors[$method->class][$method->name])) {
            return $this->loadedInterceptors[$method->class][$method->name];
        }

        $interceptors = array();
        foreach ($this->interceptors[$method->class][$method->name] as $id) {
            $interceptors[] = $this->container->get($id);
        }

        return $this->loadedInterceptors[$method->class][$method->name] = $interceptors;
    }
}
