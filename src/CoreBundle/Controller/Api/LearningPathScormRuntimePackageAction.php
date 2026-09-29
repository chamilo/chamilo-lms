<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Service\LearningPath\ScormRuntimeManager;
use Chamilo\CoreBundle\State\LearningPath\LearningPathRuntimeProvider;
use Chamilo\CoreBundle\State\LearningPath\LearningPathStateHelperTrait;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const PATHINFO_EXTENSION;
use const PHP_SESSION_ACTIVE;

#[AsController]
final readonly class LearningPathScormRuntimePackageAction
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private AssetRepository $assetRepository,
        private ResourceNodeRepository $resourceNodeRepository,
        private CDocumentRepository $documentRepository,
        private CLpRepository $learningPathRepository,
        private CLpItemRepository $learningPathItemRepository,
        private LearningPathRuntimeProvider $runtimeProvider,
        private ScormRuntimeManager $runtimeManager,
        private CidReqHelper $cidReqHelper,
    ) {}

    public function __invoke(int $lpId, Request $request): Response
    {
        $itemId = $request->query->getInt('itemId');
        if ($lpId <= 0 || $itemId <= 0) {
            throw new BadRequestHttpException('A valid SCORM learning path item is required.');
        }

        $learningPath = $this->learningPathRepository->find($lpId);
        $item = $this->learningPathItemRepository->find($itemId);
        if (!$learningPath instanceof CLp
            || !$item instanceof CLpItem
            || (int) $item->getLp()->getIid() !== $lpId
            || !$this->runtimeManager->isScormLearningPath($learningPath)
            || !$this->runtimeManager->isScormPackageItem($item)
        ) {
            throw new NotFoundHttpException('SCORM runtime package not found.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $group = $this->getContextGroup($this->entityManager, $this->cidReqHelper, $course);
        $resourceNode = $learningPath->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('VIEW', $resourceNode)) {
            throw new AccessDeniedHttpException('The SCORM learning path is not available.');
        }

        $resourceLink = $this->getContextResourceLink($learningPath, $course, $session, $group);
        if (!$resourceLink instanceof ResourceLink) {
            throw new AccessDeniedHttpException('The SCORM learning path is not linked to this context.');
        }
        if (!$this->canManageLearningPaths($this->security)
            && ResourceLink::VISIBILITY_PUBLISHED !== $resourceLink->getVisibility()
        ) {
            throw new AccessDeniedHttpException('The SCORM learning path is not visible.');
        }

        $runtime = $this->runtimeProvider->provide(
            new Get(),
            ['lpId' => $lpId],
            ['runtime_item_id' => $itemId],
        );
        if (!$runtime->runtimeSupported
            || $runtime->currentItemId !== $itemId
            || '' === (string) ($runtime->scorm['packageEntryPath'] ?? '')
        ) {
            throw new AccessDeniedHttpException('The SCORM item is not available.');
        }

        $source = $this->resolvePackageSource($learningPath, $course);
        $fileSize = $source['filesystem']->fileSize($source['path']);
        $stream = $source['filesystem']->readStream($source['path']);
        if (!\is_resource($stream)) {
            throw new NotFoundHttpException('The SCORM ZIP package could not be read.');
        }

        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $response = new StreamedResponse(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        });
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $source['downloadName'],
                $this->asciiFallbackName($source['downloadName']),
            ),
        );
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Length', (string) $fileSize);
        $response->headers->set(
            'X-Chamilo-Scorm-Fingerprint',
            (string) ($runtime->scorm['packageFingerprint'] ?? ''),
        );
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    /**
     * @return array{filesystem: FilesystemOperator, path: string, downloadName: string}
     */
    private function resolvePackageSource(CLp $learningPath, Course $course): array
    {
        $asset = $learningPath->getAsset();
        if ($asset instanceof Asset) {
            $assetPath = trim((string) $this->assetRepository->getStorage()->resolveUri($asset));
            $assetFilesystem = $this->assetRepository->getFileSystem();
            if ('' !== $assetPath && $assetFilesystem->fileExists($assetPath)) {
                return [
                    'filesystem' => $assetFilesystem,
                    'path' => $assetPath,
                    'downloadName' => $this->downloadName($learningPath, $asset, null),
                ];
            }
        }

        $document = $this->documentRepository->findScormZipDocument($course, $learningPath);
        if ($document instanceof CDocument) {
            $resourceFile = $document->getResourceNode()?->getFirstResourceFile();
            if ($resourceFile instanceof ResourceFile) {
                $resourcePath = trim((string) $this->resourceNodeRepository->getFilename($resourceFile));
                $resourceFilesystem = $this->resourceNodeRepository->getFileSystem();
                if ('' !== $resourcePath && $resourceFilesystem->fileExists($resourcePath)) {
                    return [
                        'filesystem' => $resourceFilesystem,
                        'path' => $resourcePath,
                        'downloadName' => $this->downloadName($learningPath, $asset, $resourceFile),
                    ];
                }
            }
        }

        throw new NotFoundHttpException('The original SCORM ZIP package cannot be found.');
    }

    private function downloadName(CLp $learningPath, ?Asset $asset, ?ResourceFile $resourceFile): string
    {
        foreach ([
            $asset?->getOriginalName(),
            $resourceFile?->getOriginalName(),
            $asset?->getTitle(),
            $learningPath->getTitle(),
        ] as $candidate) {
            $candidate = trim((string) $candidate);
            if ('' === $candidate) {
                continue;
            }

            $candidate = str_replace(["\r", "\n"], '', basename(str_replace('\\', '/', $candidate)));
            if ('' === $candidate) {
                continue;
            }
            if ('zip' !== strtolower((string) pathinfo($candidate, PATHINFO_EXTENSION))) {
                $candidate .= '.zip';
            }

            return $candidate;
        }

        return \sprintf('learning-path-%d-scorm.zip', (int) $learningPath->getIid());
    }

    private function asciiFallbackName(string $name): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $fallback = preg_replace('/[^A-Za-z0-9._-]+/', '-', false === $ascii ? '' : $ascii);
        $fallback = trim((string) $fallback, '-.');

        return '' !== $fallback ? $fallback : 'scorm-package.zip';
    }
}
