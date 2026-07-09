<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Announcement\AnnouncementAccessHelperTrait;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CAnnouncementAttachment;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CAnnouncementAttachmentRepository;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final readonly class AnnouncementAttachmentController
{
    use AnnouncementAccessHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CAnnouncementRepository $announcementRepository,
        private CAnnouncementAttachmentRepository $attachmentRepository,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    #[Route(
        '/api/announcement/{announcementId}/attachment/{attachmentId}/download',
        name: 'announcement_attachment_download',
        requirements: ['announcementId' => '\\d+', 'attachmentId' => '\\d+'],
        methods: ['GET'],
    )]
    public function __invoke(int $announcementId, int $attachmentId, Request $request): Response
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

        $attachment = $this->attachmentRepository->find($attachmentId);
        if (!$attachment instanceof CAnnouncementAttachment
            || $attachment->getAnnouncement()?->getIid() !== $announcement->getIid()
        ) {
            throw new NotFoundHttpException('The requested attachment was not found.');
        }

        if (null === $attachment->getResourceNode() || !$attachment->getResourceNode()->hasResourceFile()) {
            throw new NotFoundHttpException('The attachment file was not found.');
        }

        try {
            $content = $this->attachmentRepository->getResourceFileContent($attachment);
        } catch (Throwable $throwable) {
            throw new NotFoundHttpException('The attachment file was not found.', $throwable);
        }

        $filename = basename(str_replace('\\', '/', trim(str_replace(["\r", "\n", "\0"], '', (string) $attachment->getFilename()))));
        if ('' === $filename || '.' === $filename || '..' === $filename) {
            $filename = 'announcement-attachment-'.$attachmentId;
        }

        $fallbackFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'attachment';
        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            $fallbackFilename,
        );

        $mimeType = $attachment->getResourceNode()?->getFirstResourceFile()?->getMimeType();

        return new Response($content, Response::HTTP_OK, [
            'Content-Disposition' => $disposition,
            'Content-Length' => (string) \strlen($content),
            'Cache-Control' => 'private, no-store',
            'Content-Type' => $mimeType ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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
}
