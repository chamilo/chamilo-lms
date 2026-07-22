<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\AiProvider\AiImageProviderInterface;
use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\AiFeatureAccessHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Service\Ai\AiRequestQuotaGuard;
use Chamilo\CoreBundle\Service\Ai\GeneratedMediaStorageService;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class CreateCourseIllustrationTool
{
    private const MAX_PROMPT_LENGTH = 4_000;

    public function __construct(
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private CourseRelUserRepository $courseRelUserRepository,
        private AiFeatureAccessHelper $aiFeatureAccessHelper,
        private AiProviderFactory $aiProviderFactory,
        private AiRequestQuotaGuard $quotaGuard,
        private GeneratedMediaStorageService $mediaStorage,
        private CDocumentRepository $documentRepository,
        private AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    /**
     * @return array{
     *     created: true,
     *     illustration: array{
     *         document_id: int,
     *         resource_node_id: int,
     *         parent_resource_node_id: int,
     *         title: string,
     *         file_name: string|null,
     *         topic: string,
     *         prompt: string,
     *         provider_used: string,
     *         revised_prompt: string|null,
     *         published: bool,
     *         ai_assisted: true,
     *         content_type: string|null,
     *         size: int|null,
     *         content_url: string
     *     }
     * }
     */
    #[McpTool(
        name: 'create_course_illustration',
        description: 'Generate an AI illustration about a topic and save it as a real file in the Documents tool of a course managed by the authenticated teacher.',
    )]
    public function createCourseIllustration(
        int $courseId,
        string $title,
        string $topic,
        ?string $prompt = null,
        ?string $language = null,
        ?string $provider = null,
        bool $publish = true,
    ): array {
        try {
            return $this->doCreateCourseIllustration(
                $courseId,
                $title,
                $topic,
                $prompt,
                $language,
                $provider,
                $publish,
            );
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (
            InvalidArgumentException
            | RuntimeException
            | AccessDeniedException $exception
        ) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable) {
            throw new ToolCallException(
                'The illustration could not be created because of an unexpected server error. Check the Chamilo log for technical details.'
            );
        }
    }

    /**
     * @return array{
     *     created: true,
     *     illustration: array{
     *         document_id: int,
     *         resource_node_id: int,
     *         parent_resource_node_id: int,
     *         title: string,
     *         file_name: string|null,
     *         topic: string,
     *         prompt: string,
     *         provider_used: string,
     *         revised_prompt: string|null,
     *         published: bool,
     *         ai_assisted: true,
     *         content_type: string|null,
     *         size: int|null,
     *         content_url: string
     *     }
     * }
     */
    private function doCreateCourseIllustration(
        int $courseId,
        string $title,
        string $topic,
        ?string $prompt,
        ?string $language,
        ?string $provider,
        bool $publish,
    ): array {
        if ($courseId <= 0) {
            throw new InvalidArgumentException(
                'The course ID must be a positive integer.'
            );
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedException(
                'An authenticated Chamilo user is required.'
            );
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            throw new RuntimeException(
                'The current Chamilo access URL could not be resolved.'
            );
        }

        $course = $this->courseRelUserRepository
            ->findTeacherCourseForUserAndAccessUrl(
                $user,
                $accessUrl,
                $courseId,
            );

        if (null === $course) {
            throw new AccessDeniedException(
                'The course was not found or is not managed by the authenticated teacher.'
            );
        }

        if (
            !$this->aiFeatureAccessHelper->isFeatureEnabledForCourse(
                'image_generator',
                $courseId,
            )
        ) {
            throw new AccessDeniedException(
                'Image generation is not enabled for this course.'
            );
        }

        $title = trim(strip_tags($title));
        if ('' === $title) {
            throw new InvalidArgumentException(
                'The illustration title is required.'
            );
        }

        if (mb_strlen($title) > 180) {
            throw new InvalidArgumentException(
                'The illustration title cannot be longer than 180 characters.'
            );
        }

        $topic = trim(strip_tags($topic));
        if ('' === $topic) {
            throw new InvalidArgumentException(
                'The illustration topic is required.'
            );
        }

        if (mb_strlen($topic) > 1_000) {
            throw new InvalidArgumentException(
                'The illustration topic cannot be longer than 1000 characters.'
            );
        }

        $prompt = null !== $prompt ? trim($prompt) : '';
        if ('' === $prompt) {
            $prompt = $topic;
        }

        if (mb_strlen($prompt) > self::MAX_PROMPT_LENGTH) {
            throw new InvalidArgumentException(
                'The image prompt cannot be longer than 4000 characters.'
            );
        }

        $language = null !== $language ? trim($language) : '';
        if ('' === $language) {
            $language = $course->getCourseLanguage();
        }

        if (!preg_match('/^[a-zA-Z0-9_-]{1,20}$/', $language)) {
            throw new InvalidArgumentException(
                'The image language code is invalid.'
            );
        }

        $availableProviders = $this->aiProviderFactory
            ->getProvidersForType('image');

        if ([] === $availableProviders) {
            throw new RuntimeException(
                'No AI providers are configured for image generation.'
            );
        }

        $provider = null !== $provider ? trim($provider) : '';
        if ('' === $provider) {
            $provider = (string) $availableProviders[0];
        }

        if (!\in_array($provider, $availableProviders, true)) {
            throw new InvalidArgumentException(
                'The selected AI image provider is not available.'
            );
        }

        $this->quotaGuard->assertCanRequest(
            $user,
            $provider,
            'image',
        );

        $imageProvider = $this->aiProviderFactory
            ->getProvider($provider, 'image');

        if (!$imageProvider instanceof AiImageProviderInterface) {
            throw new RuntimeException(
                'The selected provider does not support image generation.'
            );
        }

        $generatedResult = $imageProvider->generateImage(
            $prompt,
            'document_image_generate',
            [
                'language' => $language,
                'n' => 1,
                'cid' => $courseId,
            ],
        );

        if (null === $generatedResult || [] === $generatedResult) {
            throw new RuntimeException(
                'The AI provider returned an empty image.'
            );
        }

        if (
            \is_string($generatedResult)
            && str_starts_with(
                strtolower(trim($generatedResult)),
                'error:',
            )
        ) {
            throw new RuntimeException(trim(substr($generatedResult, 6)));
        }

        $document = $this->mediaStorage->storeGeneratedImage(
            $course,
            $courseId,
            $title,
            $topic,
            $generatedResult,
            $language,
            $publish,
        );

        $documentId = (int) ($document->getIid() ?? 0);
        $resourceNode = $document->getResourceNode();
        $resourceNodeId = (int) ($resourceNode?->getId() ?? 0);

        if ($documentId <= 0 || $resourceNodeId <= 0) {
            throw new RuntimeException(
                'Chamilo created an incomplete illustration resource.'
            );
        }

        $resourceFile = $resourceNode?->getFirstResourceFile();
        $fileName = $resourceFile instanceof ResourceFile
            ? ($resourceFile->getOriginalName() ?: $resourceFile->getTitle())
            : null;
        $revisedPrompt = \is_array($generatedResult)
            && isset($generatedResult['revised_prompt'])
            && \is_string($generatedResult['revised_prompt'])
                ? trim($generatedResult['revised_prompt'])
                : null;

        $this->aiDisclosureHelper->logAudit(
            targetKey: 'course:'.$courseId.':document_image:'.$documentId,
            userId: (int) $user->getId(),
            meta: [
                'feature' => 'image_generator',
                'mode' => 'generated_and_saved',
                'provider' => $provider,
                'tool' => 'document_image_generate',
                'prompt_hash' => sha1($prompt),
                'document_id' => $documentId,
            ],
            courseId: $courseId,
        );

        return [
            'created' => true,
            'illustration' => [
                'document_id' => $documentId,
                'resource_node_id' => $resourceNodeId,
                'parent_resource_node_id' => (int) ($resourceNode?->getParent()?->getId() ?? 0),
                'title' => $document->getTitle(),
                'file_name' => $fileName,
                'topic' => $topic,
                'prompt' => $prompt,
                'provider_used' => $provider,
                'revised_prompt' => '' !== (string) $revisedPrompt
                    ? $revisedPrompt
                    : null,
                'published' => $publish,
                'ai_assisted' => true,
                'content_type' => $resourceFile instanceof ResourceFile
                    ? $resourceFile->getMimeType()
                    : null,
                'size' => $resourceFile instanceof ResourceFile
                    ? $resourceFile->getSize()
                    : null,
                'content_url' => $this->documentRepository->getResourceFileUrl(
                    $document,
                    ['cid' => $courseId],
                ),
            ],
        ];
    }
}
