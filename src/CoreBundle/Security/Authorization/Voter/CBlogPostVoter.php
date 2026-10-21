<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CBlogPost;
use Chamilo\CourseBundle\Entity\CBlogRelUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'CREATE', CBlogPost>
 */
final class CBlogPostVoter extends Voter
{
    public const string CREATE = 'CREATE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::CREATE === $attribute && $subject instanceof CBlogPost;
    }

    /**
     * A post can be created by whoever can edit the blog, or by a user subscribed to the blog.
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        $node = $subject->getBlog()?->getResourceNode();

        if (!$user instanceof User || null === $node) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, [ResourceNodeVoter::EDIT], $node)) {
            return true;
        }

        if (!$this->accessDecisionManager->decide($token, [ResourceNodeVoter::VIEW], $node)) {
            return false;
        }

        $subscription = $this->entityManager->getRepository(CBlogRelUser::class)->findOneBy([
            'blog' => $subject->getBlog()->getIid(),
            'user' => $user->getId(),
        ]);

        return null !== $subscription;
    }
}
