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

class RegexInterceptionLoader implements InterceptorLoaderInterface
{
    private $interceptors;

    public function __construct(array $interceptors = array())
    {
        $this->interceptors = $interceptors;
    }

    public function loadInterceptors(\ReflectionMethod $method)
    {
        $signature = $method->class.'::'.$method->name;

        $matchingInterceptors = array();
        foreach ($this->interceptors as $pattern => $interceptor) {
            if (preg_match('#'.$pattern.'#', $signature)) {
                $matchingInterceptors[] = $this->initializeInterceptor($interceptor);
            }
        }

        return $matchingInterceptors;
    }

    protected function initializeInterceptor($interceptor)
    {
        return $interceptor;
    }
}