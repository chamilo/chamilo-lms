<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Service\LearningPath\ScormRuntimeManager;
use Chamilo\CoreBundle\State\LearningPath\LearningPathRuntimeProvider;
use Chamilo\CoreBundle\State\LearningPath\LearningPathStateHelperTrait;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Attribute\Route;

final class LearningPathScormContentController extends AbstractController
{
    use LearningPathStateHelperTrait;

    #[Route(
        '/learning-path/scorm/{cid}/{sid}/{gid}/{lpId}/{itemId}/{path}',
        name: 'chamilo_core_learning_path_scorm_content',
        requirements: [
            'cid' => '\\d+',
            'sid' => '\\d+',
            'gid' => '\\d+',
            'lpId' => '\\d+',
            'itemId' => '\\d+',
            'path' => '.+',
        ],
        methods: ['GET'],
    )]
    public function __invoke(
        int $cid,
        int $sid,
        int $gid,
        int $lpId,
        int $itemId,
        string $path,
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security,
        LearningPathRuntimeProvider $runtimeProvider,
        ScormRuntimeManager $runtimeManager,
        AssetRepository $assetRepository,
        CLpRepository $lpRepository,
        CLpItemRepository $lpItemRepository,
    ): Response {
        $request->query->set('cid', $cid);
        $request->query->set('sid', $sid);
        $request->query->set('gid', $gid);
        $request->query->set('item_id', $itemId);

        $lp = $lpRepository->find($lpId);
        $item = $lpItemRepository->find($itemId);
        if (!$lp instanceof CLp
            || !$item instanceof CLpItem
            || (int) $item->getLp()->getIid() !== $lpId
            || !$runtimeManager->isScormLearningPath($lp)
            || !$runtimeManager->isScormItem($item)
        ) {
            throw new NotFoundHttpException('SCORM content not found.');
        }

        $course = $this->getContextCourse($entityManager, $request);
        $session = $this->getContextSession($entityManager, $request, $course);
        $group = $this->getContextGroup($entityManager, $request, $course);
        $resourceNode = $lp->getResourceNode();
        if (null === $resourceNode || !$security->isGranted('VIEW', $resourceNode)) {
            throw new AccessDeniedHttpException('The SCORM learning path is not available.');
        }

        $resourceLink = $this->getContextResourceLink($lp, $course, $session, $group);
        if (!$resourceLink instanceof ResourceLink) {
            throw new AccessDeniedHttpException('The SCORM learning path is not linked to this context.');
        }
        if (!$this->canManageLearningPaths($security)
            && ResourceLink::VISIBILITY_PUBLISHED !== $resourceLink->getVisibility()
        ) {
            throw new AccessDeniedHttpException('The SCORM learning path is not visible.');
        }

        if ($this->requiresRuntimeAvailabilityCheck($path)) {
            $runtime = $runtimeProvider->provide(
                new Get(),
                ['lpId' => $lpId],
                ['runtime_item_id' => $itemId],
            );
            if (!$runtime->runtimeSupported || $runtime->currentItemId !== $itemId) {
                throw new AccessDeniedHttpException('The SCORM item is not available.');
            }
        }

        $filePath = $runtimeManager->resolveAssetFilePath($lp, $path);
        $filesystem = $assetRepository->getFileSystem();
        if (!$filesystem->fileExists($filePath)) {
            throw new NotFoundHttpException('SCORM content file not found.');
        }

        $fileName = basename($filePath);
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeTypes = MimeTypes::getDefault()->getMimeTypes($extension);
        $mimeType = $mimeTypes[0] ?? 'application/octet-stream';

        $stream = $filesystem->readStream($filePath);
        if (!\is_resource($stream)) {
            throw new NotFoundHttpException('SCORM content file could not be read.');
        }

        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $response = new StreamedResponse(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        });
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $fileName);
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Length', (string) $filesystem->fileSize($filePath));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, max-age=300');

        return $response;
    }

    private function requiresRuntimeAvailabilityCheck(string $path): bool
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return \in_array($extension, ['htm', 'html', 'xht', 'xhtml'], true);
    }
}
