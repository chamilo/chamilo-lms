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

/**
 * The naming strategy interface.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface NamingStrategyInterface
{
    const SEPARATOR = '__CG__';
    const SEPARATOR_LENGTH = 6;

    /**
     * Returns the class name for the proxy class.
     *
     * The generated class name MUST be the concatenation of a nonempty prefix,
     * the namespace separator __CG__, and the original class name.
     *
     * Examples:
     *
     *    +----------------------------+------------------------------+
     *    | Original Name              | Generated Name               |
     *    +============================+==============================+
     *    | Foo\Bar                    | dred332\__CG__\Foo\Bar       |
     *    | Bar\Baz                    | Foo\Doo\__CG__\Bar\Baz       |
     *    +----------------------------+------------------------------+
     *
     * @param \ReflectionClass $class
     * @return string the class name for the generated class
     */
    function getClassName(\ReflectionClass $class);
}