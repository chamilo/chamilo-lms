<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Mobile;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Mobile\MobileAssignmentSubmission;
use Chamilo\CoreBundle\ApiResource\Mobile\MobileAssignmentSubmissionInput;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Security\Upload\UploadFilenamePolicy;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use finfo;
use LogicException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const FILEINFO_MIME_TYPE;
use const PATHINFO_EXTENSION;

/**
 * @implements ProcessorInterface<MobileAssignmentSubmissionInput, MobileAssignmentSubmission>
 */
final readonly class MobileAssignmentSubmissionProcessor implements ProcessorInterface
{
    private const int MAX_FILE_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private CStudentPublicationRepository $studentPublicationRepository,
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
        private Security $security,
        private UserHelper $userHelper,
        private UploadFilenamePolicy $uploadFilenamePolicy,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): MobileAssignmentSubmission {
        if (!$operation instanceof Post || !$data instanceof MobileAssignmentSubmissionInput) {
            throw new LogicException('Unsupported mobile assignment submission operation.');
        }

        $user = $this->userHelper->getCurrent();

        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedHttpException('An authenticated student is required.');
        }

        $course = $this->entityManager->find(Course::class, $data->courseId);

        if (!$course instanceof Course || !$this->security->isGranted(CourseVoter::VIEW, $course)) {
            throw new AccessDeniedHttpException('You do not have access to this course.');
        }

        $session = $this->resolveSession($data->sessionId);
        $this->assertStudentSubscribed($user, $course, $session);

        $assignment = $this->resolveVisibleAssignment(
            $data->assignmentId,
            $course,
            $session,
        );

        $endsOn = $assignment->getAssignment()?->getEndsOn();

        if (null !== $endsOn && new DateTime() > $endsOn) {
            throw new UnprocessableEntityHttpException('The submission deadline has passed.');
        }

        $this->assertSingleSubmissionRule($assignment, $user);

        $title = trim(strip_tags($data->title));

        if ('' === $title) {
            throw new UnprocessableEntityHttpException('A submission title is required.');
        }

        [$description, $fileName, $mimeType, $fileContent] = 'file' === $data->kind
            ? $this->prepareFileSubmission($assignment, $data)
            : $this->prepareTextSubmission($assignment, $data, $title);

        $submission = (new CStudentPublication())
            ->setTitle($title)
            ->setDescription($description)
            ->setFiletype('file')
            ->setContainsFile(1)
            ->setAccepted(true)
            ->setActive(1)
            ->setSentDate(new DateTime())
            ->setUser($user)
            ->setPublicationParent($assignment)
            ->setParent($assignment)
            ->addCourseLink($course, $session)
        ;

        $this->studentPublicationRepository->create($submission);

        $file = $this->studentPublicationRepository->addFileFromString(
            $submission,
            $fileName,
            $mimeType,
            $fileContent,
        );

        if (null === $file) {
            throw new RuntimeException('The assignment submission file could not be created.');
        }

        return MobileAssignmentSubmission::fromSubmission($submission);
    }

    /**
     * @return array{string, string, string, string}
     */
    private function prepareTextSubmission(
        CStudentPublication $assignment,
        MobileAssignmentSubmissionInput $data,
        string $title,
    ): array {
        if (!\in_array($assignment->getAllowTextAssignment(), [0, 1], true)) {
            throw new UnprocessableEntityHttpException('Text submissions are not enabled for this assignment.');
        }

        $plainText = trim($data->text);

        if ('' === $plainText) {
            throw new UnprocessableEntityHttpException('Submission text is required.');
        }

        return [
            nl2br(htmlspecialchars($plainText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
            $this->buildTextFileName($title),
            'text/plain',
            $plainText,
        ];
    }

    /**
     * @return array{string, string, string, string}
     */
    private function prepareFileSubmission(
        CStudentPublication $assignment,
        MobileAssignmentSubmissionInput $data,
    ): array {
        if (!\in_array($assignment->getAllowTextAssignment(), [0, 2], true)) {
            throw new UnprocessableEntityHttpException('File submissions are not enabled for this assignment.');
        }

        $fileName = $this->sanitizeFileName($data->fileName ?? '');
        $encodedContent = trim($data->base64Content ?? '');

        if ('' === $fileName || '' === $encodedContent) {
            throw new UnprocessableEntityHttpException('A file is required.');
        }

        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = $this->parseAllowedExtensions($assignment->getExtensions());

        if ([] !== $allowedExtensions && !\in_array($extension, $allowedExtensions, true)) {
            throw new UnprocessableEntityHttpException('This file extension is not allowed for the assignment.');
        }

        $filenamePolicy = $this->uploadFilenamePolicy->filter($fileName);

        if (false === ($filenamePolicy['allowed'] ?? false)) {
            throw new UnprocessableEntityHttpException('This file type is prohibited by the campus upload policy.');
        }

        $fileName = $filenamePolicy['filename'];

        if (str_contains($encodedContent, ',')) {
            [, $encodedContent] = explode(',', $encodedContent, 2);
        }

        $decodedContent = base64_decode($encodedContent, true);

        if (false === $decodedContent || '' === $decodedContent) {
            throw new UnprocessableEntityHttpException('The uploaded file could not be decoded.');
        }

        if (\strlen($decodedContent) > self::MAX_FILE_BYTES) {
            throw new UnprocessableEntityHttpException('The uploaded file exceeds the mobile upload limit.');
        }

        $mimeType = trim($data->mimeType ?? '');

        if (class_exists(finfo::class)) {
            $detectedMimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($decodedContent);

            if (\is_string($detectedMimeType) && '' !== $detectedMimeType) {
                $mimeType = $detectedMimeType;
            }
        }

        if ('' === $mimeType) {
            $mimeType = 'application/octet-stream';
        }

        return ['', $fileName, $mimeType, $decodedContent];
    }

    private function resolveSession(?int $sessionId): ?Session
    {
        if (null === $sessionId) {
            return null;
        }

        $session = $this->entityManager->find(Session::class, $sessionId);

        if (!$session instanceof Session) {
            throw new NotFoundHttpException('Session not found.');
        }

        return $session;
    }

    private function assertStudentSubscribed(
        User $user,
        Course $course,
        ?Session $session,
    ): void {
        if ($session instanceof Session) {
            $subscription = $this->entityManager
                ->getRepository(SessionRelCourseRelUser::class)
                ->findOneBy([
                    'user' => $user,
                    'course' => $course,
                    'session' => $session,
                    'status' => Session::STUDENT,
                ])
            ;
        } else {
            $subscription = $this->entityManager
                ->getRepository(CourseRelUser::class)
                ->findOneBy([
                    'user' => $user,
                    'course' => $course,
                    'status' => CourseRelUser::STUDENT,
                ])
            ;
        }

        if (null === $subscription) {
            throw new AccessDeniedHttpException('You are not enrolled in this course context.');
        }
    }

    private function resolveVisibleAssignment(
        int $assignmentId,
        Course $course,
        ?Session $session,
    ): CStudentPublication {
        foreach (
            $this->studentPublicationRepository->findVisibleAssignmentsForStudent(
                $course,
                $session,
            ) as $row
        ) {
            $assignment = \is_array($row) ? ($row[0] ?? null) : $row;

            if (
                $assignment instanceof CStudentPublication
                && $assignment->getIid() === $assignmentId
            ) {
                return $assignment;
            }
        }

        throw new NotFoundHttpException('Assignment not found in the current course context.');
    }

    private function assertSingleSubmissionRule(
        CStudentPublication $assignment,
        User $user,
    ): void {
        if ('true' !== $this->settingsManager->getSetting('work.allow_only_one_student_publication_per_user')) {
            return;
        }

        $existingCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(submission.iid)')
            ->from(CStudentPublication::class, 'submission')
            ->where('IDENTITY(submission.publicationParent) = :assignmentId')
            ->andWhere('IDENTITY(submission.user) = :userId')
            ->andWhere('submission.active IN (0, 1)')
            ->setParameter('assignmentId', $assignment->getIid())
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if ($existingCount > 0) {
            throw new AccessDeniedHttpException('You have already submitted work for this assignment.');
        }
    }

    private function buildTextFileName(string $title): string
    {
        $baseName = preg_replace('/[^A-Za-z0-9._-]+/u', '-', $title) ?? '';
        $baseName = trim($baseName, '-_.');

        if ('' === $baseName) {
            $baseName = 'submission';
        }

        return mb_strimwidth($baseName, 0, 120, '').'.txt';
    }

    private function sanitizeFileName(string $fileName): string
    {
        $fileName = basename(str_replace('\\', '/', trim($fileName)));
        $fileName = preg_replace('/[^A-Za-z0-9._ -]+/u', '-', $fileName) ?? '';
        $fileName = trim($fileName, " .-_\t\n\r\0\x0B");

        return mb_strimwidth($fileName, 0, 180, '');
    }

    /**
     * @return string[]
     */
    private function parseAllowedExtensions(?string $extensions): array
    {
        if (null === $extensions || '' === trim($extensions)) {
            return [];
        }

        $values = preg_split('/[,;\s]+/', strtolower($extensions)) ?: [];
        $values = array_map(static fn (string $value): string => ltrim(trim($value), '.'), $values);

        return array_values(array_unique(array_filter($values)));
    }
}
