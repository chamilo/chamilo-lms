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

namespace JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler\Func;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\VariableExpression;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\FunctionExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionCompiler;

class IsAuthenticatedFunctionCompiler extends AuthenticationTrustFunctionCompiler
{
    public function getName()
    {
        return 'isAuthenticated';
    }

    public function compile(ExpressionCompiler $compiler, FunctionExpression $function)
    {
        $compiler
            ->write("!")
            ->compileInternal(new VariableExpression('trust_resolver'))
            ->write("->isAnonymous(\$context['token'])")
        ;
    }
}