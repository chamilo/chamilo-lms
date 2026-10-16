<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Security\CourseAccessResolver;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends Voter<'CREATE'|'VIEW'|'EDIT'|'DELETE', CCalendarEvent>
 */
class CCalendarEventVoter extends Voter
{
    public const string CREATE = 'CREATE';
    public const string VIEW = 'VIEW';
    public const string EDIT = 'EDIT';
    public const string DELETE = 'DELETE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly IsAllowedToEditHelper $isAllowedToEditHelper,
        private readonly CourseAccessResolver $courseAccessResolver,
        private readonly SettingsCourseManager $settingsCourseManager,
    ) {}

    protected function supports(string $attribute, $subject): bool
    {
        $options = [
            self::CREATE,
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ];

        // if the attribute isn't one we support, return false
        if (!\in_array($attribute, $options, true)) {
            return false;
        }

        // only vote on Post objects inside this voter
        return $subject instanceof CCalendarEvent;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        // Admins have access to everything.
        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        /** @var CCalendarEvent $event */
        $event = $subject;

        // @todo check permissions
        switch ($attribute) {
            case self::CREATE:
                return $this->canAddToCourses($user, $event->getResourceLinkArray());

            case self::VIEW:
            case self::EDIT:
                if ($event->getCreator() === $user) {
                    return true;
                }

                if ($event->isCollective() && $event->isUserSubscribedToResource($user)) {
                    return true;
                }

                // no break
            case self::DELETE:
                if ($event->getCreator() === $user) {
                    return true;
                }

                break;
        }

        return false;
    }

    /**
     * Same rule as the agenda add button: the course editors, or anyone with access to the
     * course when the course setting "Allow learners to edit agenda" is on. Links without a
     * course (personal events) are not restricted here.
     */
    private function canAddToCourses(User $user, array $links): bool
    {
        foreach ($links as $link) {
            if (empty($link['cid'])) {
                continue;
            }

            $course = $this->entityManager->find(Course::class, (int) $link['cid']);
            if (!$course instanceof Course) {
                return false;
            }

            $session = null;
            if (!empty($link['sid'])) {
                $session = $this->entityManager->find(Session::class, (int) $link['sid']);
                if (!$session instanceof Session) {
                    return false;
                }
            }

            if ($this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session)) {
                continue;
            }

            $this->settingsCourseManager->setCourse($course);

            if ('1' !== $this->settingsCourseManager->getCourseSettingValue('allow_user_edit_agenda')
                || [] === $this->courseAccessResolver->resolveCourseRoles($user, $course, $session)
            ) {
                return false;
            }
        }

        return true;
    }
}
