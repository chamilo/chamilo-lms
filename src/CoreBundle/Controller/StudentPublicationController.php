<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Repository\TrackEDefaultRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationCorrection;
use Chamilo\CourseBundle\Repository\CStudentPublicationCorrectionRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use ZipArchive;

use const PATHINFO_FILENAME;

#[Route('/assignments')]
class StudentPublicationController extends AbstractController
{
    public function __construct(
        private readonly CStudentPublicationRepository $studentPublicationRepo,
        private readonly CidReqHelper $cidReqHelper
    ) {}

    #[Route('/student', name: 'chamilo_core_assignment_student_list', methods: ['GET'])]
    public function getStudentAssignments(SerializerInterface $serializer): JsonResponse
    {
        $course = $this->cidReqHelper->getCourseEntity();
        $session = $this->cidReqHelper->getSessionEntity();

        $assignments = $this->studentPublicationRepo->findVisibleAssignmentsForStudent($course, $session);

        $data = array_map(function ($row) use ($serializer) {
            $publication = $row[0] ?? null;
            $commentsCount = (int) ($row['commentsCount'] ?? 0);
            $correctionsCount = (int) ($row['correctionsCount'] ?? 0);
            $lastUpload = $row['lastUpload'] ?? null;

            $item = json_decode($serializer->serialize($publication, 'json', [
                'groups' => ['student_publication:read'],
            ]), true);

            $item['commentsCount'] = $commentsCount;
            $item['feedbackCount'] = $correctionsCount;
            $item['lastUpload'] = $lastUpload;

            return $item;
        }, $assignments);

        return new JsonResponse([
            'hydra:member' => $data,
            'hydra:totalItems' => \count($data),
        ]);
    }

    #[Route('/progress', name: 'chamilo_core_assignment_student_progress', methods: ['GET'])]
    public function getStudentProgress(
        SerializerInterface $serializer
    ): JsonResponse {
        $course = $this->cidReqHelper->getCourseEntity();
        $session = $this->cidReqHelper->getSessionEntity();

        $progressList = $this->studentPublicationRepo->findStudentProgressByCourse($course, $session);

        return new JsonResponse([
            'hydra:member' => $progressList,
            'hydra:totalItems' => \count($progressList),
        ]);
    }

    #[Route('/{assignmentId}/submissions', name: 'chamilo_core_assignment_student_submission_list', methods: ['GET'])]
    public function getAssignmentSubmissions(
        int $assignmentId,
        Request $request,
        SerializerInterface $serializer,
        CStudentPublicationRepository $repo,
        Security $security
    ): JsonResponse {
        /** @var User $user */
        $user = $security->getUser();

        $page = (int) $request->query->get('page', 1);
        $itemsPerPage = (int) $request->query->get('itemsPerPage', 10);
        $order = $request->query->all('order');

        [$submissions, $total] = $repo->findAssignmentSubmissionsPaginated(
            $assignmentId,
            $user,
            $page,
            $itemsPerPage,
            $order
        );

        $data = json_decode($serializer->serialize(
            $submissions,
            'json',
            ['groups' => ['student_publication:read']]
        ), true);

        return new JsonResponse([
            'hydra:member' => $data,
            'hydra:totalItems' => $total,
        ]);
    }

    #[Route('/{assignmentId}/submissions/teacher', name: 'chamilo_core_assignment_teacher_submission_list', methods: ['GET'])]
    public function getAssignmentSubmissionsForTeacher(
        int $assignmentId,
        Request $request,
        SerializerInterface $serializer,
        CStudentPublicationRepository $repo
    ): JsonResponse {
        $page = (int) $request->query->get('page', 1);
        $itemsPerPage = (int) $request->query->get('itemsPerPage', 10);
        $order = $request->query->all('order');

        [$submissions, $total] = $repo->findAllSubmissionsByAssignment(
            $assignmentId,
            $page,
            $itemsPerPage,
            $order
        );

        $data = json_decode($serializer->serialize(
            $submissions,
            'json',
            ['groups' => ['student_publication:read', 'resource_node:read']]
        ), true);

        return new JsonResponse([
            'hydra:member' => $data,
            'hydra:totalItems' => $total,
        ]);
    }

