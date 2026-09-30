<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionFeedbackReport;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\QuizQuestionAttemptFeedback;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<ExerciseQuestionFeedbackReport>
 */
final readonly class ExerciseQuestionFeedbackReportProvider implements ProviderInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): ExerciseQuestionFeedbackReport {
        $questionId = (int) ($uriVariables['questionId'] ?? 0);
        if ($questionId <= 0) {
            throw new BadRequestHttpException('A valid question id is required.');
        }

        $question = $this->entityManager->getRepository(CQuizQuestion::class)->find($questionId);
        if (!$question instanceof CQuizQuestion) {
            throw new NotFoundHttpException('The requested question was not found.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $request = $this->requestStack->getCurrentRequest();
        $exerciseId = $request instanceof Request ? $request->query->getInt('exerciseId') : 0;

        $qb = $this->entityManager->createQueryBuilder()
            ->select('report', 'attempt', 'learner', 'quiz')
            ->from(QuizQuestionAttemptFeedback::class, 'report')
            ->innerJoin('report.attempt', 'attempt')
            ->innerJoin('attempt.user', 'learner')
            ->leftJoin('attempt.quiz', 'quiz')
            ->andWhere('IDENTITY(report.question) = :questionId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('report.feedbackTime', 'DESC')
        ;

        if ($session instanceof Session) {
            $qb
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $qb->andWhere('attempt.session IS NULL');
        }

        if ($exerciseId > 0) {
            $qb
                ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
                ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ;
        }

        /** @var array<int, QuizQuestionAttemptFeedback> $rows */
        $rows = $qb->getQuery()->getResult();

        $response = new ExerciseQuestionFeedbackReport();
        $response->questionId = $questionId;
        $response->success = true;
        $response->message = '';

        foreach ($rows as $report) {
            $attempt = $report->getAttempt();
            $learner = $attempt->getUser();
            $quiz = $attempt->getQuiz();
            $learnerName = trim($learner->getFirstname().' '.$learner->getLastname());
            if ('' === $learnerName) {
                $learnerName = $learner->getUsername();
            }

            $response->reports[] = [
                'id' => (int) ($report->getId() ?? 0),
                'feedback' => $report->getFeedback(),
                'feedbackTime' => $report->getFeedbackTime()->format(DateTimeInterface::ATOM),
                'userId' => (int) ($learner->getId() ?? 0),
                'userName' => $learnerName,
                'attemptId' => (int) $attempt->getExeId(),
                'exerciseId' => $quiz instanceof CQuiz ? (int) ($quiz->getIid() ?? 0) : 0,
                'exerciseTitle' => $quiz instanceof CQuiz ? $quiz->getTitle() : '',
                'attemptUrl' => $quiz instanceof CQuiz
                    ? $this->buildAttemptUrl($quiz, $course, $session, (int) $attempt->getExeId())
                    : '',
            ];
        }

        return $response;
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
}
