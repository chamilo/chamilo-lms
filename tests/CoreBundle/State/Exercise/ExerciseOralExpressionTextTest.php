<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

/**
 * An oral expression can come with a written text, as on the legacy page: the learner sends it with the
 * audio or on its own, and the teacher reads it on the result and correction pages. It is shown as HTML
 * there, so it must never carry a script, whether it was saved now or unfiltered by the legacy page - refs BT#23868.
 */
final class ExerciseOralExpressionTextTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const ORAL_EXPRESSION = 13;

    public function testTheTextIsSavedFilteredEvenWithoutAudio(): void
    {
        $ctx = $this->bootstrapScenario();
        $attemptId = $this->createAttempt($ctx, 'incomplete');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['learner']))->request(
            'POST',
            '/api/exercise/runtime/'.$ctx['quiz']->getIid().'/attempt/'.$attemptId.'/upload-answer?cid='.$ctx['course']->getId().'&sid=0',
            [
                'headers' => ['Content-Type' => 'multipart/form-data', 'Accept' => 'application/json'],
                'extra' => ['parameters' => [
                    'questionId' => (string) $ctx['question']->getIid(),
                    'answerText' => '<p>My <strong>text</strong></p><script>alert(1)</script>',
                ]],
            ]
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $saved = $response->toArray()['savedAnswer'][0]['answer'] ?? '';
        $this->assertStringContainsString('<strong>text</strong>', $saved);
        $this->assertStringNotContainsString('<script', $saved);
    }

    public function testTheResultShowsTheTextFiltered(): void
    {
        $ctx = $this->bootstrapScenario();
        // As the legacy page could have saved it, without any filter
        $attemptId = $this->createAttempt($ctx, 'completed', '<p>Legacy <em>text</em></p><img src="x" onerror="alert(1)">');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['learner']))->request(
            'GET',
            '/api/exercise/runtime/'.$ctx['quiz']->getIid().'/attempt/'.$attemptId.'/result?cid='.$ctx['course']->getId().'&sid=0',
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $text = $response->toArray()['questions'][0]['answer']['text'];
        $this->assertStringContainsString('<em>text</em>', $text);
        $this->assertStringNotContainsString('onerror', $text);
    }

    /**
     * @param array{course: Course, quiz: CQuiz, question: CQuizQuestion, learner: User} $ctx
     */
    private function createAttempt(array $ctx, string $status, ?string $answerText = null): int
    {
        $em = $this->getEntityManager();
        $learner = $em->find(User::class, $ctx['learner']->getId());
        $attempt = (new TrackEExercise())
            ->setUser($learner)
            ->setCourse($em->find(Course::class, $ctx['course']->getId()))
            ->setQuiz($em->find(CQuiz::class, $ctx['quiz']->getIid()))
            ->setStatus($status)
            ->setScore(0)
            ->setMaxScore(10)
            ->setDataTracking((string) $ctx['question']->getIid())
            ->setUserIp('127.0.0.1')
            ->setOrigLpId(0)
            ->setOrigLpItemId(0)
            ->setOrigLpItemViewId(0)
        ;
        $em->persist($attempt);

        if (null !== $answerText) {
            $em->persist(
                (new TrackEAttempt())
                    ->setTrackEExercise($attempt)
                    ->setUser($learner)
                    ->setQuestionId((int) $ctx['question']->getIid())
                    ->setAnswer($answerText)
                    ->setPosition(0)
                    ->setMarks(0)
                    ->setTeacherComment('')
                    ->setTms(new DateTime())
                    ->setSecondsSpent(0)
            );
        }
        $em->flush();

        return (int) $attempt->getExeId();
    }

    /**
     * A course with a learner and a test of one oral expression question.
     *
     * @return array{course: Course, quiz: CQuiz, question: CQuizQuestion, learner: User}
     */
    private function bootstrapScenario(): array
    {
        $em = $this->getEntityManager();
        $course = $this->createCourse('Exercise oral expression text');
        $learner = $this->createUser('exercise_oral_text_learner');
        $course->addUserAsStudent($learner);
        $em->flush();

        $quiz = (new CQuiz())
            ->setTitle('Exercise oral expression text')
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizRepository::class)->create($quiz);

        $question = (new CQuizQuestion())
            ->setQuestion('Introduce yourself')
            ->setType(self::ORAL_EXPRESSION)
            ->setLevel(1)
            ->setPosition(1)
            ->setPonderation(10.0)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizQuestionRepository::class)->create($question);

        $em->persist(
            (new CQuizRelQuestion())
                ->setQuiz($quiz)
                ->setQuestion($question)
                ->setQuestionOrder(1)
        );
        $em->flush();

        return ['course' => $course, 'quiz' => $quiz, 'question' => $question, 'learner' => $learner];
    }
}
