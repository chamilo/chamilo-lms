<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use SurveyUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization of the SurveyUtil functions that SurveyCharacterizationTest does not reach: the complete
 * report (HTML) and the rows of the xls export. Fixtures carry the sv_ prefix.
 */
final class SurveyTier3CharacterizationTest extends KernelTestCase
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
        ChamiloSession::erase('login_as');
        ChamiloSession::erase('surveyuser');
        unset($_SESSION['filled_surveys']);
        $this->setContext(1, 1);
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('surveyuser');
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
        ChamiloSession::write('_user', null !== $userId ? ['id' => $userId] : null);
        if (null !== $courseId) {
            $code = (string) $this->conn->fetchOne('SELECT code FROM course WHERE id = ?', [$courseId]);
            ChamiloSession::write('_real_cid', $courseId);
            ChamiloSession::write('_cid', $code);
            ChamiloSession::write('_course', api_get_course_info($code));
        } else {
            ChamiloSession::erase('_real_cid');
            ChamiloSession::erase('_cid');
            ChamiloSession::erase('_course');
        }
        ChamiloSession::write('sid', $sessionId);
        ChamiloSession::erase('login_as');
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

    private function subscribe(int $userId, int $courseId = 1, int $sessionId = 0): void
    {
        if (0 === $sessionId) {
            $this->conn->executeStatement(
                'INSERT INTO course_rel_user (user_id, c_id, relation_type, status, is_tutor, sort, user_course_cat, progress) VALUES (?, ?, 0, 5, 0, 0, 0, 0)',
                [$userId, $courseId]
            );

            return;
        }
        $this->conn->executeStatement(
            'INSERT INTO session_rel_course_rel_user (user_id, session_id, c_id, status, visibility, legal_agreement, progress) VALUES (?, ?, ?, 0, 1, 0, 0)',
            [$userId, $sessionId, $courseId]
        );
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

    private function insertLearningPathItem(string $lpTitle, string $itemTitle): int
    {
        $this->conn->insert('c_lp', [
            'lp_type' => 1,
            'title' => $lpTitle,
            'path' => '',
            'force_commit' => 0,
            'content_maker' => '',
            'content_license' => '',
            'js_lib' => '',
            'debug' => 0,
            'theme' => '',
            'author' => '',
            'prerequisite' => 0,
            'hide_toc_frame' => 0,
            'seriousgame_mode' => 0,
            'autolaunch' => 0,
            'max_attempts' => 0,
            'subscribe_users' => 0,
            'created_on' => '2026-01-01 00:00:00',
            'modified_on' => '2026-01-01 00:00:00',
        ]);
        $lpId = (int) $this->conn->lastInsertId();
        $this->conn->insert('c_lp_item', [
            'lp_id' => $lpId,
            'title' => $itemTitle,
            'item_type' => 'document',
            'ref' => '',
            'path' => '',
            'min_score' => 0,
            'display_order' => 1,
            'launch_data' => '',
            'lvl' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function report(int $surveyId, int $userId = 0, int $lpItemId = 0): string
    {
        $html = SurveyUtil::displayCompleteReport($this->survey($surveyId), $userId, false, false, false, $lpItemId);

        return (string) preg_replace('/\s+/', ' ', $html);
    }

    // ---------------------------------------------------------------- SurveyUtil::displayCompleteReport

    public function testCompleteReportListsQuestionsAndOptionsInSortOrderAndOneRowPerAnswerRun(): void
    {
        $s = $this->insertSurvey();
        $multi = $this->insertQuestion($s, 'sv multi q', 'multiplechoice', 2);
        $b = $this->insertOption($s, $multi, 'opt B', 1);
        $a = $this->insertOption($s, $multi, 'opt A', 2);
        $open = $this->insertQuestion($s, 'sv open q', 'open', 1);
        $this->insertQuestion($s, 'sv break q', 'pagebreak', 3);
        $this->insertQuestion($s, 'sv {{tag}} q', 'open', 4);
        $this->insertAnswer($s, $multi, '1', (string) $a);
        $this->insertAnswer($s, $multi, '2', (string) $b, 3);
        $this->insertAnswer($s, $open, '1', 'free text');

        $html = $this->report($s);

        // Questions by sort, colspan = number of options, none for an option-less open question.
        $this->assertStringContainsString('<th><label>sv open q</label></th> <th colspan="2"><label>sv multi q</label></th>', $html);
        $this->assertStringNotContainsString('sv break q', $html);
        $this->assertStringNotContainsString('{{tag}}', $html);
        // Second header line: the open question, then the options by their own sort.
        $this->assertStringContainsString('<th>&nbsp;-&nbsp;</th><th>opt B</th><th>opt A</th></tr>', $html);
        // The answers come back by iid, so user 1 shows twice: a row ends when the user changes.
        $this->assertSame(2, substr_count($html, 'action=userreport&user=1"'));
        $this->assertSame(1, substr_count($html, 'action=userreport&user=2"'));
        $this->assertStringContainsString('<td align="center"></td><td align="center"><td align="center">v</tr>', $html);
        $this->assertStringContainsString('<td align="center"></td><td align="center">3<td align="center"></tr>', $html);
        $this->assertStringContainsString('<td align="center">free text</td><td align="center"><td align="center"></tr>', $html);
        $this->assertLessThan(strpos($html, '<td align="center">3<'), strpos($html, '<td align="center">v</tr>'));
        $this->assertLessThan(strpos($html, 'free text'), strpos($html, '<td align="center">3<'));
    }

    public function testCompleteReportOfAnAnonymousSurveyNumbersTheRows(): void
    {
        $s = $this->insertSurvey(['anonymous' => '1']);
        $q = $this->insertQuestion($s, 'sv q', 'yesno');
        $o = $this->insertOption($s, $q, 'yes', 1);
        $this->insertAnswer($s, $q, '1', (string) $o);
        $this->insertAnswer($s, $q, '2', (string) $o);

        $html = $this->report($s);

        $this->assertStringContainsString('<th>Anonymous 1</th>', $html);
        $this->assertStringContainsString('<th>Anonymous 2</th>', $html);
        $this->assertStringNotContainsString('action=userreport', $html);
    }

    public function testCompleteReportCanBeLimitedToOneUser(): void
    {
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q', 'yesno');
        $o = $this->insertOption($s, $q, 'yes', 1);
        $this->insertAnswer($s, $q, '1', (string) $o);
        $this->insertAnswer($s, $q, '2', (string) $o);

        $html = $this->report($s, 2);

        $this->assertStringNotContainsString('user=1"', $html);
        $this->assertStringContainsString('action=userreport&user=2"', $html);
    }

    public function testCompleteReportIsScopedToTheSessionOnlyWhenBaseInSessionsIsEnabled(): void
    {
        $sid = $this->insertSession();
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q', 'yesno');
        $o = $this->insertOption($s, $q, 'yes', 1);
        $this->insertAnswer($s, $q, '1', (string) $o);
        $this->insertAnswer($s, $q, '2', (string) $o, 0, ['session_id' => $sid]);

        $both = $this->report($s);
        $this->assertStringContainsString('user=1"', $both);
        $this->assertStringContainsString('user=2"', $both);

        $this->enableBaseInSessions();
        $base = $this->report($s);
        $this->assertStringContainsString('user=1"', $base);
        $this->assertStringNotContainsString('user=2"', $base);

        $this->setContext(1, 1, $sid);
        $inSession = $this->report($s);
        $this->assertStringNotContainsString('user=1"', $inSession);
        $this->assertStringContainsString('user=2"', $inSession);
    }

    public function testCompleteReportOfALearningPathItemShowsItsTitleAndOnlyItsAnswers(): void
    {
        $item = $this->insertLearningPathItem('sv lp title', 'sv lp item');
        $s = $this->insertSurvey();
        $q = $this->insertQuestion($s, 'sv q', 'yesno');
        $o = $this->insertOption($s, $q, 'yes', 1);
        $this->insertAnswer($s, $q, '1', (string) $o, 0, ['c_lp_item_id' => $item]);
        $this->insertAnswer($s, $q, '2', (string) $o);

        $withItem = $this->report($s, 0, $item);
        $this->assertStringContainsString('<h3 style="margin:0">sv lp title : sv lp item</h3>', $withItem);
        $this->assertStringContainsString('user=1"', $withItem);
        $this->assertStringNotContainsString('user=2"', $withItem);

        $unknownItem = $this->report($s, 0, $item + 100000);
        $this->assertStringNotContainsString('<h3 style="margin:0">', $unknownItem);
        $this->assertStringNotContainsString('user=', $unknownItem);

        $plain = $this->report($s);
        $this->assertStringNotContainsString('<h3 style="margin:0">', $plain);
        $this->assertStringContainsString('user=2"', $plain);
    }

    public function testCompleteReportQuestionFilterKeepsOnlyTheSelectedQuestionsAndFoldsPercentageOptions(): void
    {
        $s = $this->insertSurvey();
        $q1 = $this->insertQuestion($s, 'sv first q', 'yesno', 1);
        $this->insertOption($s, $q1, 'sv yes', 1);
        $q2 = $this->insertQuestion($s, 'sv second q', 'yesno', 2);
        $this->insertOption($s, $q2, 'sv no', 1);
        $pc = $this->insertQuestion($s, 'sv pct q', 'percentage', 3);
        $this->insertOption($s, $pc, 'sv p1', 1);
        $this->insertOption($s, $pc, 'sv p2', 2);

        $all = $this->report($s);
        $this->assertStringContainsString('<th colspan="1"><label>sv first q</label></th>', $all);
        $this->assertStringContainsString('<th><label>sv pct q</label></th>', $all);
        $this->assertStringContainsString('<th>sv yes</th><th>sv no</th><th>&nbsp;%&nbsp;</th></tr>', $all);

        $_POST['submit_question_filter'] = '1';
        $_POST['questions_filter'] = [$q2];
        try {
            $filtered = $this->report($s);
        } finally {
            unset($_POST['submit_question_filter'], $_POST['questions_filter']);
        }
        $this->assertStringNotContainsString('sv first q', $filtered);
        $this->assertStringContainsString('<th colspan="1"><label>sv second q</label></th>', $filtered);
        $this->assertStringNotContainsString('sv yes', $filtered);
        $this->assertStringContainsString('<th>sv no</th></tr>', $filtered);
    }

    public function testCompleteReportOfAnUnsavedSurveyIsEmpty(): void
    {
        $this->assertSame('', SurveyUtil::displayCompleteReport(new CSurvey(), 0, false, false, false));
    }
}
