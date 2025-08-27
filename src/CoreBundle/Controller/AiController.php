<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Repository\TrackEAttemptRepository;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Question;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use const FILTER_SANITIZE_NUMBER_INT;

/**
 * Controller to handle AI-related functionalities.
 */
#[Route('/ai')]
class AiController extends AbstractController
{
    public function __construct(
        private readonly AiProviderFactory $aiProviderFactory,
        private readonly TrackEAttemptRepository $attemptRepo,
        private readonly EntityManagerInterface $em
    ) {}

    #[Route('/generate_aiken', name: 'chamilo_core_ai_generate_aiken', methods: ['POST'])]
    public function generateAiken(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $nQ = (int) ($data['nro_questions'] ?? 0);
            $language = (string) ($data['language'] ?? 'en');
            $topic = trim((string) ($data['quiz_name'] ?? ''));
            $questionType = $data['question_type'] ?? 'multiple_choice';
            $aiProvider = $data['ai_provider'] ?? null;

            if ($nQ <= 0 || empty($topic)) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Invalid request parameters. Ensure all fields are filled correctly.',
                ], 400);
            }

            $aiService = $this->aiProviderFactory->getProvider($aiProvider);
            $questions = $aiService->generateQuestions($topic, $nQ, $questionType, $language);

            if (str_starts_with($questions, 'Error:')) {
                return new JsonResponse([
                    'success' => false,
                    'text' => $questions,
                ], 500);
            }

            return new JsonResponse([
                'success' => true,
                'text' => trim($questions),
            ]);
        } catch (Exception $e) {
            error_log('AI Request failed: '.$e->getMessage());

            return new JsonResponse([
                'success' => false,
                'text' => 'An error occurred while generating questions. Please contact the administrator.',
            ], 500);
        }
    }

    #[Route('/open_answer_grade', name: 'chamilo_core_ai_open_answer_grade', methods: ['POST'])]
    public function openAnswerGrade(Request $request): JsonResponse
    {
        $exeId = $request->request->getInt('exeId', 0);
        $questionId = $request->request->getInt('questionId', 0);
        $courseId = $request->request->getInt('courseId', 0);

        if (0 === $exeId || 0 === $questionId || 0 === $courseId) {
            return $this->json(['error' => 'Missing parameters'], 400);
        }

        /** @var TrackEExercise $trackExercise */
        $trackExercise = $this->em
            ->getRepository(TrackEExercise::class)
            ->find($exeId)
        ;
        if (null === $trackExercise) {
            return $this->json(['error' => 'Exercise attempt not found'], 404);
        }

        $attempt = $this->attemptRepo->findOneBy([
            'trackExercise' => $trackExercise,
            'questionId' => $questionId,
            'user' => $trackExercise->getUser(),
        ]);
        if (null === $attempt) {
            return $this->json(['error' => 'Attempt not found'], 404);
        }

        $answerText = $attempt->getAnswer();

        if (ctype_digit($answerText)) {
            $cqa = $this->em
                ->getRepository(CQuizAnswer::class)
                ->find((int) $answerText)
            ;
            if ($cqa) {
                $answerText = $cqa->getAnswer();
            }
        }

        $courseInfo = api_get_course_info_by_id($courseId);
        if (empty($courseInfo['real_id'])) {
            return $this->json(['error' => 'Course info not found'], 500);
        }

        $question = Question::read($questionId, $courseInfo);
        if (false === $question) {
            return $this->json(['error' => 'Question not found'], 404);
        }

        $language = $courseInfo['language'] ?? 'en';
        $courseTitle = $courseInfo['title'] ?? '';
        $maxScore = $question->selectWeighting();
        $questionText = $question->selectTitle();
        $prompt = \sprintf(
            "In language %s, for the question: '%s', in the context of %s, provide a score between 0 and %d on one line, then feedback on the next line for the following answer: '%s'.",
            $language,
            $questionText,
            $courseTitle,
            $maxScore,
            $answerText
        );

        $raw = trim((string) $this->aiProviderFactory
            ->getProvider()
            ->gradeOpenAnswer($prompt, 'open_answer_grade'));

        if ('' === $raw) {
            return $this->json(['error' => 'AI request failed'], 500);
        }

        if (str_contains($raw, "\n")) {
            [$scoreLine, $feedback] = explode("\n", $raw, 2);
        } else {
            $scoreLine = (string) $maxScore;
            $feedback = $raw;
        }

        $score = (int) filter_var($scoreLine, FILTER_SANITIZE_NUMBER_INT);

        $track = new TrackEDefault();
        $track
            ->setDefaultUserId($this->getUser()->getId())
            ->setDefaultEventType('ai_use_question_feedback')
            ->setDefaultValueType('attempt_id')
            ->setDefaultValue((string) $attempt->getId())
            ->setDefaultDate(new DateTime())
            ->setCId($courseId)
            ->setSessionId(api_get_session_id())
        ;
        $this->em->persist($track);
        $this->em->flush();

        return $this->json([
            'score' => $score,
            'feedback' => $feedback,
        ]);
    }
}
