<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use learnpath;
use learnpathItem;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\Uuid;
use Throwable;

/**
 * Characterization of the learnpathItem methods that LearnpathItemCharacterizationTest never executes:
 * get_prevent_reinit() / get_seriousgame_mode() (read of the parent learning path, only reachable once the property
 * is unset), removeAudio() and add_audio_from_documents(), plus the session-bound queries and the writes that the
 * main characterization file leaves unpinned (old total time, interaction lookup, prerequisites inside a session).
 * addAudio() is not covered: its upload branch is dead code ($document is always null).
 *
 * Every title starts with "li_t2_" (sibling files run against the same database).
 */
final class LearnpathItemTier2CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE_ID = 1;
    private const USER_ID = 1;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/lp/lp_controller.php');
        $request->setSession($session);
        Container::setRequest($request);
        $session->set('_real_cid', self::COURSE_ID);
        $session->set('_cid', 'TEMP');
        $session->set('_course', api_get_course_info_by_id(self::COURSE_ID));
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function newLpRow(string $title, array $extra = []): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_lp (lp_type, title, path, force_commit, content_maker, content_license, js_lib, debug, theme,
                author, prerequisite, hide_toc_frame, seriousgame_mode, autolaunch, max_attempts, subscribe_users,
                created_on, modified_on)
             VALUES (1, ?, '', 0, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0, '2030-01-01 00:00:00', '2030-01-01 00:00:00')",
            [$title]
        );
        $lpId = (int) $this->conn()->lastInsertId();
        foreach ($extra as $col => $value) {
            $this->conn()->executeStatement("UPDATE c_lp SET $col = ? WHERE iid = ?", [$value, $lpId]);
        }

        $em = $this->getEntityManager();
        $root = (new CLpItem())
            ->setTitle('root')
            ->setPath('root')
            ->setLp($em->find(CLp::class, $lpId))
            ->setItemType('root')
        ;
        $em->persist($root);
        $em->flush();
        $em->clear();

        return $lpId;
    }

    private function newItem(int $lpId, string $title): int
    {
        $this->getEntityManager()->clear();
        $em = $this->getEntityManager();
        $lp = new learnpath($em->find(CLp::class, $lpId), api_get_course_info_by_id(self::COURSE_ID), self::USER_ID);
        $rootId = (int) $this->conn()->fetchOne("SELECT iid FROM c_lp_item WHERE lp_id = ? AND path = 'root'", [$lpId]);
        $id = (int) $lp->add_item($em->find(CLpItem::class, $rootId), 0, 'document', 0, $title);
        $em->clear();

        return $id;
    }

    public function testPreventReinitIsReadFromTheLearningPathWhenTheItemHasNoValue(): void
    {
        $lpId = $this->newLpRow('li_t2_reinit', ['prevent_reinit' => 0]);
        $item = new learnpathItem($this->newItem($lpId, 'li_t2_reinit_item'));
        unset($item->prevent_reinit);

        self::assertSame(0, $item->get_prevent_reinit());

        $this->conn()->executeStatement('UPDATE c_lp SET prevent_reinit = 1 WHERE iid = ?', [$lpId]);
        $other = new learnpathItem($this->newItem($lpId, 'li_t2_reinit_item2'));
        unset($other->prevent_reinit);

        self::assertSame(1, $other->get_prevent_reinit());
    }

    public function testPreventReinitIsFalseWithAnErrorWhenTheLearningPathDoesNotExist(): void
    {
        $lpId = $this->newLpRow('li_t2_reinit_missing');
        $item = new learnpathItem($this->newItem($lpId, 'li_t2_reinit_missing_item'));
        $item->lp_id = 999999999;
        unset($item->prevent_reinit);

        self::assertFalse($item->get_prevent_reinit());
        self::assertSame('Could not find parent learnpath in lp table', $item->error);
    }

    public function testSeriousGameModeIsReadFromTheLearningPathWhenTheItemHasNoValue(): void
    {
        $lpId = $this->newLpRow('li_t2_serious', ['seriousgame_mode' => 1]);
        $item = new learnpathItem($this->newItem($lpId, 'li_t2_serious_item'));
        unset($item->seriousgame_mode);

        self::assertSame(1, $item->get_seriousgame_mode());

        $this->conn()->executeStatement('UPDATE c_lp SET seriousgame_mode = 0 WHERE iid = ?', [$lpId]);
        $other = new learnpathItem($this->newItem($lpId, 'li_t2_serious_item2'));
        unset($other->seriousgame_mode);

        self::assertSame(0, $other->get_seriousgame_mode());
    }

    public function testSeriousGameModeIsFalseWhenTheLearningPathDoesNotExist(): void
    {
        $lpId = $this->newLpRow('li_t2_serious_missing');
        $item = new learnpathItem($this->newItem($lpId, 'li_t2_serious_missing_item'));
        $item->lp_id = 999999999;
        unset($item->seriousgame_mode);

        self::assertFalse($item->get_seriousgame_mode());
    }

    public function testRemoveAudioBlanksTheAudioOfThisItemOnly(): void
    {
        $lpId = $this->newLpRow('li_t2_remove_audio');
        $first = $this->newItem($lpId, 'li_t2_audio_a');
        $second = $this->newItem($lpId, 'li_t2_audio_b');
        $this->conn()->executeStatement('UPDATE c_lp_item SET audio = ? WHERE iid IN (?, ?)', ['/audio/x.mp3', $first, $second]);

        (new learnpathItem($first))->removeAudio();

        self::assertSame('', $this->conn()->fetchOne('SELECT audio FROM c_lp_item WHERE iid = ?', [$first]));
        self::assertSame('/audio/x.mp3', $this->conn()->fetchOne('SELECT audio FROM c_lp_item WHERE iid = ?', [$second]));
    }

    public function testAddAudioFromDocumentsStoresThePathOfTheDocumentOnThisItemOnly(): void
    {
        $lpId = $this->newLpRow('li_t2_add_audio');
        $first = $this->newItem($lpId, 'li_t2_add_audio_a');
        $second = $this->newItem($lpId, 'li_t2_add_audio_b');

        $this->conn()->executeStatement(
            'INSERT INTO resource_node (resource_type_id, title, slug, created_at, updated_at, public, uuid)
             VALUES (17, ?, ?, NOW(), NOW(), 0, ?)',
            ['li_t2_audio.mp3', 'li_t2_audio.mp3-'.uniqid(), Uuid::v4()->toBinary()]
        );
        $nodeId = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            "INSERT INTO c_document (resource_node_id, title, filetype, `readonly`, template) VALUES (?, 'li_t2_audio.mp3', 'file', 0, 0)",
            [$nodeId]
        );
        $documentId = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, session_id, visibility, display_order, resource_type_group, created_at, updated_at)
             VALUES (?, ?, NULL, 2, 0, 17, NOW(), NOW())',
            [$nodeId, self::COURSE_ID]
        );

        $path = (new learnpathItem($first))->add_audio_from_documents($documentId);

        self::assertNotSame('', $path);
        self::assertStringEndsWith('li_t2_audio.mp3', $path);
        self::assertSame($path, $this->conn()->fetchOne('SELECT audio FROM c_lp_item WHERE iid = ?', [$first]));
        self::assertNull($this->conn()->fetchOne('SELECT audio FROM c_lp_item WHERE iid = ?', [$second]));
    }

    public function testAddAudioFromDocumentsDoesNothingForAnUnknownDocument(): void
    {
        $lpId = $this->newLpRow('li_t2_add_audio_none');
        $first = $this->newItem($lpId, 'li_t2_add_audio_none_a');
        $this->conn()->executeStatement('UPDATE c_lp_item SET audio = ? WHERE iid = ?', ['/audio/keep.mp3', $first]);

        self::assertSame('', (new learnpathItem($first))->add_audio_from_documents(999999999));
        self::assertSame('/audio/keep.mp3', $this->conn()->fetchOne('SELECT audio FROM c_lp_item WHERE iid = ?', [$first]));
    }

    // ---------------------------------------------------------------- helpers for the session / write tests

    protected function tearDown(): void
    {
        Container::getSession()->remove('sid');
        try {
            self::getContainer()->get(SettingsManager::class)->updateSetting('lp.lp_prerequisite_on_quiz_unblock_if_max_attempt_reached', 'false');
        } catch (Throwable) {
        }
        parent::tearDown();
    }

    private function newView(int $lpId, ?int $sessionId = null): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_view (c_id, lp_id, user_id, view_count, session_id, last_item, progress) VALUES (?, ?, ?, 1, ?, 0, 0)',
            [self::COURSE_ID, $lpId, self::USER_ID, $sessionId]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function newItemView(int $viewId, int $itemId, int $attempt, string $status, int $totalTime = 0): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_lp_item_view (lp_item_id, lp_view_id, view_count, start_time, total_time, score, status, core_exit)
             VALUES (?, ?, ?, 0, ?, 0, ?, 'none')",
            [$itemId, $viewId, $attempt, $totalTime, $status]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function newSession(string $title): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status) VALUES (?, 0, 0, 0, 1, 0)',
            [$title]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function item(int $itemId, int $viewId): learnpathItem
    {
        $this->getEntityManager()->clear();
        $item = new learnpathItem($itemId);
        $item->set_lp_view($viewId);

        return $item;
    }

    public function testWriteToDbKeepsTheStoredTotalTimeAsTheOldTotalTime(): void
    {
        $lpId = $this->newLpRow('li_t2_old_total');
        $itemId = $this->newItem($lpId, 'li_t2_old_total_item');
        $viewId = $this->newView($lpId);
        $this->newItemView($viewId, $itemId, 1, 'incomplete', 40);
        $item = $this->item($itemId, $viewId);
        $item->current_start_time = 1700000000;
        $item->current_stop_time = 1700000010;

        self::assertTrue($item->write_to_db());

        self::assertSame(40, (int) $item->oldTotalTime);
    }

    public function testWriteToDbUpdatesTheInteractionOfTheSameOrderEvenWhenItsIdChanged(): void
    {
        $lpId = $this->newLpRow('li_t2_interactions');
        $itemId = $this->newItem($lpId, 'li_t2_interactions_item');
        $viewId = $this->newView($lpId);
        $itemViewId = $this->newItemView($viewId, $itemId, 1, 'incomplete');
        $item = $this->item($itemId, $viewId);
        $item->current_start_time = 1700000000;
        $item->current_stop_time = 1700000010;
        $item->add_interaction(0, ['li_t2_int_a', 'choice', '10:00:00', 1, 'a', 'a', 'correct', '1']);
        self::assertTrue($item->write_to_db());

        $item->add_interaction(0, ['li_t2_int_b', 'choice', '10:00:05', 1, 'a', 'b', 'wrong', '2']);
        self::assertTrue($item->write_to_db());

        $rows = $this->conn()->fetchAllAssociative('SELECT interaction_id, result FROM c_lp_iv_interaction WHERE lp_iv_id = ?', [$itemViewId]);
        self::assertCount(1, $rows, 'same order_id: the row is updated, not duplicated');
        self::assertSame('li_t2_int_b', $rows[0]['interaction_id']);
        self::assertSame('wrong', $rows[0]['result']);
    }

    /**
     * PINNED QUIRK: inside a session a single-attempt item is only unlocked when the FIRST item view row of its
     * prerequisite (not the latest attempt) is finished, because the lookup has no ORDER BY.
     */
    public function testPrerequisiteInsideASessionLooksAtTheFirstItemViewOfTheViewOfThatSession(): void
    {
        $sessionId = $this->newSession('li_t2_prereq_session');

        $lpId = $this->newLpRow('li_t2_prereq_in_session');
        $first = $this->newItem($lpId, 'li_t2_prereq_first');
        $second = $this->newItem($lpId, 'li_t2_prereq_second');
        $viewId = $this->newView($lpId, $sessionId);
        $this->newItemView($viewId, $first, 1, 'incomplete');
        $this->newItemView($viewId, $first, 2, 'completed');

        // Only now: building a learnpath object inside a session would create a view of its own.
        Container::getSession()->set('sid', $sessionId);
        $items = [$first => $this->item($first, $viewId), $second => $this->item($second, $viewId)];
        $refs = [$first => $first, $second => $second];

        self::assertFalse($items[$second]->parse_prereq((string) $first, $items, $refs, self::USER_ID));

        $this->conn()->executeStatement("UPDATE c_lp_item_view SET status = 'completed' WHERE lp_view_id = ? AND lp_item_id = ? AND view_count = 1", [$viewId, $first]);
        self::assertTrue($items[$second]->parse_prereq((string) $first, $items, $refs, self::USER_ID), 'the first item view is finished too');
        $this->conn()->executeStatement("UPDATE c_lp_item_view SET status = 'incomplete' WHERE lp_view_id = ? AND lp_item_id = ? AND view_count = 1", [$viewId, $first]);

        // Another user, learning path or session view does not take part in the lookup.
        $this->conn()->executeStatement('UPDATE c_lp_view SET user_id = 2 WHERE iid = ?', [$viewId]);
        self::assertTrue($items[$second]->parse_prereq((string) $first, $items, $refs, self::USER_ID));
        $this->conn()->executeStatement('UPDATE c_lp_view SET user_id = ?, session_id = NULL WHERE iid = ?', [self::USER_ID, $viewId]);
        self::assertTrue($items[$second]->parse_prereq((string) $first, $items, $refs, self::USER_ID));
    }

    public function testQuizPrerequisiteIsUnblockedByTheAttemptsOfTheCurrentSessionOnly(): void
    {
        $sessionId = $this->newSession('li_t2_quiz_session');
        self::getContainer()->get(SettingsManager::class)->updateSetting('lp.lp_prerequisite_on_quiz_unblock_if_max_attempt_reached', 'true');

        $this->conn()->executeStatement(
            "INSERT INTO c_quiz (title, type, random, random_answers, results_disabled, max_attempt, feedback_type, expired_time,
                propagate_neg, review_answers, random_by_category, display_category_name, hide_question_title, page_result_configuration)
             VALUES ('li_t2_quiz', 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, '[]')"
        );
        $quizId = (int) $this->conn()->lastInsertId();
        $lpId = $this->newLpRow('li_t2_quiz_lp');
        $quizItem = $this->newItem($lpId, 'li_t2_quiz_item');
        $self = $this->newItem($lpId, 'li_t2_quiz_self');
        $this->conn()->executeStatement("UPDATE c_lp_item SET item_type = 'quiz', path = ?, mastery_score = 9 WHERE iid = ?", [(string) $quizId, $quizItem]);
        $viewId = $this->newView($lpId, $sessionId);
        $this->newItemView($viewId, $quizItem, 1, 'completed');
        $insertExe = function (?int $exeSession) use ($lpId, $quizItem, $quizId): void {
            $this->conn()->executeStatement(
                "INSERT INTO track_e_exercises (exe_user_id, c_id, session_id, exe_date, score, max_score, user_ip, status, data_tracking, start_date,
                    steps_counter, orig_lp_id, orig_lp_item_id, exe_duration, orig_lp_item_view_id, questions_to_check, exe_exo_id)
                 VALUES (?, 1, ?, '2030-01-01 10:00:00', 1, 10, '127.0.0.1', 'completed', '', '2030-01-01 10:00:00', 1, ?, ?, 0, 0, '', ?)",
                [self::USER_ID, $exeSession, $lpId, $quizItem, $quizId]
            );
        };
        Container::getSession()->set('sid', $sessionId);
        $items = [$quizItem => $this->item($quizItem, $viewId), $self => $this->item($self, $viewId)];
        $refs = [$quizItem => $quizItem, $self => $self];

        $insertExe(null);
        self::assertFalse($items[$self]->parse_prereq((string) $quizItem, $items, $refs, self::USER_ID), 'the only attempt belongs to no session');

        $insertExe($sessionId);
        self::assertTrue($items[$self]->parse_prereq((string) $quizItem, $items, $refs, self::USER_ID), 'one attempt in this session reaches the maximum');
    }
}
