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

use Symfony\Component\HttpKernel\Log\LoggerInterface;

use JMS\SecurityExtraBundle\Exception\RuntimeException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Expression-based voter.
 *
 * This voter allows to use complex access expression in a high-performance
 * way. This is the preferred voter for any non-simple access checks.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ExpressionVoter implements VoterInterface
{
    private $evaluators = array();
    private $compiler;
    private $cacheDir;
    private $expressionHandler;
    private $logger;

    public function __construct(ExpressionHandlerInterface $expressionHandler, LoggerInterface $logger = null)
    {
        $this->expressionHandler = $expressionHandler;
        $this->logger = $logger;
    }

    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function setCompiler(ExpressionCompiler $compiler)
    {
        $this->compiler = $compiler;
    }

    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $result = VoterInterface::ACCESS_ABSTAIN;
        $exprs = array();

        foreach ($attributes as $attribute) {
            if (!$attribute instanceof Expression) {
                continue;
            }

            $result = VoterInterface::ACCESS_DENIED;
            $exprs[] = $attribute->expression;
            if (!isset($this->evaluators[$attribute->expression])) {
                $this->evaluators[$attribute->expression] =
                    $this->createEvaluator($attribute);
            }

            if (call_user_func($this->evaluators[$attribute->expression],
                    $this->expressionHandler->createContext($token, $object))) {
                if (null !== $this->logger) {
                    $this->logger->info(sprintf('"%s" evaluated to true; voting to grant access.', $attribute->expression));
                }

                return VoterInterface::ACCESS_GRANTED;
            }
        }

        if (null !== $this->logger) {
            if (VoterInterface::ACCESS_DENIED === $result) {
                $this->logger->info(sprintf('"%s" evaluated to false; voting to deny access.', implode('", "', $exprs)));
            } else {
                $this->logger->info('No expression found; abstaining from voting.');
            }
        }

        return $result;
    }

    public function supportsAttribute($attribute)
    {
        return $attribute instanceof Expression;
    }

    public function supportsClass($class)
    {
        return true;
    }

    protected function getCompiler()
    {
        if (null === $this->compiler) {
            throw new RuntimeException('A compiler must be set.');
        }

        return $this->compiler;
    }

    private function createEvaluator(Expression $expr)
    {
        if ($this->cacheDir) {
            if (is_file($file = $this->cacheDir.'/'.sha1($expr->expression).'.php')) {
                return require $file;
            }

            $source = $this->getCompiler()->compileExpression($expr);
            file_put_contents($file, "<?php\n".$source);

            return require $file;
        }

        return eval($this->getCompiler()->compileExpression($expr));
    }
}
