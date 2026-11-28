<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

use const JSON_THROW_ON_ERROR;

final class ExerciseResultHiddenAnswersApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * @dataProvider visibilityCases
     */
    public function testResultVisibility(int $mode, bool $hide, bool $manager): void
    {
        $em = $this->getEntityManager();
        $course = $this->createCourse('Exercise result visibility');
        $creator = $this->getAdmin();
        $student = $this->createUser('result_visibility_student');
        $teacher = $this->createUser('result_visibility_teacher', '', '', 'ROLE_TEACHER');
        $course->addUserAsStudent($student);
        $course->addUserAsTeacher($teacher);
        $em->flush();
        $questions = [];
        $answers = [];
        $rows = [];
        foreach ([
            [1, 'QA B23 Choose the capital of Peru', 10.0, ''],
            [3, 'QA B23 Complete the sentence', 10.0, 'case:false'],
            [11, 'QA B23 True or false', 2.0, '1:0:0'],
        ] as $index => [$type, $title, $score, $extra]) {
            $question = (new CQuizQuestion())
                ->setQuestion($title)
                ->setDescription('')
                ->setType($type)
                ->setLevel(1)
                ->setPosition($index + 1)
                ->setPonderation($score)
                ->setExtra($extra)
                ->setCreator($creator)
                ->setParent($course)
                ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
            ;
            $em->getRepository(CQuizQuestion::class)->create($question);
            $questions[] = $question;
        }
        $choice = $questions[0];
        $fill = $questions[1];
        $trueFalse = $questions[2];
        foreach ([['Lima', 1, 10.0], ['Cusco', 0, 0.0], ['Iquitos', 0, 0.0]] as $index => [$label, $correct, $score]) {
            $answer = (new CQuizAnswer())->setQuestion($choice)->setAnswer($label)->setCorrect($correct)->setComment('')->setPonderation($score)->setPosition($index + 1);
            $em->persist($answer);
            $answers[] = $answer;
        }
        $em->persist((new CQuizAnswer())->setQuestion($fill)->setAnswer('The capital of Japan is [Tokyo].::10:0:0')->setCorrect(0)->setComment('')->setPonderation(10.0)->setPosition(1));
        $options = [];
        foreach ([1 => 'True', 2 => 'False', 3 => "Don't know"] as $position => $label) {
            $option = (new CQuizQuestionOption())->setQuestion($trueFalse)->setTitle($label)->setPosition($position);
            $em->persist($option);
            $options[$position] = $option;
        }
        $em->flush();
        $statements = [];
        foreach ([['The Earth is round', 1], ['The Earth is flat', 2]] as $index => [$label, $position]) {
            $statement = (new CQuizAnswer())->setQuestion($trueFalse)->setAnswer($label)->setCorrect((int) $options[$position]->getIid())->setComment('')->setPonderation(1.0)->setPosition($index + 1);
            $em->persist($statement);
            $statements[] = $statement;
        }
        $em->flush();
        $rows[] = [$choice, (string) $answers[0]->getIid(), 10.0, 1];
        $rows[] = [$fill, 'The capital of Japan is [Tokyo][Osaka][0].::10:0:0', 0.0, 1];
        foreach ($statements as $index => $statement) {
            $rows[] = [$trueFalse, $statement->getIid().':'.$options[1]->getIid(), 1.0, $index + 1];
        }
        $title = 'Exercise result visibility';
        $quiz = (new CQuiz())
            ->setTitle($title)
            ->setDescription('QA: correct and incorrect answers with result visibility controls.')
            ->setCreator($creator)
            ->setParent($course)
            ->setMaxAttempt(0)
            ->setResultsDisabled($mode)
            ->setPageResultConfiguration(['hide_expected_answer' => $hide ? 'on' : ''])
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
        ;
        $em->getRepository(CQuiz::class)->create($quiz);
        foreach ($questions as $index => $question) {
            $em->persist((new CQuizRelQuestion())->setQuiz($quiz)->setQuestion($question)->setQuestionOrder($index + 1));
        }
        $attempt = (new TrackEExercise())
            ->setUser($student)
            ->setCourse($course)
            ->setQuiz($quiz)
            ->setStatus('completed')
            ->setScore(11.0)
            ->setMaxScore(22.0)
            ->setDataTracking(implode(',', array_map(static fn (CQuizQuestion $question): int => (int) $question->getIid(), $questions)))
            ->setUserIp('127.0.0.1')
            ->setOrigLpId(0)
            ->setOrigLpItemId(0)
            ->setOrigLpItemViewId(0)
        ;
        $em->persist($attempt);
        foreach ($rows as [$question, $answer, $marks, $position]) {
            $em->persist(
                (new TrackEAttempt())
                    ->setTrackEExercise($attempt)
                    ->setUser($student)
                    ->setQuestionId((int) $question->getIid())
                    ->setAnswer($answer)
                    ->setPosition($position)
                    ->setMarks($marks)
                    ->setTeacherComment('')
                    ->setTms(new DateTime())
                    ->setSecondsSpent(5)
            );
        }
        $em->flush();

        $url = '/api/exercise/runtime/'.$quiz->getIid().'/attempt/'.$attempt->getExeId().'/result?cid='.$course->getId().'&sid=0';
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($manager ? $teacher : $student))->request('GET', $url);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = $response->toArray();
        $this->assertSame($manager, $payload['canManage']);
        $this->assertSame(11.0, (float) $payload['attempt']['score']);
        $expectedVisible = !$hide || $manager;
        $this->assertSame($expectedVisible, $payload['visibility']['showExpectedAnswers']);

        if (7 === $mode && $hide && !$manager) {
            $this->assertFalse($payload['visibility']['showQuestionDetails']);
            $this->assertFalse($payload['visibility']['showQuestionScore']);
            $this->assertSame([], $payload['questions']);

            return;
        }

        $this->assertCount(3, $payload['questions']);
        $choiceResult = $payload['questions'][0];
        $fillResult = $payload['questions'][1];
        $trueFalseResult = $payload['questions'][2];
        $choices = $choiceResult['answer']['choices'];
        $blank = $fillResult['answer']['blanks'][0];
        $statements = $trueFalseResult['answer']['choices'];

        if (7 === $mode && !$manager) {
            $this->assertCount(1, $choices);
            $this->assertTrue($choices[0]['correct']);
            $this->assertFalse($choices[0]['selected']);
            $this->assertNull($choiceResult['isCorrect']);

            return;
        }

        $this->assertCount(3, $choices);
        $this->assertTrue($choiceResult['isCorrect']);
        $this->assertFalse($fillResult['isCorrect']);
        $this->assertSame('Osaka', $blank['studentAnswer']);
        $this->assertFalse($trueFalseResult['isCorrect']);
        $this->assertSame(1.0, (float) $trueFalseResult['score']);
        $this->assertSame('True', $statements[0]['selectedOptionLabel']);
        $this->assertSame('True', $statements[1]['selectedOptionLabel']);
        if ($expectedVisible) {
            $this->assertTrue($choices[0]['correct']);
            $this->assertFalse($choices[1]['correct']);
            $this->assertSame('Tokyo', $blank['correctAnswer']);
            $this->assertStringContainsString('[Tokyo]', $fillResult['answer']['text']);
            $this->assertSame('False', $statements[1]['correctOptionLabel']);
        } else {
            foreach ($choices as $choice) {
                $this->assertArrayNotHasKey('correct', $choice);
            }
            $this->assertArrayNotHasKey('correctAnswer', $blank);
            $this->assertStringNotContainsString('Tokyo', json_encode($fillResult, JSON_THROW_ON_ERROR));
            foreach ($statements as $statement) {
                $this->assertArrayNotHasKey('correctOptionId', $statement);
                $this->assertArrayNotHasKey('correctOptionLabel', $statement);
            }
        }
    }

    /**
     * @return iterable<string, array{int, bool, bool}>
     */
    public static function visibilityCases(): iterable
    {
        yield 'learner with expected answers hidden' => [0, true, false];

        yield 'learner with expected answers shown' => [0, false, false];

        yield 'conflicting correct-only mode' => [7, true, false];

        yield 'correct-only mode without hiding' => [7, false, false];

        yield 'teacher review with expected answers hidden' => [0, true, true];
    }
}
