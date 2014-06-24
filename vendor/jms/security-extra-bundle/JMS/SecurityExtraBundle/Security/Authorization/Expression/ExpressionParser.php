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

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\NotExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\IsEqualExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ParameterExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\VariableExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ConstantExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\OrExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\AndExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ArrayExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\GetItemExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\GetPropertyExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\MethodCallExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ExpressionInterface;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\FunctionExpression;

final class ExpressionParser extends \JMS\Parser\AbstractParser
{
    const PRECEDENCE_OR       = 10;
    const PRECEDENCE_AND      = 15;
    const PRECEDENCE_IS_EQUAL = 20;
    const PRECEDENCE_NOT      = 30;

    public function __construct()
    {
        parent::__construct(new ExpressionLexer());
    }

    public function parseInternal()
    {
        return $this->Expression();
    }

    private function Expression($precedence = 0)
    {
        $expr = $this->Primary();

        while (true) {
            if ($this->lexer->isNext(ExpressionLexer::T_AND) && $precedence <= self::PRECEDENCE_AND) {
                $this->lexer->moveNext();

                $expr = new AndExpression($expr, $this->Expression(
                    self::PRECEDENCE_AND + 1));
                continue;
            }

            if ($this->lexer->isNext(ExpressionLexer::T_OR) && $precedence <= self::PRECEDENCE_OR) {
                $this->lexer->moveNext();

                $expr = new OrExpression($expr, $this->Expression(
                    self::PRECEDENCE_OR + 1));
                continue;
            }

            if ($this->lexer->isNext(ExpressionLexer::T_IS_EQUAL) && $precedence <= self::PRECEDENCE_IS_EQUAL) {
                $this->lexer->moveNext();

                $expr = new IsEqualExpression($expr, $this->Expression(
                    self::PRECEDENCE_IS_EQUAL + 1));
                continue;
            }

            break;
        }

        return $expr;
    }

    private function Primary()
    {
        if ($this->lexer->isNext(ExpressionLexer::T_NOT)) {
            $this->lexer->moveNext();
            $expr = new NotExpression($this->Expression(self::PRECEDENCE_NOT));

            return $this->Suffix($expr);
        }

        if ($this->lexer->isNext(ExpressionLexer::T_OPEN_PARENTHESIS)) {
            $this->lexer->moveNext();
            $expr = $this->Expression();
            $this->match(ExpressionLexer::T_CLOSE_PARENTHESIS);

            return $this->Suffix($expr);
        }

        if ($this->lexer->isNext(ExpressionLexer::T_STRING)) {
            return new ConstantExpression($this->match(ExpressionLexer::T_STRING));
        }

        if ($this->lexer->isNext(ExpressionLexer::T_OPEN_BRACE)) {
            return $this->Suffix($this->MapExpr());
        }

        if ($this->lexer->isNext(ExpressionLexer::T_OPEN_BRACKET)) {
            return $this->Suffix($this->ListExpr());
        }

        if ($this->lexer->isNext(ExpressionLexer::T_IDENTIFIER)) {
            $name = $this->match(ExpressionLexer::T_IDENTIFIER);

            if ($this->lexer->isNext(ExpressionLexer::T_OPEN_PARENTHESIS)) {
                $args = $this->Arguments();

                return $this->Suffix(new FunctionExpression($name, $args));
            }

            return $this->Suffix(new VariableExpression($name));
        }

        if ($this->lexer->isNext(ExpressionLexer::T_PARAMETER)) {
            return $this->Suffix(new ParameterExpression($this->match(ExpressionLexer::T_PARAMETER)));
        }

        $this->syntaxError('primary expression');
    }

    private function ListExpr()
    {
        $this->match(ExpressionLexer::T_OPEN_BRACKET);

        $elements = array();
        while ( ! $this->lexer->isNext(ExpressionLexer::T_CLOSE_BRACKET)) {
            $elements[] = $this->Expression();

            if ( ! $this->lexer->isNext(ExpressionLexer::T_COMMA)) {
                break;
            }
            $this->lexer->moveNext();
        }

        $this->match(ExpressionLexer::T_CLOSE_BRACKET);

        return new ArrayExpression($elements);
    }

    private function MapExpr()
    {
        $this->match(ExpressionLexer::T_OPEN_BRACE);

        $entries = array();
        while ( ! $this->lexer->isNext(ExpressionLexer::T_CLOSE_BRACE)) {
            $key = $this->match(ExpressionLexer::T_STRING);
            $this->match(ExpressionLexer::T_COLON);
            $entries[$key] = $this->Expression();

            if ( ! $this->lexer->isNext(ExpressionLexer::T_COMMA)) {
                break;
            }

            $this->lexer->moveNext();
        }

        $this->match(ExpressionLexer::T_CLOSE_BRACE);

        return new ArrayExpression($entries);
    }

    private function Suffix(ExpressionInterface $expr)
    {
        while (true) {
            if ($this->lexer->isNext(ExpressionLexer::T_OBJECT_OPERATOR)) {
                $this->lexer->moveNext();
                $name = $this->match(ExpressionLexer::T_IDENTIFIER);

                if ($this->lexer->isNext(ExpressionLexer::T_OPEN_PARENTHESIS)) {
                    $args = $this->Arguments();
                    $expr = new MethodCallExpression($expr, $name, $args);
                    continue;
                }

                $expr = new GetPropertyExpression($expr, $name);
                continue;
            }

            if ($this->lexer->isNext(ExpressionLexer::T_OPEN_BRACKET)) {
                $this->lexer->moveNext();
                $key = $this->Expression();
                $this->match(ExpressionLexer::T_CLOSE_BRACKET);
                $expr = new GetItemExpression($expr, $key);
                continue;
            }

            break;
        }

        return $expr;
    }

    private function Arguments()
    {
        $this->match(ExpressionLexer::T_OPEN_PARENTHESIS);
        $args = array();

        while ( ! $this->lexer->isNext(ExpressionLexer::T_CLOSE_PARENTHESIS)) {
            $args[] = $this->Expression();

            if ( ! $this->lexer->isNext(ExpressionLexer::T_COMMA)) {
                break;
            }

            $this->match(ExpressionLexer::T_COMMA);
        }
        $this->match(ExpressionLexer::T_CLOSE_PARENTHESIS);

        return $args;
    }
}
