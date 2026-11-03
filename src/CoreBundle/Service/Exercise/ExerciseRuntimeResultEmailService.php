<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
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
use Throwable;
use Twig\Environment;

use const ENT_HTML5;
use const ENT_QUOTES;
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
        private IsAllowedToEditHelper $isAllowedToEditHelper,
        private CidReqHelper $cidReqHelper,
    ) {}

    /**
     * @return array{success: bool, message: string, totalCount: int, sentCount: int, skippedCount: int, failedCount: int, failures: array<int, array<string, mixed>>}
     */
    public function sendReviewedAttempts(int $exerciseId, Request $request, string $node = ''): array
    {
        if ($exerciseId <= 0) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        if (!$this->isAllowedToEditHelper->check(coach: true)) {
            throw new AccessDeniedHttpException('You are not allowed to email exercise results.');
        }

        $sender = $this->getCurrentUser();
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $attempts = $this->getReviewedAttempts($quiz, $course, $session, $request);

        return $this->sendAttempts($attempts, $quiz, $course, $session, $sender, $request, $node);
    }

    /**
     * @return array{success: bool, message: string, totalCount: int, sentCount: int, skippedCount: int, failedCount: int, failures: array<int, array<string, mixed>>, recipientId: int|null, recipientName: string, recipientEmail: string}
     */
    public function sendAttempt(int $exerciseId, int $attemptId, Request $request, string $node = ''): array
    {
        if ($exerciseId <= 0 || $attemptId <= 0) {
            throw new BadRequestHttpException('A valid exercise and attempt are required.');
        }

        if (!$this->isAllowedToEditHelper->check(coach: true)) {
            throw new AccessDeniedHttpException('You are not allowed to email this exercise attempt.');
        }

        $sender = $this->getCurrentUser();
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
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
     * Default content of the "Send email" field of the correction page, as exercise_show.php
     * pre-filled it: the link leads to the learning path item when the attempt was made in one.
     */
    public function getCorrectionNotificationContent(
        TrackEExercise $attempt,
        CQuiz $quiz,
        Course $course,
        ?Session $session,
        User $sender,
        Request $request,
    ): string {
        $node = (string) $course->getResourceNode()?->getId();
        $lpId = $attempt->getOrigLpId();
        $lpItemId = $attempt->getOrigLpItemId();

        if ($lpId > 0 && $lpItemId > 0) {
            $query = [
                'cid' => (int) $course->getId(),
                'sid' => (int) $session?->getId(),
                'gid' => $request->query->getInt('gid'),
                'item_id' => $lpItemId,
            ];
            $url = \sprintf(
                '%s/resources/lp/%s/%d/runtime?%s',
                rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/'),
                rawurlencode($node),
                $lpId,
                http_build_query($query),
            );
        } else {
            $url = $this->buildResultUrl($request, $node, $course, $session, (int) $quiz->getIid(), (int) $attempt->getExeId());
        }

        // Same content as Mailer/Exercise/result_alert_body.html.twig, built without Twig: this
        // runs from an API Platform provider, before TwigListener has added its globals, and
        // rendering a template that early makes TwigListener fail for the whole request.
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return '<p><strong>'.$escape($this->translator->trans('Dear learner,')).'</strong> '
            .$escape($this->translator->trans('Your following attempt has been viewed/commented/corrected by the trainer')).'</p>'
            .'<p><strong>'.$escape($this->translator->trans('Course name')).': </strong>'.$escape($this->getCourseTitle($course))
            .'<br><strong>'.$escape($this->translator->trans('Test')).': </strong>'.$escape($quiz->getTitle()).'</p>'
            .'<p>'.$escape($this->translator->trans('Click the link below to access  your account and view your commented Examsheet.')).'</p>'
            .'<p><a href="'.$escape($url).'">'.$escape($url).'</a></p>'
            .'<p><strong>'.$escape($this->translator->trans('Regards')).'</strong><br>'.$escape($sender->getFullName()).'</p>';
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
            } catch (Throwable $exception) {
                ++$failedCount;
                $failures[] = [
                    'attemptId' => (int) $attempt->getExeId(),
                    'recipientId' => (int) $recipient->getId(),
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        $totalCount = \count($attempts);
        $success = 0 === $totalCount || $sentCount > 0;
        $message = match (true) {
            0 === $totalCount => 'No reviewed attempts found',
            $sentCount > 0 && 0 === $failedCount && 0 === $skippedCount => 'Exercise result emails sent',
            $sentCount > 0 => 'Exercise result emails sent with warnings',
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

    private function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    private function getExerciseFromCurrentContext(int $exerciseId, Course $course, ?Session $session): CQuiz
    {
        $quiz = $this->quizRepository->find($exerciseId);
        if (!$quiz instanceof CQuiz) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        $context = $this->quizRepository->findInCourseContextWithVisibility($exerciseId, $course, $session, true);
        if (null === $context) {
            throw new AccessDeniedHttpException('The requested exercise does not belong to the current course context.');
        }

        $visibility = $context['visibility'];
        if (0 !== $visibility && self::VISIBILITY_PUBLISHED !== $visibility && !$this->isAllowedToEditHelper->check(coach: true)) {
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
            ->innerJoin('attempt.revisedAttempts', 'qualify', 'WITH', 'qualify.author > 0 AND qualify.final = true')
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

        return \sprintf(
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
