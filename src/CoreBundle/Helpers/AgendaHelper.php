<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Security\CourseAccessResolver;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class AgendaHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
        private CourseAccessResolver $courseAccessResolver,
        private SettingsCourseManager $settingsCourseManager,
        private CidReqHelper $cidReqHelper,
        private UserHelper $userHelper,
        private RequestStack $requestStack,
    ) {}

    /**
     * Whether the current user may add an event with these resource links to the agenda of
     * each course they name: the course editors, or anyone with access to the course when the
     * course setting "Allow learners to edit agenda" is on (the rule of the agenda add button).
     * Links without a course (personal events and their invitations) are not restricted here.
     */
    public function canAddLinks(array $links): bool
    {
        $user = $this->userHelper->getCurrent();

        if (null === $user) {
            return false;
        }

        foreach ($links as $link) {
            if (!\is_array($link)) {
                return false;
            }

            if (empty($link['cid'])) {
                // A session or group link always belongs to a course.
                if (!empty($link['sid']) || !empty($link['gid'])) {
                    return false;
                }

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

    /**
     * The course links a new event really gets: ResourceListener::normalizeSingleLinkContextFromSession()
     * replaces the context of a single link with the course of the request.
     */
    public function getNewEventLinks(array $links): array
    {
        if (1 !== \count($links) || !\is_array($links[0] ?? null)
            || !empty($links[0]['uid']) || !empty($links[0]['ugid'])
        ) {
            return $links;
        }

        $course = $this->cidReqHelper->getDoctrineCourseEntity();
        if (!$course instanceof Course) {
            return $links;
        }

        return [[
            'cid' => $course->getId(),
            'sid' => $this->cidReqHelper->getDoctrineSessionEntity()?->getId(),
        ]];
    }

    /**
     * Whether the request asks for a global event, read as ResourceListener::addCCalendarEventGlobalLink() does.
     */
    public function isGlobalEventRequest(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return false;
        }

        $type = $request->query->get('type');
        if (null !== $type) {
            return 'global' === $type;
        }

        $payload = json_decode($request->getContent(), true);

        return \is_array($payload) && isset($payload['isGlobal']) && 1 === (int) $payload['isGlobal'];
    }
}
