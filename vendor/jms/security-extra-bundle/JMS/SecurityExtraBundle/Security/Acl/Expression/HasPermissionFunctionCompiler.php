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

namespace JMS\SecurityExtraBundle\Security\Acl\Expression;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ConstantExpression;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\VariableExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\FunctionExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionCompiler;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler\Func\FunctionCompilerInterface;

class HasPermissionFunctionCompiler implements FunctionCompilerInterface
{
    public function getName()
    {
        return 'hasPermission';
    }

    public function compilePreconditions(ExpressionCompiler $compiler, FunctionExpression $function)
    {
        $compiler->verifyItem('token', 'Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
    }

    public function compile(ExpressionCompiler $compiler, FunctionExpression $function)
    {
        $compiler
            ->compileInternal(new VariableExpression('permission_evaluator'))
            ->write('->hasPermission(')
            ->compileInternal(new VariableExpression('token'))
            ->write(', ')
            ->compileInternal($function->args[0])
            ->write(', ')
        ;

        if ($function->args[1] instanceof ConstantExpression) {
            $compiler->write(var_export(strtoupper($function->args[1]->value), true).')');

            return;
        }

        $compiler
            ->write('strtoupper(')
            ->compileInternal($function->args[1])
            ->write('))')
        ;
    }
}