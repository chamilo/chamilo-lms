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

use JMS\SecurityExtraBundle\Exception\RuntimeException;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler\TypeCompilerInterface;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler\Func\FunctionCompilerInterface;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ExpressionInterface;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\VariableExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\MethodCallExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\GetPropertyExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\GetItemExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\FunctionExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ConstantExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ArrayExpression;

class ExpressionCompiler
{
    public $attributes = array();

    private $indentationLevel = 0;
    private $indentationSpaces = 4;

    private $nameCount = 0;
    private $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private $charCount = 52;
    private $reservedNames = array('context' => true);

    private $itemExists = array();
    private $rolesName;

    private $code;
    private $parser;
    private $typeCompilers;
    private $functionCompilers;

    public function __construct()
    {
        $this->addTypeCompiler(new Compiler\AndExpressionCompiler());
        $this->addTypeCompiler(new Compiler\IsEqualExpressionCompiler());
        $this->addTypeCompiler(new Compiler\OrExpressionCompiler());
        $this->addTypeCompiler(new Compiler\VariableExpressionCompiler());
        $this->addTypeCompiler(new Compiler\NotExpressionCompiler());

        $this->functionCompilers = array(
            'isAnonymous' => new Compiler\Func\IsAnonymousFunctionCompiler(),
            'isAuthenticated' => new Compiler\Func\IsAuthenticatedFunctionCompiler(),
            'isRememberMe' => new Compiler\Func\IsRememberMeFunctionCompiler(),
            'isFullyAuthenticated' => new Compiler\Func\IsFullyAuthenticatedFunctionCompiler(),
            'hasRole' => new Compiler\Func\HasRoleFunctionCompiler(),
            'hasAnyRole' => new Compiler\Func\HasAnyRoleFunctionCompiler(),
        );
    }

    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function addTypeCompiler(TypeCompilerInterface $compiler)
    {
        $this->typeCompilers[$compiler->getType()] = $compiler;
    }

    public function addFunctionCompiler(FunctionCompilerInterface $compiler)
    {
        $this->functionCompilers[$compiler->getName()] = $compiler;
    }

    public function compileExpression(Expression $expr)
    {
        return $this->compile($this->getParser()->parse($expr->expression),
            $expr->expression);
    }

    public function compile(ExpressionInterface $expr, $raw = null)
    {
        $this->nameCount  = 0;
        $this->code       = '';
        $this->itemExists = $this->attributes = array();
        $this->rolesName  = null;

        if ($raw) {
            $this->writeln('// Expression: '.$raw);
        }

        $this
            ->writeln('return function(array $context) {')
            ->indent()
                ->compilePreconditions($expr)
                ->write('return ')
                ->compileInternal($expr)
                ->writeln(';')
            ->outdent()
            ->writeln('};')
        ;

        return $this->code;
    }

    public function indent()
    {
        $this->indentationLevel += 1;

        return $this;
    }

    public function outdent()
    {
        $this->indentationLevel -= 1;

        if ($this->indentationLevel < 0) {
            throw new RuntimeException('The indentation level cannot be less than zero.');
        }

        return $this;
    }

    public function writeln($content)
    {
        $this->write($content."\n");

        return $this;
    }

