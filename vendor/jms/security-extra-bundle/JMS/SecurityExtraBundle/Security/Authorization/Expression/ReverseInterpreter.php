<?php

namespace JMS\SecurityExtraBundle\Security\Authorization\Expression;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Reverse interprets expressions to find the sub-expressions which caused
 * access to be denied.
 *
 * In most cases, a single expression will be returned. The only exception
 * are non-short circuiting expressions which might return multiple expressions.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ReverseInterpreter
{
    private $parser;
    private $compiler;
    private $handler;

    public function __construct(ExpressionCompiler $compiler, ExpressionHandlerInterface $handler)
    {
        $this->compiler = $compiler;
        $this->handler = $handler;
        $this->parser = new ExpressionParser();
    }

    /**
     * Returns the last access denying expression if available.
     *
     * @param TokenInterface $token
     * @param array<*> $attributes
     * @param object|null $object
     *
     * @return Ast\ExpressionInterface|null
     */
    public function getDenyingExpr(TokenInterface $token, array $attributes, $object = null)
    {
        if (count($attributes) !== 1 || ! $attributes[0] instanceof Expression) {
            return null;
        }

        $context = $this->handler->createContext($token, $object);
        $expr = $this->parser->parse($attributes[0]->expression);

        return $this->getDenyingExprInternal($expr, $context);
    }

    private function getDenyingExprInternal(Ast\ExpressionInterface $expr, array $context)
    {
        switch (true) {
            case $expr instanceof Ast\OrExpression:
                if (null === $leftDenying = $this->getDenyingExprInternal($expr->left, $context)) {
                    return null;
                }

                if (null === $rightDenying = $this->getDenyingExprInternal($expr->right, $context)) {
                    return null;
                }

                return new Ast\OrExpression($leftDenying, $rightDenying);

            case $expr instanceof Ast\AndExpression:
                if (null !== $leftDenying = $this->getDenyingExprInternal($expr->left, $context)) {
                    return $leftDenying;
                }

                return $this->getDenyingExprInternal($expr->right, $context);

            default:
                return false === $this->evalExpr($expr, $context) ? $expr : null;
        }
    }

    private function evalExpr(Ast\ExpressionInterface $expr, array $context)
    {
        $code = $this->compiler->compile($expr);
        $evaluator = eval($code);

        return $evaluator($context);
    }
}