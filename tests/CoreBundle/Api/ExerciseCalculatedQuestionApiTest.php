<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Service\Exercise\ExerciseCalculatedAnswerHelper;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;

use const JSON_THROW_ON_ERROR;

final class ExerciseCalculatedQuestionApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testEditorRoundTripAndRuntimeScoring(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $course = $this->createCourse('Calculated QA '.$suffix);
        $teacher = $this->createUser('calc_teacher_'.$suffix, '', '', 'ROLE_TEACHER');
        $student = $this->createUser('calc_student_'.$suffix);
        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $this->getEntityManager()->flush();
        $quiz = (new CQuiz())->setTitle('Calculated QA')->setMaxAttempt(0)->setCreator($this->getAdmin())->setParent($course)->addCourseLink($course);
        self::getContainer()->get(CQuizRepository::class)->create($quiz);
        $courseId = (int) $course->getId();
        $quizId = (int) $quiz->getIid();
        $teacherToken = $this->getUserTokenFromUser($teacher);
        $studentToken = $this->getUserTokenFromUser($student);
        $client = $this->createClientWithCredentials($teacherToken);
        $context = '?cid='.$courseId.'&sid=0&gid=0';
        $editor = '/api/exercise/questions/'.$quizId.'/editor';
        $payload = [
            'type' => 16, 'title' => 'Calculate the quotient', 'score' => 10.0,
            'calculatedText' => '<p>Divide [b] by [a]. Result: []</p>',
            'calculatedFormula' => '[b]/[a]', 'calculatedVariations' => 2,
            'calculatedRanges' => [
                ['token' => '[b]', 'low' => '5', 'high' => '5'],
                ['token' => '[a]', 'low' => '2', 'high' => '2'],
            ],
            'calculatedComment' => 'Divide the two numbers.',
        ];
        $created = $client->request('POST', $editor.$context, ['json' => $payload])->toArray();
        self::assertResponseIsSuccessful();
        $id = (int) $created['questionId'];
        self::assertGreaterThan(0, $id);
        self::assertSame($payload['calculatedText'], $created['calculatedText']);
        self::assertSame('[b]/[a]', $created['calculatedFormula']);
        self::assertSame(2, $created['calculatedVariations']);
        $edit = $editor.'/'.$id.$context;
        $loaded = $client->request('GET', $edit)->toArray();
        self::assertSame($payload['calculatedText'], $loaded['calculatedText']);
        self::assertSame('[b]/[a]', $loaded['calculatedFormula']);
        self::assertSame(2, $loaded['calculatedVariations']);
        self::assertSame('5', $loaded['calculatedRanges'][0]['low']);
        self::assertSame('2', $loaded['calculatedRanges'][1]['high']);
        $ids = $this->answerIds($id);
        self::assertCount(2, $ids);
        $payload['title'] = 'Edited quotient';
        $updated = $client->request('POST', $edit, ['json' => $payload])->toArray();
        self::assertSame('Edited quotient', $updated['title']);
        self::assertSame($ids, $this->answerIds($id));
        $invalid = $payload;
        $invalid['calculatedFormula'] = '[b]/([a]-[a])';
        $client->request('POST', $edit, ['json' => $invalid]);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertSame($ids, $this->answerIds($id));
        $payload['calculatedVariations'] = 3;
        $updated = $client->request('POST', $edit, ['json' => $payload])->toArray();
        self::assertSame(3, $updated['calculatedVariations']);
        $ids = $this->answerIds($id);
        self::assertCount(3, $ids);
        $client = $this->createClientWithCredentials($studentToken);
        $runtime = '/api/exercise/runtime/'.$quizId;
        foreach (['2.50' => 10.0, '2.51' => 0.0] as $value => $score) {
            $attempt = $client->request('POST', $runtime.'/attempt'.$context, ['json' => ['exerciseId' => $quizId]])->toArray();
            self::assertTrue($attempt['success']);
            $attemptId = (int) $attempt['attemptId'];
            $question = $client->request('GET', $runtime.$context.'&attemptId='.$attemptId)->toArray()['questions'][0];
            $selected = (int) $question['calculated']['answerId'];
            self::assertContains($selected, $ids);
            self::assertStringNotContainsString('calculated-v1:', json_encode($question, JSON_THROW_ON_ERROR));
            $other = $ids[0] === $selected ? $ids[1] : $ids[0];
            $answerUrl = $runtime.'/attempt/'.$attemptId.'/answer'.$context;
            $client->request('POST', $answerUrl, ['json' => ['questionId' => $id, 'answer' => ['calculated' => $value, 'answerId' => $other]]]);
            self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
            $client->request('POST', $answerUrl, ['json' => ['questionId' => $id, 'answer' => ['calculated' => $value, 'answerId' => $selected]]]);
            self::assertResponseIsSuccessful();
            $finished = $client->request('POST', $runtime.'/attempt/'.$attemptId.'/finish'.$context, ['json' => ['confirmedSavedAnswers' => true]])->toArray();
            self::assertTrue($finished['success']);
            self::assertSame($score, (float) $finished['score']);
        }
        $client = $this->createClientWithCredentials($teacherToken);
        $payload['calculatedVariations'] = 4;
        $client->request('POST', $edit, ['json' => $payload]);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertSame($ids, $this->answerIds($id));
    }

    public function testLegacyDefinitionAndExplicitAnswerId(): void
    {
        $answer = (new CQuizAnswer())->setAnswer('<p>Divide 5 by 2. Result: []</p> [2.5]@@[b]/[a]');
        $definition = ExerciseCalculatedAnswerHelper::definition($answer->getAnswer());
        self::assertFalse($definition['stored']);
        self::assertSame('<p>Divide 5 by 2. Result: []</p>', $definition['text']);
        self::assertSame('[b]/[a]', $definition['formula']);
        self::assertSame([], $definition['ranges']);
        $question = (new CQuizQuestion())->setPonderation(10.0);
        self::assertSame(10.0, ExerciseCalculatedAnswerHelper::score($question, [123 => $answer], '123:2.50'));
        self::assertSame(0.0, ExerciseCalculatedAnswerHelper::score($question, [123 => $answer], '999:2.5'));
        self::assertSame(0.0, ExerciseCalculatedAnswerHelper::score($question, [123 => $answer], '123:2.50000001'));
        self::assertSame(10.0, ExerciseCalculatedAnswerHelper::score($question, [123 => $answer], '2.5'));
    }

    /**
     * @return list<int>
     */
    private function answerIds(int $questionId): array
    {
        return array_map('intval', $this->getEntityManager()->getConnection()->fetchFirstColumn('SELECT iid FROM c_quiz_answer WHERE question_id = ? ORDER BY position', [$questionId]));
    }
}
