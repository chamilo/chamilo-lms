<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Course;

use Chamilo\CoreBundle\AiProvider\AiCourseAnalyzerService;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Service\Mcp\McpCourseAiFeatureManager;
use Chamilo\CoreBundle\Service\Mcp\McpTextAiService;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CSurvey;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

final readonly class McpCourseQualityReviewer
{
    public function __construct(
        private McpTextAiService $aiService,
        private AiDisclosureHelper $aiDisclosureHelper,
        private McpCourseAiFeatureManager $courseAiFeatureManager,
        private AiCourseAnalyzerService $courseAnalyzer,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function review(
        Course $course,
        User $user,
        ?string $focus,
        ?string $provider,
    ): array {
        $enabledFeatures = $this->courseAiFeatureManager->ensureEnabled(
            $course,
            $user,
            'course_analyser',
            'review_course_quality',
        );

        $providerName = $this->aiService->resolveProvider($user, $provider);
        $teacherPrompt = trim(strip_tags((string) $focus));
        if (mb_strlen($teacherPrompt) > 5000) {
            $teacherPrompt = mb_substr($teacherPrompt, 0, 5000);
        }
        if ('' === $teacherPrompt) {
            $teacherPrompt = 'Review this course as an instructional designer. Separate verified observations from recommendations. Identify the highest-impact improvements for structure, clarity, assessment, learner engagement and feedback.';
        }

        $inventoryByVisibility = [
            'documents' => $this->summarizeResources(CDocument::class, $course, true),
            'tests' => $this->summarizeResources(CQuiz::class, $course),
            'learning_paths' => $this->summarizeResources(CLp::class, $course),
            'surveys' => $this->summarizeResources(CSurvey::class, $course),
            'assignments' => $this->summarizeResources(CStudentPublication::class, $course),
            'forums' => $this->summarizeResources(CForum::class, $course),
            'forum_posts' => $this->summarizeResources(CForumPost::class, $course),
        ];
        $inventory = [
            'documents' => $inventoryByVisibility['documents']['total'],
            'tests' => $inventoryByVisibility['tests']['total'],
            'learning_paths' => $inventoryByVisibility['learning_paths']['total'],
            'surveys' => $inventoryByVisibility['surveys']['total'],
            'assignments' => $inventoryByVisibility['assignments']['total'],
            'forums' => $inventoryByVisibility['forums']['total'],
            'forum_posts' => $inventoryByVisibility['forum_posts']['total'],
            'visible_forum_posts' => $inventoryByVisibility['forum_posts']['published'],
        ];

        $teacherPrompt .= "\n\nVerified base-course inventory for teacher review. Draft and pending resources are intentional work in progress and must still be analyzed:"
            ."\n- Documents: ".$this->formatInventorySummary($inventoryByVisibility['documents'])
            ."\n- Tests: ".$this->formatInventorySummary($inventoryByVisibility['tests'])
            ."\n- Learning paths: ".$this->formatInventorySummary($inventoryByVisibility['learning_paths'])
            ."\n- Surveys: ".$this->formatInventorySummary($inventoryByVisibility['surveys'])
            ."\n- Assignments: ".$this->formatInventorySummary($inventoryByVisibility['assignments'])
            ."\n- Forums: ".$this->formatInventorySummary($inventoryByVisibility['forums'])
            ."\n- Forum posts: ".$this->formatInventorySummary($inventoryByVisibility['forum_posts']);

        $result = $this->courseAnalyzer->analyze(
            course: $course,
            session: null,
            teacherPrompt: $teacherPrompt,
            provider: $providerName,
            includeStandaloneDocuments: true,
            includeStandaloneExercises: true,
            includeDraftResources: true,
        );

        $this->aiDisclosureHelper->logAudit(
            targetKey: 'course:'.(int) $course->getId().':mcp_quality_review',
            userId: (int) $user->getId(),
            meta: [
                'feature' => 'mcp_course_quality_review',
                'provider' => $providerName,
                'inventory' => $inventory,
                'inventory_by_visibility' => $inventoryByVisibility,
                'include_draft_resources' => true,
                'content_analysis' => $result['payloadStats'] ?? [],
                'response_mode' => $result['responseMode'] ?? 'full',
                'response_repaired' => $result['responseRepaired'] ?? false,
            ],
            courseId: (int) $course->getId(),
            sessionId: 0,
        );

        return [
            'scope' => 'base_course',
            'course' => [
                'course_id' => (int) $course->getId(),
                'title' => $course->getTitle(),
            ],
            'provider_used' => $providerName,
            'course_features_enabled' => $enabledFeatures,
            'inventory' => $inventory,
            'inventory_by_visibility' => $inventoryByVisibility,
            'content_analysis' => $result['payloadStats'] ?? [],
            'analysis_generation' => [
                'structured_response' => \is_array($result['structuredResponse']),
                'response_mode' => $result['responseMode'] ?? 'full',
                'response_repaired' => $result['responseRepaired'] ?? false,
                'raw_response_length' => $result['rawResponseLength'] ?? 0,
            ],
            'analysis' => $result['structuredResponse'],
            'raw_analysis' => null === $result['structuredResponse']
                ? mb_substr((string) $result['rawResponse'], 0, 30000)
                : null,
            'analysis_scope' => $result['payload']['analysisScope'] ?? null,
        ];
    }

    /**
     * @param class-string<AbstractResource> $resourceClass
     *
     * @return array{total:int,published:int,pending:int,drafts:int}
     */
    private function summarizeResources(
        string $resourceClass,
        Course $course,
        bool $excludeFolders = false,
    ): array {
        return [
            'total' => $this->countResources($resourceClass, $course, $excludeFolders, null),
            'published' => $this->countResources(
                $resourceClass,
                $course,
                $excludeFolders,
                ResourceLink::VISIBILITY_PUBLISHED,
            ),
            'pending' => $this->countResources(
                $resourceClass,
                $course,
                $excludeFolders,
                ResourceLink::VISIBILITY_PENDING,
            ),
            'drafts' => $this->countResources(
                $resourceClass,
                $course,
                $excludeFolders,
                ResourceLink::VISIBILITY_DRAFT,
            ),
        ];
    }

    /**
     * @param class-string<AbstractResource> $resourceClass
     */
    private function countResources(
        string $resourceClass,
        Course $course,
        bool $excludeFolders,
        ?int $visibility,
    ): int {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT resource.iid)')
            ->from($resourceClass, 'resource')
            ->innerJoin('resource.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'resourceLink')
            ->andWhere('resourceLink.course = :courseId')
            ->andWhere('resourceLink.session IS NULL')
            ->andWhere('resourceLink.group IS NULL')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;

        if (null !== $visibility) {
            $queryBuilder
                ->andWhere('resourceLink.visibility = :visibility')
                ->setParameter('visibility', $visibility, Types::INTEGER)
            ;
        } else {
            $queryBuilder
                ->andWhere('resourceLink.visibility IN (:reviewVisibilities)')
                ->setParameter('reviewVisibilities', [
                    ResourceLink::VISIBILITY_DRAFT,
                    ResourceLink::VISIBILITY_PENDING,
                    ResourceLink::VISIBILITY_PUBLISHED,
                ], ArrayParameterType::INTEGER)
            ;
        }

        if ($excludeFolders && CDocument::class === $resourceClass) {
            $queryBuilder->andWhere('resource.filetype != :folderType')
                ->setParameter('folderType', 'folder', Types::STRING)
            ;
        }

        if (CStudentPublication::class === $resourceClass) {
            $queryBuilder->andWhere('resource.publicationParent IS NULL');
        }

        if (CForumPost::class === $resourceClass) {
            $queryBuilder->andWhere('resource.visible = :visible')
                ->setParameter('visible', true, Types::BOOLEAN)
            ;
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array{total:int,published:int,pending:int,drafts:int} $summary
     */
    private function formatInventorySummary(array $summary): string
    {
        return \sprintf(
            '%d total (%d published, %d pending, %d drafts)',
            $summary['total'],
            $summary['published'],
            $summary['pending'],
            $summary['drafts'],
        );
    }
}
