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

namespace JMS\SecurityExtraBundle\Security\Acl\Expression;

use Symfony\Component\Security\Acl\Exception\NoAceFoundException;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Acl\Voter\FieldVote;

class PermissionEvaluator
{
    private $aclProvider;
    private $oidRetrievalStrategy;
    private $sidRetrievalStrategy;
    private $permissionMap;
    private $allowIfObjectIdentityUnavailable;
    private $logger;

    public function __construct(AclProviderInterface $aclProvider,
        ObjectIdentityRetrievalStrategyInterface $oidRetrievalStrategy,
        SecurityIdentityRetrievalStrategyInterface $sidRetrievalStrategy,
        PermissionMapInterface $permissionMap,
        LoggerInterface $logger = null,
        $allowIfObjectIdentityUnavailable = true)
    {
        $this->aclProvider = $aclProvider;
        $this->oidRetrievalStrategy = $oidRetrievalStrategy;
        $this->sidRetrievalStrategy = $sidRetrievalStrategy;
        $this->permissionMap = $permissionMap;
        $this->allowIfObjectIdentityUnavailable = $allowIfObjectIdentityUnavailable;
        $this->logger = $logger;
    }

    public function hasPermission(TokenInterface $token, $object, $permission)
    {
        if (null === $masks = $this->permissionMap->getMasks($permission, $object)) {
            return false;
        }

        if (null === $object) {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('Object identity unavailable. Voting to %s', $this->allowIfObjectIdentityUnavailable? 'grant access' : 'abstain'));
            }

            return $this->allowIfObjectIdentityUnavailable ? true : false;
        } elseif ($object instanceof FieldVote) {
            $field = $object->getField();
            $object = $object->getDomainObject();
        } else {
            $field = null;
        }

        if ($object instanceof ObjectIdentityInterface) {
            $oid = $object;
        } elseif (null === $oid = $this->oidRetrievalStrategy->getObjectIdentity($object)) {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('Object identity unavailable. Voting to %s', $this->allowIfObjectIdentityUnavailable? 'grant access' : 'abstain'));
            }

            return $this->allowIfObjectIdentityUnavailable ? true : false;
        }

        $sids = $this->sidRetrievalStrategy->getSecurityIdentities($token);

        try {
            $acl = $this->aclProvider->findAcl($oid, $sids);

            if (null === $field && $acl->isGranted($masks, $sids, false)) {
                if (null !== $this->logger) {
                    $this->logger->debug('ACL found, permission granted. Voting to grant access');
                }

                return true;
            } elseif (null !== $field && $acl->isFieldGranted($field, $masks, $sids, false)) {
                if (null !== $this->logger) {
                    $this->logger->debug('ACL found, permission granted. Voting to grant access');
                }

                return true;
            }

            if (null !== $this->logger) {
                $this->logger->debug('ACL found, insufficient permissions. Voting to deny access.');
            }

            return false;
        } catch (AclNotFoundException $noAcl) {
            if (null !== $this->logger) {
                $this->logger->debug('No ACL found for the object identity. Voting to deny access.');
            }

            return false;
        } catch (NoAceFoundException $noAce) {
            if (null !== $this->logger) {
                $this->logger->debug('ACL found, no ACE applicable. Voting to deny access.');
            }

            return false;
        }
    }
}
