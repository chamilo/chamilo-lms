<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\AgendaHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Security\Upload\UploadFilenamePolicy;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CCalendarEventAttachment;
use Chamilo\CourseBundle\Repository\CCalendarEventAttachmentRepository;
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
use Throwable;

/**
 * The files attached to a course event, as in the legacy agenda form: the editors of the event add
 * and delete them, and whoever sees the event in their agenda downloads them.
 */
final readonly class CalendarEventAttachmentController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CCalendarEventAttachmentRepository $attachmentRepository,
        private AgendaHelper $agendaHelper,
        private CidReqHelper $cidReqHelper,
        private Security $security,
        private UploadFilenamePolicy $uploadFilenamePolicy,
    ) {}

    #[Route(
        '/api/calendar-events/{eventId}/attachments',
        name: 'calendar_event_attachment_upload',
        requirements: ['eventId' => '\d+'],
        methods: ['POST'],
    )]
    public function upload(int $eventId, Request $request): JsonResponse
    {
        $event = $this->getEditableEvent($eventId);
        $course = $this->getEventCourse($event);
        $files = $request->files->all('files');
        if ([] === $files) {
            throw new BadRequestHttpException('At least one attachment is required.');
        }

        $comment = mb_substr(trim((string) $request->request->get('comment', '')), 0, 10000);

        /** @var list<array{file: UploadedFile, filename: string}> $validatedFiles */
        $validatedFiles = [];
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                throw new BadRequestHttpException('An attachment could not be uploaded.');
            }

            $policy = $this->uploadFilenamePolicy->filter($file->getClientOriginalName());
            if (false === $policy['allowed']) {
                throw new BadRequestHttpException('File upload failed: this file extension or file type is prohibited.');
            }

            $validatedFiles[] = ['file' => $file, 'filename' => $this->normalizeFilename((string) $policy['filename'])];
        }

        $created = [];
        foreach ($validatedFiles as $validatedFile) {
            $attachment = $this->entityManager->wrapInTransaction(
                function () use ($event, $course, $comment, $validatedFile): CCalendarEventAttachment {
                    $attachment = (new CCalendarEventAttachment())
                        ->setFilename($validatedFile['filename'])
                        ->setComment($comment)
                        ->setEvent($event)
                        ->setParent($event)
                        ->addCourseLink($course, $this->cidReqHelper->getDoctrineSessionEntity())
                    ;

                    $event->addAttachment($attachment);
                    $this->attachmentRepository->create($attachment);
                    $this->attachmentRepository->addFile($attachment, $validatedFile['file']);
                    $this->attachmentRepository->update($attachment);

                    return $attachment;
                }
            );

            $created[] = [
                'id' => (int) $attachment->getIid(),
                'filename' => $attachment->getFilename(),
                'comment' => (string) $attachment->getComment(),
            ];
        }

        return new JsonResponse(['attachments' => $created], Response::HTTP_CREATED);
    }

    #[Route(
        '/api/calendar-events/{eventId}/attachments/{attachmentId}/download',
        name: 'calendar_event_attachment_download',
        requirements: ['eventId' => '\d+', 'attachmentId' => '\d+'],
        methods: ['GET'],
    )]
    public function download(int $eventId, int $attachmentId): Response
    {
        $event = $this->getEvent($eventId);
        if (!$this->agendaHelper->canViewEvent($event)) {
            throw new AccessDeniedHttpException();
        }

        $attachment = $this->getAttachment($event, $attachmentId);
        if (!$attachment->getResourceNode()?->hasResourceFile()) {
            throw new NotFoundHttpException('The attachment file was not found.');
        }

        try {
            $content = $this->attachmentRepository->getResourceFileContent($attachment);
        } catch (Throwable $throwable) {
            throw new NotFoundHttpException('The attachment file was not found.', $throwable);
        }

        $filename = $attachment->getFilename();
        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'attachment',
        );

        return new Response($content, Response::HTTP_OK, [
            'Content-Disposition' => $disposition,
            'Content-Length' => (string) \strlen($content),
            'Cache-Control' => 'private, no-store',
            'Content-Type' => $attachment->getResourceNode()?->getFirstResourceFile()?->getMimeType() ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    #[Route(
        '/api/calendar-events/{eventId}/attachments/{attachmentId}',
        name: 'calendar_event_attachment_delete',
        requirements: ['eventId' => '\d+', 'attachmentId' => '\d+'],
        methods: ['DELETE'],
    )]
    public function delete(int $eventId, int $attachmentId): JsonResponse
    {
        $event = $this->getEditableEvent($eventId);
        $attachment = $this->getAttachment($event, $attachmentId);

        $event->getAttachments()->removeElement($attachment);
        $resourceNode = $attachment->getResourceNode();
        $this->entityManager->remove($resourceNode ?? $attachment);
        $this->entityManager->flush();

        return new JsonResponse(['attachmentId' => $attachmentId]);
    }

    private function getEvent(int $eventId): CCalendarEvent
    {
        $event = $this->entityManager->find(CCalendarEvent::class, $eventId);
        if (!$event instanceof CCalendarEvent) {
            throw new NotFoundHttpException('The event was not found.');
        }

        return $event;
    }

    private function getEditableEvent(int $eventId): CCalendarEvent
    {
        $event = $this->getEvent($eventId);
        if (!$this->security->isGranted('EDIT', $event)) {
            throw new AccessDeniedHttpException();
        }

        return $event;
    }

    /**
     * The course of the request, which the event must belong to: as in the legacy agenda, only
     * course events have attachments.
     */
    private function getEventCourse(CCalendarEvent $event): Course
    {
        $course = $this->cidReqHelper->getDoctrineCourseEntity();
        if (null === $course) {
            throw new BadRequestHttpException('Only course events have attachments.');
        }

        foreach ($event->getResourceNode()?->getResourceLinks() ?? [] as $link) {
            if ($link->getCourse()?->getId() === $course->getId()) {
                return $course;
            }
        }

        throw new BadRequestHttpException('The event does not belong to the course.');
    }

    private function getAttachment(CCalendarEvent $event, int $attachmentId): CCalendarEventAttachment
    {
        foreach ($event->getAttachments() as $attachment) {
            if ($attachmentId === $attachment->getIid()) {
                return $attachment;
            }
        }

        throw new NotFoundHttpException('The attachment was not found.');
    }

    private function normalizeFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', trim(str_replace(["\r", "\n", "\0"], '', $filename))));
        if ('' === $filename || '.' === $filename || '..' === $filename) {
            return 'attachment';
        }

        return mb_substr($filename, 0, 255);
    }
}
