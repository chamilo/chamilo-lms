<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesArchiveService;
use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesArchiveUpdateValidator;
use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesAttemptReviewService;
use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesAttemptReviewValidator;
use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesAttemptService;
use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesEntrypoints;
use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesPackageInspector;
use Chamilo\CoreBundle\State\LearningPath\LearningPathRuntimeProgressManager;
use Chamilo\CoreBundle\State\LearningPath\LearningPathRuntimeProvider;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use ZipArchive;

use const PATHINFO_EXTENSION;

final class HotPotatoesController extends AbstractController
{
    private const int MAX_ARCHIVE_BYTES = 25 * 1024 * 1024;

    #[Route('/api/hotpotatoes/archives', name: 'chamilo_hotpotatoes_list', methods: ['GET'])]
    public function index(HotPotatoesArchiveService $archives): JsonResponse
    {
        [$course, $session, $group, $canManage] = $archives->context();
        $rows = [];
        foreach ($archives->listDocuments($course, $session, $group) as $document) {
            try {
                [$document, $file] = $archives->findVisible((int) $document->getIid(), $course, $session, $group, $canManage);
            } catch (AccessDeniedHttpException|NotFoundHttpException) {
                continue;
            }
            $exactLink = $archives->findExactActiveLink($document, $course, $session, $group);
            $link = $exactLink;
            if (null === $link && null !== $session && null === $group) {
                $link = $archives->findExactActiveLink($document, $course, null, null);
            }
            $entrypoints = $file->getMetadata()['chamilo_hotpotatoes']['entrypoints'];
            $editable = null !== $exactLink;
            $rows[] = [
                'editable' => $canManage && $editable,
                'lpItemCount' => $canManage ? $archives->countLearningPathReferences((int) $document->getIid()) : 0,
                'id' => (int) $document->getIid(),
                'title' => $document->getTitle(),
                'entrypoints' => $entrypoints,
                'visible' => ResourceLink::VISIBILITY_PUBLISHED === $link?->getVisibility(),
            ];
        }

        return new JsonResponse(['items' => $rows, 'canManage' => $canManage]);
    }

    /**
     * Edit an archive's course-facing metadata without changing the ZIP or any LP item.
     * A session/group request cannot silently mutate a different resource link.
     */
    #[Route('/api/hotpotatoes/archives/{id}', name: 'chamilo_hotpotatoes_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(
        int $id,
        Request $request,
        HotPotatoesArchiveService $archives,
        HotPotatoesArchiveUpdateValidator $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        [$course, $session, $group, $canManage] = $archives->context();
        if (!$canManage) {
            throw new AccessDeniedHttpException('You cannot manage Hot Potatoes in this context.');
        }

        [$document] = $archives->findVisible($id, $course, $session, $group, true);
        $node = $document->getResourceNode();
        if (null === $node || !$this->isGranted('EDIT', $node)) {
            throw new AccessDeniedHttpException('The Hot Potatoes resource cannot be edited.');
        }

        // Never call getResourceLinkByContext() here: when a parameter is absent,
        // it does not constrain that field to NULL and can pick another context.
        $matchingLinks = [];
        foreach ($node->getResourceLinks() as $link) {
            if (null !== $link->getDeletedAt()
                || (int) ($link->getCourse()?->getId() ?? 0) !== (int) $course->getId()
                || (int) ($link->getSession()?->getId() ?? 0) !== (int) ($session?->getId() ?? 0)
                || (int) ($link->getGroup()?->getIid() ?? 0) !== (int) ($group?->getIid() ?? 0)
                || null !== $link->getUser()
                || null !== $link->getUserGroup()
            ) {
                continue;
            }

            $matchingLinks[] = $link;
        }

        if (1 !== \count($matchingLinks)) {
            throw new AccessDeniedHttpException('There is no unique editable link in this course context.');
        }

        $values = $validator->validate($request->getPayload()->all());
        if (\array_key_exists('title', $values)) {
            // Renaming the document label does not rename files in the archive or
            // existing LP item titles/paths, which must remain stable.
            $document->setTitle($values['title']);
        }
        if (\array_key_exists('published', $values)) {
            $matchingLinks[0]->setVisibility(
                $values['published'] ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT,
            );
        }
        $entityManager->flush();

