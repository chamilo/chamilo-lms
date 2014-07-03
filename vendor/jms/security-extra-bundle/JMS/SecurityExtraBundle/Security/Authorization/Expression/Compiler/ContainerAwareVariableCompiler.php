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

use Symfony\Component\DependencyInjection\ContainerInterface;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ExpressionInterface;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionCompiler;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler\VariableExpressionCompiler;

class ContainerAwareVariableCompiler extends VariableExpressionCompiler
{
    private $serviceMap = array();
    private $parameterMap = array();

    public function setMaps(array $serviceMap, array $parameterMap)
    {
        $this->serviceMap = $serviceMap;
        $this->parameterMap = $parameterMap;
    }

    public function compile(ExpressionCompiler $compiler, ExpressionInterface $expr)
    {
        if (isset($this->serviceMap[$expr->name])) {
            $compiler->write("\$context['container']->get('{$this->serviceMap[$expr->name]}'");

            if ($expr->allowNull) {
                $compiler->write(", ".ContainerInterface::NULL_ON_INVALID_REFERENCE);
            }

            $compiler->write(")");

            return;
        }

        if (isset($this->parameterMap[$expr->name])) {
            $compiler->write("\$context['container']->getParameter('{$this->parameterMap[$expr->name]}')");

            return;
        }

        parent::compile($compiler, $expr);
    }

    protected function isKnown($variable)
    {
        return isset($this->serviceMap[$variable]) || isset($this->parameterMap[$variable]) || parent::isKnown($variable);
    }
}
