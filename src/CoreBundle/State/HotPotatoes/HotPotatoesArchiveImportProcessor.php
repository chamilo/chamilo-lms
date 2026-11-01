<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\HotPotatoes;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\ResourceFileHelper;
use Chamilo\CoreBundle\Mcp\CourseDocumentCreator;
use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesArchiveService;
use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesPackageInspector;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ZipArchive;

use const JSON_THROW_ON_ERROR;
use const PATHINFO_FILENAME;

/** @implements ProcessorInterface<mixed, JsonResponse> */
final readonly class HotPotatoesArchiveImportProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CidReqHelper $cidReqHelper,
        private HotPotatoesArchiveService $archives,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
        private HotPotatoesPackageInspector $packageInspector,
        private CourseDocumentCreator $courseDocumentCreator,
        private ResourceFileHelper $resourceFileHelper,
        private EntityManagerInterface $entityManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        $request = $this->requestStack->getCurrentRequest()
            ?? throw new BadRequestHttpException('The current request is required.');

        if (!str_starts_with(strtolower((string) $request->headers->get('Content-Type', '')), 'multipart/form-data')) {
            throw new BadRequestHttpException('A multipart ZIP upload is required.');
        }

        // The request's cid/sid/gid, not a mutable PHP session, determines this import's scope.
        [$course, $session, $group] = $this->archives->context();
        if (null !== $session && (int) $this->cidReqHelper->getSessionId() !== (int) $session->getId()) {
            throw new BadRequestHttpException('The session context is not initialized for this import.');
        }
        if (null !== $group && (int) $this->cidReqHelper->getGroupId() !== (int) $group->getIid()) {
            throw new BadRequestHttpException('The group context is not initialized for this import.');
        }
        if (!$this->isAllowedToEditHelper->check(course: $course, session: $session)) {
            throw new AccessDeniedHttpException('You are not allowed to import content in this context.');
        }

        $courseNodeId = (int) $course->getResourceNode()?->getId();
        if ($courseNodeId <= 0) {
            throw new BadRequestHttpException('The course has no resource node.');
        }

        $package = $request->files->get('package');
        if (!$package instanceof UploadedFile || !$package->isValid()) {
            throw new BadRequestHttpException('A valid Hot Potatoes ZIP package is required.');
        }

        try {
            $inspection = $this->packageInspector->inspect($package->getPathname(), $package->getClientOriginalName());
        } catch (RuntimeException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        $temporaryZip = null;
        if ('html' === $inspection['format']) {
            $temporaryZip = tempnam(sys_get_temp_dir(), 'chamilo_hp_upload_');
            if (false === $temporaryZip) {
                throw new BadRequestHttpException('The HTML activity cannot be prepared.');
            }
            $zip = new ZipArchive();
            if (true !== $zip->open($temporaryZip, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
                @unlink($temporaryZip);

                throw new BadRequestHttpException('The HTML activity cannot be packaged.');
            }
            $zip->addFile($package->getPathname(), 'index.html');
            $zip->close();
            $original = (string) pathinfo($package->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = substr((string) preg_replace('/[^a-zA-Z0-9_-]+/', '-', $original), 0, 80);
            $package = new UploadedFile($temporaryZip, ($safeName ?: 'hotpotatoes').'.zip', 'application/zip', null, true);

            try {
                $inspection = $this->packageInspector->inspect($temporaryZip, $package->getClientOriginalName());
            } catch (RuntimeException $exception) {
                @unlink($temporaryZip);

                throw new BadRequestHttpException($exception->getMessage(), $exception);
            }
        }

        // Force a private, unextracted archive. Never execute third-party HTML on Chamilo's origin.
        $request->request->set('filetype', 'file');
        $request->request->set('parentResourceNodeId', (string) $courseNodeId);
        $request->request->set('isUncompressZipEnabled', 'false');
        $request->request->set('fileExistsOption', 'rename');
        $request->request->set('resourceLinkList', json_encode([
            ['visibility' => ResourceLink::VISIBILITY_PUBLISHED],
        ], JSON_THROW_ON_ERROR));
        $request->request->set('comment', 'Hot Potatoes archive');
        $request->request->remove('contentFile');
        $request->files->set('uploadFile', $package);

        try {
            $document = $this->courseDocumentCreator->createFromRequest($request, $course);
        } finally {
            if (null !== $temporaryZip) {
                @unlink($temporaryZip);
            }
        }
        $resourceNode = $document->getResourceNode();
        $resourceFile = null === $resourceNode ? null : $this->resourceFileHelper->resolveResourceFileByAccessUrl($resourceNode);
        if (null === $resourceFile) {
            throw new BadRequestHttpException('The imported Hot Potatoes archive has no resource file.');
        }
        $metadata = $resourceFile->getMetadata();
        $metadata['chamilo_hotpotatoes'] = [
            'version' => 1,
            'entrypoints' => $inspection['entrypoints'],
        ];
        $resourceFile->setMetadata($metadata);
        $this->entityManager->flush();

        return new JsonResponse([
            'documentId' => $document->getIid(),
            'resourceNodeId' => $document->getResourceNode()?->getId(),
            'format' => 'zip',
            'entrypoints' => $inspection['entrypoints'],
            'files' => $inspection['files'],
            'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
            'playbackAvailable' => true,
        ], JsonResponse::HTTP_CREATED);
    }
}
