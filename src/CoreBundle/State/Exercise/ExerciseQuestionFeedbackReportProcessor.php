<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionFeedbackReport;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\QuizQuestionAttemptFeedback;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * @implements ProcessorInterface<ExerciseQuestionFeedbackReport, ExerciseQuestionFeedbackReport>
 */
final readonly class ExerciseQuestionFeedbackReportProcessor implements ProcessorInterface
{
    private const int MAX_FEEDBACK_LENGTH = 5000;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): ExerciseQuestionFeedbackReport {
        if (!$data instanceof ExerciseQuestionFeedbackReport) {
            throw new LogicException('Unexpected exercise question feedback payload.');
        }

        $exerciseId = (int) ($uriVariables['exerciseId'] ?? 0);
        $attemptId = (int) ($uriVariables['attemptId'] ?? 0);
        $questionId = (int) ($uriVariables['questionId'] ?? 0);
        if ($exerciseId <= 0 || $attemptId <= 0 || $questionId <= 0) {
            throw new BadRequestHttpException('Exercise, attempt and question identifiers are required.');
        }

        $feedback = trim($data->feedback);
        if ('' === $feedback) {
            throw new BadRequestHttpException('A feedback comment is required.');
        }
        if (mb_strlen($feedback) > self::MAX_FEEDBACK_LENGTH) {
            throw new BadRequestHttpException('The feedback comment is too long.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $quiz = $this->quizRepository->findInCourseContext($exerciseId, $course, $session)
            ?? throw new NotFoundHttpException('The requested exercise was not found.');

        if (!$quiz->isQuestionFeedbackReportsAllowed()) {
            throw new AccessDeniedHttpException('Question feedback reporting is disabled for this exercise.');
        }

        $attempt = $this->getOwnedAttempt($attemptId, $quiz, $course, $session, $user);
        $question = $this->getQuestionFromAttempt($quiz, $attempt, $questionId);

        $report = (new QuizQuestionAttemptFeedback())
            ->setAttempt($attempt)
            ->setQuestion($question)
            ->setFeedback($feedback)
        ;
        $this->entityManager->persist($report);

        $this->createTeacherMessage($report, $quiz, $course, $session, $user);
        $this->entityManager->flush();

        $data->questionId = $questionId;
        $data->feedback = '';
        $data->success = true;
        $data->message = 'Question feedback sent.';

        return $data;
    }

    private function getOwnedAttempt(
        int $attemptId,
        CQuiz $quiz,
        Course $course,
        ?Session $session,
        User $user,
    ): TrackEExercise {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('attempt')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('attempt.exeId = :attemptId')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere('IDENTITY(attempt.user) = :userId')
            ->andWhere('attempt.status = :status')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setParameter('status', 'incomplete', Types::STRING)
            ->setMaxResults(1)
        ;

        if ($session instanceof Session) {
            $qb
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $qb->andWhere('attempt.session IS NULL');
        }

        $attempt = $qb->getQuery()->getOneOrNullResult();
        if (!$attempt instanceof TrackEExercise) {
            throw new NotFoundHttpException('The requested attempt was not found.');
        }

        return $attempt;
    }

    private function getQuestionFromAttempt(CQuiz $quiz, TrackEExercise $attempt, int $questionId): CQuizQuestion
    {
        $attemptQuestionIds = $this->parseQuestionIds((string) $attempt->getDataTracking());
        if (!\in_array($questionId, $attemptQuestionIds, true)) {
            throw new AccessDeniedHttpException('The requested question is not part of this attempt.');
        }

        $relation = $this->entityManager->createQueryBuilder()
            ->select('relation', 'question')
            ->from(CQuizRelQuestion::class, 'relation')
            ->innerJoin('relation.question', 'question')
            ->andWhere('IDENTITY(relation.quiz) = :exerciseId')
            ->andWhere('IDENTITY(relation.question) = :questionId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$relation instanceof CQuizRelQuestion) {
            throw new NotFoundHttpException('The requested question was not found in this exercise.');
        }

        return $relation->getQuestion();
    }

    /**
     * @return array<int, int>
     */
    private function parseQuestionIds(string $value): array
    {
        if ('' === trim($value)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(static fn (string $id): int => (int) trim($id), explode(',', $value))
            )
        );
    }

    private function createTeacherMessage(
        QuizQuestionAttemptFeedback $report,
        CQuiz $quiz,
        Course $course,
        ?Session $session,
        User $sender,
    ): void {
        $recipients = $this->getTeacherRecipients($course, $session, $sender);
        if ([] === $recipients) {
            return;
        }

        $question = $report->getQuestion();
        $questionTitle = trim(strip_tags($question->getQuestion()));
        if ('' === $questionTitle) {
            $questionTitle = 'Question #'.(int) $question->getIid();
        }

        $attemptUrl = $this->buildAttemptUrl($quiz, $course, $session, (int) $report->getAttempt()->getExeId());
        $senderName = trim($sender->getFirstname().' '.$sender->getLastname());
        if ('' === $senderName) {
            $senderName = $sender->getUsername();
        }

        $content = '<p>A learner reported a problem in an exercise question.</p>'
            .'<p><strong>Learner:</strong> '.$this->escape($senderName).'<br>'
            .'<strong>Exercise:</strong> '.$this->escape($quiz->getTitle()).'<br>'
            .'<strong>Question:</strong> '.$this->escape($questionTitle).'</p>'
            .'<p><strong>Comment:</strong><br>'.nl2br($this->escape($report->getFeedback())).'</p>';

        if ('' !== $attemptUrl) {
            $content .= '<p><a href="'.$this->escape($attemptUrl).'">View attempt</a></p>';
        }

        $message = (new Message())
            ->setSender($sender)
            ->setTitle('Question issue reported')
            ->setContent($content)
        ;

        foreach ($recipients as $recipient) {
            $message->addReceiverTo($recipient);
        }

        $senderRelation = (new MessageRelUser())
            ->setReceiver($sender)
            ->setReceiverType(MessageRelUser::TYPE_SENDER)
        ;
        $message->addReceiver($senderRelation);

        $this->entityManager->persist($message);
    }

    /**
     * @return array<int, User>
     */
    private function getTeacherRecipients(Course $course, ?Session $session, User $sender): array
    {
        $users = [];

        if (!$session instanceof Session) {
            foreach ($course->getTeachersSubscriptions() as $subscription) {
                if (!$subscription instanceof CourseRelUser) {
                    continue;
                }

                $this->addTeacherRecipient($users, $subscription->getUser(), $sender);
            }

            return array_values($users);
        }

        foreach ($session->getSessionRelCourseRelUsers() as $subscription) {
            if (
                !$subscription instanceof SessionRelCourseRelUser
                || Session::COURSE_COACH !== $subscription->getStatus()
                || $subscription->getCourse()->getId() !== $course->getId()
            ) {
                continue;
            }

            $this->addTeacherRecipient($users, $subscription->getUser(), $sender);
        }

        foreach ($session->getGeneralCoaches() as $generalCoach) {
            if ($generalCoach instanceof User) {
                $this->addTeacherRecipient($users, $generalCoach, $sender);
            }
        }

        return array_values($users);
    }

    /**
     * @param array<int, User> $users
     */
    private function addTeacherRecipient(array &$users, User $teacher, User $sender): void
    {
        $teacherId = (int) ($teacher->getId() ?? 0);
        if (
            $teacherId <= 0
            || $teacherId === (int) $sender->getId()
            || !$teacher->isEnabled()
            || $teacher->isSoftDeleted()
        ) {
            return;
        }

        $users[$teacherId] = $teacher;
    }

    private function buildAttemptUrl(CQuiz $quiz, Course $course, ?Session $session, int $attemptId): string
    {
        $resourceNodeId = (int) ($quiz->getResourceNode()?->getId() ?? 0);
        $exerciseId = (int) ($quiz->getIid() ?? 0);
        if ($resourceNodeId <= 0 || $exerciseId <= 0 || $attemptId <= 0) {
            return '';
        }

        $request = $this->requestStack->getCurrentRequest();
        $baseUrl = $request instanceof Request ? rtrim($request->getBaseUrl(), '/') : '';

        $query = [
            'cid' => (int) $course->getId(),
            'sid' => (int) ($session?->getId() ?? 0),
        ];
        if ($request instanceof Request && $request->query->getInt('gid') > 0) {
            $query['gid'] = $request->query->getInt('gid');
        }

        return $baseUrl.'/resources/exercise/'.$resourceNodeId.'/'.$exerciseId.'/result/'.$attemptId.'?'.http_build_query($query);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