        return new JsonResponse([
            'id' => (int) $document->getIid(),
            'title' => $document->getTitle(),
            'visible' => ResourceLink::VISIBILITY_PUBLISHED === $matchingLinks[0]->getVisibility(),
        ]);
    }

    /**
     * Remove only this course-context ResourceLink, never the ZIP itself. Any
     * current learning-path reference prevents removal to avoid broken lessons.
     */
    #[Route('/api/hotpotatoes/archives/{id}', name: 'chamilo_hotpotatoes_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(
        int $id,
        HotPotatoesArchiveService $archives,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        [$course, $session, $group, $canManage] = $archives->context();
        if (!$canManage) {
            throw new AccessDeniedHttpException('You cannot delete Hot Potatoes in this context.');
        }

        [$document] = $archives->findVisible($id, $course, $session, $group, true);
        $node = $document->getResourceNode();
        if (null === $node || !$this->isGranted('DELETE', $node)) {
            throw new AccessDeniedHttpException('The Hot Potatoes resource cannot be deleted.');
        }

        $link = $archives->findExactActiveLink($document, $course, $session, $group);
        if (!$link instanceof ResourceLink) {
            throw new AccessDeniedHttpException('The resource is inherited or has no unique link in this context.');
        }

        if ($archives->countLearningPathReferences($id) > 0) {
            throw new ConflictHttpException('This Hot Potatoes resource is used by a learning path. Remove its learning path items first.');
        }

        // A session override must not unexpectedly reveal an inherited course
        // copy after removal; hide it first or manage it from the base course.
        if (null !== $session && null === $group
            && null !== $archives->findExactActiveLink($document, $course, null, null)
        ) {
            throw new ConflictHttpException('The archive has a base-course link. Manage that link from the base course.');
        }

        // Soft-delete the link, preserving the underlying archive and other
        // context links for audit/rollback. No filesystem operations happen here.
        $link->setDeletedAt(new DateTime());
        $entityManager->flush();

        return new JsonResponse(['removed' => true, 'archived' => true, 'id' => $id]);
    }

    /**
     * Record a launch for the signed-in learner. This is tracking, not a grade.
     */
    #[Route('/api/hotpotatoes/archives/{id}/attempts', name: 'chamilo_hotpotatoes_attempt_start', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function startAttempt(int $id, Request $request, HotPotatoesArchiveService $archives, HotPotatoesAttemptService $attempts): JsonResponse
    {
        [$course, $session, $group, $canManage] = $archives->context();
        [$document] = $archives->findVisible($id, $course, $session, $group, $canManage);
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required to track an activity.');
        }

        $payload = $request->getPayload()->all();
        if (array_keys($payload) !== ['entrypoint'] || !\is_string($payload['entrypoint'])) {
            throw new BadRequestHttpException('An entrypoint is required.');
        }
        $entrypoint = $payload['entrypoint'];
        if (!\in_array($entrypoint, HotPotatoesEntrypoints::forDocument($document), true)) {
            throw new BadRequestHttpException('The entrypoint is not part of this Hot Potatoes archive.');
        }

        $attempt = $attempts->start($id, $entrypoint, (int) $user->getId(), $course, $session, $group);

        return new JsonResponse($attempt->toSummary(false), Response::HTTP_CREATED);
    }

    /**
     * A teacher reviews a completed attempt. The recorded score is teacher-assessed,
     * not automatically obtained from untrusted Hot Potatoes JavaScript.
     */
    #[Route(
        '/api/hotpotatoes/archives/{id}/attempts/{attemptId}/reviews',
        name: 'chamilo_hotpotatoes_attempt_review',
        requirements: ['id' => '\d+', 'attemptId' => '\d+'],
        methods: ['POST'],
    )]
    public function reviewAttempt(
        int $id,
        int $attemptId,
        Request $request,
        HotPotatoesArchiveService $archives,
        HotPotatoesAttemptReviewService $reviews,
        HotPotatoesAttemptReviewValidator $validator,
    ): JsonResponse {
        [$course, $session, $group, $canManage] = $archives->context();
        if (!$canManage) {
            throw new AccessDeniedHttpException('You cannot review Hot Potatoes attempts.');
        }

        [$document] = $archives->findVisible($id, $course, $session, $group, true);
        $node = $document->getResourceNode();
        if (null === $node
            || !$this->isGranted('EDIT', $node)
            || null === $archives->findExactActiveLink($document, $course, $session, $group)
        ) {
            throw new AccessDeniedHttpException('This Hot Potatoes resource cannot be reviewed in this context.');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $scorePercent = $validator->validate($request->getPayload()->all());
        $review = $reviews->review(
            $attemptId,
            $id,
            (int) $user->getId(),
            $scorePercent,
            $course,
            $session,
            $group,
        );

        return new JsonResponse($review->toSummary(), Response::HTTP_CREATED);
    }

    /**
     * Teachers see attempts within their course context; learners only see their own.
     */
    #[Route('/api/hotpotatoes/archives/{id}/attempts', name: 'chamilo_hotpotatoes_attempt_list', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function listAttempts(
        int $id,
        HotPotatoesArchiveService $archives,
        HotPotatoesAttemptService $attempts,
        HotPotatoesAttemptReviewService $reviews,
    ): JsonResponse {
        [$course, $session, $group, $canManage] = $archives->context();
        $archives->findVisible($id, $course, $session, $group, $canManage);
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required to view attempts.');
        }

        $rows = $attempts->list($id, (int) $user->getId(), $course, $session, $group, $canManage);
        // Teacher reviews are private until an explicit grade release is designed.
        $latestReviews = $canManage ? $reviews->latestForAttempts($rows) : [];

        return new JsonResponse([
            'items' => array_map(
                static fn ($attempt) => array_merge(
                    $attempt->toSummary($canManage),
                    $latestReviews[(int) $attempt->getId()] ?? [
                        'scorePercent' => null,
                        'reviewedByUserId' => null,
                        'reviewedAt' => null,
                        'scoreSource' => null,
                    ],
                ),
                $rows,
            ),
            'automaticallyGraded' => false,
            'gradebookSynced' => false,
            'limit' => 100,
        ]);
    }

    #[Route('/api/hotpotatoes/archives/{id}/attempts/{attemptId}/complete', name: 'chamilo_hotpotatoes_attempt_complete', requirements: ['id' => '\d+', 'attemptId' => '\d+'], methods: ['POST'])]
    public function completeAttempt(int $id, int $attemptId, HotPotatoesArchiveService $archives, HotPotatoesAttemptService $attempts): JsonResponse
    {
        [$course, $session, $group, $canManage] = $archives->context();
        $archives->findVisible($id, $course, $session, $group, $canManage);
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required to complete an attempt.');
        }

        $attempt = $attempts->complete($attemptId, $id, (int) $user->getId(), $course, $session, $group);

        return new JsonResponse($attempt->toSummary(false));
    }

    #[Route(
        '/api/hotpotatoes/archives/{cid}/{sid}/{gid}/{id}/content/{path}',
        name: 'chamilo_hotpotatoes_content',
        requirements: ['cid' => '\d+', 'sid' => '\d+', 'gid' => '\d+', 'id' => '\d+', 'path' => '.+'],
        methods: ['GET'],
    )]
    public function content(
        int $cid,
        int $sid,
        int $gid,
        int $id,
        string $path,
        Request $request,
        HotPotatoesArchiveService $archives,
        HotPotatoesPackageInspector $inspector,
    ): Response {
        $request->query->set('cid', $cid);
        $request->query->set('sid', $sid);
        $request->query->set('gid', $gid);
        [$course, $session, $group, $canManage] = $archives->context();
        if ((int) $course->getId() !== $cid
            || (int) ($session?->getId() ?? 0) !== $sid
            || (int) ($group?->getIid() ?? 0) !== $gid
        ) {
            throw new AccessDeniedHttpException('The content context does not match the request.');
        }
        [$document, $file] = $archives->findVisible($id, $course, $session, $group, $canManage);

        if (!$this->isSafeEntryPath($path)) {
            throw new NotFoundHttpException('Invalid Hot Potatoes content path.');
        }

        $stream = $archives->archiveStream($document, $file);
        if (!\is_resource($stream)) {
            throw new NotFoundHttpException('Hot Potatoes archive cannot be read.');
        }

        $temporary = tempnam(sys_get_temp_dir(), 'chamilo_hp_');
        if (false === $temporary) {
            fclose($stream);

            throw new RuntimeException('Hot Potatoes archive cannot be prepared.');
        }
        $zip = new ZipArchive();

        try {
            $target = fopen($temporary, 'wb');
            if (!\is_resource($target)) {
                throw new RuntimeException('Hot Potatoes archive cannot be prepared.');
            }

            try {
                $written = stream_copy_to_stream($stream, $target, self::MAX_ARCHIVE_BYTES + 1);
            } finally {
                fclose($stream);
                fclose($target);
            }
            if (false === $written || $written > self::MAX_ARCHIVE_BYTES) {
                throw new BadRequestHttpException('Hot Potatoes archive exceeds the maximum supported size.');
            }

            // A previous import or a replaced ResourceFile must not bypass validation.
            $inspector->inspect($temporary, 'activity.zip');
            if (true !== $zip->open($temporary, ZipArchive::CHECKCONS)) {
                throw new NotFoundHttpException('Hot Potatoes archive is invalid.');
            }
            if (false === $zip->statName($path)) {
                throw new NotFoundHttpException('Hot Potatoes content file not found.');
            }
            $content = $zip->getFromName($path);
            if (false === $content) {
                throw new NotFoundHttpException('Hot Potatoes content cannot be read.');
            }
        } finally {
            $zip->close();
            @unlink($temporary);
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'htm', 'html' => 'text/html; charset=UTF-8',
            'css' => 'text/css; charset=UTF-8',
            'js' => 'text/javascript; charset=UTF-8',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'application/octet-stream',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'wav' => 'audio/wav',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'ico' => 'image/x-icon',
            default => 'text/plain; charset=UTF-8',
        };
        $response = new Response($content, Response::HTTP_OK, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ]);
        if (\in_array($extension, ['htm', 'html'], true)) {
            // Opaque sandbox origin: no same-origin access to Chamilo's cookies,
            // localStorage, DOM, or authenticated API responses.
            $origin = $request->getSchemeAndHttpHost();
            $response->headers->set(
                'Content-Security-Policy',
                "sandbox allow-scripts; default-src 'none'; script-src 'unsafe-inline' 'unsafe-eval' ".$origin."; style-src 'unsafe-inline' ".$origin.'; img-src data: blob: '.$origin.'; media-src data: blob: '.$origin.'; font-src data: '.$origin."; connect-src 'none'; form-action 'none'; frame-src 'none'; object-src 'none'; base-uri 'none'; frame-ancestors 'self'"
            );
        }

        return $response;
    }

    /**
     * Learners acknowledge that they have finished an ungraded activity.
     * No score is inferred from untrusted Hot Potatoes JavaScript.
     */
    #[Route('/api/hotpotatoes/archives/{id}/complete', name: 'chamilo_hotpotatoes_complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function complete(
        int $id,
        Request $request,
        HotPotatoesArchiveService $archives,
        EntityManagerInterface $entityManager,
        LearningPathRuntimeProvider $runtimeProvider,
        LearningPathRuntimeProgressManager $progressManager,
    ): JsonResponse {
        [$course, $session, $group, $canManage] = $archives->context();
        $archives->findVisible($id, $course, $session, $group, $canManage);
        $lpId = $request->getPayload()->getInt('lpId');
        $itemId = $request->getPayload()->getInt('itemId');
        if ($lpId <= 0 || $itemId <= 0) {
            throw new BadRequestHttpException('Learning path and item identifiers are required.');
        }

        $lp = $entityManager->getRepository(CLp::class)->find($lpId);
        $item = $entityManager->getRepository(CLpItem::class)->find($itemId);
        if (!$lp instanceof CLp || !$item instanceof CLpItem
            || (int) $item->getLp()->getIid() !== $lpId
            || 'hotpotatoes' !== $item->getItemType()
            || (int) $item->getPath() !== $id
        ) {
            throw new NotFoundHttpException('The Hot Potatoes learning path item does not exist.');
        }

        $lpNode = $lp->getResourceNode();
        $lpLink = $lpNode?->getResourceLinkByContext($course, $session, $group);
        if (null === $lpLink && null !== $session && null === $group) {
            $lpLink = $lpNode?->getResourceLinkByContext($course);
        }
        if (!$lpLink instanceof ResourceLink || !$this->isGranted('VIEW', $lpNode)
            || (!$canManage && ResourceLink::VISIBILITY_PUBLISHED !== $lpLink->getVisibility())
        ) {
            throw new AccessDeniedHttpException('Learning path is not accessible in this context.');
        }

        $runtime = $runtimeProvider->provide(new Get(), ['lpId' => $lpId], ['runtime_item_id' => $itemId]);
        if (!$runtime->runtimeSupported || $runtime->currentItemId !== $itemId) {
            throw new AccessDeniedHttpException('The learning path item is not currently accessible.');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $view = $entityManager->getRepository(CLpView::class)->findOneBy(
            ['lp' => $lp, 'course' => $course, 'session' => $session, 'user' => $user],
            ['viewCount' => 'DESC', 'iid' => 'DESC'],
        );
        if (!$view instanceof CLpView || (int) $view->getLastItem() !== $itemId) {
            throw new AccessDeniedHttpException('The learning path attempt is not active.');
        }
        $itemView = $progressManager->findLatestItemView($view, $item);
        if (!$itemView instanceof CLpItemView) {
            throw new AccessDeniedHttpException('The Hot Potatoes item attempt is missing.');
        }

        $itemView->setStatus('completed');
        $view->setLastItem($itemId);
        $entityManager->flush();
        $progressManager->synchronize($lp, $view);

        return new JsonResponse(['completed' => true, 'graded' => false]);
    }

    private function isSafeEntryPath(string $path): bool
    {
        if ('' === $path || \strlen($path) > 512 || str_contains($path, '\\')
            || str_contains($path, '%') || str_contains($path, ':')
            || str_contains($path, '?') || str_contains($path, '#')
            || preg_match('/[\x00-\x1f\x7f]/', $path)
        ) {
            return false;
        }

        foreach (explode('/', $path) as $part) {
            if ('' === $part || '.' === $part || '..' === $part || str_starts_with($part, '.')) {
                return false;
            }
        }

        return true;
    }
}
