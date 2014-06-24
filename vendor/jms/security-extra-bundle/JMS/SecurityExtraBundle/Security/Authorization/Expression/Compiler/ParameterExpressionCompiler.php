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

namespace JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\VariableExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionCompiler;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ExpressionInterface;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler\TypeCompilerInterface;

class ParameterExpressionCompiler implements TypeCompilerInterface
{
    public function getType()
    {
        return 'JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ParameterExpression';
    }

    public function compilePreconditions(ExpressionCompiler $compiler, ExpressionInterface $parameter)
    {
        $compiler->verifyItem('object', 'CG\Proxy\MethodInvocation');

        if (!isset($compiler->attributes['parameter_mapping_name'])) {
            $this->addParameterMapping($compiler);
        }

        $compiler
            ->writeln("if (!isset(\${$compiler->attributes['parameter_mapping_name']}['{$parameter->name}'])) {")
            ->indent()
            ->write("throw new RuntimeException(sprintf('There is no parameter with name \"{$parameter->name}\" for method \"%s\".', ")
            ->compileInternal(new VariableExpression('object'))
            ->writeln("));")
            ->outdent()
            ->write("}\n\n")
        ;
    }

    public function compile(ExpressionCompiler $compiler, ExpressionInterface $parameter)
    {
        $compiler
            ->compileInternal(new VariableExpression('object'))
            ->write("->arguments[")
            ->write("\${$compiler->attributes['parameter_mapping_name']}")
            ->write("['{$parameter->name}']]")
        ;
    }

    private function addParameterMapping(ExpressionCompiler $compiler)
    {
        $name = $compiler->nextName();
        $indexName = $compiler->nextName();
        $paramName = $compiler->nextName();

        $compiler
            ->setAttribute('parameter_mapping_name', $name)
            ->writeln("\$$name = array();")
            ->write("foreach (")
            ->compileInternal(new VariableExpression('object'))
            ->writeln("->reflection->getParameters() as \$$indexName => \$$paramName) {")
            ->indent()
            ->writeln("\${$name}[\${$paramName}->name] = \$$indexName;")
            ->outdent()
            ->writeln("}\n")
        ;
    }
}