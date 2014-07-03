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

namespace JMS\SecurityExtraBundle\Security\Authorization\Expression;

final class Expression
{
    /** READ-ONLY */
    public $expression;

    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    public function getHashCode()
    {
        return sha1($this->expression);
    }

    public function __toString()
    {
        return 'EXPRESSION('.$this->expression.')';
    }
}
