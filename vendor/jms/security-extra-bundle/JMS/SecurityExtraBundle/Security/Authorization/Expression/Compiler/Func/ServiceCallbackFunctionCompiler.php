<?php

namespace JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler\Func;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\VariableExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\FunctionExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionCompiler;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler\Func\FunctionCompilerInterface;

/**
 * Allows to register a method on a service as a expression function.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ServiceCallbackFunctionCompiler implements FunctionCompilerInterface
{
    private $functionName;
    private $serviceId;
    private $methodName;

    public function __construct($functionName, $serviceId, $methodName)
    {
        $this->functionName = $functionName;
        $this->serviceId = $serviceId;
        $this->methodName = $methodName;
    }

    public function compilePreconditions(ExpressionCompiler $compiler, FunctionExpression $function)
    {
        $compiler->compilePreconditions(new VariableExpression('container'));
    }

    public function compile(ExpressionCompiler $compiler, FunctionExpression $function)
    {
        $compiler
            ->compileInternal(new VariableExpression('container'))
            ->write('->get('.var_export($this->serviceId, true).')')
            ->write('->'.$this->methodName.'(')
        ;

        $first = true;
        foreach ($function->args as $arg) {
            if ( ! $first) {
                $compiler->write(', ');
            }
            $first = false;

            $compiler->compileInternal($arg);
        }
        $compiler->write(')');
    }

    public function getName()
    {
        return $this->functionName;
    }
}