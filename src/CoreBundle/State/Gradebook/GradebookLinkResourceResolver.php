<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Repository\CAttendanceRepository;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GradebookLinkResourceResolver
{
    public const LINK_EXERCISE = 1;
    public const LINK_DROPBOX = 2;
    public const LINK_STUDENT_PUBLICATION = 3;
    public const LINK_LEARNING_PATH = 4;
    public const LINK_FORUM_THREAD = 5;
    public const LINK_ATTENDANCE = 7;
    public const LINK_SURVEY = 8;
    public const LINK_HOTPOTATOES = 9;
    public const LINK_FORUM_PARTICIPATION = 11;

    /**
     * @var list<int>
     */
    private const SUPPORTED_TYPES = [
        self::LINK_EXERCISE,
        self::LINK_STUDENT_PUBLICATION,
        self::LINK_LEARNING_PATH,
        self::LINK_FORUM_THREAD,
        self::LINK_ATTENDANCE,
        self::LINK_SURVEY,
        self::LINK_FORUM_PARTICIPATION,
    ];

    public function __construct(
        private CQuizRepository $quizRepository,
        private CStudentPublicationRepository $studentPublicationRepository,
        private CLpRepository $lpRepository,
        private CLpItemRepository $lpItemRepository,
        private CForumThreadRepository $forumThreadRepository,
        private CAttendanceRepository $attendanceRepository,
        private CSurveyRepository $surveyRepository,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function getAvailableTypes(Course $course, ?Session $session): array
    {
        $types = [];

        foreach (self::SUPPORTED_TYPES as $type) {
            $items = $this->getResourceOptions($type, $course, $session);
            $types[] = [
                'type' => $type,
                'label' => $this->getTypeLabel($type),
                'icon' => $this->getTypeIcon($type),
                'items' => $items,
                'available' => [] !== $items,
                'usesParticipationPoints' => self::LINK_FORUM_PARTICIPATION === $type,
            ];
        }

        return $types;
    }

    /**
     * @return list<array{id: int, title: string, description: string}>
     */
    public function getResourceOptions(int $type, Course $course, ?Session $session): array
    {
        $resources = $this->getResources($type, $course, $session);
        $options = [];

        foreach ($resources as $resource) {
            $summary = $this->normalizeResource($type, $resource);
            $options[] = [
                'id' => $summary['id'],
                'title' => $summary['title'],
                'description' => $summary['description'],
            ];
        }

        usort(
            $options,
            static fn (array $left, array $right): int => strnatcasecmp($left['title'], $right['title']),
        );

        return $options;
    }

    public function requireResource(int $type, int $refId, Course $course, ?Session $session): object
    {
        if (!\in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new BadRequestHttpException('Unsupported Gradebook online activity type.');
        }
        if ($refId <= 0) {
            throw new BadRequestHttpException('A valid linked resource id is required.');
        }

        foreach ($this->getResources($type, $course, $session) as $resource) {
            if ($this->getResourceId($resource) === $refId) {
                return $resource;
            }
        }

        throw new AccessDeniedHttpException('The requested resource is not available in the current course and session context.');
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeLink(
        GradebookLink $link,
        Course $course,
        ?Session $session,
        int $groupId,
        bool $canManage,
    ): array {
        $type = (int) $link->getType();
        $refId = (int) $link->getRefId();

        try {
            $resource = $this->requireResource($type, $refId, $course, $session);
            $resourceSummary = $this->normalizeResource($type, $resource);
            $url = $this->buildResourceUrl($type, $resource, $course, $session, $groupId, $canManage);
            $valid = true;
        } catch (AccessDeniedHttpException|BadRequestHttpException|NotFoundHttpException) {
            $resourceSummary = [
                'id' => $refId,
                'title' => $this->getTypeLabel($type).' #'.$refId,
                'description' => '',
            ];
            $url = null;
            $valid = false;
        }

        $weight = self::LINK_FORUM_PARTICIPATION === $type
            ? $this->getForumParticipationWeight($link)
            : (float) $link->getWeight();

        $description = $resourceSummary['description'];
        if (self::LINK_FORUM_PARTICIPATION === $type) {
            $pointsDescription = $this->buildForumParticipationDescription($link);
            if ('' !== $pointsDescription) {
                $description = $pointsDescription;
            }
        }

        return [
            'id' => (int) $link->getId(),
            'kind' => 'link',
            'title' => $resourceSummary['title'],
            'description' => $description,
            'weight' => $weight,
            'visible' => 1 === (int) $link->getVisible(),
            'locked' => 1 === (int) $link->getLocked(),
            'maxScore' => null,
            'minScore' => $link->getMinScore(),
            'score' => null,
            'refId' => $refId,
            'linkType' => $type,
            'linkTypeLabel' => $this->getTypeLabel($type),
            'icon' => $this->getTypeIcon($type),
            'url' => $url,
            'valid' => $valid,
            'pointsOne' => null !== $link->getPointsOne() ? (float) $link->getPointsOne() : null,
            'pointsMany' => null !== $link->getPointsMany() ? (float) $link->getPointsMany() : null,
        ];
    }

    public function getTypeLabel(int $type): string
    {
        return match ($type) {
            self::LINK_EXERCISE => 'Tests',
            self::LINK_DROPBOX => 'Dropbox',
            self::LINK_HOTPOTATOES => 'HotPotatoes',
            self::LINK_STUDENT_PUBLICATION => 'Assignments',
            self::LINK_LEARNING_PATH => 'Learning paths',
            self::LINK_FORUM_THREAD => 'Forum threads',
            self::LINK_ATTENDANCE => 'Attendance',
            self::LINK_SURVEY => 'Survey',
            self::LINK_FORUM_PARTICIPATION => 'Forum participation',
            default => 'Type '.$type,
        };
    }

    public function getTypeIcon(int $type): string
    {
        return match ($type) {
            self::LINK_LEARNING_PATH => 'learning-paths',
            self::LINK_FORUM_THREAD, self::LINK_FORUM_PARTICIPATION => 'add-topic',
            default => 'link',
        };
    }

    /**
     * @return list<object>
     */
    private function getResources(int $type, Course $course, ?Session $session): array
    {
        return match ($type) {
            self::LINK_EXERCISE => $this->quizRepository
                ->findAllByCourse($course, $session, null, 1, false)
                ->getQuery()
                ->getResult(),
            self::LINK_STUDENT_PUBLICATION => $this->studentPublicationRepository
                ->findAllByCourse($course, $session, null, 1, 'folder')
                ->getQuery()
                ->getResult(),
            self::LINK_LEARNING_PATH => $this->lpRepository
                ->getResourcesByCourse($course, $session)
                ->getQuery()
                ->getResult(),
            self::LINK_FORUM_THREAD, self::LINK_FORUM_PARTICIPATION => $this->forumThreadRepository
                ->findAllByCourse($course, $session)
                ->getQuery()
                ->getResult(),
            self::LINK_ATTENDANCE => $this->attendanceRepository->getAttendanceListForCourse($course, $session),
            self::LINK_SURVEY => $this->surveyRepository
                ->getResourcesByCourse($course, $session)
                ->getQuery()
                ->getResult(),
            default => throw new BadRequestHttpException('Unsupported Gradebook online activity type.'),
        };
    }

    /**
     * @return array{id: int, title: string, description: string}
     */
    private function normalizeResource(int $type, object $resource): array
    {
        return match ($type) {
            self::LINK_EXERCISE => $this->normalizeQuiz($resource),
            self::LINK_STUDENT_PUBLICATION => $this->normalizeStudentPublication($resource),
            self::LINK_LEARNING_PATH => $this->normalizeLearningPath($resource),
            self::LINK_FORUM_THREAD => $this->normalizeForumThread($resource, true),
            self::LINK_FORUM_PARTICIPATION => $this->normalizeForumThread($resource, false),
            self::LINK_ATTENDANCE => $this->normalizeAttendance($resource),
            self::LINK_SURVEY => $this->normalizeSurvey($resource),
            default => throw new BadRequestHttpException('Unsupported Gradebook online activity type.'),
        };
    }

    /**
     * @return array{id: int, title: string, description: string}
     */
    private function normalizeQuiz(object $resource): array
    {
        if (!$resource instanceof CQuiz) {
            throw new NotFoundHttpException('The linked test was not found.');
        }

        return [
            'id' => (int) $resource->getIid(),
            'title' => $resource->getTitle(),
            'description' => $this->plainText($resource->getDescription()),
        ];
    }

    /**
     * @return array{id: int, title: string, description: string}
     */
    private function normalizeStudentPublication(object $resource): array
    {
        if (!$resource instanceof CStudentPublication) {
            throw new NotFoundHttpException('The linked assignment was not found.');
        }

        $title = trim($resource->getTitle());

        return [
            'id' => (int) $resource->getIid(),
            'title' => '' !== $title ? $title : 'Untitled',
            'description' => $this->plainText($resource->getDescription()),
        ];
    }

    /**
     * @return array{id: int, title: string, description: string}
     */
    private function normalizeLearningPath(object $resource): array
    {
        if (!$resource instanceof CLp) {
            throw new NotFoundHttpException('The linked learning path was not found.');
        }

        return [
            'id' => (int) $resource->getIid(),
            'title' => $resource->getTitle(),
            'description' => $this->plainText($resource->getDescription()),
        ];
    }

    /**
     * @return array{id: int, title: string, description: string}
     */
    private function normalizeForumThread(object $resource, bool $useQualifiedTitle): array
    {
        if (!$resource instanceof CForumThread) {
            throw new NotFoundHttpException('The linked forum thread was not found.');
        }

        $title = $resource->getTitle();
        if ($useQualifiedTitle && '' !== trim((string) $resource->getThreadTitleQualify())) {
            $title = (string) $resource->getThreadTitleQualify();
        }

        return [
            'id' => (int) $resource->getIid(),
            'title' => $title,
            'description' => '',
        ];
    }

    /**
     * @return array{id: int, title: string, description: string}
     */
    private function normalizeAttendance(object $resource): array
    {
        if (!$resource instanceof CAttendance) {
            throw new NotFoundHttpException('The linked attendance was not found.');
        }

        $qualifiedTitle = trim((string) $resource->getAttendanceQualifyTitle());

        return [
            'id' => (int) $resource->getIid(),
            'title' => '' !== $qualifiedTitle ? $qualifiedTitle : $resource->getTitle(),
            'description' => '',
        ];
    }

    /**
     * @return array{id: int, title: string, description: string}
     */
    private function normalizeSurvey(object $resource): array
    {
        if (!$resource instanceof CSurvey) {
            throw new NotFoundHttpException('The linked survey was not found.');
        }

        $code = trim((string) $resource->getCode());
        $title = $this->plainText($resource->getTitle());

        return [
            'id' => (int) $resource->getIid(),
            'title' => '' !== $code ? $code.': '.$title : $title,
            'description' => $this->plainText($resource->getSubtitle()),
        ];
    }

    private function getResourceId(object $resource): int
    {
        if ($resource instanceof CQuiz
            || $resource instanceof CStudentPublication
            || $resource instanceof CLp
            || $resource instanceof CForumThread
            || $resource instanceof CAttendance
            || $resource instanceof CSurvey
        ) {
            return (int) $resource->getIid();
        }

        return 0;
    }

    private function buildResourceUrl(
        int $type,
        object $resource,
        Course $course,
        ?Session $session,
        int $groupId,
        bool $canManage,
    ): ?string {
        $courseNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);
        if ($courseNodeId <= 0) {
            return null;
        }

        $query = http_build_query([
            'cid' => (int) $course->getId(),
            'sid' => null !== $session ? (int) $session->getId() : 0,
            'gid' => $groupId,
            'gradebook' => 'view',
            'origin' => 'gradebook',
        ]);

        return match ($type) {
            self::LINK_EXERCISE => $resource instanceof CQuiz
                ? $this->buildExerciseUrl($resource, $course, $session, $groupId, $courseNodeId, $query)
                : null,
            self::LINK_STUDENT_PUBLICATION => $resource instanceof CStudentPublication
                && null !== $resource->getResourceNode()
                ? '/resources/assignment/'.(int) $resource->getResourceNode()->getId()
                    .'/submission/'.(int) $resource->getIid().'?'.$query
                : null,
            self::LINK_LEARNING_PATH => $resource instanceof CLp
                ? '/resources/lp/'.$courseNodeId.'/'.(int) $resource->getIid().'/runtime?'.$query
                : null,
            self::LINK_FORUM_THREAD, self::LINK_FORUM_PARTICIPATION => $this->buildForumUrl(
                $resource,
                $courseNodeId,
                $query,
            ),
            self::LINK_ATTENDANCE => $resource instanceof CAttendance
                ? '/resources/attendance/'.$courseNodeId.'/'.(int) $resource->getIid().'/sheet-list?'.$query
                : null,
            self::LINK_SURVEY => $resource instanceof CSurvey
                && $canManage
                && !$this->isSettingEnabled('survey.hide_survey_reporting_button')
                ? '/resources/survey/'.$courseNodeId.'/'.(int) $resource->getIid().'/reporting?'.$query
                : null,
            default => null,
        };
    }

    private function buildExerciseUrl(
        CQuiz $quiz,
        Course $course,
        ?Session $session,
        int $groupId,
        int $courseNodeId,
        string $defaultQuery,
    ): string {
        $exerciseId = (string) $quiz->getIid();
        $items = $this->lpItemRepository->createQueryBuilder('item')
            ->distinct()
            ->join('item.lp', 'lp')
            ->join('lp.resourceNode', 'resourceNode')
            ->join('resourceNode.resourceLinks', 'resourceLink')
            ->andWhere('item.itemType = :itemType')
            ->andWhere('item.path = :exerciseId')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->andWhere('resourceLink.deletedAt IS NULL')
            ->setParameter('itemType', 'quiz', Types::STRING)
            ->setParameter('exerciseId', $exerciseId, Types::STRING)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        /** @var array<int, CLpItem> $matchingLearningPaths */
        $matchingLearningPaths = [];
        foreach ($items as $item) {
            if (!$item instanceof CLpItem) {
                continue;
            }

            $learningPathId = (int) ($item->getLp()->getIid() ?? 0);
            if ($learningPathId > 0 && !isset($matchingLearningPaths[$learningPathId])) {
                $matchingLearningPaths[$learningPathId] = $item;
            }
        }

        if (1 === \count($matchingLearningPaths)) {
            $item = array_values($matchingLearningPaths)[0];
            $learningPathId = (int) ($item->getLp()->getIid() ?? 0);
            $itemId = (int) ($item->getIid() ?? 0);
            $query = [
                'cid' => (int) $course->getId(),
                'sid' => null !== $session ? (int) $session->getId() : 0,
                'gid' => $groupId,
                'gradebook' => 1,
                'origin' => 'gradebook',
                'isStudentView' => 'true',
            ];
            if ($itemId > 0) {
                $query['item_id'] = $itemId;
            }

            return '/resources/lp/'.$courseNodeId.'/'.$learningPathId.'/runtime?'.http_build_query($query);
        }

        return '/resources/exercise/'.$courseNodeId.'/'.(int) $quiz->getIid().'/overview?'.$defaultQuery;
    }

    private function buildForumUrl(object $resource, int $courseNodeId, string $query): ?string
    {
        if (!$resource instanceof CForumThread) {
            return null;
        }

        $forumId = (int) ($resource->getForum()?->getIid() ?? 0);
        $threadId = (int) ($resource->getIid() ?? 0);
        if ($forumId <= 0 || $threadId <= 0) {
            return null;
        }

        return '/resources/forum/'.$courseNodeId.'/forum/'.$forumId.'/thread/'.$threadId.'?'.$query;
    }

    private function buildForumParticipationDescription(GradebookLink $link): string
    {
        if (null === $link->getPointsOne() && null === $link->getPointsMany()) {
            return '';
        }

        $description = 'Points for one message: '.(float) ($link->getPointsOne() ?? 0);
        if (null !== $link->getPointsMany() && (float) $link->getPointsMany() > 0) {
            $description .= ' · Points for two or more messages: '.(float) $link->getPointsMany();
        }

        return $description;
    }

    private function getForumParticipationWeight(GradebookLink $link): float
    {
        $pointsOne = (float) ($link->getPointsOne() ?? 0);
        $pointsMany = (float) ($link->getPointsMany() ?? 0);
        $effectiveMany = $pointsMany > 0 ? $pointsMany : $pointsOne;

        return max($pointsOne, $effectiveMany);
    }

    private function plainText(?string $value): string
    {
        return trim(strip_tags((string) $value));
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }
}
