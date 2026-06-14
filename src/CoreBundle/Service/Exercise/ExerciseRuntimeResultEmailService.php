<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

use const FILTER_VALIDATE_EMAIL;

final readonly class ExerciseRuntimeResultEmailService
{
    private const STATUS_INCOMPLETE = 'incomplete';
    private const STATUS_PENDING_CORRECTION = 'pending_correction';
    private const STATUS_COMPLETED = 'completed';
    private const VISIBILITY_PUBLISHED = 2;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
        private MailerInterface $mailer,
        private SettingsManager $settingsManager,
        private Environment $twig,
        private TranslatorInterface $translator,
    ) {}

    /**
     * @return array{success: bool, message: string, totalCount: int, sentCount: int, skippedCount: int, failedCount: int, failures: array<int, array<string, mixed>>}
     */
    public function sendReviewedAttempts(int $exerciseId, Request $request, string $node = ''): array
    {
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to email exercise results.');
        }

        $sender = $this->getCurrentUser();
        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $attempts = $this->getReviewedAttempts($quiz, $course, $session, $request);

        return $this->sendAttempts($attempts, $quiz, $course, $session, $sender, $request, $node);
    }

    /**
     * @return array{success: bool, message: string, totalCount: int, sentCount: int, skippedCount: int, failedCount: int, failures: array<int, array<string, mixed>>, recipientId: int|null, recipientName: string, recipientEmail: string}
     */
    public function sendAttempt(int $exerciseId, int $attemptId, Request $request, string $node = ''): array
    {
        if (0 >= $exerciseId || 0 >= $attemptId) {
            throw new BadRequestHttpException('A valid exercise and attempt are required.');
        }

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to email this exercise attempt.');
        }

        $sender = $this->getCurrentUser();
        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $attempt = $this->getAttempt($attemptId, $quiz, $course, $session);
        $result = $this->sendAttempts([$attempt], $quiz, $course, $session, $sender, $request, $node);
        $recipient = $attempt->getUser();

        return $result + [
            'recipientId' => (int) $recipient->getId(),
            'recipientName' => $recipient->getFullName(),
            'recipientEmail' => $recipient->getEmail(),
        ];
    }

    /**
     * @param array<int, TrackEExercise> $attempts
     *
     * @return array{success: bool, message: string, totalCount: int, sentCount: int, skippedCount: int, failedCount: int, failures: array<int, array<string, mixed>>}
     */
    private function sendAttempts(
        array $attempts,
        CQuiz $quiz,
        Course $course,
        ?Session $session,
        User $sender,
        Request $request,
        string $node,
    ): array {
        $sentCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $failures = [];

        foreach ($attempts as $attempt) {
            $recipient = $attempt->getUser();
            $recipientEmail = trim($recipient->getEmail());
            if ('' === $recipientEmail || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                ++$skippedCount;
                $failures[] = [
                    'attemptId' => (int) $attempt->getExeId(),
                    'recipientId' => (int) $recipient->getId(),
                    'reason' => 'Invalid recipient email',
                ];
                continue;
            }

            try {
                $this->sendAttemptEmail($attempt, $quiz, $course, $session, $sender, $request, $node);
                ++$sentCount;
            } catch (\Throwable $exception) {
                ++$failedCount;
                $failures[] = [
                    'attemptId' => (int) $attempt->getExeId(),
                    'recipientId' => (int) $recipient->getId(),
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        $totalCount = \count($attempts);
        $success = 0 === $totalCount || 0 < $sentCount;
        $message = match (true) {
            0 === $totalCount => 'No reviewed attempts found',
            0 < $sentCount && 0 === $failedCount && 0 === $skippedCount => 'Exercise result emails sent',
            0 < $sentCount => 'Exercise result emails sent with warnings',
            default => 'Could not send emails',
        };

        return [
            'success' => $success,
            'message' => $message,
            'totalCount' => $totalCount,
            'sentCount' => $sentCount,
            'skippedCount' => $skippedCount,
            'failedCount' => $failedCount,
            'failures' => $failures,
        ];
    }

    private function sendAttemptEmail(
        TrackEExercise $attempt,
        CQuiz $quiz,
        Course $course,
        ?Session $session,
        User $sender,
        Request $request,
        string $node,
    ): void {
        $recipient = $attempt->getUser();
        $resultUrl = $this->buildResultUrl($request, $node, $course, $session, (int) $quiz->getIid(), (int) $attempt->getExeId());
        $body = $this->twig->render('@ChamiloCore/Mailer/Exercise/result_alert_body.html.twig', [
            'course_title' => $this->getCourseTitle($course),
            'test_title' => $quiz->getTitle(),
            'url' => $resultUrl,
            'teacher_name' => $sender->getFullName(),
        ]);

        $email = (new Email())
            ->from($this->getFromAddress($request))
            ->to(new Address($recipient->getEmail(), $this->getUserDisplayName($recipient)))
            ->subject($this->translator->trans('Corrected test result'))
            ->html($body)
            ->text($this->htmlToText($body))
        ;

        $senderEmail = trim($sender->getEmail());
        if (filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            $email->replyTo(new Address($senderEmail, $this->getUserDisplayName($sender)));
        }

        $this->mailer->send($email);
    }

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if (0 >= $courseId) {
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
        if (0 >= $sessionId) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function getExerciseFromCurrentContext(int $exerciseId, Course $course, ?Session $session): CQuiz
    {
        $quiz = $this->quizRepository->find($exerciseId);
        if (!$quiz instanceof CQuiz) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz.iid')
            ->addSelect('links.visibility AS linkVisibility')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('quiz.iid = :exerciseId')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(links.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        $row = $queryBuilder->getQuery()->getOneOrNullResult();
        if (null === $row) {
            throw new AccessDeniedHttpException('The requested exercise does not belong to the current course context.');
        }

        $visibility = \is_array($row) ? (int) ($row['linkVisibility'] ?? 0) : 0;
        if (0 !== $visibility && self::VISIBILITY_PUBLISHED !== $visibility && !$this->canManageExercises()) {
            throw new AccessDeniedHttpException('The requested exercise is not visible.');
        }

        return $quiz;
    }

    private function getAttempt(int $attemptId, CQuiz $quiz, Course $course, ?Session $session): TrackEExercise
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt', 'user')
            ->from(TrackEExercise::class, 'attempt')
            ->innerJoin('attempt.user', 'user')
            ->andWhere('attempt.exeId = :attemptId')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        $attempt = $queryBuilder->getQuery()->getOneOrNullResult();
        if (!$attempt instanceof TrackEExercise) {
            throw new NotFoundHttpException('The requested attempt was not found.');
        }

        return $attempt;
    }

    /**
     * @return array<int, TrackEExercise>
     */
    private function getReviewedAttempts(CQuiz $quiz, Course $course, ?Session $session, Request $request): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT attempt', 'user')
            ->from(TrackEExercise::class, 'attempt')
            ->innerJoin('attempt.user', 'user')
            ->innerJoin('attempt.revisedAttempts', 'qualify', 'WITH', 'qualify.author > 0')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('attempt.exeDate', 'DESC')
            ->addOrderBy('attempt.exeId', 'DESC')
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        $firstName = trim((string) $request->query->get('firstName', ''));
        if ('' !== $firstName) {
            $queryBuilder
                ->andWhere('LOWER(user.firstname) LIKE :firstName')
                ->setParameter('firstName', '%'.mb_strtolower($firstName).'%', Types::STRING)
            ;
        }

        $lastName = trim((string) $request->query->get('lastName', ''));
        if ('' !== $lastName) {
            $queryBuilder
                ->andWhere('LOWER(user.lastname) LIKE :lastName')
                ->setParameter('lastName', '%'.mb_strtolower($lastName).'%', Types::STRING)
            ;
        }

        $status = trim((string) $request->query->get('status', ''));
        if (self::STATUS_PENDING_CORRECTION === $status) {
            $queryBuilder->andWhere("attempt.questionsToCheck <> ''");
        } elseif (self::STATUS_INCOMPLETE === $status) {
            $queryBuilder
                ->andWhere('attempt.status = :status')
                ->setParameter('status', self::STATUS_INCOMPLETE, Types::STRING)
            ;
        } elseif (self::STATUS_COMPLETED === $status) {
            $queryBuilder
                ->andWhere('attempt.status = :status')
                ->andWhere("attempt.questionsToCheck = ''")
                ->setParameter('status', self::STATUS_COMPLETED, Types::STRING)
            ;
        }

        $attempts = [];
        foreach ($queryBuilder->getQuery()->getResult() as $attempt) {
            if ($attempt instanceof TrackEExercise) {
                $attempts[] = $attempt;
            }
        }

        return $attempts;
    }

    private function buildResultUrl(
        Request $request,
        string $node,
        Course $course,
        ?Session $session,
        int $exerciseId,
        int $attemptId,
    ): string {
        $safeNode = trim($node);
        if ('' === $safeNode) {
            $referer = (string) $request->headers->get('referer', '');
            if (preg_match('#/resources/exercise/([^/]+)/#', $referer, $matches)) {
                $safeNode = rawurldecode((string) $matches[1]);
            }
        }

        if ('' === $safeNode) {
            $safeNode = (string) $exerciseId;
        }

        $query = [
            'cid' => (int) $course->getId(),
            'gid' => $request->query->getInt('gid'),
        ];

        if (null !== $session) {
            $query['sid'] = (int) $session->getId();
        }

        $baseUrl = rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');

        return sprintf(
            '%s/resources/exercise/%s/%d/result/%d?%s',
            $baseUrl,
            rawurlencode($safeNode),
            $exerciseId,
            $attemptId,
            http_build_query($query),
        );
    }

    private function getFromAddress(Request $request): Address
    {
        $fromEmail = trim((string) $this->settingsManager->getSetting('mail.mailer_from_email', true));
        $fromName = trim((string) $this->settingsManager->getSetting('mail.mailer_from_name', true));

        if ('' === $fromName) {
            $fromName = trim((string) $this->settingsManager->getSetting('platform.site_name', true));
        }

        if ('' === $fromName) {
            $fromName = 'Chamilo';
        }

        if ('' === $fromEmail || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $fromEmail = trim((string) $this->settingsManager->getSetting('admin.administrator_email', true));
        }

        if ('' === $fromEmail || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $host = preg_replace('/[^A-Za-z0-9.-]+/', '', $request->getHost()) ?: 'example.org';
            $fromEmail = 'noreply@'.$host;
        }

        return new Address($fromEmail, $fromName);
    }

    private function getUserDisplayName(User $user): string
    {
        $name = trim($user->getFullName());
        if ('' !== $name) {
            return $name;
        }

        return $user->getUsername();
    }

    private function getCourseTitle(Course $course): string
    {
        if (method_exists($course, 'getTitle')) {
            return (string) $course->getTitle();
        }

        if (method_exists($course, 'getName')) {
            return (string) $course->getName();
        }

        if (method_exists($course, 'getCode')) {
            return (string) $course->getCode();
        }

        return 'Course';
    }

    private function htmlToText(string $html): string
    {
        return trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
