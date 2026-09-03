<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Controller\Api\BaseResourceFileAction;
use Chamilo\CoreBundle\Dto\ResourceFileInput;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * Creates a course document. CreateDocumentFileAction is the HTTP face of this;
 * callers that are not a request (the MCP tools, the AI media storage service) go
 * through create() with a typed CourseDocumentInput instead of fabricating one.
 *
 * It extends BaseResourceFileAction for the file-handling helpers, the way the API
 * actions do -- FileManagerController is the precedent for reaching them from
 * outside API Platform.
 *
 * Neither entry point fabricates a Request: create() hands handleCreateFile() a
 * ResourceFileInput, and createFromRequest() passes the real request through. The
 * zip upload is the one thing only the request path offers, because unpacking an
 * archive into a course is a browser feature and no in-process caller wants it.
 *
 * It sits under Mcp/ although POST /api/documents goes through it too, so do not
 * read the namespace as "only reachable from MCP": CreateDocumentFileAction and
 * GeneratedMediaStorageService are consumers as well.
 */
class CourseDocumentCreator extends BaseResourceFileAction
{
    /**
     * Hosts a "link" document may point at. Anything else is refused: the URL is
     * rendered as a link in the course, so it is stored user input.
     */
    private const array ALLOWED_CLOUD_LINK_HOSTS = [
        'asuswebstorage.com',
        'box.com',
        'dropbox.com',
        'dropboxusercontent.com',
        'docs.google.com',
        'drive.google.com',
        'fileserve.com',
        'icloud.com',
        'livefilestore.com',
        'mediafire.com',
        'mega.nz',
        'onedrive.live.com',
        'scribd.com',
        'slideshare.net',
        'sharepoint.com',
        'wetransfer.com',
    ];

