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

namespace JMS\SecurityExtraBundle\Security\Authorization\Interception;

use JMS\SecurityExtraBundle\Exception\RuntimeException;

use CG\Proxy\MethodInterceptorInterface;
use CG\Proxy\MethodInvocation;
use JMS\SecurityExtraBundle\Metadata\MethodMetadata;
use JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken;
use JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation\AfterInvocationManagerInterface;
use JMS\SecurityExtraBundle\Security\Authorization\RunAsManagerInterface;
use Metadata\MetadataFactoryInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/**
 * All invocations of secure methods will go through this class.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class MethodSecurityInterceptor implements MethodInterceptorInterface
{
    private $alwaysAuthenticate;
    private $securityContext;
    private $metadataFactory;
    private $authenticationManager;
    private $accessDecisionManager;
    private $afterInvocationManager;
    private $runAsManager;
    private $logger;

    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, AccessDecisionManagerInterface $accessDecisionManager,
                                AfterInvocationManagerInterface $afterInvocationManager, RunAsManagerInterface $runAsManager, MetadataFactoryInterface $metadataFactory, LoggerInterface $logger = null)
    {
        $this->alwaysAuthenticate = false;
        $this->securityContext = $securityContext;
        $this->metadataFactory = $metadataFactory;
        $this->authenticationManager = $authenticationManager;
        $this->accessDecisionManager = $accessDecisionManager;
        $this->afterInvocationManager = $afterInvocationManager;
        $this->runAsManager = $runAsManager;
        $this->logger = $logger;
    }

    public function setAlwaysAuthenticate($boolean)
    {
        $this->alwaysAuthenticate = !!$boolean;
    }

    public function intercept(MethodInvocation $method)
    {
        $metadata = $this->metadataFactory->getMetadataForClass($method->reflection->class);

        // no security metadata, proceed
        if (empty($metadata) || !isset($metadata->methodMetadata[$method->reflection->name])) {
            return $method->proceed();
        }
        $metadata = $metadata->methodMetadata[$method->reflection->name];

        if (null === $token = $this->securityContext->getToken()) {
            throw new AuthenticationCredentialsNotFoundException(
                'The security context was not populated with a Token.'
            );
        }

        if ($this->alwaysAuthenticate || !$token->isAuthenticated()) {
            $token = $this->authenticationManager->authenticate($token);
            $this->securityContext->setToken($token);
        }

        if (!empty($metadata->roles) && false === $this->accessDecisionManager->decide($token, $metadata->roles, $method)) {
            throw new AccessDeniedException('Token does not have the required roles.');
        }

        if (!empty($metadata->paramPermissions)) {
            foreach ($method->arguments as $index => $argument) {
                if (null !== $argument && isset($metadata->paramPermissions[$index]) && false === $this->accessDecisionManager->decide($token, $metadata->paramPermissions[$index], $argument)) {
                    throw new AccessDeniedException(sprintf('Token does not have the required permissions for method "%s::%s".', $method->reflection->class, $method->reflection->name));
                }
            }
        }

        $runAsToken = null;
        if (!empty($metadata->runAsRoles)) {
            $runAsToken = $this->runAsManager->buildRunAs($token, $method, $metadata->runAsRoles);

            if (null !== $this->logger) {
                $this->logger->debug('Populating security context with RunAsToken');
            }

            if (null === $runAsToken) {
                throw new RuntimeException('RunAsManager must not return null from buildRunAs().');
            }

            $this->securityContext->setToken($runAsToken);
        }

        try {
            $returnValue = $method->proceed();

            if (null !== $runAsToken) {
                $this->restoreOriginalToken($runAsToken);
            }

            if (empty($metadata->returnPermissions)) {
                return $returnValue;
            }

            return $this->afterInvocationManager->decide($this->securityContext->getToken(), $method, $metadata->returnPermissions, $returnValue);
        } catch (\Exception $failed) {
            if (null !== $runAsToken) {
                $this->restoreOriginalToken($runAsToken);
            }

            throw $failed;
        }
    }

    private function restoreOriginalToken(RunAsUserToken $runAsToken)
    {
        if (null !== $this->logger) {
            $this->logger->debug('Populating security context with original Token.');
        }

        $this->securityContext->setToken($runAsToken->getOriginalToken());
    }
}