    public function getRolesExpr()
    {
        if (null !== $this->rolesName) {
            return '$'.$this->rolesName;
        }

        $this->verifyItem('token', 'Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->rolesName = $rolesName = $this->nextName();
        $hierarchyName = $this->nextName();
        $tmpName = $this->nextName();
        $this
            ->writeln("\$$rolesName = \$context['token']->getRoles();")
            ->write("if (null !== \$$hierarchyName = ")
            ->compileInternal(new VariableExpression('role_hierarchy', true))
            ->writeln(") {")
            ->indent()
            ->writeln("\$$rolesName = \${$hierarchyName}->getReachableRoles(\$$rolesName);")
            ->outdent()
            ->write("}\n\n")
            ->writeln("\$$tmpName = array();")
            ->writeln("foreach (\$$rolesName as \$role) {")
            ->indent()
            ->writeln("\${$tmpName}[\$role->getRole()] = true;")
            ->outdent()
            ->writeln("}")
            ->write("\$$rolesName = \$$tmpName;\n\n")
        ;

        return '$'.$rolesName;
    }

    public function verifyItem($key, $expectedType = null)
    {
        if (!isset($this->itemExists[$key])) {
            $this->itemExists[$key] = true;

            $this
                ->writeln("if (!isset(\$context['$key'])) {")
                ->indent()
                ->writeln("throw new RuntimeException('The context contains no item with key \"$key\".');")
                ->outdent()
                ->write("}\n\n")
            ;
        }

        if (null !== $expectedType) {
            $this
                ->writeln("if (!\$context['$key'] instanceof $expectedType) {")
                ->indent()
                ->writeln("throw new RuntimeException(sprintf('The item \"$key\" is expected to be of type \"$expectedType\", but got \"%s\".', get_class(\$context['$key'])));")
                ->outdent()
                ->write("}\n\n")
            ;
        }

        return $this;
    }

    public function write($content)
    {
        $lines = explode("\n", $content);
        for ($i=0,$c=count($lines); $i<$c; $i++) {
            if ($this->indentationLevel > 0
            && !empty($lines[$i])
            && (empty($this->code) || "\n" === substr($this->code, -1))) {
                $this->code .= str_repeat(' ', $this->indentationLevel * $this->indentationSpaces);
            }

            $this->code .= $lines[$i];

            if ($i+1 < $c) {
                $this->code .= "\n";
            }
        }

        return $this;
    }

    public function nextName()
    {
        while (true) {
            $name = '';
            $i = $this->nameCount;

            $name .= $this->chars[$i % $this->charCount];
            $i = intval($i / $this->charCount);

            while ($i > 0) {
                $i -= 1;
                $name .= $this->chars[$i % $this->charCount];
                $i = intval($i / $this->charCount);
            }

            $this->nameCount += 1;

            // check that the name is not reserved
            if (isset($this->reservedNames[$name])) {
                continue;
            }

            return $name;
        }
    }

    public function compilePreconditions(ExpressionInterface $expr)
    {
        if ($typeCompiler = $this->findTypeCompiler(get_class($expr))) {
            $typeCompiler->compilePreconditions($this, $expr);

            return $this;
        }

        if ($expr instanceof FunctionExpression) {
            foreach ($expr->args as $arg) {
                $this->compilePreconditions($arg);
            }

            $this->getFunctionCompiler($expr->name)->compilePreconditions($this, $expr);

            return $this;
        }

        if ($expr instanceof MethodCallExpression) {
            $this->compilePreconditions($expr->object);

            foreach ($expr->args as $arg) {
                $this->compilePreconditions($arg);
            }

            return $this;
        }

        if ($expr instanceof GetPropertyExpression) {
            $this->compilePreconditions($expr->object);

            return $this;
        }

        return $this;
    }

    public function compileInternal(ExpressionInterface $expr)
    {
        if ($typeCompiler = $this->findTypeCompiler(get_class($expr))) {
            $typeCompiler->compile($this, $expr);

            return $this;
        }

        if ($expr instanceof ArrayExpression) {
            $this->code .= 'array(';
            foreach ($expr->elements as $key => $value) {
                $this->code .= var_export($key, true).' => ';
                $this->compileInternal($value);
                $this->code .= ',';
            }
            $this->code .= ')';

            return $this;
        }

        if ($expr instanceof ConstantExpression) {
            $this->code .= var_export($expr->value, true);

            return $this;
        }

        if ($expr instanceof FunctionExpression) {
            $this->getFunctionCompiler($expr->name)->compile($this, $expr);

            return $this;
        }

        if ($expr instanceof GetItemExpression) {
            $this->compileInternal($expr->array);
            $this->code .= '['.$expr->key.']';

            return $this;
        }

        if ($expr instanceof GetPropertyExpression) {
            $this->compileInternal($expr->object);
            $this->code .= '->'.$expr->name;

            return $this;
        }

        if ($expr instanceof MethodCallExpression) {
            $this->compileInternal($expr->object);
            $this->code .= '->'.$expr->method.'(';

            $first = true;
            foreach ($expr->args as $arg) {
                if (!$first) {
                    $this->code .= ', ';
                }
                $first = false;

                $this->compileInternal($arg);
            }
            $this->code .= ')';

            return $this;
        }

        throw new RuntimeException(sprintf('Unknown expression "%s".', get_class($expr)));
    }

    public function getFunctionCompiler($name)
    {
        if (!isset($this->functionCompilers[$name])) {
            throw new RuntimeException(sprintf('There is no compiler for function "%s".', $name));
        }

        return $this->functionCompilers[$name];
    }

    private function findTypeCompiler($type)
    {
        return isset($this->typeCompilers[$type]) ? $this->typeCompilers[$type] : null;
    }

    private function getParser()
    {
        if (null !== $this->parser) {
            return $this->parser;
        }

        return $this->parser = new ExpressionParser();
    }
}
