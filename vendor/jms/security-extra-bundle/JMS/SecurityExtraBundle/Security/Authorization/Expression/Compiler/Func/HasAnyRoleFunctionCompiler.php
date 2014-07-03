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

use JMS\SecurityExtraBundle\Exception\RuntimeException;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\FunctionExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionCompiler;

class HasAnyRoleFunctionCompiler implements FunctionCompilerInterface
{
    private $rolesExpr;

    public function getName()
    {
        return 'hasAnyRole';
    }

    public function compilePreconditions(ExpressionCompiler $compiler, FunctionExpression $function)
    {
        if (0 === count($function->args)) {
            throw new RuntimeException('The function hasAnyRole() expects at least one argument, but got none.');
        }

        $this->rolesExpr = $compiler->getRolesExpr();
    }

    public function compile(ExpressionCompiler $compiler, FunctionExpression $function)
    {
        $compiler->write("(");

        $first = true;
        foreach ($function->args as $arg) {
            if (!$first) {
                $compiler->write(" || ");
            }
            $first = false;

            $compiler
                ->write("isset({$this->rolesExpr}[")
                ->compileInternal($arg)
                ->write("])")
            ;
        }

        $compiler->write(")");
    }
}
