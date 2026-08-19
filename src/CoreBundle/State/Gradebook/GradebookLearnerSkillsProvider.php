<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookLearnerSkills;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\SkillRelItem;
use Chamilo\CoreBundle\Entity\SkillRelItemRelUser;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CSurvey;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<GradebookLearnerSkills>
 */
final readonly class GradebookLearnerSkillsProvider implements ProviderInterface
{
    private const SKILL_ITEM_TYPE_EXERCISE = 1;
    private const SKILL_ITEM_TYPE_LINK = 3;
    private const SKILL_ITEM_TYPE_LEARNING_PATH = 4;
    private const SKILL_ITEM_TYPE_GRADEBOOK = 5;
    private const SKILL_ITEM_TYPE_STUDENT_PUBLICATION = 6;
    private const SKILL_ITEM_TYPE_ATTENDANCE = 8;
    private const SKILL_ITEM_TYPE_SURVEY = 9;
    private const SKILL_ITEM_TYPE_FORUM_THREAD = 10;

    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private EntityManagerInterface $entityManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookLearnerSkills
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }
        if (!$this->contextResolver->isSettingEnabled('skill.allow_skill_rel_items')) {
            throw new AccessDeniedHttpException('Gradebook skill-item validation is disabled.');
        }

        $resolved = $this->contextResolver->resolve($request, true);
        if (!$resolved['rootCategory'] instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $userId = $request->query->getInt('userId');
        $learner = $this->contextResolver->getStudentInContext($userId, $resolved['course'], $resolved['session']);
        $courseId = (int) $resolved['course']->getId();
        $sessionId = (int) ($resolved['session']?->getId() ?? 0);
        $relations = $this->entityManager->getRepository(SkillRelItem::class)->findBy(
            ['courseId' => $courseId, 'sessionId' => $sessionId],
            ['id' => 'ASC'],
        );
        $issued = $this->entityManager->getRepository(SkillRelUser::class)->findBy([
            'user' => $learner,
            'course' => $resolved['course'],
            'session' => $resolved['session'],
        ]);
        $issuedSkillIds = [];
        foreach ($issued as $issue) {
            if ($issue instanceof SkillRelUser && null !== $issue->getSkill()?->getId()) {
                $issuedSkillIds[(int) $issue->getSkill()->getId()] = true;
            }
        }

        $grouped = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof SkillRelItem || null === $relation->getSkill()->getId()) {
                continue;
            }
            $skill = $relation->getSkill();
            $skillId = (int) $skill->getId();
            if (!isset($grouped[$skillId])) {
                $grouped[$skillId] = [
                    'id' => $skillId,
                    'title' => trim((string) $skill->getTitle()),
                    'description' => trim((string) $skill->getDescription()),
                    'acquired' => isset($issuedSkillIds[$skillId]),
                    'items' => [],
                ];
            }

            $validation = $this->entityManager->getRepository(SkillRelItemRelUser::class)->findOneBy([
                'user' => $learner,
                'skillRelItem' => $relation,
            ]);
            $validated = $validation instanceof SkillRelItemRelUser;
            $resultId = $validated ? (int) $validation->getResultId() : 0;
            $grouped[$skillId]['items'][] = $this->normalizeItem(
                $relation,
                $validated,
                $resultId,
                $courseId,
                $sessionId,
                $resolved['groupId'],
                $request->query->getInt('node'),
            );
        }

        $resource = new GradebookLearnerSkills();
        $resource->context = [
            'cid' => $courseId,
            'sid' => $sessionId,
            'gid' => $resolved['groupId'],
            'node' => $request->query->getInt('node'),
        ];
        $resource->learner = [
            'id' => (int) $learner->getId(),
            'fullName' => $learner->getFullName(),
            'username' => $learner->getUsername(),
        ];
        $resource->skills = array_values($grouped);
        $resource->csrfToken = (string) $this->csrfTokenManager->getToken(
            GradebookLearnerSkillActionProcessor::CSRF_TOKEN_ID,
        );

        return $resource;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeItem(
        SkillRelItem $relation,
        bool $validated,
        int $resultId,
        int $courseId,
        int $sessionId,
        int $groupId,
        int $courseNodeId,
    ): array {
        $itemId = (int) $relation->getItemId();
        $itemType = (int) $relation->getItemType();
        $title = 'Activity #'.$itemId;
        $url = '';
        $spa = false;

        $query = http_build_query([
            'cid' => $courseId,
            'sid' => $sessionId,
            'gid' => $groupId,
        ]);

        switch ($itemType) {
            case self::SKILL_ITEM_TYPE_EXERCISE:
                $quiz = $this->entityManager->getRepository(CQuiz::class)->find($itemId);
                if ($quiz instanceof CQuiz) {
                    $title = trim((string) $quiz->getTitle());
                    $spa = true;
                    $url = $resultId > 0
                        ? '/resources/exercise/'.$courseNodeId.'/'.$itemId.'/result/'.$resultId.'?'.$query
                        : '/resources/exercise/'.$courseNodeId.'/'.$itemId.'/report?'.$query;
                }

                break;

            case self::SKILL_ITEM_TYPE_STUDENT_PUBLICATION:
                $publication = $this->entityManager->getRepository(CStudentPublication::class)->find($itemId);
                if ($publication instanceof CStudentPublication && null !== $publication->getResourceNode()?->getId()) {
                    $title = trim((string) $publication->getTitle());
                    $spa = true;
                    $url = $resultId > 0
                        ? '/resources/assignment/'.(int) $publication->getResourceNode()->getId().'/submission/'.$resultId.'?'.$query
                        : '/resources/assignment/'.(int) $publication->getResourceNode()->getId().'/?'.$query;
                }

                break;

            case self::SKILL_ITEM_TYPE_LEARNING_PATH:
                $learningPath = $this->entityManager->getRepository(CLp::class)->find($itemId);
                if ($learningPath instanceof CLp) {
                    $title = trim((string) $learningPath->getTitle());
                    $spa = true;
                    $url = '/resources/lp/'.$courseNodeId.'/'.$itemId.'/reporting?'.$query;
                }

                break;

            case self::SKILL_ITEM_TYPE_ATTENDANCE:
                $attendance = $this->entityManager->getRepository(CAttendance::class)->find($itemId);
                if ($attendance instanceof CAttendance) {
                    $title = trim((string) $attendance->getTitle());
                    $spa = true;
                    $url = '/resources/attendance/'.$courseNodeId.'/'.$itemId.'/sheet-list?'.$query;
                }

                break;

            case self::SKILL_ITEM_TYPE_SURVEY:
                $survey = $this->entityManager->getRepository(CSurvey::class)->find($itemId);
                if ($survey instanceof CSurvey) {
                    $title = trim(strip_tags((string) $survey->getTitle()));
                    $spa = true;
                    $url = '/resources/survey/'.$courseNodeId.'/'.$itemId.'/reporting?'.$query;
                }

                break;

            case self::SKILL_ITEM_TYPE_FORUM_THREAD:
                $thread = $this->entityManager->getRepository(CForumThread::class)->find($itemId);
                $forumId = (int) ($thread?->getForum()?->getIid() ?? 0);
                if ($thread instanceof CForumThread && $forumId > 0) {
                    $title = trim((string) $thread->getTitle());
                    $spa = true;
                    $url = '/resources/forum/'.$courseNodeId.'/forum/'.$forumId.'/thread/'.$itemId.'?'.$query;
                }

                break;

            case self::SKILL_ITEM_TYPE_LINK:
                $title = 'Link #'.$itemId;

                break;

            case self::SKILL_ITEM_TYPE_GRADEBOOK:
                $title = 'Assessment #'.$itemId;

                break;
        }

        return [
            'id' => (int) $relation->getId(),
            'itemId' => $itemId,
            'itemType' => $itemType,
            'title' => '' !== $title ? $title : 'Activity #'.$itemId,
            'validated' => $validated,
            'url' => $url,
            'spa' => $spa,
        ];
    }
}
