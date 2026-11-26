<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

/**
 * The result page marks each match of a matching question as right or wrong. When the test hides the
 * expected answers (here before the last allowed attempt), the page used to deduce that status from
 * the expected answer it no longer received, and showed every match as wrong, even the right ones,
 * while the score was right.
 */
final class ExerciseResultMatchingStatusTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    // Show the score of every attempt, the expected answers only on the last one
    private const RESULT_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT = 4;
    private const MATCHING_DRAGGABLE = 19;

    public function testEachMatchIsMarkedWithoutRevealingTheExpectedAnswer(): void
    {
        $ctx = $this->bootstrapScenario();
        // The learner gets "Paris" right and swaps "Berlin" and "Rome"
        $attemptId = $this->createAttempt($ctx, [
            $ctx['prompts']['France'] => $ctx['options']['Paris'],
            $ctx['prompts']['Germany'] => $ctx['options']['Rome'],
            $ctx['prompts']['Italy'] => $ctx['options']['Berlin'],
        ]);

        $response = $this
            ->createClientWithCredentials($this->getUserTokenFromUser($ctx['learner']))
            ->request(
                'GET',
                '/api/exercise/runtime/'.$ctx['quiz']->getIid().'/attempt/'.$attemptId.'/result?cid='.$ctx['course']->getId().'&sid=0',
            )
        ;
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $response->toArray();
        $this->assertFalse($payload['visibility']['showExpectedAnswers']);

        $statusByPrompt = [];
        foreach ($payload['questions'][0]['answer']['prompts'] as $prompt) {
            $this->assertArrayNotHasKey('correctOptionId', $prompt);
            $this->assertArrayNotHasKey('correctOptionAnswer', $prompt);
            $statusByPrompt[$prompt['answer']] = $prompt['isCorrect'];
        }

        $this->assertSame(['France' => true, 'Germany' => false, 'Italy' => false], $statusByPrompt);
    }

    /**
     * @param array{course: Course, quiz: CQuiz, question: CQuizQuestion, learner: User, options: array<string, int>, prompts: array<string, int>} $ctx
     * @param array<int, int>                                                                                                                      $choices option id by prompt id
     */
    private function createAttempt(array $ctx, array $choices): int
    {
        $em = $this->getEntityManager();
        // A request to the API in between leaves the entities of the scenario detached
        $learner = $em->find(User::class, $ctx['learner']->getId());
        $attempt = (new TrackEExercise())
            ->setUser($learner)
            ->setCourse($em->find(Course::class, $ctx['course']->getId()))
            ->setQuiz($em->find(CQuiz::class, $ctx['quiz']->getIid()))
            ->setStatus('completed')
            ->setScore(1)
            ->setMaxScore(3)
            ->setDataTracking((string) $ctx['question']->getIid())
            ->setUserIp('127.0.0.1')
            ->setOrigLpId(0)
            ->setOrigLpItemId(0)
            ->setOrigLpItemViewId(0)
        ;
        $em->persist($attempt);

        // As the runtime saves them: the prompt in position, the chosen option in answer
        foreach ($choices as $promptId => $optionId) {
            $em->persist(
                (new TrackEAttempt())
                    ->setTrackEExercise($attempt)
                    ->setUser($learner)
                    ->setQuestionId((int) $ctx['question']->getIid())
                    ->setAnswer((string) $optionId)
                    ->setPosition($promptId)
                    ->setMarks(1)
                    ->setTeacherComment('')
                    ->setTms(new DateTime())
                    ->setSecondsSpent(0)
            );
        }
        $em->flush();

        return (int) $attempt->getExeId();
    }

    /**
     * A course with a learner and a test of three allowed attempts holding one matching question.
     *
     * @return array{course: Course, quiz: CQuiz, question: CQuizQuestion, learner: User, options: array<string, int>, prompts: array<string, int>}
     */
    private function bootstrapScenario(): array
    {
        $em = $this->getEntityManager();
        $course = $this->createCourse('Exercise result matching status');
        $learner = $this->createUser('exercise_matching_status_learner');
        $course->addUserAsStudent($learner);
        $em->flush();

        $quiz = (new CQuiz())
            ->setTitle('Exercise result matching status')
            ->setResultsDisabled(self::RESULT_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT)
            ->setMaxAttempt(3)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizRepository::class)->create($quiz);

        $question = (new CQuizQuestion())
            ->setQuestion('Match each country with its capital')
            ->setType(self::MATCHING_DRAGGABLE)
            ->setLevel(1)
            ->setPosition(1)
            ->setPonderation(3.0)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizQuestionRepository::class)->create($question);

        // The options come first (correct = 0), then the prompts, whose correct holds the id of their option
        $position = 1;
        $options = [];
        foreach (['Paris', 'Berlin', 'Rome'] as $label) {
            $option = $this->createAnswer($question, $label, 0, 0.0, $position++);
            $options[$label] = (int) $option->getIid();
        }

        $prompts = [];
        foreach (['France' => 'Paris', 'Germany' => 'Berlin', 'Italy' => 'Rome'] as $label => $capital) {
            $prompt = $this->createAnswer($question, $label, $options[$capital], 1.0, $position++);
            $prompts[$label] = (int) $prompt->getIid();
        }

        $em->persist(
            (new CQuizRelQuestion())
                ->setQuiz($quiz)
                ->setQuestion($question)
                ->setQuestionOrder(1)
        );
        $em->flush();

        return [
            'course' => $course,
            'quiz' => $quiz,
            'question' => $question,
            'learner' => $learner,
            'options' => $options,
            'prompts' => $prompts,
        ];
    }

    private function createAnswer(CQuizQuestion $question, string $label, int $correct, float $ponderation, int $position): CQuizAnswer
    {
        $answer = (new CQuizAnswer())
            ->setQuestion($question)
            ->setAnswer($label)
            ->setCorrect($correct)
            ->setComment('')
            ->setPonderation($ponderation)
            ->setPosition($position)
        ;
        $em = $this->getEntityManager();
        $em->persist($answer);
        // The prompts need the iid of their option
        $em->flush();

        return $answer;
    }
}