    public function __construct(
        private readonly CDocumentRepository $documentRepository,
        private readonly EntityManager $entityManager,
        private readonly KernelInterface $kernel,
        private readonly TranslatorInterface $translator,
        private readonly CourseRepository $courseRepository,
        private readonly CourseHelper $courseHelper,
        private readonly CidReqHelper $cidReqHelper,
        private readonly AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    /**
     * The typed entry point, for in-process callers.
     *
     * $course is what those callers have and a session does not give them; it
     * binds the resource link and gates the parent node. Session and group are
     * deliberately not part of it -- they only ever create base-course content.
     */
    public function create(CourseDocumentInput $input, Course $course): CDocument
    {
        $document = new CDocument();

        $result = $this->handleCreateFile(
            $document,
            $this->documentRepository,
            new ResourceFileInput(
                filetype: $input->filetype,
                parentResourceNodeId: $input->parentResourceNodeId,
                title: $input->title,
                comment: $input->comment,
                resourceLinkList: $this->buildResourceLinkListFromContext(
                    $this->cidReqHelper,
                    [['visibility' => $input->visibility]],
                    ResourceLink::VISIBILITY_PUBLISHED,
                    $course
                ),
                uploadFile: $input->uploadFile,
                contentFile: $input->contentFile,
                contentFileExtension: $input->contentFileExtension ?? 'html',
                contentFileMimeType: $input->contentFileMimeType ?? 'text/html',
                language: $input->language,
            ),
            $this->entityManager,
            $this->cidReqHelper,
            $input->fileExistsOption,
            $this->translator,
            $this->courseRepository,
            $this->courseHelper,
            $course
        );

        return $this->finish($document, $result, $input->language, $input->aiAssisted, $course);
    }

    /**
     * The request entry point, for CreateDocumentFileAction.
     *
     * $course stays null here: CidReqListener already resolved the context that
     * gated the operation, and that context -- course, session and group -- is the
     * single source of truth for the link binding.
     */
    public function createFromRequest(Request $request, ?Course $course = null): CDocument
    {
        $resourceLinkList = $this->buildResourceLinkListFromContext(
            $this->cidReqHelper,
            $this->extractResourceLinkListFromRequest($request),
            ResourceLink::VISIBILITY_PUBLISHED,
            $course
        );

        $isUncompressZipEnabled = 'true' === (string) $request->request->get('isUncompressZipEnabled', 'false');
        $fileExistsOption = (string) $request->request->get('fileExistsOption', 'rename');
        $aiAssistedRaw = strtolower(trim((string) $request->request->get('ai_assisted', '')));
        $isAiAssisted = \in_array($aiAssistedRaw, ['1', 'true', 'yes', 'on'], true);

        $document = new CDocument();

        if ($isUncompressZipEnabled) {
            $result = $this->handleCreateFileRequestUncompress(
                $document,
                $request,
                $this->entityManager,
                $this->kernel,
                $this->cidReqHelper,
                $this->courseRepository,
                $this->documentRepository,
                $this->courseHelper,
                $resourceLinkList,
                $course
            );
        } else {
            $result = $this->handleCreateFileRequest(
                $document,
                $this->documentRepository,
                $request,
                $this->entityManager,
                $this->cidReqHelper,
                $fileExistsOption,
                $this->translator,
                $this->courseRepository,
                $this->courseHelper,
                $resourceLinkList,
                $course
            );
        }

        $document = $this->titleFiletypeAndComment($document, $result);

        // We need the iid to write ExtraFieldValue, so we persist+flush here.
        $this->entityManager->persist($document);
        $this->entityManager->flush();

        $this->applyResourceLanguageFromRequest($document, $request, $this->entityManager);
        $this->entityManager->flush();

        $this->markAiAssisted($document, $isAiAssisted);

        return $document;
    }

    /**
     * The tail both entry points share, with the language applied from the typed
     * input rather than from a request.
     *
     * @param array<string, string> $result
     */
    private function finish(
        CDocument $document,
        array $result,
        ?string $language,
        bool $isAiAssisted,
        Course $course
    ): CDocument {
        $document = $this->titleFiletypeAndComment($document, $result);

        // We need the iid to write ExtraFieldValue, so we persist+flush here.
        $this->entityManager->persist($document);
        $this->entityManager->flush();

        $this->applyResourceLanguageCode($document, $language, $this->entityManager, $course);
        $this->entityManager->flush();

        $this->markAiAssisted($document, $isAiAssisted);

        return $document;
    }

    /**
     * @param array<string, string> $result
     */
    private function titleFiletypeAndComment(CDocument $document, array $result): CDocument
    {
        $filetype = (string) ($result['filetype'] ?? 'file');
        $comment = (string) ($result['comment'] ?? '');

        if ('link' === $filetype) {
            $comment = $this->normalizeCloudLinkUrl($comment);
        }

        $document->setTitle($result['title'] ?? $document->getResourceName());
        $document->setFiletype($filetype);
        $document->setComment($comment);

        return $document;
    }

    /**
     * Marks ExtraField: type=document, variable=ai_assisted, item_id=document iid.
     */
    private function markAiAssisted(CDocument $document, bool $isAiAssisted): void
    {
        if (!$isAiAssisted || !$this->aiDisclosureHelper->isDisclosureEnabled()) {
            return;
        }

        try {
            $documentId = (int) ($document->getIid() ?? 0);
            if ($documentId > 0) {
                $this->aiDisclosureHelper->markAiAssistedExtraField('document', $documentId, true);
            }
        } catch (Throwable) {
            // Never block the upload flow because of AI marking.
        }
    }

    private function normalizeCloudLinkUrl(string $url): string
    {
        $url = trim($url);

        if ('' === $url) {
            throw new BadRequestHttpException($this->translator->trans('The URL is required.'));
        }

        $parts = parse_url($url);

        if (!\is_array($parts)) {
            throw new BadRequestHttpException($this->translator->trans('Invalid URL.'));
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!\in_array($scheme, ['http', 'https'], true)) {
            throw new BadRequestHttpException($this->translator->trans('Only HTTP and HTTPS URLs are allowed.'));
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ('' === $host) {
            throw new BadRequestHttpException($this->translator->trans('Invalid URL host.'));
        }

        if (!$this->isAllowedCloudLinkHost($host)) {
            throw new BadRequestHttpException($this->translator->trans('This cloud provider is not allowed.'));
        }

        return $url;
    }

    private function isAllowedCloudLinkHost(string $host): bool
    {
        foreach (self::ALLOWED_CLOUD_LINK_HOSTS as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.'.$allowedHost)) {
                return true;
            }
        }

        return false;
    }
}
