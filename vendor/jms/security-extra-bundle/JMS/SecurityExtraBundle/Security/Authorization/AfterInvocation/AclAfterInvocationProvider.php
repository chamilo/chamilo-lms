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

namespace JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Exception\NoAceFoundException;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * This after invocation provider filters returned objects based on ACLs.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AclAfterInvocationProvider implements AfterInvocationProviderInterface
{
    private $aclProvider;
    private $oidRetrievalStrategy;
    private $sidRetrievalStrategy;
    private $permissionMap;
    private $logger;

    public function __construct(AclProviderInterface $aclProvider, ObjectIdentityRetrievalStrategyInterface $oidRetrievalStrategy, SecurityIdentityRetrievalStrategyInterface $sidRetrievalStrategy, PermissionMapInterface $permissionMap, LoggerInterface $logger = null)
    {
        $this->aclProvider = $aclProvider;
        $this->oidRetrievalStrategy = $oidRetrievalStrategy;
        $this->sidRetrievalStrategy = $sidRetrievalStrategy;
        $this->permissionMap = $permissionMap;
        $this->logger = $logger;
    }

    public function decide(TokenInterface $token, $secureObject, array $attributes, $returnedObject)
    {
        if (null === $returnedObject) {
            if (null !== $this->logger) {
                $this->logger->debug('Returned object was null, skipping security check.');
            }

            return null;
        }

        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            if (null === $oid = $this->oidRetrievalStrategy->getObjectIdentity($returnedObject)) {
                if (null !== $this->logger) {
                    $this->logger->debug('Returned object was no domain object, skipping security check.');
                }

                return $returnedObject;
            }

            $sids = $this->sidRetrievalStrategy->getSecurityIdentities($token);

            try {
                $acl = $this->aclProvider->findAcl($oid, $sids);
                if ($acl->isGranted($this->permissionMap->getMasks($attribute, $returnedObject), $sids, false)) {
                    return $returnedObject;
                }

                if (null !== $this->logger) {
                    $this->logger->debug('Token has been denied access for returned object.');
                }
            } catch (AclNotFoundException $noAcl) {
                throw new AccessDeniedException('No applicable ACL found for domain object.');
            } catch (NoAceFoundException $noAce) {
                if (null !== $this->logger) {
                    $this->logger->debug('No applicable ACE found for the given Token, denying access.');
                }
            }

            throw new AccessDeniedException('ACL has denied access for attribute: '.$attribute);
        }

        // no attribute was supported
        return $returnedObject;
    }

    public function supportsAttribute($attribute)
    {
        return $this->permissionMap->contains($attribute);
    }

    public function supportsClass($className)
    {
        return true;
    }
}
