<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization of the legacy survey libraries (public/main/survey/survey.lib.php: SurveyManager and
 * public/main/survey/surveyUtil.class.php: SurveyUtil).
 *
 * Every test pins what the SQL currently stores or returns (rows, ordering, NULL/session handling, counters),
 * so that a rewrite to DQL that changes them fails here. Fixtures are inserted with prepared statements
 * (no resource_node is created: deadlocks with sibling suites) and carry the sv_ prefix. Surveys are course
 * tools: c_survey has no c_id/session_id any more (scoping lives in resource_link), invitations carry c_id and
 * session_id, answers carry session_id only.
 */
final class SurveyCharacterizationTest extends KernelTestCase
{
    private Connection $conn;
    private int $seq = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/survey/survey.lib.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        \ChamiloSession::erase('login_as');
        \ChamiloSession::erase('surveyuser');
        unset($_SESSION['filled_surveys']);
        $this->setContext(1, 1);
    }

    protected function tearDown(): void
    {
        \ChamiloSession::erase('surveyuser');
        unset($_SESSION['filled_surveys'], $_REQUEST['cid']);
        global $_configuration;
        unset($_configuration['show_surveys_base_in_sessions']);
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManager();
    }

    private function setContext(?int $userId, ?int $courseId, int $sessionId = 0): void
    {
        \ChamiloSession::write('_user', null !== $userId ? ['id' => $userId] : null);
        if (null !== $courseId) {
            $code = (string) $this->conn->fetchOne('SELECT code FROM course WHERE id = ?', [$courseId]);
            \ChamiloSession::write('_real_cid', $courseId);
            \ChamiloSession::write('_cid', $code);
            \ChamiloSession::write('_course', api_get_course_info($code));
        } else {
            \ChamiloSession::erase('_real_cid');
            \ChamiloSession::erase('_cid');
            \ChamiloSession::erase('_course');
        }
        \ChamiloSession::write('sid', $sessionId);
        \ChamiloSession::erase('login_as');
        $this->em()->clear();
    }

    private function enableBaseInSessions(): void
    {
        global $_configuration;
        $_configuration['show_surveys_base_in_sessions'] = true;
    }

    private function insertSession(): int
    {
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status) VALUES (?, 0, 0, 0, 1, 0)',
            ['sv_session_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function insertSurvey(array $over = []): int
    {
        $row = array_merge([
            'code' => 'sv_code_'.(++$this->seq),
            'title' => 'sv_survey '.$this->seq,
            'lang' => 'english',
            'avail_from' => '2026-01-01 00:00:00',
            'avail_till' => '2026-12-31 00:00:00',
            'is_shared' => '0',
            'template' => 'template',
            'creation_date' => '2026-01-01 00:00:00',
            'invited' => 0,
            'answered' => 0,
            'invite_mail' => '',
            'reminder_mail' => '',
            'mail_subject' => '',
            'anonymous' => '0',
            'shuffle' => 0,
            'one_question_per_page' => 0,
            'survey_version' => '',
            'survey_type' => 0,
            'show_form_profile' => 0,
            'form_fields' => '',
        ], $over);
        $this->conn->insert('c_survey', $row);

        return (int) $this->conn->lastInsertId();
    }

    private function insertQuestion(int $surveyId, string $text, string $type = 'yesno', int $sort = 1, array $over = []): int
    {
        $this->conn->insert('c_survey_question', array_merge([
            'survey_id' => $surveyId,
            'survey_question' => $text,
            'survey_question_comment' => '',
            'type' => $type,
            'display' => 'vertical',
            'sort' => $sort,
            'shared_question_id' => 0,
            'max_value' => 0,
            'survey_group_pri' => 0,
            'survey_group_sec1' => 0,
            'survey_group_sec2' => 0,
            'is_required' => 0,
        ], $over));

        return (int) $this->conn->lastInsertId();
    }

    private function insertOption(int $surveyId, int $questionId, string $text, int $sort, int $value = 0): int
    {
        $this->conn->insert('c_survey_question_option', [
            'survey_id' => $surveyId,
            'question_id' => $questionId,
            'option_text' => $text,
            'sort' => $sort,
            'value' => $value,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertInvitation(int $surveyId, ?int $userId, array $over = []): int
    {
        $this->conn->insert('c_survey_invitation', array_merge([
            'c_id' => 1,
            'session_id' => null,
            'group_id' => null,
            'survey_id' => $surveyId,
            'user_id' => $userId,
            'invitation_code' => 'sv_inv_'.(++$this->seq),
            'answered' => 0,
            'invitation_date' => '2026-02-01 10:00:00',
            'c_lp_item_id' => 0,
        ], $over));

        return (int) $this->conn->lastInsertId();
    }

    private function insertAnswer(int $surveyId, int $questionId, string $user, string $optionId, int $value = 0, array $over = []): int
    {
        $this->conn->insert('c_survey_answer', array_merge([
            'survey_id' => $surveyId,
            'question_id' => $questionId,
            'option_id' => $optionId,
            'value' => $value,
            'user' => $user,
            'session_id' => null,
            'c_lp_item_id' => 0,
        ], $over));

        return (int) $this->conn->lastInsertId();
    }

    private function surveyRow(int $surveyId): array
    {
        return $this->conn->fetchAssociative('SELECT * FROM c_survey WHERE iid = ?', [$surveyId]) ?: [];
    }

    private function rowCount(string $table, string $where, array $params = []): int
    {
        return (int) $this->conn->fetchOne("SELECT COUNT(*) FROM $table WHERE $where", $params);
    }

    private function survey(int $surveyId): CSurvey
    {
        $this->em()->clear();

        return $this->em()->find(CSurvey::class, $surveyId);
    }

    // ---------------------------------------------------------------- SurveyManager: codes and survey data

    public function testGenerateUniqueCodeEmptyCodeReturnsFalse(): void
    {
        $this->assertFalse(\SurveyManager::generate_unique_code(''));
    }

    public function testGenerateUniqueCodeAppendsFirstFreeNumber(): void
    {
        $this->insertSurvey(['code' => 'sv_dup']);
        $this->insertSurvey(['code' => 'sv_dup1']);

        $this->assertSame('sv_dup2', \SurveyManager::generate_unique_code('sv_dup'));
    }

    public function testGenerateUniqueCodeOfAFreeCodeGetsAZeroSuffix(): void
    {
        // PINNED QUIRK: a code that is not taken is returned as "<code>0", not unchanged. Harmless today
        // because the only caller (copySurvey) always passes the code of an existing survey.
        $this->assertSame('sv_free0', \SurveyManager::generate_unique_code('sv_free'));
    }

    public function testGenerateUniqueCodeWithSurveyIdOnlyCollidesWithThatSurvey(): void
    {
        $a = $this->insertSurvey(['code' => 'sv_own']);
        $b = $this->insertSurvey(['code' => 'sv_own']);

        // The code exists on survey $a: with $a it collides, with a foreign id it does not.
        $this->assertSame('sv_own1', \SurveyManager::generate_unique_code('sv_own', $a));
        $this->assertSame('sv_own0', \SurveyManager::generate_unique_code('sv_own', $b + 1000));
    }

    public function testGenerateSurveyCodeIsLowercasedAndSanitized(): void
    {
        $this->assertSame('svcode', \SurveyManager::generateSurveyCode('SV_Code'));
    }

    public function testGetSurveyNeedsACourseAndAKnownId(): void
    {
        $id = $this->insertSurvey();

        $this->assertSame([], \SurveyManager::get_survey(0));
        $this->assertSame([], \SurveyManager::get_survey($id + 100000));

        $this->setContext(1, null);
        $this->assertSame([], \SurveyManager::get_survey($id));
    }

    public function testGetSurveyAddsLegacyAliasKeys(): void
    {
        $id = $this->insertSurvey([
            'code' => 'sv_alias',
            'title' => 'sv alias title',
            'subtitle' => 'sv sub',
            'intro' => 'sv intro',
            'surveythanks' => 'sv thanks',
            'lang' => 'french',
        ]);

        $survey = \SurveyManager::get_survey($id);

        $this->assertSame('sv_alias', $survey['survey_code']);
        $this->assertSame('sv alias title', $survey['survey_title']);
        $this->assertSame('sv sub', $survey['survey_subtitle']);
        $this->assertSame('french', $survey['survey_language']);
        $this->assertSame('2026-01-01 00:00:00', $survey['start_date']);
        $this->assertSame('2026-12-31 00:00:00', $survey['end_date']);
        $this->assertSame('0', $survey['survey_share']);
        $this->assertSame('sv intro', $survey['survey_introduction']);
        $this->assertSame('sv thanks', $survey['survey_thanks']);
        // PINNED QUIRK: c_survey has no c_id/session_id column any more, the aliases are always 0/null.
        $this->assertSame(0, $survey['c_id']);
        $this->assertSame(0, $survey['session_id']);
        $this->assertNull($survey['input_name_list']);
    }

    public function testGetSurveySimpleReturnIsTheRawRow(): void
    {
        $id = $this->insertSurvey(['code' => 'sv_raw']);

        $raw = \SurveyManager::get_survey($id, 0, '', true);

        $this->assertSame('sv_raw', $raw['code']);
        $this->assertArrayNotHasKey('survey_code', $raw);
    }

    // ---------------------------------------------------------------- SurveyManager: store_survey

    public function testStoreSurveyRejectsADuplicateCodeInTheSameLanguage(): void
    {
        $this->insertSurvey(['code' => 'svtaken', 'lang' => 'english']);
        $before = $this->rowCount('c_survey', 'code = ?', ['svtaken']);

        $result = \SurveyManager::store_survey([
            'survey_code' => 'SV_TAKEN',
            'survey_language' => 'english',
            'survey_title' => 'sv dup',
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-02-01 00:00:00',
            'survey_introduction' => '',
            'survey_thanks' => '',
        ]);

        $this->assertSame(['type' => 'error', 'id' => 0], $result);
        $this->assertSame($before, $this->rowCount('c_survey', 'code = ?', ['svtaken']));
    }

    public function testStoreSurveyUpdateOfAnUnknownSurveyIsAnError(): void
    {
        $result = \SurveyManager::store_survey(['survey_id' => 99999999]);

        $this->assertSame(['type' => 'error', 'id' => 99999999], $result);
    }

    public function testStoreSurveyUpdateRejectsACodeUsedByAnotherSurveyInTheSameLanguage(): void
    {
        $this->insertSurvey(['code' => 'svother', 'lang' => 'english']);
        $mine = $this->insertSurvey(['code' => 'svmine', 'lang' => 'english']);

        $result = \SurveyManager::store_survey([
            'survey_id' => $mine,
            'survey_code' => 'svother',
            'survey_language' => 'english',
            'survey_title' => 'x',
            'survey_introduction' => '',
            'survey_thanks' => '',
        ]);

        $this->assertSame(['type' => 'error', 'id' => $mine], $result);
        $this->assertSame('svmine', $this->surveyRow($mine)['code']);
    }

    // ---------------------------------------------------------------- SurveyManager: questions and options

    public function testGetQuestionReturnsEmptyForUnknownId(): void
    {
        $this->assertSame([], \SurveyManager::get_question(0));
    }

    public function testGetQuestionReturnsOptionsInSortOrder(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q', 'multiplechoice', 1, ['max_value' => 5, 'is_required' => 1, 'survey_group_pri' => 7]);
        $o2 = $this->insertOption($s, $q, 'second', 2, 20);
        $o1 = $this->insertOption($s, $q, 'first', 1, 10);

        $question = \SurveyManager::get_question($q);

        $this->assertSame($s, (int) $question['survey_id']);
        $this->assertSame($q, (int) $question['question_id']);
        $this->assertSame('multiplechoice', $question['type']);
        $this->assertSame('sv q', $question['question']);
        $this->assertSame('vertical', $question['horizontalvertical']);
        $this->assertSame(5, (int) $question['maximum_score']);
        $this->assertSame(1, (int) $question['is_required']);
        $this->assertSame(0, $question['parent_id']);
        $this->assertSame(['first', 'second'], $question['answers']);
        $this->assertSame([10, 20], array_map('intval', $question['values']));
        $this->assertSame([$o1, $o2], array_map('intval', $question['answersid']));
        $this->assertSame($o1, (int) $question['answer_data'][0]['iid']);
        // Group pri set => "choose 1"; the secondary groups are only reported otherwise.
        $this->assertSame(7, (int) $question['assigned']);
        $this->assertSame(1, $question['choose']);
        $this->assertArrayNotHasKey('assigned1', $question);
    }

    public function testGetQuestionWithoutPrimaryGroupReportsTheSecondaryOnes(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q2', 'open', 1, ['survey_group_sec1' => 3, 'survey_group_sec2' => 4]);

        $question = \SurveyManager::get_question($q);

        $this->assertSame(2, $question['choose']);
        $this->assertSame(3, (int) $question['assigned1']);
        $this->assertSame(4, (int) $question['assigned2']);
        $this->assertArrayNotHasKey('answers', $question);
    }

    public function testGetQuestionsIsScopedToTheSurveyAndIndexedByQuestionId(): void
    {
        $s1 = $this->insertSurvey();
        $s2 = $this->insertSurvey();
        $q1 = $this->insertQuestion($s1, 'sv a', 'yesno', 2, ['survey_question_comment' => 'generated']);
        $q2 = $this->insertQuestion($s1, 'sv b', 'multiplechoice', 1, ['max_value' => 3]);
        $other = $this->insertQuestion($s2, 'sv other', 'yesno', 1);
        $this->insertOption($s1, $q2, 'only', 1);
        $this->insertOption($s2, $other, 'foreign option', 1);

        $questions = \SurveyManager::get_questions($s1);

        $this->assertSame([$q1, $q2], array_keys($questions));
        $this->assertSame('sv a', $questions[$q1]['question']);
        $this->assertSame('generated', $questions[$q1]['survey_question_comment']);
        $this->assertSame(2, (int) $questions[$q1]['sort']);
        $this->assertSame(3, (int) $questions[$q2]['maximum_score']);
        $this->assertSame(['only'], $questions[$q2]['answers']);
        $this->assertArrayNotHasKey('answers', $questions[$q1]);
        $this->assertSame([], \SurveyManager::get_questions($s1 + 100000));
    }

    public function testSaveQuestionAppendsAtTheEndAndStoresItsOptions(): void
    {
        $s = $this->insertSurvey();
        $this->insertQuestion($s, 'sv existing', 'yesno', 4);
        $survey = $this->survey($s);

        $result = \SurveyManager::saveQuestion($survey, [
            'question' => 'sv new question',
            'type' => 'multiplechoice',
            'horizontalvertical' => 'horizontal',
            'answers' => ['one', 'two'],
            'values' => [1, 2],
            'shared_question_id' => 0,
            'survey_id' => $s,
            'question_id' => 0,
        ], false);

        $this->assertSame(['error' => false, 'message' => 'The question has been added.'], $result);
        $row = $this->conn->fetchAssociative(
            'SELECT * FROM c_survey_question WHERE survey_id = ? AND survey_question = ?',
            [$s, 'sv new question']
        );
        $this->assertSame(5, (int) $row['sort']);
        $this->assertSame('horizontal', $row['display']);
        $options = $this->conn->fetchAllAssociative(
            'SELECT option_text, sort, value FROM c_survey_question_option WHERE question_id = ? ORDER BY sort',
            [$row['iid']]
        );
        $this->assertSame([['option_text' => 'one', 'sort' => 1, 'value' => 1], ['option_text' => 'two', 'sort' => 2, 'value' => 2]], array_map(
            static fn (array $o): array => ['option_text' => $o['option_text'], 'sort' => (int) $o['sort'], 'value' => (int) $o['value']],
            $options
        ));
    }

    public function testSaveQuestionRejectsTooShortQuestionsAndEmptyAnswers(): void
    {
        $s = $this->insertSurvey();
        $survey = $this->survey($s);

        $short = \SurveyManager::saveQuestion($survey, ['question' => 'x', 'type' => 'open'], false);
        $this->assertSame(['error' => true, 'message' => 'PleaseEnterAQuestion'], $short);

        $empty = \SurveyManager::saveQuestion($survey, [
            'question' => 'sv with empty answer',
            'type' => 'multiplechoice',
            'answers' => ['ok', ''],
            'shared_question_id' => 0,
        ], false);
        $this->assertSame(['error' => true, 'message' => 'PleasFillAllAnswer'], $empty);
        $this->assertSame(0, $this->rowCount('c_survey_question', 'survey_id = ?', [$s]));
    }

    public function testSaveQuestionEditsTheQuestionAndRemovesDroppedOptions(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv before', 'multiplechoice', 1);
        $keep = $this->insertOption($s, $q, 'keep', 1, 1);
        $drop = $this->insertOption($s, $q, 'drop', 2, 2);
        $survey = $this->survey($s);
        $data = \SurveyManager::get_question($q);

        $result = \SurveyManager::saveQuestion($survey, [
            'question' => 'sv after',
            'type' => 'multiplechoice',
            'horizontalvertical' => 'horizontal',
            'answers' => ['kept renamed'],
            'answersid' => [$keep],
            'values' => [9],
            'shared_question_id' => 0,
            'survey_id' => $s,
            'question_id' => $q,
        ], false, $data);

        $this->assertSame(['error' => false, 'message' => 'QuestionUpdated'], $result);
        $this->assertSame('sv after', $this->conn->fetchOne('SELECT survey_question FROM c_survey_question WHERE iid = ?', [$q]));
        $this->assertSame(0, $this->rowCount('c_survey_question_option', 'iid = ?', [$drop]));
        $kept = $this->conn->fetchAssociative('SELECT option_text, value, sort FROM c_survey_question_option WHERE iid = ?', [$keep]);
        $this->assertSame('kept renamed', $kept['option_text']);
        $this->assertSame(9, (int) $kept['value']);
    }

    public function testSaveQuestionOnPercentageGeneratesOneToHundredOptions(): void
    {
        $s = $this->insertSurvey();
        $survey = $this->survey($s);

        \SurveyManager::saveQuestion($survey, [
            'question' => 'sv percentage',
            'type' => 'percentage',
            'horizontalvertical' => 'vertical',
            'shared_question_id' => 0,
            'survey_id' => $s,
            'question_id' => 0,
        ], false);

        $q = (int) $this->conn->fetchOne('SELECT iid FROM c_survey_question WHERE survey_id = ?', [$s]);
        $this->assertSame(100, $this->rowCount('c_survey_question_option', 'question_id = ?', [$q]));
        $this->assertSame('100', (string) $this->conn->fetchOne('SELECT option_text FROM c_survey_question_option WHERE question_id = ? ORDER BY sort DESC LIMIT 1', [$q]));
    }

    public function testMoveSurveyQuestionSwapsTheSortOfTwoNeighbours(): void
    {
        $s = $this->insertSurvey();
        $q1 = $this->insertQuestion($s, 'sv m1', 'yesno', 1);
        $q2 = $this->insertQuestion($s, 'sv m2', 'yesno', 2);
        $q3 = $this->insertQuestion($s, 'sv m3', 'yesno', 3);

        \SurveyManager::moveSurveyQuestion('moveup', $q2, $s);
        $this->assertSame([$q2, $q1, $q3], $this->questionIdsBySort($s));

        $this->em()->clear();
        \SurveyManager::moveSurveyQuestion('movedown', $q2, $s);
        $this->assertSame([$q1, $q2, $q3], $this->questionIdsBySort($s));

        // The first question cannot go up and the last cannot go down: nothing changes.
        $this->em()->clear();
        \SurveyManager::moveSurveyQuestion('moveup', $q1, $s);
        \SurveyManager::moveSurveyQuestion('movedown', $q3, $s);
        $this->assertSame([$q1, $q2, $q3], $this->questionIdsBySort($s));
    }

    private function questionIdsBySort(int $surveyId): array
    {
        return array_map('intval', $this->conn->fetchFirstColumn(
            'SELECT iid FROM c_survey_question WHERE survey_id = ? ORDER BY sort',
            [$surveyId]
        ));
    }

    public function testDeleteQuestionRemovesTheQuestionItsOptionsAndAnswers(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv del', 'multiplechoice');
        $keepQ = $this->insertQuestion($s, 'sv keep', 'multiplechoice', 2);
        $o = $this->insertOption($s, $q, 'opt', 1);
        $this->insertAnswer($s, $q, '1', (string) $o);
        $this->insertAnswer($s, $keepQ, '1', 'x');

        $this->assertFalse(\SurveyManager::deleteQuestion(0));
        $this->assertFalse(\SurveyManager::deleteQuestion($q + 100000));
        $this->assertTrue(\SurveyManager::deleteQuestion($q));

        $this->assertSame(0, $this->rowCount('c_survey_question', 'iid = ?', [$q]));
        $this->assertSame(0, $this->rowCount('c_survey_question_option', 'question_id = ?', [$q]));
        $this->assertSame(0, $this->rowCount('c_survey_answer', 'question_id = ?', [$q]));
        $this->assertSame(1, $this->rowCount('c_survey_answer', 'question_id = ?', [$keepQ]));
    }

    public function testCopyQuestionDuplicatesQuestionAndOptionsAtTheEnd(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, '  sv orig  ', 'multiplechoice', 3, ['is_required' => 1, 'max_value' => 4]);
        $this->insertQuestion($s, 'sv last', 'yesno', 8);
        $this->insertOption($s, $q, 'a', 1, 5);
        $this->insertOption($s, $q, 'b', 2, 6);

        $new = \SurveyManager::copyQuestion($q);

        $this->assertGreaterThan($q, $new);
        $row = $this->conn->fetchAssociative('SELECT * FROM c_survey_question WHERE iid = ?', [$new]);
        $this->assertSame('sv orig', $row['survey_question']); // trimmed
        $this->assertSame($s, (int) $row['survey_id']);
        $this->assertSame(9, (int) $row['sort']);
        $this->assertSame(1, (int) $row['is_required']);
        $this->assertSame(4, (int) $row['max_value']);
        $this->assertSame(
            [['a', 1, 5], ['b', 2, 6]],
            array_map(
                static fn (array $o): array => [$o['option_text'], (int) $o['sort'], (int) $o['value']],
                $this->conn->fetchAllAssociative('SELECT * FROM c_survey_question_option WHERE question_id = ? ORDER BY sort', [$new])
            )
        );
        $this->assertSame(0, \SurveyManager::copyQuestion(0));
    }

    public function testDeleteAllSurveyQuestionsIsANoOpThatReturnsTrue(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv stays');

        // PINNED QUIRK: the body is commented out, the questions are removed by the ORM cascade instead.
        $this->assertTrue(\SurveyManager::delete_all_survey_questions($s));
        $this->assertSame(1, $this->rowCount('c_survey_question', 'iid = ?', [$q]));
    }

    public function testHasDependencyDetectsQuestionsConditionedByAnOption(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv parent');
        $this->assertFalse(\SurveyManager::hasDependency($this->survey($s)));

        $o = $this->insertOption($s, $q, 'opt', 1);
        $this->insertQuestion($s, 'sv child', 'yesno', 2, ['parent_id' => $q, 'parent_option_id' => $o]);

        $this->assertTrue(\SurveyManager::hasDependency($this->survey($s)));
    }

    public function testGetCountPagesCountsPageBreaksUnlessOneQuestionPerPage(): void
    {
        $s = $this->insertSurvey();
        $this->insertQuestion($s, 'sv p1', 'yesno', 1);
        $this->insertQuestion($s, 'sv break', 'pagebreak', 2);
        $this->insertQuestion($s, 'sv p2', 'yesno', 3);
        $this->insertQuestion($s, 'sv {{class_name}}', 'pagebreak', 4); // generated templates are not counted
        $this->insertQuestion($s, 'sv {{student_full_name}}', 'yesno', 5);

        $this->assertSame(2, \SurveyManager::getCountPages($this->survey($s)));

        $one = $this->insertSurvey(['one_question_per_page' => 1]);
        $this->assertSame(1, \SurveyManager::getCountPages($this->survey($one)), 'no question at all: one page');
        $this->insertQuestion($one, 'sv o1', 'yesno', 1);
        $this->insertQuestion($one, 'sv o2', 'yesno', 2);
        $this->insertQuestion($one, 'sv obreak', 'pagebreak', 3);
        $this->assertSame(2, \SurveyManager::getCountPages($this->survey($one)));

        $none = $this->insertSurvey();
        $this->assertSame(1, \SurveyManager::getCountPages($this->survey($none)));
    }

    // ---------------------------------------------------------------- SurveyManager: copy

    public function testCopySurveyIntoAnExistingSurveyCopiesQuestionsAndOptionsWithNewIds(): void
    {
        $src = $this->insertSurvey();
        $dst = $this->insertSurvey();
        $q1 = $this->insertQuestion($src, 'sv c1', 'multiplechoice', 1, ['is_required' => 1]);
        $q2 = $this->insertQuestion($src, 'sv c2', 'open', 2);
        $this->insertOption($src, $q1, 'x', 1, 1);
        $this->insertOption($src, $q1, 'y', 2, 2);
        $foreign = $this->insertSurvey();
        $this->insertQuestion($foreign, 'sv foreign', 'open', 1);

        $result = \SurveyManager::copySurvey($src, $dst);

        $this->assertSame($dst, $result);
        $copied = $this->conn->fetchAllAssociative('SELECT * FROM c_survey_question WHERE survey_id = ? ORDER BY sort', [$dst]);
        $this->assertSame(['sv c1', 'sv c2'], array_column($copied, 'survey_question'));
        $this->assertSame([1, 0], array_map(static fn (array $r): int => (int) $r['is_required'], $copied));
        $this->assertNotSame($q1, (int) $copied[0]['iid']);
        $options = $this->conn->fetchAllAssociative('SELECT * FROM c_survey_question_option WHERE survey_id = ? ORDER BY sort', [$dst]);
        $this->assertSame(['x', 'y'], array_column($options, 'option_text'));
        $this->assertSame([(int) $copied[0]['iid'], (int) $copied[0]['iid']], array_map(static fn (array $o): int => (int) $o['question_id'], $options));
        // The source is untouched.
        $this->assertSame(2, $this->rowCount('c_survey_question', 'survey_id = ?', [$src]));
    }

    public function testCopySurveyOfAnUnknownSurveyReturnsNull(): void
    {
        $this->assertNull(\SurveyManager::copySurvey(99999999, 1));
    }

    // ---------------------------------------------------------------- SurveyManager: invitations and counters

    public function testDeleteAllSurveyInvitationsByUserDecrementsTheInvitedCounters(): void
    {
        $s1 = $this->insertSurvey(['invited' => 2]);
        $s2 = $this->insertSurvey(['invited' => 1]);
        $this->insertInvitation($s1, 2);
        $this->insertInvitation($s1, 1);
        $this->insertInvitation($s2, 2, ['c_id' => 2]);

        $this->assertFalse(\SurveyManager::delete_all_survey_invitations_by_user(''));
        \SurveyManager::delete_all_survey_invitations_by_user(2);

        $this->assertSame(0, $this->rowCount('c_survey_invitation', 'user_id = ? AND survey_id IN (?, ?)', [2, $s1, $s2]));
        $this->assertSame(1, $this->rowCount('c_survey_invitation', 'user_id = ? AND survey_id = ?', [1, $s1]));
        $this->assertSame(1, (int) $this->surveyRow($s1)['invited']);
        $this->assertSame(0, (int) $this->surveyRow($s2)['invited']);
    }

    public function testEmptySurveyDeletesInvitationsAndAnswersAndResetsCounters(): void
    {
        $s = $this->insertSurvey(['invited' => 2, 'answered' => 1]);
        $other = $this->insertSurvey(['invited' => 1, 'answered' => 1]);
        $q = $this->insertQuestion($s, 'sv q');
        $oq = $this->insertQuestion($other, 'sv oq');
        $this->insertInvitation($s, 1);
        $this->insertInvitation($s, 2);
        $this->insertInvitation($other, 1);
        $this->insertAnswer($s, $q, '1', 'a');
        $this->insertAnswer($other, $oq, '1', 'a');

        $this->assertTrue(\SurveyManager::empty_survey($s));

        $this->assertSame(0, $this->rowCount('c_survey_invitation', 'survey_id = ?', [$s]));
        $this->assertSame(0, $this->rowCount('c_survey_answer', 'survey_id = ?', [$s]));
        $this->assertSame(['invited' => 0, 'answered' => 0], array_map('intval', array_intersect_key($this->surveyRow($s), ['invited' => 1, 'answered' => 1])));
        $this->assertSame(1, $this->rowCount('c_survey_invitation', 'survey_id = ?', [$other]));
        $this->assertSame(1, $this->rowCount('c_survey_answer', 'survey_id = ?', [$other]));
        $this->assertSame(1, (int) $this->surveyRow($other)['answered']);
        $this->assertSame(1, $this->rowCount('track_e_default', "default_event_type = 'survey_clean_results' AND default_value = ?", [(string) $s]));
    }

    public function testEmptySurveyInASessionOnlyEmptiesThatSessionsRows(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey(['invited' => 2, 'answered' => 2]);
        $q = $this->insertQuestion($s, 'sv q');
        $this->insertInvitation($s, 1, ['session_id' => $sid]);
        $this->insertInvitation($s, 2);
        $this->insertAnswer($s, $q, '1', 'a', 0, ['session_id' => $sid]);
        $this->insertAnswer($s, $q, '2', 'a');
        $this->setContext(1, 1, $sid);

        \SurveyManager::empty_survey($s);

        $this->assertSame(0, $this->rowCount('c_survey_invitation', 'survey_id = ? AND session_id = ?', [$s, $sid]));
        $this->assertSame(1, $this->rowCount('c_survey_invitation', 'survey_id = ? AND session_id IS NULL', [$s]));
        $this->assertSame(0, $this->rowCount('c_survey_answer', 'survey_id = ? AND session_id = ?', [$s, $sid]));
        $this->assertSame(1, $this->rowCount('c_survey_answer', 'survey_id = ? AND session_id IS NULL', [$s]));
        // PINNED QUIRK: the counters are reset even though the base-course rows of the survey survive.
        $this->assertSame(0, (int) $this->surveyRow($s)['invited']);
        $this->assertSame(0, (int) $this->surveyRow($s)['answered']);
    }

    public function testUpdateSurveyAnsweredIncrementsTheCounterAndFlagsTheCurrentUsersInvitation(): void
    {
        $s = $this->insertSurvey(['answered' => 4]);
        $mine = $this->insertInvitation($s, 1);
        $theirs = $this->insertInvitation($s, 2);
        $otherLp = $this->insertInvitation($s, 1, ['c_lp_item_id' => 77]);

        \SurveyManager::updateSurveyAnswered($this->survey($s), 1);

        $this->assertSame(5, (int) $this->surveyRow($s)['answered']);
        $rows = $this->conn->fetchAllKeyValue('SELECT iid, answered FROM c_survey_invitation WHERE survey_id = ?', [$s]);
        // PINNED QUIRK: with no lp item given, every invitation of the user to that survey is flagged,
        // including the one tied to a learning path item.
        $this->assertSame(1, (int) $rows[$mine]);
        $this->assertSame(0, (int) $rows[$theirs]);
        $this->assertSame(1, (int) $rows[$otherLp]);
        $this->assertNotNull($this->conn->fetchOne('SELECT answered_at FROM c_survey_invitation WHERE iid = ?', [$mine]));
    }

    public function testUpdateSurveyAnsweredWithALpItemOnlyFlagsThatItemsInvitation(): void
    {
        $s = $this->insertSurvey();
        $plain = $this->insertInvitation($s, 1);
        $lp = $this->insertInvitation($s, 1, ['c_lp_item_id' => 77]);

        \SurveyManager::updateSurveyAnswered($this->survey($s), 1, 77);

        $rows = $this->conn->fetchAllKeyValue('SELECT iid, answered FROM c_survey_invitation WHERE survey_id = ?', [$s]);
        $this->assertSame(0, (int) $rows[$plain]);
        $this->assertSame(1, (int) $rows[$lp]);
    }

    public function testUpdateSurveyAnsweredIgnoresInvitationsOfAnotherCourseOrSession(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey();
        $base = $this->insertInvitation($s, 1);
        $otherCourse = $this->insertInvitation($s, 1, ['c_id' => 2]);
        $inSession = $this->insertInvitation($s, 1, ['session_id' => $sid]);

        \SurveyManager::updateSurveyAnswered($this->survey($s), 1);

        $rows = $this->conn->fetchAllKeyValue('SELECT iid, answered FROM c_survey_invitation WHERE survey_id = ?', [$s]);
        $this->assertSame(1, (int) $rows[$base]);
        $this->assertSame(0, (int) $rows[$otherCourse]);
        $this->assertSame(0, (int) $rows[$inSession]);
    }

    public function testGetPeopleWhoFilledSurveyListsDistinctAnswerAuthors(): void
    {
        $s = $this->insertSurvey();
        $other = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q');
        $q2 = $this->insertQuestion($s, 'sv q2', 'yesno', 2);
        $oq = $this->insertQuestion($other, 'sv oq');
        $this->insertAnswer($s, $q, '2', 'a');
        $this->insertAnswer($s, $q2, '2', 'a');
        $this->insertAnswer($s, $q, '1', 'a');
        $this->insertAnswer($other, $oq, '3', 'a');

        $people = \SurveyManager::get_people_who_filled_survey($s);

        sort($people);
        $this->assertSame(['1', '2'], $people);
    }

    public function testGetPeopleWhoFilledSurveyWithUserInfoIsOrderedByName(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q');
        $this->insertAnswer($s, $q, '1', 'a');
        $this->insertAnswer($s, $q, '2', 'a');

        $people = \SurveyManager::get_people_who_filled_survey($s, true);

        $ids = array_map(static fn (array $p): int => (int) $p['user_id'], $people);
        sort($ids);
        $this->assertSame([1, 2], $ids);
        $this->assertSame($people[0]['user_id'], $people[0]['user_info']['user_id'] ?? $people[0]['user_id']);
        $this->assertArrayHasKey('invited_user', $people[0]);
    }

    // ---------------------------------------------------------------- SurveyManager: hashes and links

    public function testSurveyHashDependsOnEveryScopeComponent(): void
    {
        $hash = \SurveyManager::generate_survey_hash(5, 6, 7, 8);

        $this->assertSame(128, strlen($hash));
        $this->assertSame($hash, \SurveyManager::generate_survey_hash(5, 6, 7, 8));
        foreach ([[6, 6, 7, 8], [5, 7, 7, 8], [5, 6, 8, 8], [5, 6, 7, 9]] as $other) {
            $this->assertNotSame($hash, \SurveyManager::generate_survey_hash(...$other));
        }
        $this->assertTrue(\SurveyManager::validate_survey_hash(5, 6, 7, 8, $hash));
        $this->assertFalse(\SurveyManager::validate_survey_hash(5, 6, 7, 9, $hash));
        $this->assertFalse(\SurveyManager::validate_survey_hash(5, 6, 7, 8, 'nope'));
    }

    public function testGenerateSurveyLinkCarriesTheHashAndTheScope(): void
    {
        $link = \SurveyManager::generate_survey_link(5, 6, 7, 8);

        $this->assertStringContainsString('survey/link.php?h='.\SurveyManager::generate_survey_hash(5, 6, 7, 8), $link);
        $this->assertStringContainsString('&i=5&c=6&s=7&g=8', $link);
    }

    public function testSurveyGenerationHashIsAvailableOnlyWithMcrypt(): void
    {
        $this->assertSame(extension_loaded('mcrypt'), \SurveyManager::survey_generation_hash_available());
    }

    public function testIconQuestionMapsKnownTypesAndFallsBackOnUnknownOnes(): void
    {
        $known = \SurveyManager::icon_question('yesno');
        $this->assertNotSame('', (string) $known);
        $this->assertNotSame((string) $known, (string) \SurveyManager::icon_question('sv_unknown_type'));
    }

    // ---------------------------------------------------------------- SurveyManager: invitation queries (DQL)

    public function testGetInvitationsAnsweredFiltersByCodeCourseAndSession(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey(['code' => 'sv_ans']);
        $other = $this->insertSurvey(['code' => 'sv_ans_other']);
        $old = $this->insertInvitation($s, 1, ['answered' => 1, 'invitation_date' => '2026-02-01 10:00:00']);
        $new = $this->insertInvitation($s, 2, ['answered' => 1, 'invitation_date' => '2026-03-01 10:00:00']);
        $this->insertInvitation($s, 1, ['answered' => 0]);
        $this->insertInvitation($s, 1, ['answered' => 1, 'c_id' => 2]);
        $this->insertInvitation($s, 1, ['answered' => 1, 'session_id' => $sid]);
        $this->insertInvitation($other, 1, ['answered' => 1]);

        $base = \SurveyManager::getInvitationsAnswered('sv_ans', 1, 0);
        $inSession = \SurveyManager::getInvitationsAnswered('sv_ans', 1, $sid);

        // Newest first; base course invitations only when no session is given.
        $this->assertSame([$new, $old], array_map(static fn ($i): int => $i->getIid(), $base));
        $this->assertCount(1, $inSession);
    }

    public function testGetUserInvitationsForSurveyInCourseIsNewestFirstAndFilteredByLpItem(): void
    {
        $s = $this->insertSurvey(['code' => 'sv_uinv']);
        $a = $this->insertInvitation($s, 1, ['invitation_date' => '2026-02-01 10:00:00']);
        $b = $this->insertInvitation($s, 1, ['invitation_date' => '2026-04-01 10:00:00', 'c_lp_item_id' => 9]);
        $this->insertInvitation($s, 2);

        $all = \SurveyManager::getUserInvitationsForSurveyInCourse(1, 'sv_uinv', 1);
        $lp = \SurveyManager::getUserInvitationsForSurveyInCourse(1, 'sv_uinv', 1, 0, 0, 9);

        $this->assertSame([$b, $a], array_map(static fn ($i): int => $i->getIid(), $all));
        $this->assertSame([$b], array_map(static fn ($i): int => $i->getIid(), $lp));
        $this->assertSame([], \SurveyManager::getUserInvitationsForSurveyInCourse(1, 'sv_missing', 1));
    }

    // ---------------------------------------------------------------- SurveyUtil: answers

    public function testSaveAnswerStoresTheRowForANamedUser(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q', 'multiplechoice');

        $ok = \SurveyUtil::saveAnswer('1', $this->survey($s), $this->em()->find(CSurveyQuestion::class, $q), '15', 3, '', 0);

        $this->assertTrue($ok);
        $row = $this->conn->fetchAssociative('SELECT * FROM c_survey_answer WHERE survey_id = ?', [$s]);
        $this->assertSame($q, (int) $row['question_id']);
        $this->assertSame('1', $row['user']);
        $this->assertSame('15', $row['option_id']);
        $this->assertSame(3, (int) $row['value']);
        $this->assertNull($row['session_id'], 'no session: NULL, not 0');
        $this->assertSame(0, (int) $row['c_lp_item_id']);
    }

    public function testSaveAnswerOtherOptionIsAppendedAfterTheSeparator(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q', 'multiplechoiceother');

        \SurveyUtil::saveAnswer('1', $this->survey($s), $this->em()->find(CSurveyQuestion::class, $q), '15', 0, 'my text', 4);

        $row = $this->conn->fetchAssociative('SELECT option_id, c_lp_item_id FROM c_survey_answer WHERE survey_id = ?', [$s]);
        $this->assertSame('15@:@my text', $row['option_id']);
        $this->assertSame(4, (int) $row['c_lp_item_id']);
    }

    public function testSaveAnswerInASessionStoresTheSessionId(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q');
        $this->setContext(1, 1, $sid);

        \SurveyUtil::saveAnswer('1', $this->survey($s), $this->em()->find(CSurveyQuestion::class, $q), '1', 0);

        $this->assertSame($sid, (int) $this->conn->fetchOne('SELECT session_id FROM c_survey_answer WHERE survey_id = ?', [$s]));
    }

    public function testSaveAnswerOfAnAnonymousSurveyHidesTheUserBehindAStableHash(): void
    {
        $s = $this->insertSurvey(['anonymous' => '1']);
        $q1 = $this->insertQuestion($s, 'sv q1');
        $q2 = $this->insertQuestion($s, 'sv q2', 'yesno', 2);
        $survey = $this->survey($s);

        \SurveyUtil::saveAnswer('1', $survey, $this->em()->find(CSurveyQuestion::class, $q1), 'a', 0);
        \SurveyUtil::saveAnswer('1', $survey, $this->em()->find(CSurveyQuestion::class, $q2), 'b', 0);

        $users = $this->conn->fetchFirstColumn('SELECT user FROM c_survey_answer WHERE survey_id = ? ORDER BY iid', [$s]);
        $this->assertNotSame('1', $users[0], 'the real user id must not be stored for an anonymous survey');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $users[0]);
        $this->assertSame($users[0], $users[1], 'all the answers of one respondent share one pseudonym');
        $this->assertSame($users[0], \ChamiloSession::read('surveyuser'));
        // The pseudonym must not be derivable from the user id and the answer time (both readable on the
        // invitation row), otherwise anonymous answers can be linked back to a person.
        for ($t = time() - 30; $t <= time() + 2; $t++) {
            $this->assertNotSame(md5('1'.$t), $users[0]);
        }
    }

    public function testRemoveAnswerDeletesOnlyTheGivenUsersAnswerToTheGivenQuestion(): void
    {
        $s = $this->insertSurvey();
        $other = $this->insertSurvey();
        $q1 = $this->insertQuestion($s, 'sv q1');
        $q2 = $this->insertQuestion($s, 'sv q2', 'yesno', 2);
        $oq = $this->insertQuestion($other, 'sv oq');
        $this->insertAnswer($s, $q1, '1', 'a');
        $this->insertAnswer($s, $q1, '1', 'b');
        $this->insertAnswer($s, $q1, '2', 'a');
        $this->insertAnswer($s, $q2, '1', 'a');
        $this->insertAnswer($other, $oq, '1', 'a');

        \SurveyUtil::remove_answer('1', $s, $q1);

        $this->assertSame(0, $this->rowCount('c_survey_answer', 'survey_id = ? AND question_id = ? AND user = ?', [$s, $q1, '1']));
        $this->assertSame(1, $this->rowCount('c_survey_answer', 'survey_id = ? AND question_id = ? AND user = ?', [$s, $q1, '2']));
        $this->assertSame(1, $this->rowCount('c_survey_answer', 'survey_id = ? AND question_id = ?', [$s, $q2]));
        $this->assertSame(1, $this->rowCount('c_survey_answer', 'survey_id = ?', [$other]));
    }

    public function testRemoveAnswerHonoursTheLearningPathItem(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q');
        $this->insertAnswer($s, $q, '1', 'a', 0, ['c_lp_item_id' => 5]);
        $this->insertAnswer($s, $q, '1', 'a', 0, ['c_lp_item_id' => 6]);

        \SurveyUtil::remove_answer('1', $s, $q, 5);

        $this->assertSame([6], array_map('intval', $this->conn->fetchFirstColumn('SELECT c_lp_item_id FROM c_survey_answer WHERE survey_id = ?', [$s])));
    }

    public function testRemoveAnswerIsScopedToTheSessionOnlyWhenBaseInSessionsIsEnabled(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q');
        $this->insertAnswer($s, $q, '1', 'a', 0, ['session_id' => $sid]);
        $this->insertAnswer($s, $q, '1', 'b');
        $this->setContext(1, 1, $sid);

        \SurveyUtil::remove_answer('1', $s, $q);
        $this->assertSame(0, $this->rowCount('c_survey_answer', 'survey_id = ?', [$s]), 'default: the session is ignored');

        $this->insertAnswer($s, $q, '1', 'a', 0, ['session_id' => $sid]);
        $this->insertAnswer($s, $q, '1', 'b');
        $this->enableBaseInSessions();
        \SurveyUtil::remove_answer('1', $s, $q);
        $this->assertSame(['b'], $this->conn->fetchFirstColumn('SELECT option_id FROM c_survey_answer WHERE survey_id = ?', [$s]));
    }

    public function testGetAnswersOfQuestionByUserGroupsByUserAndEncodesTheValue(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q', 'score');
        $oq = $this->insertQuestion($s, 'sv oq', 'score', 2);
        $this->insertAnswer($s, $q, '2', '10', 3);
        $this->insertAnswer($s, $q, '1', '10', 0);
        $this->insertAnswer($s, $q, '1', '11', 2);
        $this->insertAnswer($s, $oq, '1', '99', 1);

        $answers = \SurveyUtil::get_answers_of_question_by_user($s, $q);

        // value 0 is the bare option id, any other value is "<option>*<value>"; ordered by user.
        $this->assertSame(['1' => ['10', '11*2'], '2' => ['10*3']], $answers);
        $this->assertSame(['1', '2'], array_map('strval', array_keys($answers)));
    }

    public function testGetAnswersOfQuestionByUserFiltersOnLpItemAndOptionallyOnSession(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q');
        $this->insertAnswer($s, $q, '1', 'a', 0, ['c_lp_item_id' => 5, 'session_id' => $sid]);
        $this->insertAnswer($s, $q, '2', 'b', 0, ['c_lp_item_id' => 6]);
        $this->setContext(1, 1, $sid);

        $this->assertSame(['1' => ['a']], \SurveyUtil::get_answers_of_question_by_user($s, $q, 5));
        $this->assertSame(['1' => ['a'], '2' => ['b']], \SurveyUtil::get_answers_of_question_by_user($s, $q));

        $this->enableBaseInSessions();
        $this->assertSame(['1' => ['a']], \SurveyUtil::get_answers_of_question_by_user($s, $q));
    }

    public function testComparativeCheckCountsUsersHavingBothOptions(): void
    {
        $x = ['1' => ['10', '11*2'], '2' => ['10'], '3' => ['12']];
        $y = ['1' => ['20'], '2' => ['21'], '3' => ['20']];

        $this->assertSame(1, \SurveyUtil::comparative_check($x, $y, '10', '20'));
        $this->assertSame(1, \SurveyUtil::comparative_check($x, $y, '11', '20', 2));
        $this->assertSame(0, \SurveyUtil::comparative_check($x, $y, '11', '20'));
        $this->assertSame(2, \SurveyUtil::comparative_check($x, $y, '10', '21') + \SurveyUtil::comparative_check($x, $y, '12', '20'));
        $this->assertSame(0, \SurveyUtil::comparative_check('nope', $y, '10', '20'));
    }

    public function testCheckIfSurveyHasAnswers(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q');

        $this->assertFalse(\SurveyUtil::checkIfSurveyHasAnswers(0));
        $this->assertFalse(\SurveyUtil::checkIfSurveyHasAnswers($s));
        $this->insertAnswer($s, $q, '1', 'a');
        $this->assertTrue(\SurveyUtil::checkIfSurveyHasAnswers($s));
        $this->assertFalse(\SurveyUtil::checkIfSurveyHasAnswers($s + 100000));
    }

    public function testDeleteUserReportRemovesThatUsersAnswersAndReopensTheInvitation(): void
    {
        $s = $this->insertSurvey();
        $other = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q');
        $oq = $this->insertQuestion($other, 'sv oq');
        $this->insertAnswer($s, $q, '1', 'a');
        $this->insertAnswer($s, $q, '2', 'a');
        $this->insertAnswer($other, $oq, '1', 'a');
        $mine = $this->insertInvitation($s, 1, ['answered' => 1]);
        $theirs = $this->insertInvitation($s, 2, ['answered' => 1]);
        $otherSurvey = $this->insertInvitation($other, 1, ['answered' => 1]);

        ob_start();
        \SurveyUtil::delete_user_report($s, 1);
        $out = (string) ob_get_clean();

        $this->assertStringContainsString(get_lang('Go back'), $out);
        $this->assertSame(0, $this->rowCount('c_survey_answer', 'survey_id = ? AND user = ?', [$s, '1']));
        $this->assertSame(1, $this->rowCount('c_survey_answer', 'survey_id = ? AND user = ?', [$s, '2']));
        $this->assertSame(1, $this->rowCount('c_survey_answer', 'survey_id = ?', [$other]));
        $rows = $this->conn->fetchAllKeyValue('SELECT iid, answered FROM c_survey_invitation WHERE survey_id IN (?, ?)', [$s, $other]);
        $this->assertSame(0, (int) $rows[$mine]);
        $this->assertSame(1, (int) $rows[$theirs]);
        $this->assertSame(1, (int) $rows[$otherSurvey]);
    }

    public function testDeleteUserReportWithoutAUserDoesNothing(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q');
        $this->insertAnswer($s, $q, '1', 'a');

        ob_start();
        \SurveyUtil::delete_user_report($s, 0);
        $out = (string) ob_get_clean();

        $this->assertSame('', $out);
        $this->assertSame(1, $this->rowCount('c_survey_answer', 'survey_id = ?', [$s]));
    }

    // ---------------------------------------------------------------- SurveyUtil: first/last question check

    public function testCheckFirstLastQuestionFlagsPageBreaksAtTheEdges(): void
    {
        $s = $this->insertSurvey();
        $this->insertQuestion($s, 'sv break', 'pagebreak', 1);
        $this->insertQuestion($s, 'sv q', 'yesno', 2);
        $this->insertQuestion($s, 'sv break2', 'pagebreak', 3);

        ob_start();
        \SurveyUtil::check_first_last_question($s);
        $out = (string) ob_get_clean();
        $this->assertStringContainsString(get_lang('The page break cannot be the first'), $out);
        $this->assertStringContainsString(get_lang('The page break cannot be the last one'), $out);

        $onlyLast = $this->insertSurvey();
        $this->insertQuestion($onlyLast, 'sv a', 'yesno', 1);
        $this->insertQuestion($onlyLast, 'sv brk', 'pagebreak', 2);
        $this->insertQuestion($onlyLast, 'sv b', 'yesno', 3);
        $this->insertQuestion($onlyLast, 'sv trailing', 'pagebreak', 4);
        ob_start();
        \SurveyUtil::check_first_last_question($onlyLast);
        $out = (string) ob_get_clean();
        $this->assertStringContainsString(get_lang('The page break cannot be the last one'), $out);
        $this->assertStringNotContainsString(get_lang('The page break cannot be the first'), $out);

        $onlyFirst = $this->insertSurvey();
        $this->insertQuestion($onlyFirst, 'sv leading', 'pagebreak', 1);
        $this->insertQuestion($onlyFirst, 'sv a', 'yesno', 2);
        $this->insertQuestion($onlyFirst, 'sv b', 'yesno', 3);
        ob_start();
        \SurveyUtil::check_first_last_question($onlyFirst);
        $out = (string) ob_get_clean();
        $this->assertStringContainsString(get_lang('The page break cannot be the first'), $out);
        $this->assertStringNotContainsString(get_lang('The page break cannot be the last one'), $out);

        $ok = $this->insertSurvey();
        $this->insertQuestion($ok, 'sv a', 'yesno', 1);
        $this->insertQuestion($ok, 'sv brk', 'pagebreak', 2);
        $this->insertQuestion($ok, 'sv b', 'yesno', 3);
        ob_start();
        \SurveyUtil::check_first_last_question($ok);
        $this->assertSame('', (string) ob_get_clean());
    }

    // ---------------------------------------------------------------- SurveyUtil: invitations

    public function testSaveInviteMailStoresTheInvitationOrTheReminderText(): void
    {
        $s = $this->insertSurvey();

        \SurveyUtil::saveInviteMail($this->survey($s), 'invite body', 'invite subject', false);
        $row = $this->surveyRow($s);
        $this->assertSame('invite body', $row['invite_mail']);
        $this->assertSame('', $row['reminder_mail']);
        $this->assertSame('invite subject', $row['mail_subject']);

        \SurveyUtil::saveInviteMail($this->survey($s), 'reminder body', 'reminder subject', true);
        $row = $this->surveyRow($s);
        $this->assertSame('invite body', $row['invite_mail']);
        $this->assertSame('reminder body', $row['reminder_mail']);
        $this->assertSame('reminder subject', $row['mail_subject']);
    }

    public function testSaveInvitationsStoresOneInvitationPerNewUserWithoutSendingMail(): void
    {
        $s = $this->insertSurvey();

        $sent = \SurveyUtil::saveInvitations($this->survey($s), ['USER:1', 'USER:2'], 'subject', 'text **link**', 0, false);

        $this->assertSame(0, $sent, 'no mail requested: nothing "sent"');
        $rows = $this->conn->fetchAllAssociative('SELECT * FROM c_survey_invitation WHERE survey_id = ? ORDER BY user_id', [$s]);
        $this->assertSame([1, 2], array_map(static fn (array $r): int => (int) $r['user_id'], $rows));
        $this->assertSame([1, 1], array_map(static fn (array $r): int => (int) $r['c_id'], $rows));
        $this->assertNull($rows[0]['session_id']);
        $this->assertSame(0, (int) $rows[0]['answered']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $rows[0]['invitation_code']);
        $this->assertNotSame($rows[0]['invitation_code'], $rows[1]['invitation_code']);
    }

    public function testSaveInvitationsDoesNotInviteAnAlreadyInvitedUserTwice(): void
    {
        $s = $this->insertSurvey();
        $this->insertInvitation($s, 1, ['invitation_code' => 'sv_keep']);

        \SurveyUtil::saveInvitations($this->survey($s), ['USER:1', 'USER:2'], 'subject', 'text', 0, false);

        $this->assertSame(1, $this->rowCount('c_survey_invitation', 'survey_id = ? AND user_id = 1', [$s]));
        $this->assertSame('sv_keep', $this->conn->fetchOne('SELECT invitation_code FROM c_survey_invitation WHERE survey_id = ? AND user_id = 1', [$s]));
        $this->assertSame(1, $this->rowCount('c_survey_invitation', 'survey_id = ? AND user_id = 2', [$s]));
    }

    public function testSaveInvitationsRequiresAnArray(): void
    {
        $s = $this->insertSurvey();

        $this->assertSame(0, \SurveyUtil::saveInvitations($this->survey($s), 'not an array', 's', 't'));
        $this->assertSame(0, $this->rowCount('c_survey_invitation', 'survey_id = ?', [$s]));
    }

    public function testSaveInvitationsInASessionStoresTheSession(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey();
        $this->setContext(1, 1, $sid);

        \SurveyUtil::saveInvitations($this->survey($s), ['USER:2'], 'subject', 'text');

        $this->assertSame($sid, (int) $this->conn->fetchOne('SELECT session_id FROM c_survey_invitation WHERE survey_id = ?', [$s]));
    }

    public function testInvitationExistsMatchesTheExactCourseSessionGroupAndSurvey(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey();
        $groupId = $this->insertGroup();
        $this->insertInvitation($s, null, ['session_id' => $sid, 'group_id' => $groupId]);

        $this->assertSame(1, \SurveyUtil::invitationExists(1, $sid, $groupId, $s));
        $this->assertSame(0, \SurveyUtil::invitationExists(2, $sid, $groupId, $s));
        $this->assertSame(0, \SurveyUtil::invitationExists(1, $sid + 1, $groupId, $s));
        $this->assertSame(0, \SurveyUtil::invitationExists(1, $sid, $groupId + 1, $s));
        $this->assertSame(0, \SurveyUtil::invitationExists(1, $sid, $groupId, $s + 1));
    }

    public function testInvitationExistsSeesInvitationsStoredWithoutASession(): void
    {
        $s = $this->insertSurvey();
        $groupId = $this->insertGroup();
        $this->insertInvitation($s, null, ['session_id' => null, 'group_id' => $groupId]);

        // The base course is stored as session_id NULL (the column has a foreign key, 0 is impossible).
        $this->assertSame(1, \SurveyUtil::invitationExists(1, 0, $groupId, $s));
        $this->assertSame(0, \SurveyUtil::invitationExists(1, 0, $groupId + 1, $s));
        $this->assertSame(0, \SurveyUtil::invitationExists(2, 0, $groupId, $s));
    }

    private function insertGroup(): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_group_info (title, status, max_student, doc_state, calendar_state, work_state, announcements_state, forum_state, wiki_state, chat_state, self_registration_allowed, self_unregistration_allowed, document_access)
             VALUES ('sv_group', 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0)"
        );

        return (int) $this->conn->lastInsertId();
    }

    public function testUpdateInvitedCountCountsUserInvitationsOfTheCourseAndSession(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey(['invited' => 99]);
        $this->insertInvitation($s, 1);
        $this->insertInvitation($s, 2);
        $this->insertInvitation($s, 1, ['c_id' => 2]);
        $this->insertInvitation($s, 2, ['session_id' => $sid]);
        $this->insertInvitation($s, null); // a group invitation has no user

        $total = \SurveyUtil::updateInvitedCount($this->survey($s));

        $this->assertSame(2, (int) $total);
        $this->assertSame(2, (int) $this->surveyRow($s)['invited']);

        // Explicit course and session arguments win over the current context.
        $this->assertSame(1, (int) \SurveyUtil::updateInvitedCount($this->survey($s), 1, $sid));
        $this->assertSame(1, (int) $this->surveyRow($s)['invited']);
        $this->assertSame(1, (int) \SurveyUtil::updateInvitedCount($this->survey($s), 2));
    }

    public function testGetInvitedUsersSplitsUsersAndGroups(): void
    {
        $s = $this->insertSurvey();
        $groupId = $this->insertGroup();
        $this->insertInvitation($s, 2);
        $this->insertInvitation($s, 1);
        $this->insertInvitation($s, null, ['group_id' => $groupId]);
        $this->insertInvitation($s, 1, ['c_id' => 2]);

        $invited = \SurveyUtil::get_invited_users($this->survey($s));

        $users = array_map('intval', $invited['course_users']);
        sort($users);
        $this->assertSame([1, 2], $users);
        $this->assertSame('', $invited['additional_users']);
        $this->assertContains('GROUP:'.$groupId, $invited['users']);
        $this->assertContains('USER:1', $invited['users']);
        $this->assertContains('USER:2', $invited['users']);
        $this->assertCount(3, $invited['users']);
    }

    public function testGetInvitedUsersByCourseCodeUsesThatCourse(): void
    {
        $s = $this->insertSurvey();
        $this->insertInvitation($s, 2, ['c_id' => 2]);
        $code = (string) $this->conn->fetchOne('SELECT code FROM course WHERE id = 2');

        $invited = \SurveyUtil::get_invited_users($this->survey($s), $code);

        $this->assertSame([2], array_map('intval', $invited['course_users']));
        $this->assertSame([], \SurveyUtil::get_invited_users($this->survey($s))['course_users']);
    }

    public function testGetInvitationsIsKeyedByUserAndScopedToCourseAndExactSession(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey();
        $this->insertInvitation($s, 1, ['session_id' => null]);
        $this->insertInvitation($s, 2, ['c_id' => 2, 'session_id' => $sid]);
        $inSession = $this->insertInvitation($s, 2, ['session_id' => $sid]);

        $this->setContext(1, 1, $sid);
        $found = \SurveyUtil::get_invitations($s);

        $this->assertSame([2], array_map('intval', array_keys($found)));
        $this->assertSame($inSession, (int) $found[2]['iid']);
    }

    public function testGetInvitationsOfTheBaseCourseSeesInvitationsStoredWithoutASession(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey();
        $base = $this->insertInvitation($s, 1, ['session_id' => null]);
        $this->insertInvitation($s, 2, ['session_id' => $sid]);

        $found = \SurveyUtil::get_invitations($s);

        $this->assertSame([1], array_map('intval', array_keys($found)));
        $this->assertSame($base, (int) $found[1]['iid']);
    }

    public function testGetSentInvitationsJoinsUserNamesAndHonoursCourseAndSession(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey();
        $this->insertInvitation($s, 1);
        $this->insertInvitation($s, 2, ['session_id' => $sid]);
        $this->insertInvitation($s, 1, ['c_id' => 2]);
        $other = $this->insertSurvey();
        $this->insertInvitation($other, 1);

        $base = \SurveyUtil::getSentInvitations($s, 1);
        $inSession = \SurveyUtil::getSentInvitations($s, 1, $sid);

        $this->assertCount(1, $base);
        $this->assertSame(1, (int) $base[0]['user_id']);
        $this->assertSame('admin', strtolower((string) $this->conn->fetchOne('SELECT username FROM user WHERE id = 1')));
        $this->assertSame((string) $this->conn->fetchOne('SELECT firstname FROM user WHERE id = 1'), $base[0]['firstname']);
        $this->assertSame((string) $this->conn->fetchOne('SELECT email FROM user WHERE id = 1'), $base[0]['email']);
        $this->assertCount(1, $inSession);
        $this->assertSame(2, (int) $inSession[0]['user_id']);
        $this->assertCount(1, \SurveyUtil::getSentInvitations($s, 2));
    }

    // ---------------------------------------------------------------- SurveyUtil: lists and links

    public function testGetSurveyListShowsOnlyOpenSurveysOfTheInvitedUserInThisCourse(): void
    {
        $open = $this->insertSurvey(['title' => 'sv open survey', 'avail_from' => '2000-01-01 00:00:00', 'avail_till' => '2999-01-01 00:00:00']);
        $closed = $this->insertSurvey(['title' => 'sv closed survey', 'avail_from' => '2000-01-01 00:00:00', 'avail_till' => '2001-01-01 00:00:00']);
        $future = $this->insertSurvey(['title' => 'sv future survey', 'avail_from' => '2998-01-01 00:00:00', 'avail_till' => '2999-01-01 00:00:00']);
        $notMine = $this->insertSurvey(['title' => 'sv not mine', 'avail_from' => '2000-01-01 00:00:00', 'avail_till' => '2999-01-01 00:00:00']);
        $otherCourse = $this->insertSurvey(['title' => 'sv other course', 'avail_from' => '2000-01-01 00:00:00', 'avail_till' => '2999-01-01 00:00:00']);
        foreach ([$open, $closed, $future, $otherCourse] as $id) {
            $this->insertInvitation($id, 2, ['c_id' => $id === $otherCourse ? 2 : 1]);
        }
        $this->insertInvitation($notMine, 1);

        ob_start();
        \SurveyUtil::getSurveyList(2);
        $html = (string) ob_get_clean();

        $this->assertSame(substr_count($html, '<section'), substr_count($html, '</section>'), 'balanced markup');
        $this->assertSame(substr_count($html, '<div'), substr_count($html, '</div>'), 'balanced markup');
        $this->assertStringContainsString('sv open survey', $html);
        $this->assertStringNotContainsString('sv closed survey', $html);
        $this->assertStringNotContainsString('sv future survey', $html);
        $this->assertStringNotContainsString('sv not mine', $html);
        $this->assertStringNotContainsString('sv other course', $html);
    }

    public function testGenerateFillSurveyLinkDistinguishesAnonymousAndNamedSurveys(): void
    {
        $named = $this->insertSurvey(['anonymous' => '0']);
        $anon = $this->insertSurvey(['anonymous' => '1']);
        $meeting = $this->insertSurvey(['survey_type' => 3]);
        $course = api_get_course_entity(1);

        $namedUrl = \SurveyUtil::generateFillSurveyLink($this->survey($named), 'abc', $course, 5);
        $anonUrl = \SurveyUtil::generateFillSurveyLink($this->survey($anon), 'abc', $course, 5);
        $meetingUrl = \SurveyUtil::generateFillSurveyLink($this->survey($meeting), 'abc', $course);

        $this->assertStringContainsString('/'.$named.'/answer?', $namedUrl);
        $this->assertStringContainsString('invitationCode=abc', $namedUrl);
        $this->assertStringContainsString('cid=1&sid=5', $namedUrl);
        $this->assertStringContainsString('publicCid=1&publicSid=5&publicGid=0', $anonUrl);
        $this->assertStringNotContainsString('&cid=', $anonUrl);
        $this->assertStringContainsString('/'.$meeting.'/meeting?', $meetingUrl);
    }

    public function testGenerateFillSurveyLinkSanitizesTheInvitationCode(): void
    {
        $s = $this->insertSurvey();

        $url = \SurveyUtil::generateFillSurveyLink($this->survey($s), '"><script>alert(1)</script>', api_get_course_entity(1));

        $this->assertStringNotContainsString('<script>', $url);
    }

    public function testGenerateSurveyReportingLinkMapsLegacyReportNamesAndUsers(): void
    {
        $base = \SurveyUtil::generateSurveyReportingLink(7);
        $question = \SurveyUtil::generateSurveyReportingLink(7, null, 'questionreport');
        $user = \SurveyUtil::generateSurveyReportingLink(7, 3, 'completereport');

        $this->assertStringContainsString('/7/reporting?cid=1&sid=0&gid=0', $base);
        $this->assertStringNotContainsString('report=', $base);
        $this->assertStringContainsString('report=question', $question);
        // A user always opens the user tab, whatever report was asked.
        $this->assertStringContainsString('report=user', $user);
        $this->assertStringContainsString('user=3', $user);
        $this->assertStringNotContainsString('report=complete', $user);
    }

    public function testAnonymousFilterTranslatesTheFlag(): void
    {
        $this->assertSame(get_lang('Yes'), \SurveyUtil::anonymous_filter(1));
        $this->assertSame(get_lang('Yes'), \SurveyUtil::anonymous_filter('1'));
        $this->assertSame(get_lang('No'), \SurveyUtil::anonymous_filter(0));
        $this->assertSame(get_lang('No'), \SurveyUtil::anonymous_filter(''));
    }

    public function testAnsweredFlagIsPerUserCourseAndSurveyCode(): void
    {
        $this->assertFalse(\SurveyUtil::isSurveyAnsweredFlagged('sv_flag', 1));

        \SurveyUtil::flagSurveyAsAnswered('sv_flag', 1);

        $this->assertTrue(\SurveyUtil::isSurveyAnsweredFlagged('sv_flag', 1));
        $this->assertFalse(\SurveyUtil::isSurveyAnsweredFlagged('sv_flag', 2));
        $this->assertFalse(\SurveyUtil::isSurveyAnsweredFlagged('sv_other', 1));
        $this->setContext(2, 1);
        $this->assertFalse(\SurveyUtil::isSurveyAnsweredFlagged('sv_flag', 1), 'another user is not flagged');
    }

    public function testSurveySearchRestrictionBuildsAnAndedClauseOnlyOnSearch(): void
    {
        $get = $_GET;
        try {
            $_GET = [];
            $this->assertFalse(\SurveyUtil::survey_search_restriction());

            $_GET = ['do_search' => '1', 'keyword_title' => "o'brien", 'keyword_code' => 'svc', 'keyword_language' => 'french'];
            $sql = \SurveyUtil::survey_search_restriction();
            $this->assertStringContainsString("code ='svc'", $sql);
            $this->assertStringContainsString("lang ='french'", $sql);
            $this->assertStringContainsString("o\\'brien", $sql, 'the title is escaped');
            $this->assertSame(2, substr_count($sql, ' AND '));
        } finally {
            $_GET = $get;
        }
    }

    public function testSurveySearchRestrictionWithNoCriterionIsEmpty(): void
    {
        $get = $_GET;
        $_GET = ['do_search' => '1', 'keyword_title' => '', 'keyword_code' => '', 'keyword_language' => '%'];
        try {
            $warnings = [];
            set_error_handler(static function (int $no, string $msg) use (&$warnings): bool {
                $warnings[] = $msg;

                return true;
            });
            try {
                $sql = \SurveyUtil::survey_search_restriction();
            } finally {
                restore_error_handler();
            }
            $this->assertSame([], $warnings);
            $this->assertSame('', $sql);
        } finally {
            $_GET = $get;
        }
    }

    private int $nodeOffset = 5;

    /**
     * Surveys are resources: they belong to a course/session through resource_link rows on their node. The node
     * is borrowed from an unrelated resource (creating one deadlocks with sibling suites) and its own links
     * are dropped for the duration of the (rolled back) transaction.
     */
    private function linkSurvey(int $surveyId, int $courseId, ?int $sessionId = null, int $visibility = 2): int
    {
        $nodeId = (int) $this->conn->fetchOne(
            'SELECT id FROM resource_node WHERE id NOT IN (SELECT resource_node_id FROM c_survey WHERE resource_node_id IS NOT NULL)
             ORDER BY id LIMIT 1 OFFSET '.(int) $this->nodeOffset++
        );
        $this->conn->executeStatement('DELETE FROM resource_link WHERE resource_node_id = ?', [$nodeId]);
        $this->conn->executeStatement('UPDATE c_survey SET resource_node_id = ? WHERE iid = ?', [$nodeId, $surveyId]);
        $this->conn->executeStatement('UPDATE resource_node SET resource_type_id = 49 WHERE id = ?', [$nodeId]);
        $this->conn->insert('resource_link', [
            'resource_node_id' => $nodeId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'visibility' => $visibility,
            'display_order' => 0,
            'resource_type_group' => 49,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
        $this->em()->clear();

        return $nodeId;
    }

    public function testGetSurveysListsThePublishedSurveysOfTheCurrentCourseOnly(): void
    {
        $mine = $this->insertSurvey(['code' => 'sv_in_course']);
        $draft = $this->insertSurvey(['code' => 'sv_draft']);
        $foreign = $this->insertSurvey(['code' => 'sv_foreign_course']);
        $this->linkSurvey($mine, 1);
        $this->linkSurvey($draft, 1, null, 0);
        $this->linkSurvey($foreign, 2);

        $ids = array_map(static fn (array $s): int => (int) $s['iid'], \SurveyManager::get_surveys('', 0));

        $this->assertContains($mine, $ids);
        $this->assertNotContains($draft, $ids, 'an unpublished survey is not listed');
        $this->assertNotContains($foreign, $ids, 'a survey of another course is not listed');
    }

    public function testSurveyListRowsAreScopedToTheSessionWhenOneIsOpen(): void
    {
        $sid = $this->insertSession();
        $base = $this->insertSurvey(['code' => 'sv_row_base', 'answered' => 2, 'invited' => 5]);
        $inSession = $this->insertSurvey(['code' => 'sv_row_session']);
        $this->linkSurvey($base, 1);
        $this->linkSurvey($inSession, 1, $sid);
        $q = $this->insertQuestion($base, 'sv q');
        $this->insertQuestion($base, 'sv q2', 'yesno', 2);

        $before = \SurveyUtil::get_number_of_surveys();
        $rows = \SurveyUtil::get_survey_data(0, 50, 0, 'asc');
        $codes = array_column($rows, 2);

        $this->assertContains('sv_row_base', $codes);
        $baseRow = $rows[array_search('sv_row_base', $codes, true)];
        $this->assertSame($base, (int) $baseRow[0]);
        $this->assertSame(2, $baseRow[3], 'question count');
        $this->assertStringContainsString('survey_invitation.php?view=answered&amp;survey_id='.$base, $baseRow[7]);
        $this->assertStringContainsString('>2</a> / <a', $baseRow[7]);
        $this->assertTrue($baseRow['can_edit']);
        $this->assertGreaterThanOrEqual(1, $before);

        $this->setContext(1, 1, $sid);
        $sessionCodes = array_column(\SurveyUtil::get_survey_data(0, 50, 0, 'asc'), 2);
        $this->assertContains('sv_row_session', $sessionCodes);
        $this->assertNotContains('sv_row_base', $sessionCodes);
    }

    public function testGetSurveyDataTreatsAnInvalidDirectionAsAscending(): void
    {
        $a = $this->insertSurvey(['code' => 'sv_dir']);
        $this->linkSurvey($a, 1);

        $this->assertSame(
            array_column(\SurveyUtil::get_survey_data(0, 50, 0, 'asc'), 2),
            array_column(\SurveyUtil::get_survey_data(0, 50, 0, 'sideways; DROP TABLE x'), 2)
        );
    }

    // ---------------------------------------------------------------- dead SQL: columns that no longer exist

    public function testDeleteAllSurveyAnswersRemovesOnlyThatSurveysAnswers(): void
    {
        $s = $this->insertSurvey();
        $other = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q');
        $oq = $this->insertQuestion($other, 'sv oq');
        $this->insertAnswer($s, $q, '1', 'a');
        $this->insertAnswer($s, $q, '2', 'a');
        $this->insertAnswer($other, $oq, '1', 'a');

        $this->assertTrue(\SurveyManager::delete_all_survey_answers($s));

        $this->assertSame(0, $this->rowCount('c_survey_answer', 'survey_id = ?', [$s]));
        $this->assertSame(1, $this->rowCount('c_survey_answer', 'survey_id = ?', [$other]));
    }

    public function testDeleteAllSurveyQuestionsOptionsRemovesOnlyThatSurveysOptions(): void
    {
        $s = $this->insertSurvey();
        $other = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q', 'multiplechoice');
        $oq = $this->insertQuestion($other, 'sv oq', 'multiplechoice');
        $this->insertOption($s, $q, 'a', 1);
        $this->insertOption($s, $q, 'b', 2);
        $this->insertOption($other, $oq, 'c', 1);

        $this->assertTrue(\SurveyManager::delete_all_survey_questions_options($s));

        $this->assertSame(0, $this->rowCount('c_survey_question_option', 'survey_id = ?', [$s]));
        $this->assertSame(1, $this->rowCount('c_survey_question_option', 'survey_id = ?', [$other]));
        $this->assertSame(1, $this->rowCount('c_survey_question', 'iid = ?', [$q]), 'the questions themselves stay');
    }

    public function testEmptySurveyFromIdDeletesEveryInvitationAndAnswerOfThatSurvey(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey(['invited' => 3, 'answered' => 2]);
        $other = $this->insertSurvey(['invited' => 1, 'answered' => 1]);
        $q = $this->insertQuestion($s, 'sv q');
        $oq = $this->insertQuestion($other, 'sv oq');
        $this->insertInvitation($s, 1);
        $this->insertInvitation($s, 2, ['session_id' => $sid]);
        $this->insertInvitation($s, 1, ['c_id' => 2]);
        $this->insertInvitation($other, 1);
        $this->insertAnswer($s, $q, '1', 'a');
        $this->insertAnswer($other, $oq, '1', 'a');

        $this->assertTrue(\SurveyManager::emptySurveyFromId($s));

        $this->assertSame(0, $this->rowCount('c_survey_invitation', 'survey_id = ?', [$s]));
        $this->assertSame(0, $this->rowCount('c_survey_answer', 'survey_id = ?', [$s]));
        $row = $this->surveyRow($s);
        $this->assertSame([0, 0], [(int) $row['invited'], (int) $row['answered']]);
        $this->assertSame(1, $this->rowCount('c_survey_invitation', 'survey_id = ?', [$other]));
        $this->assertSame(1, $this->rowCount('c_survey_answer', 'survey_id = ?', [$other]));
        $this->assertSame([1, 1], [(int) $this->surveyRow($other)['invited'], (int) $this->surveyRow($other)['answered']]);
    }

    public function testEmptySurveyFromIdOfAnUnknownSurveyReturnsFalse(): void
    {
        $this->assertFalse(\SurveyManager::emptySurveyFromId(99999999));
    }

    public function testGetPeopleWhoFilledSurveyUsesAnsweredInvitationsWhenAnonymousAnsweredIsShown(): void
    {
        $s = $this->insertSurvey(['anonymous' => '1']);
        $other = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q');
        // The answers of an anonymous survey carry pseudonyms; the invitations carry the real users.
        $this->insertAnswer($s, $q, 'pseudonym-a', 'a');
        $this->insertInvitation($s, 2, ['answered' => 1]);
        $this->insertInvitation($s, 1, ['answered' => 0]);
        $this->insertInvitation($other, 1, ['answered' => 1]);
        \Chamilo\CoreBundle\Framework\Container::getSettingsManager()->updateSetting('survey.survey_anonymous_show_answered', 'true');

        $this->assertSame(['2'], array_map('strval', \SurveyManager::get_people_who_filled_survey($s)));
    }

    public function testGetSurveyDataForCoachThrowsBecauseItUsesDroppedColumns(): void
    {
        // PINNED QUIRK: selects survey.author and filters survey.c_id, neither is a c_survey column.
        $this->expectException(\Chamilo\CoreBundle\Exception\NotAllowedException::class);
        \SurveyUtil::get_survey_data_for_coach(0, 10, 0, 'asc');
    }

    public function testSaveInvitationsOfAGroupStoresOneUserlessInvitationOnce(): void
    {
        $s = $this->insertSurvey();
        $groupId = $this->insertGroup();

        \SurveyUtil::saveInvitations($this->survey($s), ['GROUP:'.$groupId], 'subject', 'text');
        \SurveyUtil::saveInvitations($this->survey($s), ['GROUP:'.$groupId], 'subject', 'text');

        $rows = $this->conn->fetchAllAssociative('SELECT * FROM c_survey_invitation WHERE survey_id = ?', [$s]);
        $this->assertCount(1, $rows, 'the group invitation is not stored twice');
        $this->assertSame($groupId, (int) $rows[0]['group_id']);
        $this->assertNull($rows[0]['user_id']);
        $this->assertSame(1, (int) $rows[0]['c_id']);
        $this->assertSame(0, (int) $rows[0]['answered']);
    }

    public function testCopySurveyIntoAnotherCourseAndSessionLinksTheCopyThere(): void
    {
        // Creates a resource node through the repository: the one test of this class that does.
        $admin = $this->em()->find(\Chamilo\CoreBundle\Entity\User::class, 1);
        self::getContainer()->get('security.token_storage')->setToken(
            new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($admin, 'main', $admin->getRoles())
        );
        $sid = $this->insertSession();
        $src = $this->insertSurvey(['code' => 'svcopysrc', 'title' => 'sv copy source']);
        $q = $this->insertQuestion($src, 'sv q');
        $this->insertOption($src, $q, 'sv opt', 1);

        $copy = \SurveyManager::copySurvey($src, null, 2, $sid);

        $this->assertGreaterThan($src, $copy);
        $row = $this->surveyRow($copy);
        $this->assertSame('sv copy source', $row['title'], 'no " Copy" suffix when the target is another course');
        $this->assertSame('svcopysrc1', $row['code']);
        $link = $this->conn->fetchAssociative(
            'SELECT l.c_id, l.session_id FROM resource_link l INNER JOIN c_survey s ON s.resource_node_id = l.resource_node_id WHERE s.iid = ?',
            [$copy]
        );
        $this->assertSame([2, $sid], [(int) $link['c_id'], (int) $link['session_id']]);
        $this->assertSame(1, $this->rowCount('c_survey_question', 'survey_id = ?', [$copy]));
        $this->assertSame(1, $this->rowCount('c_survey_question_option', 'survey_id = ?', [$copy]));
    }

    public function testSurveyReportShowsTheAnswersOfThatUserOnly(): void
    {
        $s = $this->insertSurvey(['title' => 'sv reported survey']);
        $q = $this->insertQuestion($s, 'sv question text', 'open');
        $this->insertAnswer($s, $q, '1', 'sv my own answer');
        $this->insertAnswer($s, $q, '2', 'sv somebody elses answer');
        $this->insertInvitation($s, 1, ['answered' => 1]);
        $this->setContext(1, null); // my_space/survey_report.php runs without a course

        $html = \SurveyManager::surveyReport(['user_id' => 1], 1);

        $this->assertStringContainsString('sv reported survey', $html);
        $this->assertStringContainsString('sv question text', $html);
        $this->assertStringContainsString('sv my own answer', $html);
        $this->assertStringNotContainsString('sv somebody elses answer', $html);
    }

    public function testSurveyReportDoesNotShowTheAnswersOfAnAnonymousSurvey(): void
    {
        $s = $this->insertSurvey(['title' => 'sv anon reported', 'anonymous' => '1']);
        $q = $this->insertQuestion($s, 'sv anon question', 'open');
        $this->insertAnswer($s, $q, 'pseudonym', 'sv anon answer');
        $this->insertInvitation($s, 1, ['answered' => 1]);
        $this->setContext(1, null);

        $html = \SurveyManager::surveyReport(['user_id' => 1], 1);

        $this->assertStringContainsString('sv anon reported', $html);
        $this->assertStringNotContainsString('sv anon answer', $html);
    }

    public function testSurveyReportListsUnansweredSurveysOfTheUser(): void
    {
        $s = $this->insertSurvey(['title' => 'sv pending survey']);
        $this->insertInvitation($s, 1, ['answered' => 0]);
        $this->setContext(1, null);

        $html = \SurveyManager::surveyReport(['user_id' => 1], 0);

        $this->assertStringContainsString('sv pending survey', $html);
        $this->assertSame('', \SurveyManager::surveyReport([], 0));
    }

    public function testSaveInvitationsReminderReusesTheStoredInvitationCodeInASession(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey();
        $this->insertInvitation($s, 2, ['session_id' => $sid, 'invitation_code' => 'svcodekeep']);
        $this->setContext(1, 1, $sid);

        $sent = \SurveyUtil::saveInvitations($this->survey($s), ['USER:2'], 'sv reminder subject', 'sv body **link**', 1, true);

        $this->assertSame(1, $sent);
        $content = (string) $this->conn->fetchOne(
            "SELECT content FROM message WHERE title = 'sv reminder subject' ORDER BY id DESC LIMIT 1"
        );
        $this->assertStringContainsString('invitationCode=svcodekeep', $content);
        $this->assertSame(1, $this->rowCount('c_survey_invitation', 'survey_id = ?', [$s]), 'no second invitation');
    }

    public function testSaveInvitationsReminderReusesTheStoredInvitationCodeOutsideSessions(): void
    {
        $s = $this->insertSurvey();
        $this->insertInvitation($s, 2, ['invitation_code' => 'svcodebase']);

        $sent = \SurveyUtil::saveInvitations($this->survey($s), ['USER:2'], 'sv reminder base', 'sv body **link**', 1, true);

        $this->assertSame(1, $sent);
        $content = (string) $this->conn->fetchOne(
            "SELECT content FROM message WHERE title = 'sv reminder base' ORDER BY id DESC LIMIT 1"
        );
        $this->assertStringContainsString('invitationCode=svcodebase', $content);
        $this->assertSame(1, $this->rowCount('c_survey_invitation', 'survey_id = ?', [$s]));
    }
}
