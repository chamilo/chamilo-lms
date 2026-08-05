<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyPending;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Repository\CSurveyInvitationRepository;
use DateTimeInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProviderInterface<SurveyPending>
 */
final readonly class SurveyPendingProvider implements ProviderInterface
{
    public function __construct(
        private CSurveyInvitationRepository $surveyInvitationRepository,
        private Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyPending
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        $pending = new SurveyPending();
        $pending->items = $this->getPendingItems($user);
        $pending->totalItems = \count($pending->items);

        return $pending;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPendingItems(User $user): array
    {
        $items = [];

        foreach ($this->surveyInvitationRepository->getUserPendingInvitations($user) as $invitation) {
            if (!$invitation instanceof CSurveyInvitation) {
                continue;
            }

            $item = $this->normalizeInvitation($invitation);
            if (null === $item) {
                continue;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeInvitation(CSurveyInvitation $invitation): ?array
    {
        $course = $invitation->getCourse();
        $survey = $invitation->getSurvey();

        if (!$course instanceof Course || !$survey instanceof CSurvey || null === $survey->getIid()) {
            return null;
        }

        $session = $invitation->getSession();
        $group = $invitation->getGroup();
        $nodeId = $this->getSurveyNodeId($survey, $course);
        $query = $this->buildAnswerQuery($course, $session, $group, $invitation);
        $routeSegment = 3 === $survey->getSurveyType() ? 'meeting' : 'answer';

        return [
            'iid' => (int) $invitation->getIid(),
            'surveyId' => (int) $survey->getIid(),
            'nodeId' => $nodeId,
            'code' => $survey->getCode(),
            'title' => $survey->getTitle(),
            'subtitle' => $survey->getSubtitle(),
            'availableFrom' => $this->formatDate($survey->getAvailFrom()),
            'availableUntil' => $this->formatDate($survey->getAvailTill()),
            'surveyType' => $survey->getSurveyType(),
            'surveyTypeLabel' => $this->getSurveyTypeLabel($survey->getSurveyType()),
            'routeName' => 3 === $survey->getSurveyType() ? 'SurveyMeeting' : 'SurveyAnswer',
            'answerUrl' => \sprintf(
                '/resources/survey/%d/%d/%s?%s',
                $nodeId,
                (int) $survey->getIid(),
                $routeSegment,
                http_build_query($query),
            ),
            'invitationCode' => $invitation->getInvitationCode(),
            'course' => $this->normalizeCourse($course),
            'session' => $this->normalizeSession($session),
            'group' => $this->normalizeGroup($group),
        ];
    }

    private function getSurveyNodeId(CSurvey $survey, Course $course): int
    {
        $surveyNode = $survey->getResourceNode();
        if (null !== $surveyNode && null !== $surveyNode->getId()) {
            return (int) $surveyNode->getId();
        }

        $courseNode = $course->getResourceNode();
        if (null !== $courseNode && null !== $courseNode->getId()) {
            return (int) $courseNode->getId();
        }

        return (int) $course->getId();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAnswerQuery(
        Course $course,
        ?Session $session,
        ?CGroup $group,
        CSurveyInvitation $invitation
    ): array {
        $query = [
            'cid' => (int) $course->getId(),
            'invitationCode' => $invitation->getInvitationCode(),
        ];

        if ($session instanceof Session && null !== $session->getId()) {
            $query['sid'] = (int) $session->getId();
        }

        if ($group instanceof CGroup && null !== $group->getIid()) {
            $query['gid'] = (int) $group->getIid();
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeCourse(Course $course): array
    {
        return [
            'id' => (int) $course->getId(),
            'code' => $course->getCode(),
            'title' => $course->getTitle(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeSession(?Session $session): ?array
    {
        if (!$session instanceof Session || null === $session->getId()) {
            return null;
        }

        return [
            'id' => (int) $session->getId(),
            'title' => $session->getTitle(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeGroup(?CGroup $group): ?array
    {
        if (!$group instanceof CGroup || null === $group->getIid()) {
            return null;
        }

        return [
            'id' => (int) $group->getIid(),
            'title' => $group->getTitle(),
        ];
    }

    private function formatDate(?DateTimeInterface $date): ?string
    {
        return $date?->format(DateTimeInterface::ATOM);
    }

    private function getSurveyTypeLabel(int $type): string
    {
        return match ($type) {
            3 => 'Meeting poll',
            default => 'Regular survey',
        };
    }
}
