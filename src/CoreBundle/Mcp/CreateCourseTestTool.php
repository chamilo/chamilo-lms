<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Service\Exercise\AiCourseTestGenerator;
use Chamilo\CoreBundle\Service\Mcp\McpCourseAiFeatureManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class CreateCourseTestTool
{
    private const MAX_TITLE_LENGTH = 255;
    private const MAX_TOPIC_LENGTH = 20_000;

    public function __construct(
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private CourseRelUserRepository $courseRelUserRepository,
        private CDocumentRepository $documentRepository,
        private AiCourseTestGenerator $testGenerator,
        private McpCourseAiFeatureManager $courseAiFeatureManager,
    ) {}

    /**
     * @return array{
     *     created: true,
     *     test: array{
     *         quiz_id: int,
     *         resource_node_id: int,
     *         title: string,
     *         question_count: int,
     *         question_type: 'unique_answer',
     *         total_score: float,
     *         published: bool,
     *         provider_used: string,
     *         ai_assisted: true,
     *         source: array{type: 'topic'|'document', document_id: int|null, title: string},
     *         questions: list<array{question_id: int, title: string, score: float}>,
     *         content_url: string
     *     }
     * }
     */
    #[McpTool(
        name: 'create_course_test',
        description: 'Create an AI-assisted test with single-answer multiple-choice questions in a course managed by the authenticated teacher. Use exactly one source: a detailed topic description or an editable HTML document from the same course. Tests are drafts unless publish is true.',
    )]
    public function createCourseTest(
        int $courseId,
        string $title,
        int $questionCount,
        ?string $topicDescription = null,
        ?int $documentId = null,
        ?string $language = null,
        ?string $provider = null,
        bool $publish = false,
    ): array {
        try {
            $result = $this->doCreateCourseTest(
                $courseId,
                $title,
                $questionCount,
                $topicDescription,
                $documentId,
                $language,
                $provider,
                $publish,
            );

            return [
                'created' => true,
                'course_features_enabled' => $result['course_features_enabled'],
                'test' => $result['test'],
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The course test could not be created because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{
     *     quiz_id: int,
     *     resource_node_id: int,
     *     title: string,
     *     question_count: int,
     *     question_type: 'unique_answer',
     *     total_score: float,
     *     published: bool,
     *     provider_used: string,
     *     ai_assisted: true,
     *     source: array{type: 'topic'|'document', document_id: int|null, title: string},
     *     questions: list<array{question_id: int, title: string, score: float}>,
     *     content_url: string
     * }
     */
    private function doCreateCourseTest(
        int $courseId,
        string $title,
        int $questionCount,
        ?string $topicDescription,
        ?int $documentId,
        ?string $language,
        ?string $provider,
        bool $publish,
    ): array {
        if ($courseId <= 0) {
            throw new InvalidArgumentException('The course ID must be a positive integer.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedException('An authenticated Chamilo user is required.');
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            throw new RuntimeException('The current Chamilo access URL could not be resolved.');
        }

        $course = $this->courseRelUserRepository->findTeacherCourseForUserAndAccessUrl(
            $user,
            $accessUrl,
            $courseId,
        );
        if (null === $course) {
            throw new AccessDeniedException('The course was not found or is not managed by the authenticated teacher.');
        }

        $enabledFeatures = $this->courseAiFeatureManager->ensureEnabled(
            $course,
            $user,
            'exercise_generator',
            'create_course_test',
        );

        $title = trim(strip_tags($title));
        if ('' === $title) {
            throw new InvalidArgumentException('The test title is required.');
        }
        if (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            throw new InvalidArgumentException('The test title cannot be longer than 255 characters.');
        }

        $topicDescription = null !== $topicDescription ? trim($topicDescription) : '';
        $documentId = null !== $documentId ? $documentId : 0;
        $hasTopic = '' !== $topicDescription;
        $hasDocument = $documentId > 0;

        if ($hasTopic === $hasDocument) {
            throw new InvalidArgumentException('Provide exactly one test source: topicDescription or documentId.');
        }

        if ($hasTopic) {
            if (mb_strlen($topicDescription) > self::MAX_TOPIC_LENGTH) {
                throw new InvalidArgumentException('The topic description cannot be longer than 20000 characters.');
            }

            return [
                'course_features_enabled' => $enabledFeatures,
                'test' => $this->testGenerator->createTest(
                    $course,
                    $user,
                    $title,
                    $questionCount,
                    'topic',
                    $title,
                    $topicDescription,
                    null,
                    $language,
                    $provider,
                    $publish,
                ),
            ];
        }

        $document = $this->documentRepository->find($documentId);
        if (!$document instanceof CDocument) {
            throw new InvalidArgumentException('The source document was not found.');
        }

        $documentLink = $document->getResourceNode()?->getResourceLinkByContext($course, null, null);
        if (!$documentLink instanceof ResourceLink) {
            throw new AccessDeniedException('The source document does not belong to the selected course.');
        }

        if (!$document->getResourceNode()?->hasEditableTextContent()) {
            throw new InvalidArgumentException('The source document must be an editable HTML document.');
        }

        $sourceText = $this->testGenerator->getDocumentSource($document);

        return [
            'course_features_enabled' => $enabledFeatures,
            'test' => $this->testGenerator->createTest(
                $course,
                $user,
                $title,
                $questionCount,
                'document',
                trim(strip_tags((string) $document->getTitle())),
                $sourceText,
                $documentId,
                $language,
                $provider,
                $publish,
            ),
        ];
    }
}
