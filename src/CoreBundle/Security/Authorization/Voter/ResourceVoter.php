<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CGroup;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'CREATE'|'VIEW'|'EDIT'|'DELETE'|'EXPORT', AbstractResource>
 */
class ResourceVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const CREATE = 'CREATE';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';
    public const EXPORT = 'EXPORT';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    public static function getReaderMask(): int
    {
        $builder = (new MaskBuilder())
            ->add(self::VIEW)
        ;

        return $builder->get();
    }

    public static function getEditorMask(): int
    {
        $builder = (new MaskBuilder())
            ->add(self::VIEW)
            ->add(self::EDIT)
        ;

        return $builder->get();
    }

    protected function supports(string $attribute, $subject): bool
    {
        $options = [
            self::VIEW,
            self::CREATE,
            self::EDIT,
            self::DELETE,
            self::EXPORT,
        ];

        // if the attribute isn't one we support, return false
        if (!\in_array($attribute, $options, true)) {
            return false;
        }

        // These are AbstractResource subclasses, but each is authorized by its
        // own dedicated voter (CourseVoter, GroupVoter, CCalendarEventVoter,
        // UsergroupVoter). This voter must abstain on them so it does not
        // co-decide under the unanimous strategy.
        if ($subject instanceof Course
            || $subject instanceof CGroup
            || $subject instanceof CCalendarEvent
            || $subject instanceof Usergroup
        ) {
            return false;
        }

        // only vote on AbstractResource objects inside this voter
        return $subject instanceof AbstractResource;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        // Delegate the decision to the resource node ACL (ResourceNodeVoter),
        // which performs the real course/session/group/owner scoping. Failing
        // closed when there is no node (e.g. a not-yet-persisted resource on
        // CREATE) replaces the previous unconditional grant.
        if (!$subject instanceof AbstractResource) {
            return false;
        }

        $resourceNode = $subject->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        // Use the AccessDecisionManager (not Security::isGranted) so the nested
        // decision runs against the exact token passed to this voter, per the
        // Symfony voter docs ("Checking for Roles inside a Voter").
        return $this->accessDecisionManager->decide($token, [$attribute], $resourceNode);
    }
}
