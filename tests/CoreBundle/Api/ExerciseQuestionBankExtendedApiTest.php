<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;

/** Run against the disposable API test database only. */
final class ExerciseQuestionBankExtendedApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testExtendedBankAndProtectedMutations(): void
    {
        $em = $this->getEntityManager();
        $suffix = bin2hex(random_bytes(4));
        $course = $this->createCourse('Extended bank '.$suffix);
        $otherCourse = $this->createCourse('Foreign bank '.$suffix);
        $teacher = $this->createUser('qb_teacher_'.$suffix, '', '', 'ROLE_TEACHER');
        $student = $this->createUser('qb_student_'.$suffix);
        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $em->flush();
        $quiz = (new CQuiz())->setTitle('Bank destination')->setCreator($this->getAdmin())->setParent($course)->addCourseLink($course);
        self::getContainer()->get(CQuizRepository::class)->create($quiz);
        $used = $this->createSingleQuestion($course, 'Used question');
        $unused = $this->createSingleQuestion($course, 'Unused question');
        $foreign = $this->createSingleQuestion($otherCourse, 'Foreign question');
        $field = (new ExtraField())->setItemType(ExtraField::QUESTION_FIELD_TYPE)
            ->setValueType(ExtraField::FIELD_TYPE_TEXT)->setVariable('bank_'.$suffix)
            ->setDisplayText('Bank classification')->setVisibleToSelf(true)->setChangeable(true)->setFilter(true)->setFieldOrder(0)
        ;
        $em->persist($field);
        $em->persist((new CQuizRelQuestion())->setQuiz($quiz)->setQuestion($used)->setQuestionOrder(1));
        $em->flush();
        $courseId = (int) $course->getId();
        $fieldId = (int) $field->getId();
        $unusedId = (int) $unused->getIid();
        $usedId = (int) $used->getIid();
        $foreignId = (int) $foreign->getIid();
        $quizId = (int) $quiz->getIid();
        $studentId = (int) $student->getId();
        $connection = $em->getConnection();
        foreach ([['completed', '', [2.0, 3.0]], ['', '', [1.0]], ['completed', (string) $usedId, [0.0]], ['incomplete', '', [0.0]]] as [$status, $pending, $marks]) {
            $connection->insert('track_e_exercises', [
                'exe_user_id' => $studentId, 'c_id' => $courseId, 'session_id' => null,
                'exe_exo_id' => $quizId, 'exe_date' => '2026-01-01 10:00:00', 'score' => array_sum($marks),
                'max_score' => 5.0, 'user_ip' => '127.0.0.1', 'status' => $status,
                'data_tracking' => (string) $usedId, 'start_date' => '2026-01-01 09:00:00', 'steps_counter' => 0,
                'orig_lp_id' => 0, 'orig_lp_item_id' => 0, 'exe_duration' => 60, 'orig_lp_item_view_id' => 0,
                'questions_to_check' => $pending,
            ]);
            $attemptId = (int) $connection->lastInsertId();
            foreach ($marks as $mark) {
                $connection->insert('track_e_attempt', [
                    'exe_id' => $attemptId, 'user_id' => $studentId, 'question_id' => $usedId,
                    'answer' => 'test', 'teacher_comment' => '', 'marks' => $mark,
                    'tms' => '2026-01-01 10:00:00', 'seconds_spent' => 30,
                ]);
            }
        }
        $teacherToken = $this->getUserTokenFromUser($teacher);
        $studentToken = $this->getUserTokenFromUser($student);
        $client = $this->createClientWithCredentials($teacherToken);
        $context = '?cid='.$courseId.'&sid=0';
        $bank = '/api/exercise/questions/bank';
        $response = $client->request('GET', $bank.$context.'&sortField=occurrences&sortDirection=DESC')->toArray();
        self::assertResponseIsSuccessful();
        self::assertSame($usedId, $response['items'][0]['id']);
        $rows = array_column($response['items'], null, 'id');
        self::assertArrayNotHasKey($foreignId, $rows);
        self::assertSame(1, $rows[$usedId]['occurrences']);
        self::assertSame(1, $rows[$usedId]['correctAttempts']);
        self::assertSame(1, $rows[$usedId]['incorrectAttempts']);
        self::assertFalse($rows[$usedId]['canAssignScores']);
        self::assertTrue($rows[$unusedId]['canAssignScores']);
        $response = $client->request('GET', '/api/exercise/questions/'.$quizId.'/bank'.$context)->toArray();
        self::assertResponseIsSuccessful();
        self::assertSame(1, $response['exerciseQuestionCount']);
        self::assertTrue(array_column($response['items'], null, 'id')[$usedId]['alreadyInExercise']);
        $client->request('POST', $bank.'/action'.$context, ['json' => ['action' => 'assign_scores', 'questionIds' => [$unusedId, $usedId], 'correctScore' => 7.0, 'incorrectScore' => -0.5]]);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $client->request('POST', $bank.'/action'.$context, ['json' => ['action' => 'assign_scores', 'questionIds' => [$foreignId], 'correctScore' => 7.0, 'incorrectScore' => -0.5]]);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $response = $client->request('GET', $bank.$context)->toArray();
        self::assertSame(5.0, (float) array_column($response['items'], null, 'id')[$unusedId]['score']);
        $response = $client->request('POST', $bank.'/action'.$context, ['json' => ['action' => 'assign_scores', 'questionIds' => [$unusedId], 'correctScore' => 7.0, 'incorrectScore' => -0.5]])->toArray();
        self::assertResponseIsSuccessful();
        self::assertSame(1, $response['updatedCount']);
        $client->request('POST', $bank.'/action'.$context, ['json' => ['action' => 'classify', 'questionIds' => [$unusedId], 'fieldValues' => [$fieldId => 'QP7042']]]);
        self::assertResponseIsSuccessful();
        $response = $client->request('GET', $bank.$context.'&questionField_'.$fieldId.'=QP7042')->toArray();
        self::assertResponseIsSuccessful();
        self::assertSame(1, $response['totalItems']);
        self::assertSame($unusedId, $response['items'][0]['id']);
        self::assertSame(7.0, (float) $response['items'][0]['score']);
        $this->createClientWithCredentials($studentToken)->request('GET', $bank.$context);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function createSingleQuestion(Course $course, string $title): CQuizQuestion
    {
        $question = (new CQuizQuestion())->setQuestion($title)->setType(1)->setLevel(1)->setPosition(1)
            ->setExtra('')->setPonderation(5.0)->setCreator($this->getAdmin())->setParent($course)->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizQuestionRepository::class)->create($question);
        $em = $this->getEntityManager();
        foreach ([1, 0] as $index => $correct) {
            $em->persist((new CQuizAnswer())->setQuestion($question)->setAnswer('Option '.$index)
                ->setCorrect($correct)->setComment('')->setPonderation(1 === $correct ? 5.0 : 0.0)->setPosition($index + 1));
        }
        $em->flush();

        return $question;
    }
}
