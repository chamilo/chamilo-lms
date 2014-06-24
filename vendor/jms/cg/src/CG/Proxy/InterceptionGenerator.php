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

use CG\Core\ClassUtils;

use CG\Core\ReflectionUtils;

use CG\Generator\PhpParameter;
use CG\Generator\PhpProperty;
use CG\Generator\PhpMethod;
use CG\Generator\PhpClass;

/**
 * Interception Generator.
 *
 * This generator creates joinpoints to allow for AOP advices. Right now, it only
 * supports the most powerful around advice.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class InterceptionGenerator implements GeneratorInterface
{
    private $prefix = '__CGInterception__';
    private $filter;
    private $requiredFile;

    public function setRequiredFile($file)
    {
        $this->requiredFile = $file;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function setFilter(\Closure $filter)
    {
        $this->filter = $filter;
    }

    public function generate(\ReflectionClass $originalClass, PhpClass $genClass)
    {
        $methods = ReflectionUtils::getOverrideableMethods($originalClass);

        if (null !== $this->filter) {
            $methods = array_filter($methods, $this->filter);
        }

        if (empty($methods)) {
            return;
        }

        if (!empty($this->requiredFile)) {
            $genClass->addRequiredFile($this->requiredFile);
        }

        $interceptorLoader = new PhpProperty();
        $interceptorLoader
            ->setName($this->prefix.'loader')
            ->setVisibility(PhpProperty::VISIBILITY_PRIVATE)
        ;
        $genClass->setProperty($interceptorLoader);

        $loaderSetter = new PhpMethod();
        $loaderSetter
            ->setName($this->prefix.'setLoader')
            ->setVisibility(PhpMethod::VISIBILITY_PUBLIC)
            ->setBody('$this->'.$this->prefix.'loader = $loader;')
        ;
        $genClass->setMethod($loaderSetter);
        $loaderParam = new PhpParameter();
        $loaderParam
            ->setName('loader')
            ->setType('CG\Proxy\InterceptorLoaderInterface')
        ;
        $loaderSetter->addParameter($loaderParam);

        $interceptorCode =
             '$ref = new \ReflectionMethod(%s, %s);'."\n"
            .'$interceptors = $this->'.$this->prefix.'loader->loadInterceptors($ref, $this, array(%s));'."\n"
        	.'$invocation = new \CG\Proxy\MethodInvocation($ref, $this, array(%s), $interceptors);'."\n\n"
            .'return $invocation->proceed();'
        ;

        foreach ($methods as $method) {
            $params = array();
            foreach ($method->getParameters() as $param) {
                $params[] = '$'.$param->name;
            }
            $params = implode(', ', $params);

            $genMethod = PhpMethod::fromReflection($method)
                ->setBody(sprintf($interceptorCode, var_export(ClassUtils::getUserClass($method->class), true), var_export($method->name, true), $params, $params))
                ->setDocblock(null)
            ;
            $genClass->setMethod($genMethod);
        }
    }
}