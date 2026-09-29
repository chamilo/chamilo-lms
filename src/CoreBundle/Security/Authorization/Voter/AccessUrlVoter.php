<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Restricts POST/PUT/PATCH/DELETE on /api/access_urls(/{id}) to an unrestricted
 * ROLE_GLOBAL_ADMIN (registered in the topmost URL of a tree — AccessUrlScopeHelper). Creating,
 * editing and deleting a URL are all reserved to unrestricted admins specifically, not merely
 * gated by subtree: a subtree admin may not do any of these, even for a URL they otherwise
 * manage content on. This matches AccessUrlManageController's create()/update()/setStatus().
 * The default (topmost, id 1) URL can additionally never be deleted, by anyone.
 *
 * @extends Voter<'CREATE'|'EDIT'|'DELETE', AccessUrl>
 */
class AccessUrlVoter extends Voter
{
    public const string CREATE = 'CREATE';
    public const string EDIT = 'EDIT';
    public const string DELETE = 'DELETE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly AccessUrlScopeHelper $accessUrlScope,
    ) {}

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::CREATE, self::EDIT, self::DELETE], true) && $subject instanceof AccessUrl;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $currentUser = $token->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        if (!$this->accessDecisionManager->decide($token, ['ROLE_GLOBAL_ADMIN'])) {
            return false;
        }

        if (!$this->accessUrlScope->isUnrestricted($currentUser)) {
            return false;
        }

        if (self::CREATE === $attribute) {
            return true;
        }

        /** @var AccessUrl $accessUrl */
        $accessUrl = $subject;

        return self::DELETE !== $attribute || 1 !== (int) $accessUrl->getId();
    }
}
