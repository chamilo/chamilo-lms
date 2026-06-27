<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\LearningPath\LearningPathStateHelperTrait;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
final readonly class LearningPathScormPackageAction
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private AssetRepository $assetRepository,
        private ResourceNodeRepository $resourceNodeRepository,
        private CDocumentRepository $documentRepository,
        private CLpRepository $learningPathRepository,
    ) {}

    public function __invoke(int $lpId, Request $request): Response
    {
        if ($this->settingEnabled('lp.hide_scorm_export_link')) {
            throw new AccessDeniedHttpException('SCORM package download is disabled.');
        }

        $course = $this->getContextCourse($this->entityManager, $request);
        $requestedNodeId = $request->query->getInt('node');
        $courseNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);
        if ($requestedNodeId > 0 && $requestedNodeId !== $courseNodeId) {
            throw new AccessDeniedHttpException('The requested resource node does not belong to this course.');
        }

        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);

        $learningPath = $this->learningPathRepository->find($lpId);
        if (!$learningPath instanceof CLp || CLp::SCORM_TYPE !== $learningPath->getLpType()) {
            throw new NotFoundHttpException('SCORM learning path not found.');
        }

        $resourceNode = $learningPath->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('VIEW', $resourceNode)) {
            throw new AccessDeniedHttpException('The SCORM learning path is not available.');
        }

        $resourceLink = $this->getContextResourceLink($learningPath, $course, $session, $group);
        if (!$resourceLink instanceof ResourceLink) {
            throw new AccessDeniedHttpException('The SCORM learning path is not linked to this context.');
        }

        $canManage = $this->canManageLearningPaths($this->security);
        if (!$canManage && !$this->settingEnabled('lp.lp_allow_export_to_students')) {
            throw new AccessDeniedHttpException('You are not allowed to download this SCORM package.');
        }
        if (!$canManage && ResourceLink::VISIBILITY_PUBLISHED !== $resourceLink->getVisibility()) {
            throw new AccessDeniedHttpException('The SCORM learning path is not visible.');
        }

        $source = $this->resolvePackageSource($learningPath, $course);
        $fileSize = $source['filesystem']->fileSize($source['path']);
        $stream = $source['filesystem']->readStream($source['path']);
        if (!\is_resource($stream)) {
            throw new NotFoundHttpException('The original SCORM ZIP package could not be read.');
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

    private function settingEnabled(string $name): bool
    {
        return 'true' === strtolower(trim((string) $this->settingsManager->getSetting($name, true)));
    }

    private function downloadName(CLp $learningPath, ?Asset $asset, ?ResourceFile $resourceFile): string
    {
        $candidates = [
            $asset?->getOriginalName(),
            $resourceFile?->getOriginalName(),
            $asset?->getTitle(),
            $learningPath->getTitle(),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ('' === $candidate) {
                continue;
            }

            $candidate = basename(str_replace('\\', '/', $candidate));
            $candidate = str_replace(["\r", "\n"], '', $candidate);
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
