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

/**
 * A regex pointcut implementation.
 *
 * Uses a regular expression for determining whether the pointcut matches.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class RegexPointcut implements PointcutInterface
{
    private $pattern;

    /**
     * @param array<string> $pattern
     */
    public function __construct($pattern)
    {
        $this->pattern = $pattern;
    }

    public function matchesClass(\ReflectionClass $class)
    {
        return true;
    }

    public function matchesMethod(\ReflectionMethod $method)
    {
        return 0 < preg_match('#'.$this->pattern.'#', sprintf('%s::%s', $method->class, $method->name));
    }
}
