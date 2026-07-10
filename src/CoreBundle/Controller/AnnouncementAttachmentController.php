<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Security\Upload\UploadFilenamePolicy;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Announcement\AnnouncementAccessHelperTrait;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CAnnouncementAttachment;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CAnnouncementAttachmentRepository;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

final readonly class AnnouncementAttachmentController
{
    use AnnouncementAccessHelperTrait;

    public const CSRF_TOKEN_ID = 'announcement_attachment';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CAnnouncementRepository $announcementRepository,
        private CAnnouncementAttachmentRepository $attachmentRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private UploadFilenamePolicy $uploadFilenamePolicy,
    ) {}

    #[Route(
        '/api/announcement/{announcementId}/attachment/{attachmentId}/download',
        name: 'announcement_attachment_download',
        requirements: ['announcementId' => '\d+', 'attachmentId' => '\d+'],
        methods: ['GET'],
    )]
    public function download(int $announcementId, int $attachmentId, Request $request): Response
    {
        [$course, $session, $group] = $this->resolveContext($request);
        $announcement = $this->getReadableAnnouncement($announcementId, $course, $session, $group, $request);
        $attachment = $this->getAttachment($announcement, $attachmentId);

        if (null === $attachment->getResourceNode() || !$attachment->getResourceNode()->hasResourceFile()) {
            throw new NotFoundHttpException('The attachment file was not found.');
        }

        try {
            $content = $this->attachmentRepository->getResourceFileContent($attachment);
        } catch (Throwable $throwable) {
            throw new NotFoundHttpException('The attachment file was not found.', $throwable);
        }

        $filename = $this->normalizeFilename($attachment->getFilename(), 'announcement-attachment-'.$attachmentId);
        $fallbackFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'attachment';
        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            $fallbackFilename,
        );

        $mimeType = $attachment->getResourceNode()?->getFirstResourceFile()?->getMimeType();
        $this->registerAnnouncementEventLog(
            'download_attachment',
            $course,
            $session,
            $announcementId,
            $attachmentId,
        );

        return new Response($content, Response::HTTP_OK, [
            'Content-Disposition' => $disposition,
            'Content-Length' => (string) \strlen($content),
            'Cache-Control' => 'private, no-store',
            'Content-Type' => $mimeType ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    #[Route(
        '/api/announcement/{announcementId}/attachments',
        name: 'announcement_attachment_upload',
        requirements: ['announcementId' => '\d+'],
        methods: ['POST'],
    )]
    public function upload(int $announcementId, Request $request): JsonResponse
    {
        [$course, $session, $group] = $this->resolveContext($request);
        $this->assertAttachmentsEnabled();
        $this->validateCsrfToken((string) $request->request->get('csrfToken', ''));
        $announcement = $this->getEditableAnnouncement($announcementId, $course, $session, $group, $request);
        $files = $this->getUploadedFiles($request);
        if ([] === $files) {
            throw new BadRequestHttpException('At least one attachment is required.');
        }

        $comment = mb_substr(trim((string) $request->request->get('comment', '')), 0, 10000);

        /** @var array<int, array{file: UploadedFile, filename: string}> $validatedFiles */
        $validatedFiles = [];
        foreach ($files as $file) {
            if (!$file->isValid()) {
                throw new BadRequestHttpException('An attachment could not be uploaded.');
            }

            $policy = $this->uploadFilenamePolicy->filter($file->getClientOriginalName());
            if (false === $policy['allowed']) {
                throw new BadRequestHttpException('File upload failed: this file extension or file type is prohibited.');
            }

            $validatedFiles[] = [
                'file' => $file,
                'filename' => $this->normalizeFilename((string) $policy['filename'], 'attachment'),
            ];
        }

        $created = [];
        foreach ($validatedFiles as $validatedFile) {
            $file = $validatedFile['file'];
            $filename = $validatedFile['filename'];
            $attachment = $this->entityManager->wrapInTransaction(function () use (
                $announcement,
                $comment,
                $course,
                $file,
                $filename,
                $group,
                $session,
            ): CAnnouncementAttachment {
                $attachment = (new CAnnouncementAttachment())
                    ->setFilename($filename)
                    ->setPath(uniqid('announce_', true))
                    ->setComment($comment)
                    ->setAnnouncement($announcement)
                    ->setSize((int) ($file->getSize() ?: 0))
                    ->setParent($announcement)
                    ->addCourseLink($course, $session, $group)
                ;

                $announcement->addAttachment($attachment);
                $this->attachmentRepository->create($attachment);
                $this->attachmentRepository->addFile($attachment, $file);
                $this->attachmentRepository->update($attachment);

                return $attachment;
            });

            $created[] = $this->normalizeAttachment($announcement, $attachment, $course, $session, $group);
        }

        $this->registerAnnouncementEventLog(
            'upload_attachment',
            $course,
            $session,
            $announcementId,
            details: (string) \count($created),
        );

        return new JsonResponse([
            'attachments' => $created,
        ], Response::HTTP_CREATED);
    }

    #[Route(
        '/api/announcement/{announcementId}/attachment/{attachmentId}',
        name: 'announcement_attachment_delete',
        requirements: ['announcementId' => '\d+', 'attachmentId' => '\d+'],
        methods: ['DELETE'],
    )]
    public function delete(int $announcementId, int $attachmentId, Request $request): JsonResponse
    {
        [$course, $session, $group] = $this->resolveContext($request);
        $this->assertAttachmentsEnabled();
        $this->validateCsrfToken((string) $request->headers->get('X-CSRF-TOKEN', ''));
        $announcement = $this->getEditableAnnouncement($announcementId, $course, $session, $group, $request);
        $attachment = $this->getAttachment($announcement, $attachmentId);

        $announcement->removeAttachment($attachment);
        $resourceNode = $attachment->getResourceNode();
        if (null !== $resourceNode) {
            $this->entityManager->remove($resourceNode);
        } else {
            $this->entityManager->remove($attachment);
        }
        $this->entityManager->flush();
        $this->registerAnnouncementEventLog(
            'delete_attachment',
            $course,
            $session,
            $announcementId,
            $attachmentId,
        );

        return new JsonResponse([
            'success' => true,
            'attachmentId' => $attachmentId,
        ]);
    }

    /**
     * @return array{0: Course, 1: Session|null, 2: CGroup|null}
     */
    private function resolveContext(Request $request): array
    {
        $course = $this->getCourse($request);
        $this->assertAnnouncementToolEnabled($this->entityManager, $course);

        $session = $this->getSession($request);
        $this->assertSessionBelongsToCourse($session, $course);

        $group = $this->getGroup($request);
        $this->assertGroupBelongsToContext($group, $course, $session);

        if (!$this->canReadAnnouncementContext(
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to view announcements in this context.');
        }

        return [$course, $session, $group];
    }

    private function getReadableAnnouncement(
        int $announcementId,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        Request $request,
    ): CAnnouncement {
        $announcement = $this->announcementRepository->find($announcementId);
        if (!$announcement instanceof CAnnouncement) {
            throw new NotFoundHttpException('The requested announcement was not found.');
        }

        $studentView = $this->isStudentView($request);
        $canManage = !$studentView && $this->canManageAnnouncements(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        );

        $contextLinks = $this->getAnnouncementContextLinks($announcement, $course, $session, $group);
        if (!$this->canReadAnnouncement(
            $announcement,
            $contextLinks,
            $this->security,
            $canManage,
            $studentView,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to view this announcement.');
        }

        return $announcement;
    }

    private function getEditableAnnouncement(
        int $announcementId,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        Request $request,
    ): CAnnouncement {
        if ($this->isStudentView($request)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this announcement.');
        }

        $announcement = $this->announcementRepository->find($announcementId);
        if (!$announcement instanceof CAnnouncement) {
            throw new NotFoundHttpException('The requested announcement was not found.');
        }

        if ([] === $this->getAnnouncementContextLinks($announcement, $course, $session, $group)) {
            throw new AccessDeniedHttpException('The requested announcement does not belong to the current course context.');
        }

        if ($group instanceof CGroup && $this->hasMultipleAnnouncementGroupTargets(
            $announcement,
            $course,
            $session,
        )) {
            throw new AccessDeniedHttpException(
                'This announcement targets several groups and its attachments cannot be managed from one group.',
            );
        }

        if (!$this->canEditAnnouncement(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $announcement,
            $course,
            $session,
            $group,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to edit this announcement.');
        }

        return $announcement;
    }

    private function getAttachment(CAnnouncement $announcement, int $attachmentId): CAnnouncementAttachment
    {
        $attachment = $this->attachmentRepository->find($attachmentId);
        if (!$attachment instanceof CAnnouncementAttachment
            || $attachment->getAnnouncement()?->getIid() !== $announcement->getIid()
        ) {
            throw new NotFoundHttpException('The requested attachment was not found.');
        }

        return $attachment;
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if ($sessionId <= 0) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function getGroup(Request $request): ?CGroup
    {
        $groupId = $request->query->getInt('gid');
        if ($groupId <= 0) {
            return null;
        }

        $group = $this->entityManager->getRepository(CGroup::class)->find($groupId);
        if (!$group instanceof CGroup) {
            throw new BadRequestHttpException('The requested group was not found.');
        }

        return $group;
    }

    private function assertAttachmentsEnabled(): void
    {
        if (!$this->isSettingEnabled(
            $this->settingsManager->getSetting('announcement.disable_announcement_attachment', true),
        )) {
            return;
        }

        throw new AccessDeniedHttpException('Announcement attachments are disabled.');
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function getUploadedFiles(Request $request): array
    {
        $files = [];
        $value = $request->files->all('files');
        foreach ($value as $file) {
            if ($file instanceof UploadedFile) {
                $files[] = $file;
            }
        }

        if ([] !== $files) {
            return $files;
        }

        foreach ($request->files->all() as $file) {
            if ($file instanceof UploadedFile) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeAttachment(
        CAnnouncement $announcement,
        CAnnouncementAttachment $attachment,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        $query = ['cid' => (int) $course->getId()];
        if ($session instanceof Session && null !== $session->getId()) {
            $query['sid'] = (int) $session->getId();
        }
        if ($group instanceof CGroup && null !== $group->getIid()) {
            $query['gid'] = (int) $group->getIid();
        }

        return [
            'id' => (int) $attachment->getIid(),
            'filename' => $attachment->getFilename(),
            'comment' => (string) $attachment->getComment(),
            'size' => (int) $attachment->getSize(),
            'downloadUrl' => \sprintf(
                '/api/announcement/%d/attachment/%d/download?%s',
                (int) $announcement->getIid(),
                (int) $attachment->getIid(),
                http_build_query($query),
            ),
            'canDelete' => true,
        ];
    }

    private function normalizeFilename(string $filename, string $fallback): string
    {
        $filename = basename(str_replace('\\', '/', trim(str_replace(["\r", "\n", "\0"], '', $filename))));
        if ('' === $filename || '.' === $filename || '..' === $filename) {
            return $fallback;
        }

        return mb_substr($filename, 0, 255);
    }
}
