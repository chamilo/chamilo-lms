<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CalendarEventRecipients;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<CalendarEventRecipients>
 */
final readonly class CalendarEventRecipientsProvider implements ProviderInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private CGroupRepository $groupRepository,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CalendarEventRecipients
    {
        $course = $this->cidReqHelper->getDoctrineCourseEntity();
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('A course context is required.');
        }

        $session = $this->cidReqHelper->getDoctrineSessionEntity();

        $recipients = new CalendarEventRecipients();
        $recipients->users = $this->getUsers($course, $session);
        $recipients->groups = $this->getGroups($course, $session);

        return $recipients;
    }

    /**
     * The users subscribed to the course, or to the course in the session.
     *
     * @return list<array{id: int, label: string}>
     */
    private function getUsers(Course $course, ?Session $session): array
    {
        $subscriptions = $session instanceof Session
            ? $session->getSessionRelCourseRelUserInCourse($course)
            : $course->getUsers();

        $users = [];
        foreach ($subscriptions as $subscription) {
            $user = $subscription->getUser();
            if ($user instanceof User && $user->isActive()) {
                $users[(int) $user->getId()] = [
                    'id' => (int) $user->getId(),
                    'label' => $user->getFullNameWithUsername(),
                ];
            }
        }

        usort($users, static fn (array $a, array $b): int => strcasecmp($a['label'], $b['label']));

        return $users;
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function getGroups(Course $course, ?Session $session): array
    {
        $groups = [];

        /** @var CGroup $group */
        foreach ($this->groupRepository->findAllByCourse($course, $session)->getQuery()->getResult() as $group) {
            $groups[] = [
                'id' => (int) $group->getIid(),
                'label' => $group->getTitle(),
            ];
        }

        usort($groups, static fn (array $a, array $b): int => strcasecmp($a['label'], $b['label']));

        return $groups;
    }
}
