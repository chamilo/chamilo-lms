<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'DOWNLOAD', ResourceFile>
 */
final class ResourceFileVoter extends Voter
{
    public const DOWNLOAD = 'VIEW';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        $options = [
            self::DOWNLOAD,
        ];

        if (!\in_array($attribute, $options, true)) {
            return false;
        }

        return $subject instanceof ResourceFile;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $resourceFile = $subject;
        $resourceNode = $resourceFile->getResourceNode();

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        return match ($attribute) {
            self::DOWNLOAD => $this->accessDecisionManager->decide(
                $token,
                [ResourceNodeVoter::VIEW],
                $resourceNode
            ),
            default => false,
        };
    }
}
