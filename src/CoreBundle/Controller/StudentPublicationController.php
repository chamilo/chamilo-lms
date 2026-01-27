<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\AiProvider\AiTaskGraderService;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Repository\TrackEDefaultRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
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
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use ZipArchive;

use const ENT_HTML5;
use const ENT_QUOTES;
use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;

#[IsGranted('ROLE_USER')]
#[Route('/assignments')]
class StudentPublicationController extends AbstractController
{
    public function __construct(
        private readonly CStudentPublicationRepository $studentPublicationRepo,
        private readonly CidReqHelper $cidReqHelper
    ) {}

    #[Route('/student', name: 'chamilo_core_assignment_student_list', methods: ['GET'])]
    public function getStudentAssignments(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $course = $this->cidReqHelper->getCourseEntity();
        $session = $this->cidReqHelper->getSessionEntity();
        $gid = $request->query->getInt('gid', 0);

        $assignments = $this->studentPublicationRepo->findVisibleAssignmentsForStudent($course, $session, $gid);

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
            throw $this->createNotFoundException('Assignment not found.');
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
                } catch (Throwable) {
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

    #[Route('/ai/text-providers', name: 'chamilo_core_assignment_ai_text_providers', methods: ['GET'])]
    public function getAiTextProviders(
        SettingsManager $settingsManager,
        AiProviderFactory $aiProviderFactory,
        KernelInterface $kernel
    ): JsonResponse {
        $configJson = $settingsManager->getSetting('ai_helpers.ai_providers', true);

        $config = \is_string($configJson)
            ? (json_decode($configJson, true) ?? [])
            : (\is_array($configJson) ? $configJson : []);

        $providers = [];

        foreach ($config as $name => $cfg) {
            if (!\is_string($name) || !\is_array($cfg)) {
                continue;
            }

            // Only list providers that declare "text"
            if (!isset($cfg['text']) || !\is_array($cfg['text'])) {
                continue;
            }

            // Extra safety: only list providers that can be instantiated
            try {
                $aiProviderFactory->create($name);
                $providers[] = $name;
            } catch (Throwable $e) {
                // In debug, log why provider was not listed
                if ($kernel->isDebug()) {
                    error_log('[Assignments][AI][providers] Skipping provider "'.$name.'": '.$e->getMessage());
                }

                continue;
            }
        }

        sort($providers);

        return new JsonResponse(['providers' => $providers]);
    }

    #[Route('/submissions/{id}/ai-task-grader-default-prompt', name: 'chamilo_core_assignment_ai_task_grader_default_prompt', methods: ['GET'])]
    public function getAiTaskGraderDefaultPrompt(
        int $id,
        Request $request,
        CStudentPublicationRepository $repo
    ): JsonResponse {
        $submission = $repo->find($id);
        if (!$submission) {
            return new JsonResponse(['error' => 'Submission not found.'], 404);
        }

        // Only editors should request the prompt (same rule as grading).
        $this->denyAccessUnlessGranted('EDIT', $submission->getResourceNode());

        $language = (string) ($request->query->get('language') ?? 'en');

        return new JsonResponse([
            'prompt' => $this->buildDefaultTaskGraderPrompt($submission, $language),
        ]);
    }

    #[Route('/submissions/{id}/ai-task-grade-capabilities', name: 'chamilo_core_assignment_ai_task_grade_capabilities', methods: ['GET'])]
    public function aiTaskGradeCapabilities(
        int $id,
        CStudentPublicationRepository $repo,
        ResourceNodeRepository $resourceNodeRepository
    ): JsonResponse {
        $submission = $repo->find($id);
        if (!$submission) {
            return new JsonResponse(['success' => false, 'message' => 'Submission not found.'], 404);
        }

        $this->denyAccessUnlessGranted('EDIT', $submission->getResourceNode());

        $studentText = trim((string) ($submission->getDescription() ?? ''));
        $hasText = '' !== $this->toPlainText($studentText);

        $meta = $this->getSubmissionFileMeta($submission, $resourceNodeRepository);

        $cap = [
            'success' => true,
            'hasText' => $hasText,
            'hasFile' => (bool) ($meta['hasFile'] ?? false),
            'filename' => (string) ($meta['filename'] ?? ''),
            'mimeType' => (string) ($meta['mimeType'] ?? ''),
            'extension' => (string) ($meta['extension'] ?? ''),
            'fileSize' => (int) ($meta['fileSize'] ?? 0),
            'documentSupported' => false,
            'textFileSupported' => false,
            'docxSupported' => false,
            'recommendedMode' => 'text',
            'reason' => '',
        ];

        if (!$cap['hasFile']) {
            $cap['recommendedMode'] = $hasText ? 'text' : 'text';
            $cap['reason'] = $hasText
                ? 'No file attached. AI will grade the text submission.'
                : 'No file attached and submission text is empty. AI will have little to grade.';

            return new JsonResponse($cap);
        }

        $filename = $cap['filename'];
        $mimeType = $cap['mimeType'];

        if ($this->isPdfForDocumentProcess($filename, $mimeType)) {
            $cap['documentSupported'] = true;
            $cap['recommendedMode'] = 'document';
            $cap['reason'] = 'PDF detected. AI will process the document.';

            return new JsonResponse($cap);
        }

        if ($this->isDocxFile($filename, $mimeType)) {
            $cap['docxSupported'] = true;
            $cap['recommendedMode'] = 'text';
            $cap['reason'] = 'DOCX detected. AI will extract the text content and grade it as text.';

            return new JsonResponse($cap);
        }

        if ($this->isPlainTextFile($filename, $mimeType)) {
            $cap['textFileSupported'] = true;
            $cap['recommendedMode'] = 'text';
            $cap['reason'] = 'Text-based file detected. AI will read the content and grade it as text.';

            return new JsonResponse($cap);
        }

        if ($this->isImageFile($filename, $mimeType)) {
            $cap['recommendedMode'] = $hasText ? 'text' : 'text';
            $cap['reason'] = 'Image detected. Image grading is not supported by the document processor. Please upload a PDF or paste text.';

            return new JsonResponse($cap);
        }

        $cap['recommendedMode'] = $hasText ? 'text' : 'text';
        $cap['reason'] = 'Unsupported file type. Please upload a PDF (recommended) or a text-based file.';

        return new JsonResponse($cap);
    }

    #[Route('/submissions/{id}/ai-task-grade', name: 'chamilo_core_assignment_ai_task_grade', methods: ['POST'])]
    public function aiTaskGrade(
        int $id,
        Request $request,
        CStudentPublicationRepository $repo,
        AiTaskGraderService $aiTaskGraderService
    ): JsonResponse {
        $submission = $repo->find($id);
        if (!$submission) {
            return new JsonResponse(['success' => false, 'message' => 'Submission not found.'], 404);
        }

        $this->denyAccessUnlessGranted('EDIT', $submission->getResourceNode());

        $teacher = $this->getUser();
        if (!$teacher instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'User is not authenticated.'], 401);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $providerName = trim((string) ($data['ai_provider'] ?? ''));
        $language = trim((string) ($data['language'] ?? 'en'));
        $requestedMode = trim((string) ($data['mode'] ?? 'auto')); // auto|text|document
        $userPrompt = trim((string) ($data['prompt'] ?? ''));

        if ('' === $providerName) {
            return new JsonResponse(['success' => false, 'message' => 'Missing ai_provider.'], 400);
        }

        $result = $aiTaskGraderService->gradeSubmission(
            submission: $submission,
            teacher: $teacher,
            options: [
                'ai_provider' => $providerName,
                'language' => $language,
                'mode' => $requestedMode,
                'prompt' => $userPrompt,
                'provider_options' => \is_array($data['options'] ?? null) ? $data['options'] : [],
                'teacher_notes' => (string) ($data['teacher_notes'] ?? ''),
                'rubric' => (string) ($data['rubric'] ?? ''),
            ]
        );

        $status = (int) ($result['httpStatus'] ?? 200);

        // Normalize payload (keep UI contract)
        if (true !== ($result['success'] ?? false)) {
            return new JsonResponse([
                'success' => false,
                'message' => (string) ($result['message'] ?? 'AI grading failed.'),
                'mode' => (string) ($result['mode'] ?? $requestedMode),
            ], $status);
        }

        return new JsonResponse([
            'success' => true,
            'feedback' => (string) ($result['feedback'] ?? ''),
            'suggestedScore' => $result['suggestedScore'] ?? null,
            'mode' => (string) ($result['mode'] ?? $requestedMode),
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

    private function buildDefaultTaskGraderPrompt(CStudentPublication $submission, string $language): string
    {
        $language = '' !== trim($language) ? $language : 'en';

        $max = $this->getMaxScore($submission);
        $maxText = null !== $max ? (string) $max : 'N/A';

        return \sprintf(
            "You are an assignment grader.\n".
            "Language: %s.\n".
            "Provide constructive feedback and actionable improvements.\n".
            "At the end, add a final line exactly like: SCORE: <number> (0 to %s).\n".
            'Return plain text only.',
            $language,
            $maxText
        );
    }

    private function buildTaskGraderContextBlock(
        CStudentPublication $submission,
        string $studentText,
        string $fileText,
        bool $hasFile
    ): string {
        $assignment = $submission->getPublicationParent();
        $assignmentTitle = $assignment?->getTitle() ?? 'Assignment';
        $assignmentInstructions = $this->toPlainText((string) ($assignment?->getDescription() ?? ''));

        $max = $this->getMaxScore($submission);

        $lines = [];
        $lines[] = 'ASSIGNMENT TITLE: '.$assignmentTitle;
        $lines[] = 'ASSIGNMENT INSTRUCTIONS:'.("\n".('' !== $assignmentInstructions ? $assignmentInstructions : '(none)'));
        if (null !== $max) {
            $lines[] = 'MAX SCORE: '.$max;
        }

        if ('' !== trim($studentText)) {
            $lines[] = 'STUDENT SUBMISSION (TEXT):'.("\n".$studentText);
        } else {
            $lines[] = 'STUDENT SUBMISSION (TEXT): (empty)';
        }

        if ('' !== trim($fileText)) {
            $lines[] = 'STUDENT SUBMISSION (FILE CONTENT):'.("\n".$this->safeTruncateText($fileText, 12000));
        } elseif ($hasFile) {
            $lines[] = 'STUDENT SUBMISSION (DOCUMENT): An attached file is available.';
        }

        return implode("\n\n", $lines);
    }

    private function getSubmissionFileMeta(CStudentPublication $submission, ResourceNodeRepository $resourceNodeRepository): array
    {
        $node = $submission->getResourceNode();
        if (null === $node) {
            return ['hasFile' => false];
        }

        $rf = $node->getFirstResourceFile();
        if (null === $rf) {
            return ['hasFile' => false];
        }

        $filename = (string) ($rf->getOriginalName() ?? 'submission.bin');
        $mimeType = (string) ($rf->getMimeType() ?? 'application/octet-stream');
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        $size = 0;
        if (method_exists($rf, 'getSize')) {
            $size = (int) ($rf->getSize() ?? 0);
        }

        return [
            'hasFile' => true,
            'filename' => $filename,
            'mimeType' => $mimeType,
            'extension' => $ext,
            'fileSize' => $size,
        ];
    }

    private function isPdfForDocumentProcess(string $filename, string $mimeType): bool
    {
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        if ('pdf' === $ext) {
            return true;
        }

        return 'application/pdf' === strtolower(trim($mimeType));
    }

    private function isDocxFile(string $filename, string $mimeType): bool
    {
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        if ('docx' === $ext) {
            return true;
        }

        return str_contains(strtolower($mimeType), 'officedocument.wordprocessingml.document');
    }

    private function isPlainTextFile(string $filename, string $mimeType): bool
    {
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        $mime = strtolower(trim($mimeType));

        $allowedExt = ['txt', 'md', 'markdown', 'html', 'htm', 'json', 'xml', 'yaml', 'yml', 'csv', 'log', 'ini', 'env'];
        if (\in_array($ext, $allowedExt, true)) {
            return true;
        }

        return str_starts_with($mime, 'text/');
    }

    private function isImageFile(string $filename, string $mimeType): bool
    {
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        if (\in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'], true)) {
            return true;
        }

        return str_starts_with(strtolower(trim($mimeType)), 'image/');
    }

    private function safeTruncateText(string $s, int $maxChars = 12000): string
    {
        $s = trim($s);
        if (mb_strlen($s) <= $maxChars) {
            return $s;
        }

        return mb_substr($s, 0, $maxChars)."\n\n[...truncated...]";
    }

    private function toPlainText(string $html): string
    {
        $s = trim($html);
        if ('' === $s) {
            return '';
        }

        $s = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $s);
        $s = preg_replace('/<\/(p|div|li|h[1-6])\s*>/i', "\n", $s);

        $s = strip_tags($s);
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5);

        $s = preg_replace("/[ \t]+\n/", "\n", $s);
        $s = preg_replace("/\n{3,}/", "\n\n", $s);

        return trim($s);
    }

    private function getMaxScore(CStudentPublication $submission): ?float
    {
        $assignment = $submission->getPublicationParent();
        if (null === $assignment) {
            return null;
        }

        $raw = $assignment->getQualification();
        if (null === $raw || '' === (string) $raw) {
            return null;
        }

        $n = (float) $raw;
        if ($n <= 0) {
            return null;
        }

        return $n;
    }
}
