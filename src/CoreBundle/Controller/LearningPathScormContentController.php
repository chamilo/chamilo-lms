<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Service\LearningPath\ScormRuntimeManager;
use Chamilo\CoreBundle\State\LearningPath\LearningPathRuntimeProvider;
use Chamilo\CoreBundle\State\LearningPath\LearningPathStateHelperTrait;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CToolbox;
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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Attribute\Route;

use const PATHINFO_EXTENSION;
use const PHP_SESSION_ACTIVE;

final class LearningPathScormContentController extends AbstractController
{
    use LearningPathStateHelperTrait;

    #[Route(
        '/learning-path/scorm/{cid}/{sid}/{gid}/{lpId}/{itemId}/{path}',
        name: 'chamilo_core_learning_path_scorm_content',
        requirements: [
            'cid' => '\d+',
            'sid' => '\d+',
            'gid' => '\d+',
            'lpId' => '\d+',
            'itemId' => '\d+',
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
        CidReqHelper $cidReqHelper,
        Security $security,
        LearningPathRuntimeProvider $runtimeProvider,
        ScormRuntimeManager $runtimeManager,
        AssetRepository $assetRepository,
        CLpRepository $lpRepository,
        CLpItemRepository $lpItemRepository,
    ): Response {
        $parentLpId = $request->query->getInt('lp_id');
        $parentItemId = $request->query->getInt('lp_item_id') ?: $request->query->getInt('item_id');

        $request->query->set('cid', $cid);
        $request->query->set('sid', $sid);
        $request->query->set('gid', $gid);

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

        $course = $cidReqHelper->requireDoctrineCourseEntity();
        $session = $cidReqHelper->getDoctrineSessionEntity();
        $group = $this->getContextGroup($entityManager, $cidReqHelper, $course);
        $embeddedToolboxAccess = $this->isEmbeddedToolboxAccessAllowed(
            $lp,
            $parentLpId,
            $parentItemId,
            $course,
            $session,
            $group,
            $entityManager,
            $runtimeProvider,
            $lpRepository,
            $lpItemRepository,
        );

        $request->query->set('item_id', $itemId);

        $resourceNode = $lp->getResourceNode();
        if (null === $resourceNode) {
            throw new AccessDeniedHttpException('The SCORM learning path is not available.');
        }

        $resourceLink = $this->getContextResourceLink($lp, $course, $session, $group);
        if (!$resourceLink instanceof ResourceLink) {
            throw new AccessDeniedHttpException('The SCORM learning path is not linked to this context.');
        }

        if (!$embeddedToolboxAccess) {
            if (!$security->isGranted('VIEW', $resourceNode)) {
                throw new AccessDeniedHttpException('The SCORM learning path is not available.');
            }

            if (!$this->canManageLearningPaths($security)
                && ResourceLink::VISIBILITY_PUBLISHED !== $resourceLink->getVisibility()
            ) {
                throw new AccessDeniedHttpException('The SCORM learning path is not visible.');
            }
        }

        if (!$embeddedToolboxAccess && $this->requiresRuntimeAvailabilityCheck($path)) {
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
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $fileName,
            $this->createAsciiFilenameFallback($fileName),
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Length', (string) $filesystem->fileSize($filePath));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, max-age=300');

        if ('toolbox' === strtolower(trim((string) $lp->getContentMaker()))
            && \in_array($extension, ['htm', 'html', 'xht', 'xhtml'], true)
        ) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src data: blob:; media-src data: blob:; font-src data:; connect-src 'none'; frame-src 'none'; object-src 'none'; base-uri 'none'; form-action 'none'",
            );
            $response->headers->set('Referrer-Policy', 'no-referrer');
            $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
            $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        }

        return $response;
    }

    private function isEmbeddedToolboxAccessAllowed(
        CLp $toolboxLearningPath,
        int $parentLpId,
        int $parentItemId,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        EntityManagerInterface $entityManager,
        LearningPathRuntimeProvider $runtimeProvider,
        CLpRepository $lpRepository,
        CLpItemRepository $lpItemRepository,
    ): bool {
        if ('toolbox' !== strtolower(trim((string) $toolboxLearningPath->getContentMaker()))
            || $parentLpId <= 0
            || $parentItemId <= 0
        ) {
            return false;
        }

        $parentLearningPath = $lpRepository->find($parentLpId);
        $parentItem = $lpItemRepository->find($parentItemId);
        if (!$parentLearningPath instanceof CLp
            || !$parentItem instanceof CLpItem
            || (int) $parentItem->getLp()->getIid() !== $parentLpId
            || 'toolbox' !== strtolower(trim((string) $parentItem->getItemType()))
        ) {
            return false;
        }

        $toolboxId = ctype_digit((string) $parentItem->getPath()) ? (int) $parentItem->getPath() : 0;
        if ($toolboxId <= 0) {
            return false;
        }

        $toolbox = $entityManager->getRepository(CToolbox::class)->find($toolboxId);
        if (!$toolbox instanceof CToolbox
            || !$this->getContextResourceLink($toolbox, $course, $session, $group) instanceof ResourceLink
        ) {
            return false;
        }

        $embeddedLearningPath = $toolbox->getCurrentVersionEntity()?->getLearningPath();
        if (!$embeddedLearningPath instanceof CLp
            || (int) $embeddedLearningPath->getIid() !== (int) $toolboxLearningPath->getIid()
        ) {
            return false;
        }

        try {
            $parentRuntime = $runtimeProvider->provide(
                new Get(),
                ['lpId' => $parentLpId],
                ['runtime_item_id' => $parentItemId],
            );
        } catch (AccessDeniedHttpException|BadRequestHttpException|NotFoundHttpException) {
            return false;
        }

        return $parentRuntime->runtimeSupported && $parentRuntime->currentItemId === $parentItemId;
    }

    private function requiresRuntimeAvailabilityCheck(string $path): bool
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return \in_array($extension, ['htm', 'html', 'xht', 'xhtml'], true);
    }

    private function createAsciiFilenameFallback(string $fileName): string
    {
        $fallback = '';
        $length = \strlen($fileName);

        for ($i = 0; $i < $length; ++$i) {
            $character = $fileName[$i];
            $code = \ord($character);

            $fallback .= '%' === $character || $code < 32 || $code > 126 ? '_' : $character;
        }

        return $fallback;
    }
}