    #[Route('/submissions/{id}', name: 'chamilo_core_assignment_student_submission_delete', methods: ['DELETE'])]
    public function deleteSubmission(
        int $id,
        EntityManagerInterface $em,
        CStudentPublicationRepository $repo,
        TrackEDefaultRepository $trackRepo
    ): JsonResponse {
        $submission = $repo->find($id);

        if (!$submission) {
            return new JsonResponse(['error' => 'Submission not found.'], 404);
        }

        $this->denyAccessUnlessGranted('DELETE', $submission->getResourceNode());

        $resourceNode = $submission->getResourceNode();
        if ($resourceNode) {
            $trackRepo->registerResourceEvent($resourceNode, 'deletion');
        }

        $em->remove($resourceNode);
        $em->flush();

        return new JsonResponse(null, 204);
    }

    #[Route('/submissions/{id}/edit', name: 'chamilo_core_assignment_student_submission_edit', methods: ['PATCH'])]
    public function editSubmission(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        CStudentPublicationRepository $repo,
        MessageHelper $messageHelper
    ): JsonResponse {
        $submission = $repo->find($id);

        if (!$submission) {
            return new JsonResponse(['error' => 'Submission not found.'], 404);
        }

        $this->denyAccessUnlessGranted('EDIT', $submission->getResourceNode());

        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;
        $sendMail = $data['sendMail'] ?? false;

        if (null !== $title) {
            $submission->setTitle($title);
        }
        if (null !== $description) {
            $submission->setDescription($description);
        }

        $em->flush();

        if ($sendMail) {
            $user = $submission->getUser();
            if ($user) {
                $messageSubject = \sprintf('Feedback updated for "%s"', $submission->getTitle());
                $messageContent = \sprintf(
                    'There is a new feedback update for your submission "%s". Please check it on the platform.',
                    $submission->getTitle()
                );

                $messageHelper->sendMessageSimple(
                    $user->getId(),
                    $messageSubject,
                    $messageContent,
                    $this->getUser()->getId()
                );
            }
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/submissions/{id}/move', name: 'chamilo_core_assignment_student_submission_move', methods: ['PATCH'])]
    public function moveSubmission(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        CStudentPublicationRepository $repo
    ): JsonResponse {
        $submission = $repo->find($id);

        if (!$submission) {
            return new JsonResponse(['error' => 'Submission not found.'], 404);
        }

        $this->denyAccessUnlessGranted('EDIT', $submission->getResourceNode());

        $data = json_decode($request->getContent(), true);
        $newAssignmentId = $data['newAssignmentId'] ?? null;

        if (!$newAssignmentId) {
            return new JsonResponse(['error' => 'New assignment ID is required.'], 400);
        }

        $newParent = $repo->find($newAssignmentId);

        if (!$newParent) {
            return new JsonResponse(['error' => 'Target assignment not found.'], 404);
        }

        $submission->setPublicationParent($newParent);

        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{assignmentId}/unsubmitted-users', name: 'chamilo_core_assignment_unsubmitted_users', methods: ['GET'])]
    public function getUnsubmittedUsers(
        int $assignmentId,
        SerializerInterface $serializer,
        CStudentPublicationRepository $repo,
        CourseRelUserRepository $courseRelUserRepo
    ): JsonResponse {
        $course = $this->cidReqHelper->getCourseEntity();
        $session = $this->cidReqHelper->getSessionEntity();

        $students = $session
            ? $session->getSessionRelCourseRelUsersByStatus($course, Session::STUDENT)
            : $courseRelUserRepo->findBy([
                'course' => $course,
                'status' => CourseRelUser::STUDENT,
            ]);

        $studentsArray = $students instanceof Collection
            ? $students->toArray()
            : $students;

        $studentIds = array_map(fn ($rel) => $rel->getUser()->getId(), $studentsArray);

        $submittedUserIds = $repo->findUserIdsWithSubmissions($assignmentId);

        $unsubmitted = array_filter(
            $studentsArray,
            fn ($rel) => !\in_array($rel->getUser()->getId(), $submittedUserIds, true)
        );

        $data = array_values(array_map(fn ($rel) => $rel->getUser(), $unsubmitted));

        return $this->json([
            'hydra:member' => $data,
            'hydra:totalItems' => \count($data),
        ], 200, [], ['groups' => ['user:read']]);
    }

    #[Route('/{assignmentId}/unsubmitted-users/email', name: 'chamilo_core_assignment_unsubmitted_users_email', methods: ['POST'])]
    public function emailUnsubmittedUsers(
        int $assignmentId,
        CStudentPublicationRepository $repo,
        MessageHelper $messageHelper,
        Security $security
    ): JsonResponse {
        $course = $this->cidReqHelper->getCourseEntity();
        $session = $this->cidReqHelper->getSessionEntity();

        /** @var User $user */
        $user = $security->getUser();
        $senderId = $user?->getId();

        $students = $session
            ? $session->getSessionRelCourseRelUsersByStatus($course, Session::STUDENT)
            : $course->getStudentSubscriptions();

        $submittedUserIds = $repo->findUserIdsWithSubmissions($assignmentId);

        $unsubmitted = array_filter(
            $students->toArray(),
            fn ($rel) => !\in_array($rel->getUser()->getId(), $submittedUserIds, true)
        );

        foreach ($unsubmitted as $rel) {
            $user = $rel->getUser();
            $messageHelper->sendMessageSimple(
                $user->getId(),
                'You have not submitted your assignment',
                'Please submit your assignment as soon as possible.',
                $senderId
            );
        }

        return new JsonResponse(['success' => true, 'sent' => \count($unsubmitted)]);
    }

    #[Route('/{id}/export/pdf', name: 'chamilo_core_assignment_export_pdf', methods: ['GET'])]
    public function exportPdf(
        int $id,
        Request $request,
        CStudentPublicationRepository $repo
    ): Response {
        $course = $this->cidReqHelper->getCourseEntity();
        $session = $this->cidReqHelper->getSessionEntity();

        $assignment = $repo->find($id);

        if (!$assignment) {
            throw $this->createNotFoundException('Assignment not found');
        }

        [$submissions] = $repo->findAllSubmissionsByAssignment(
            assignmentId: $assignment->getIid(),
            page: 1,
            itemsPerPage: 10000
        );

        $html = $this->renderView('@ChamiloCore/Work/pdf_export.html.twig', [
            'assignment' => $assignment,
            'course' => $course,
            'session' => $session,
            'submissions' => $submissions,
        ]);

        try {
            $mpdf = new Mpdf([
                'tempDir' => api_get_path(SYS_ARCHIVE_PATH).'mpdf/',
            ]);
            $mpdf->WriteHTML($html);

            return new Response(
                $mpdf->Output('', Destination::INLINE),
                200,
                ['Content-Type' => 'application/pdf']
            );
        } catch (MpdfException $e) {
            throw new RuntimeException('Failed to generate PDF: '.$e->getMessage(), 500, $e);
        }
    }

    #[Route('/{assignmentId}/corrections/delete', name: 'chamilo_core_assignment_delete_all_corrections', methods: ['DELETE'])]
    public function deleteAllCorrections(
        int $assignmentId,
        EntityManagerInterface $em,
        CStudentPublicationRepository $repo
    ): JsonResponse {
        $submissions = $repo->findAllSubmissionsByAssignment($assignmentId, 1, 10000)[0];

        $count = 0;

        /** @var CStudentPublication $submission */
        foreach ($submissions as $submission) {
            $correctionNode = $submission->getCorrection();

            if (null !== $correctionNode) {
                $correctionNode = $em->getRepository(ResourceNode::class)->find($correctionNode->getId());
                if ($correctionNode) {
                    $em->remove($correctionNode);
                    $submission->setExtensions(null);
                    $count++;
                }
            }
        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'deleted' => $count,
        ]);
    }

    #[Route('/{assignmentId}/download-package', name: 'chamilo_core_assignment_download_package', methods: ['GET'])]
    public function downloadAssignmentPackage(
        int $assignmentId,
        CStudentPublicationRepository $repo,
        ResourceNodeRepository $resourceNodeRepository
    ): Response {
        $assignment = $repo->find($assignmentId);

        if (!$assignment) {
            throw $this->createNotFoundException('Assignment not found');
        }

        [$submissions] = $repo->findAllSubmissionsByAssignment($assignmentId, 1, 10000);
        $zipPath = api_get_path(SYS_ARCHIVE_PATH).uniqid('assignment_', true).'.zip';
        $zip = new ZipArchive();

        if (true !== $zip->open($zipPath, ZipArchive::CREATE)) {
            throw new RuntimeException('Cannot create zip archive');
        }

        foreach ($submissions as $submission) {
            $resourceNode = $submission->getResourceNode();
            $resourceFile = $resourceNode?->getFirstResourceFile();
            $user = $submission->getUser();
            $sentDate = $submission->getSentDate()?->format('Y-m-d_H-i') ?? 'unknown';

            if ($resourceFile) {
                try {
                    $path = $resourceNodeRepository->getFilename($resourceFile);
                    $content = $resourceNodeRepository->getFileSystem()->read($path);

                    $filename = \sprintf('%s_%s_%s', $sentDate, $user->getUsername(), $resourceFile->getOriginalName());
                    $zip->addFromString($filename, $content);
                } catch (Throwable $e) {
                    continue;
                }
            }
        }

        $zip->close();

        return $this->file($zipPath, $assignment->getTitle().'.zip')->deleteFileAfterSend();
    }

    #[Route('/{assignmentId}/upload-corrections-package', name: 'chamilo_core_assignment_upload_corrections_package', methods: ['POST'])]
    public function uploadCorrectionsPackage(
        int $assignmentId,
        Request $request,
        CStudentPublicationRepository $repo,
        CStudentPublicationCorrectionRepository $correctionRepo,
        EntityManagerInterface $em,
        TranslatorInterface $translator
    ): JsonResponse {
        $file = $request->files->get('file');
        if (!$file || 'zip' !== $file->getClientOriginalExtension()) {
            return new JsonResponse(['error' => 'Invalid file'], 400);
        }

        $folder = uniqid('corrections_', true);
        $destinationDir = api_get_path(SYS_ARCHIVE_PATH).$folder;
        mkdir($destinationDir, 0777, true);

        $zip = new ZipArchive();
        $zip->open($file->getPathname());
        $zip->extractTo($destinationDir);
        $zip->close();

        [$submissions] = $repo->findAllSubmissionsByAssignment($assignmentId, 1, 10000);

        $matchMap = [];
        foreach ($submissions as $submission) {
            $date = $submission->getSentDate()?->format('Y-m-d_H-i') ?? 'unknown';
            $username = $submission->getUser()?->getUsername() ?? 'unknown';
            $title = $this->cleanFilename($submission->getTitle() ?? '');
            $title = preg_replace('/_[a-f0-9]{10,}$/', '', pathinfo($title, PATHINFO_FILENAME));
            $key = \sprintf('%s_%s_%s', $date, $username, $title);
            $matchMap[$key] = $submission;
        }

        $finder = new Finder();
        $finder->files()->in($destinationDir);

        $uploaded = 0;
        $skipped = [];

        foreach ($finder as $foundFile) {
            $filename = $foundFile->getFilename();
            $nameOnly = pathinfo($filename, PATHINFO_FILENAME);
            $nameOnly = preg_replace('/_[a-f0-9]{10,}$/', '', $nameOnly);

            $matched = false;
            foreach ($matchMap as $prefix => $submission) {
                if ($nameOnly === $prefix) {
                    if ($submission->getCorrection()) {
                        $em->remove($submission->getCorrection());
                        $em->flush();
                    }

                    $uploadedFile = new UploadedFile(
                        $foundFile->getRealPath(),
                        $filename,
                        null,
                        null,
                        true
                    );

                    $correction = new CStudentPublicationCorrection();
                    $correction->setTitle($filename);
                    $correction->setUploadFile($uploadedFile);
                    $correction->setParentResourceNode($submission->getResourceNode()->getId());

                    $em->persist($correction);

                    $submission->setExtensions($filename);
                    $submission->setDescription('Correction uploaded');
                    $submission->setQualification(0);
                    $submission->setDateOfQualification(new DateTime());
                    $submission->setAccepted(true);
                    $em->persist($submission);

                    $uploaded++;
                    $matched = true;

                    break;
                }
            }

            if (!$matched) {
                $skipped[] = $filename;
            }
        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'uploaded' => $uploaded,
            'skipped' => \count($skipped),
            'skipped_files' => $skipped,
        ]);
    }

    /**
     * Sanitize filenames.
     */
    private function cleanFilename(string $name): string
    {
        $name = str_replace([':', '\\', '/', '*', '?', '"', '<', '>', '|'], '_', $name);
        $name = preg_replace('/\s+/', '_', $name);
        $name = preg_replace('/[^\w\-\.]/u', '', $name);
        $name = preg_replace('/_+/', '_', $name);

        return trim($name, '_');
    }
}
