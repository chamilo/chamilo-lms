<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Chamilo\CoreBundle\Service\Document\DocumentParagraphMediaEmbedder;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

use const PATHINFO_EXTENSION;

final readonly class IllustrateDocumentParagraphTool
{
    public function __construct(
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private CourseRelUserRepository $courseRelUserRepository,
        private CDocumentRepository $documentRepository,
        private DocumentParagraphMediaEmbedder $mediaEmbedder,
        private CourseDocumentContentService $documentContentService,
    ) {}

    /**
     * @return array{
     *     updated: bool,
     *     already_present: bool,
     *     document: array{
     *         document_id: int,
     *         title: string,
     *         paragraph_number: int,
     *         paragraphs_total: int,
     *         paragraph_preview: string,
     *         content_url: string
     *     },
     *     media: array{
     *         document_id: int,
     *         title: string,
     *         type: 'image'|'video',
     *         content_url: string
     *     },
     *     placement: 'before'|'after'
     * }
     */
    #[McpTool(
        name: 'illustrate_document_paragraph',
        description: 'Illustrate a specific section or paragraph of an editable HTML document by inserting an existing image or video from the same course Documents tool. Identify both the target document and media by ID or exact title. Use paragraphNumber or a distinctive paragraphText excerpt, and choose before or after placement.',
    )]
    public function illustrateDocumentParagraph(
        int $courseId,
        ?int $documentId = null,
        ?int $mediaDocumentId = null,
        ?string $documentTitle = null,
        ?string $mediaTitle = null,
        ?int $paragraphNumber = null,
        ?string $paragraphText = null,
        ?string $altText = null,
        ?string $caption = null,
        string $placement = 'after',
    ): array {
        try {
            return $this->doIllustrateDocumentParagraph(
                $courseId,
                $documentId,
                $mediaDocumentId,
                $documentTitle,
                $mediaTitle,
                $paragraphNumber,
                $paragraphText,
                $altText,
                $caption,
                $placement,
            );
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The paragraph could not be illustrated because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{
     *     updated: bool,
     *     already_present: bool,
     *     document: array{
     *         document_id: int,
     *         title: string,
     *         paragraph_number: int,
     *         paragraphs_total: int,
     *         paragraph_preview: string,
     *         content_url: string
     *     },
     *     media: array{
     *         document_id: int,
     *         title: string,
     *         type: 'image'|'video',
     *         content_url: string
     *     },
     *     placement: 'before'|'after'
     * }
     */
    private function doIllustrateDocumentParagraph(
        int $courseId,
        ?int $documentId,
        ?int $mediaDocumentId,
        ?string $documentTitle,
        ?string $mediaTitle,
        ?int $paragraphNumber,
        ?string $paragraphText,
        ?string $altText,
        ?string $caption,
        string $placement,
    ): array {
        if ($courseId <= 0) {
            throw new InvalidArgumentException('The course ID must be a positive integer.');
        }

        $placement = strtolower(trim($placement));
        if (!\in_array($placement, ['before', 'after'], true)) {
            throw new InvalidArgumentException('The placement must be "before" or "after".');
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

        $document = $this->documentContentService->resolveDocument(
            $course,
            $documentId,
            $documentTitle,
        );
        $mediaDocument = $this->documentContentService->resolveDocument(
            $course,
            $mediaDocumentId,
            $mediaTitle,
        );

        if ((int) $document->getIid() === (int) $mediaDocument->getIid()) {
            throw new InvalidArgumentException('The target document and media document must be different.');
        }
        $this->assertEditableHtmlDocument($document);
        $mediaType = $this->assertImageOrVideoDocument($mediaDocument);

        $result = $this->mediaEmbedder->embed(
            $document,
            $mediaDocument,
            $courseId,
            (int) $user->getId(),
            $paragraphNumber,
            $paragraphText,
            $altText,
            $caption,
            $placement,
        );

        return [
            'updated' => $result['updated'],
            'already_present' => $result['already_present'],
            'document' => [
                'document_id' => (int) $document->getIid(),
                'title' => $document->getTitle(),
                'paragraph_number' => $result['paragraph_number'],
                'paragraphs_total' => $result['paragraphs_total'],
                'paragraph_preview' => $result['paragraph_preview'],
                'content_url' => $this->documentRepository
                    ->getResourceFileUrl(
                        $document,
                        ['cid' => $courseId],
                    ),
            ],
            'media' => [
                'document_id' => (int) $mediaDocument->getIid(),
                'title' => $mediaDocument->getTitle(),
                'type' => $mediaType,
                'content_url' => $result['media_url'],
            ],
            'placement' => $placement,
        ];
    }

    private function assertEditableHtmlDocument(
        CDocument $document,
    ): void {
        if ('file' !== $document->getFiletype()) {
            throw new InvalidArgumentException('The target document must be a file.');
        }

        if ($document->getReadonly()) {
            throw new AccessDeniedException('The target document is read-only.');
        }

        $resourceFile = $document->getResourceNode()?->getFirstResourceFile();

        if (null === $resourceFile) {
            throw new InvalidArgumentException('The target document has no stored file.');
        }

        $mimeType = strtolower(trim((string) $resourceFile->getMimeType()));
        $fileName = strtolower(
            (string) (
                $resourceFile->getOriginalName()
                ?: $resourceFile->getTitle()
            )
        );
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if (
            'text/html' !== $mimeType
            && !\in_array($extension, ['html', 'htm'], true)
        ) {
            throw new InvalidArgumentException('The target document must be an editable HTML file.');
        }
    }

    /**
     * @return 'image'|'video'
     */
    private function assertImageOrVideoDocument(
        CDocument $document,
    ): string {
        if ('file' !== $document->getFiletype()) {
            throw new InvalidArgumentException('The media document must be a file.');
        }

        $resourceFile = $document->getResourceNode()?->getFirstResourceFile();

        if (null === $resourceFile) {
            throw new InvalidArgumentException('The media document has no stored file.');
        }

        $mimeType = strtolower(trim((string) $resourceFile->getMimeType()));

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        throw new InvalidArgumentException('The media document must contain an image or video file.');
    }
}
