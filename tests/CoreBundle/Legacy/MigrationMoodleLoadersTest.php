<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use ReflectionClass;

/**
 * The Chamilo side of the MigrationMoodle plugin loaders and scripts that read or write the learning path tables.
 *
 * Those queries used c_id columns that c_lp, c_lp_item, c_lp_item_view, c_tool_intro and c_quiz_rel_question no longer
 * have (the course is reached through the resource node link of the learning path or the course of the learning path
 * view), session.name (now title), and track_e_exercises.exe_result/exe_weighting (now score/max_score). The Moodle
 * database is never touched: every loader receives the values the extractors would have produced.
 */
final class MigrationMoodleLoadersTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private Course $course;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        Container::setContainer(self::getContainer());
        Container::setLegacyServices(self::getContainer());

        $plugin = api_get_path(SYS_PLUGIN_PATH).'MigrationMoodle/src/';

        require_once $plugin.'Interfaces/LoaderInterface.php';
        foreach (glob($plugin.'Loader/*.php') as $file) {
            require_once $file;
        }

        require_once $plugin.'Traits/MapTrait.php';

        require_once $plugin.'Script/BaseScript.php';

        require_once $plugin.'Script/UserLearnPathsProgressScript.php';

        require_once $plugin.'Script/UserScormsProgressScript.php';

        $this->course = $this->createCourse('moodle-migration');
        $this->user = $this->createUser('moodle_student');
    }

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function loader(string $class): object
    {
        $class = 'Chamilo\PluginBundle\MigrationMoodle\Loader\\'.$class;

        return new $class();
    }

    private function newLp(int $type = 1, string $title = 'lp'): CLp
    {
        $lp = (new CLp())
            ->setLpType($type)
            ->setTitle($title)
            ->setCreator($this->getUser('admin'))
            ->setParent($this->course)
            ->addCourseLink($this->course)
        ;
        self::getContainer()->get(CLpRepository::class)->createLp($lp);

        return $lp;
    }

    private function rootOf(CLp $lp): CLpItem
    {
        return $this->getEntityManager()->getRepository(CLpItem::class)->findOneBy(['lp' => $lp, 'itemType' => 'root']);
    }

    private function newItem(CLp $lp, string $title, string $type = 'document', int $order = 1, string $path = '0', ?CLpItem $parent = null): CLpItem
    {
        $item = (new CLpItem())
            ->setTitle($title)
            ->setItemType($type)
            ->setPath($path)
            ->setLp($lp)
            ->setParent($parent ?? $this->rootOf($lp))
            ->setDisplayOrder($order)
        ;
        $this->getEntityManager()->persist($item);
        $this->getEntityManager()->flush();

        return $item;
    }

    private function newSession(string $title = 'moodle session'): Session
    {
        $session = $this->createSession($title);
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (user_id, session_id, c_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, 0, 1, 0, 0)',
            [$this->user->getId(), $session->getId(), $this->course->getId()]
        );

        return $session;
    }

    private function newView(CLp $lp, ?Session $session): CLpView
    {
        $view = (new CLpView())
            ->setCourse($this->course)
            ->setLp($lp)
            ->setUser($this->user)
            ->setViewCount(1)
            ->setSession($session)
            ->setLastItem(0)
        ;
        $this->getEntityManager()->persist($view);
        $this->getEntityManager()->flush();

        return $view;
    }

    private function newItemView(CLpView $view, CLpItem $item, string $status = 'not attempted', int $start = 0, int $total = 0): CLpItemView
    {
        $itemView = (new CLpItemView())
            ->setItem($item)
            ->setView($view)
            ->setViewCount(1)
            ->setStatus($status)
            ->setStartTime($start)
            ->setTotalTime($total)
            ->setScore(0)
            ->setMaxScore('100')
        ;
        $this->getEntityManager()->persist($itemView);
        $this->getEntityManager()->flush();

        return $itemView;
    }

    private function itemViewRow(int $iid): array
    {
        return $this->conn()->fetchAssociative('SELECT * FROM c_lp_item_view WHERE iid = ?', [$iid]);
    }

    private function newQuiz(string $title = 'quiz'): CQuiz
    {
        $quiz = (new CQuiz())
            ->setTitle($title)
            ->setParent($this->course)
            ->setCreator($this->getUser('admin'))
            ->addCourseLink($this->course)
        ;
        self::getContainer()->get(CQuizRepository::class)->create($quiz);

        return $quiz;
    }

    public function testScormLoaderCreatesTheLpLinkedToTheCourse(): void
    {
        $created = new DateTime('2020-01-02 03:04:05');

        $lpId = $this->loader('CourseModulesScormLoader')->load([
            'c_id' => $this->course->getId(),
            'name' => 'My SCORM',
            'ref' => 'ORG-1',
            'path' => 'ignored',
            'use_max_score' => 100,
            'hide_toc_frame' => 1,
            'created_on' => $created,
            'modified_on' => $created,
            'publicated_on' => null,
        ]);

        $lp = $this->getEntityManager()->find(CLp::class, $lpId);
        $this->assertSame(CLp::SCORM_TYPE, $lp->getLpType());
        $this->assertSame('My SCORM', $lp->getTitle());
        $this->assertSame('ORG-1', $lp->getRef());
        $this->assertSame(1, $lp->getUseMaxScore());
        $this->assertTrue($lp->getHideTocFrame());
        $this->assertSame('scorm_api.php', $lp->getJsLib());
        $this->assertSame('My-SCORM/.', $lp->getPath());
        // The course is reached through the link of the resource node (c_lp has no c_id).
        $this->assertSame(
            1,
            (int) $this->conn()->fetchOne(
                'SELECT COUNT(*) FROM resource_link WHERE resource_node_id = ? AND c_id = ? AND session_id IS NULL',
                [$lp->getResourceNode()->getId(), $this->course->getId()]
            )
        );
        $this->assertSame(
            1,
            (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_item WHERE lp_id = ? AND item_type = ?', [$lpId, 'root'])
        );
    }

    public function testUserLearnPathsLoaderCreatesAViewAndItemViewsForTheLpsOfTheSubscribedCourses(): void
    {
        $session = $this->newSession();
        $lp = $this->newLp(1);
        $other = $this->newLp(2, 'scorm, not a chamilo lp');
        $first = $this->newItem($lp, 'first', 'document', 1);
        $first->setMaxScore(30.0);
        $this->getEntityManager()->flush();
        $this->newItem($lp, 'second', 'quiz', 2);

        $returned = $this->loader('UserLearnPathsLoader')->load(['user_id' => $this->user->getId()]);

        $this->assertSame($this->user->getId(), $returned);
        $views = $this->conn()->fetchAllAssociative('SELECT * FROM c_lp_view WHERE user_id = ?', [$this->user->getId()]);
        $this->assertCount(1, $views, 'Only the lp_type 1 learning path of the course is viewed');
        $this->assertSame($lp->getIid(), (int) $views[0]['lp_id']);
        $this->assertSame($this->course->getId(), (int) $views[0]['c_id']);
        $this->assertSame($session->getId(), (int) $views[0]['session_id']);
        $this->assertSame(0, (int) $views[0]['last_item']);

        // root, first, second, parents first then display order.
        $itemViews = $this->conn()->fetchAllAssociative(
            'SELECT lpi.title, lpiv.status, lpiv.max_score, lpiv.view_count FROM c_lp_item_view lpiv
             INNER JOIN c_lp_item lpi ON lpi.iid = lpiv.lp_item_id WHERE lpiv.lp_view_id = ? ORDER BY lpiv.iid',
            [$views[0]['iid']]
        );
        $this->assertSame(['root', 'first', 'second'], array_column($itemViews, 'title'));
        $this->assertSame(['not attempted'], array_values(array_unique(array_column($itemViews, 'status'))));
        $this->assertSame('30', $itemViews[1]['max_score']);
        $this->assertSame(0, (int) $this->conn()->fetchOne(
            'SELECT COUNT(*) FROM c_lp_view WHERE lp_id = ?',
            [$other->getIid()]
        ));
    }

    public function testUsersScormsViewLoaderCreatesThenReusesTheViews(): void
    {
        $session = $this->newSession();
        $lp = $this->newLp(2);
        $item = $this->newItem($lp, 'sco', 'sco');
        $data = [
            'user_id' => $this->user->getId(),
            'c_id' => $this->course->getId(),
            'lp_id' => $lp->getIid(),
            'lp_item_id' => $item->getIid(),
            'lp_item_view_count' => 2,
            'item_data' => ['status' => 'completed', 'total_time' => 50, 'score' => 80],
        ];

        $viewId = $this->loader('UsersScormsViewLoader')->load($data);
        $again = $this->loader('UsersScormsViewLoader')->load($data);

        $this->assertSame($viewId, $again, 'The existing lp view is reused');
        $view = $this->conn()->fetchAssociative('SELECT * FROM c_lp_view WHERE iid = ?', [$viewId]);
        $this->assertSame($session->getId(), (int) $view['session_id']);
        $this->assertSame($item->getIid(), (int) $view['last_item']);
        $this->assertSame(2, (int) $view['view_count']);
        $rows = $this->conn()->fetchAllAssociative('SELECT * FROM c_lp_item_view WHERE lp_view_id = ?', [$viewId]);
        $this->assertCount(1, $rows, 'The existing item view is updated, not duplicated');
        $this->assertSame('completed', $rows[0]['status']);
        $this->assertSame(50, (int) $rows[0]['total_time']);
        $this->assertSame(80.0, (float) $rows[0]['score']);
        $this->assertSame(2, (int) $rows[0]['view_count']);
    }

    public function testUsersScormsViewLoaderFailsWithoutSubscription(): void
    {
        $lp = $this->newLp(2);
        $item = $this->newItem($lp, 'sco', 'sco');

        $this->expectExceptionMessage('Session not found for user');
        $this->loader('UsersScormsViewLoader')->load([
            'user_id' => $this->user->getId(),
            'c_id' => $this->course->getId(),
            'lp_id' => $lp->getIid(),
            'lp_item_id' => $item->getIid(),
            'lp_item_view_count' => 1,
        ]);
    }

    public function testQuizLoaderUpdatesTheItemViewOfTheUserInThatSessionOnly(): void
    {
        $session = $this->newSession();
        $lp = $this->newLp();
        $item = $this->newItem($lp, 'quiz item', 'quiz');
        $mine = $this->newItemView($this->newView($lp, $session), $item);
        $noSession = $this->newItemView($this->newView($lp, null), $item);

        $returned = $this->loader('UserLearnPathQuizLoader')->load([
            'item_id' => $item->getIid(),
            'user_id' => $this->user->getId(),
            'session_id' => $session->getId(),
            'start_time' => 100,
            'total_time' => 20,
            'score' => 7,
            'status' => 'passed',
        ]);

        $this->assertSame($mine->getIid(), $returned);
        $this->assertEquals(
            ['start_time' => 100, 'total_time' => 20, 'score' => 7, 'status' => 'passed'],
            array_intersect_key($this->itemViewRow($mine->getIid()), array_flip(['start_time', 'total_time', 'score', 'status']))
        );
        $this->assertSame('not attempted', $this->itemViewRow($noSession->getIid())['status']);

        $this->expectExceptionMessage('Item view not found for item');
        $this->loader('UserLearnPathQuizLoader')->load([
            'item_id' => $item->getIid(),
            'user_id' => $this->user->getId(),
            'session_id' => $session->getId() + 1000,
            'start_time' => 0, 'total_time' => 0, 'score' => 0, 'status' => 'x',
        ]);
    }

    public function testQuizLoaderFindsTheViewOutOfAnySessionWithSessionZero(): void
    {
        $lp = $this->newLp();
        $item = $this->newItem($lp, 'quiz item', 'quiz');
        $noSession = $this->newItemView($this->newView($lp, null), $item);

        $returned = $this->loader('UserLearnPathQuizLoader')->load([
            'item_id' => $item->getIid(),
            'user_id' => $this->user->getId(),
            'session_id' => 0,
            'start_time' => 1, 'total_time' => 2, 'score' => 3, 'status' => 'completed',
        ]);

        $this->assertSame($noSession->getIid(), $returned);
    }

    public function testBranchLoaderChainsTheStartTimeOfTheItemFromTheItemBeforeIt(): void
    {
        $lp = $this->newLp();
        $first = $this->newItem($lp, 'first', 'document', 1);
        $second = $this->newItem($lp, 'second', 'document', 2);
        $first->setNextItemId($second->getIid());
        $this->getEntityManager()->flush();
        $view = $this->newView($lp, null);
        $firstView = $this->newItemView($view, $first, 'completed', 1000, 60);
        $secondView = $this->newItemView($view, $second);

        $returned = $this->loader('UserLearnPathLessonBranchLoader')->load([
            'item_id' => $second->getIid(),
            'user_id' => $this->user->getId(),
            'end_time' => 1200,
        ]);

        $this->assertSame($secondView->getIid(), $returned);
        $row = $this->itemViewRow($secondView->getIid());
        $this->assertSame('completed', $row['status']);
        $this->assertSame(1060, (int) $row['start_time'], 'Starts when the previous item ended');
        $this->assertSame(140, (int) $row['total_time']);

        // The first item (display_order 1) keeps its own start time.
        $this->loader('UserLearnPathLessonBranchLoader')->load([
            'item_id' => $first->getIid(),
            'user_id' => $this->user->getId(),
            'end_time' => 1100,
        ]);
        $this->assertSame(100, (int) $this->itemViewRow($firstView->getIid())['total_time']);
    }

    public function testBranchLoaderFailsForUnknownItem(): void
    {
        $this->expectExceptionMessage('not found');
        $this->loader('UserLearnPathLessonBranchLoader')->load([
            'item_id' => 999999,
            'user_id' => $this->user->getId(),
            'end_time' => 1,
        ]);
    }

    public function testAttemptLoaderScoresTheItemViewWithTheMaxScoreWhenCorrect(): void
    {
        $lp = $this->newLp();
        $item = $this->newItem($lp, 'only', 'document', 1);
        $view = $this->newView($lp, null);
        $itemView = $this->newItemView($view, $item);

        $this->loader('UserLearnPathLessonAttemptLoader')->load([
            'item_id' => $item->getIid(),
            'user_id' => $this->user->getId(),
            'end_time' => 10,
            'is_correct' => 1,
        ]);

        $this->assertSame(100.0, (float) $this->itemViewRow($itemView->getIid())['score']);
    }

    public function testTimerLoaderSetsTheStartTimeOfTheDirectoryAndOfItsFirstDocument(): void
    {
        $session = $this->newSession();
        $lp = $this->newLp();
        $dir = $this->newItem($lp, 'dir', 'dir', 1);
        $second = $this->newItem($lp, 'doc 2', 'document', 2, '0', $dir);
        $first = $this->newItem($lp, 'doc 1', 'document', 1, '0', $dir);
        $view = $this->newView($lp, $session);
        $dirView = $this->newItemView($view, $dir);
        $firstView = $this->newItemView($view, $first);
        $secondView = $this->newItemView($view, $second);

        $returned = $this->loader('UserLearnPathLessonTimerLoader')->load([
            'user_id' => $this->user->getId(),
            'parent_item_id' => $dir->getIid(),
            'session_id' => $session->getId(),
            'start_time' => 555,
        ]);

        $this->assertSame($firstView->getIid(), $returned);
        $this->assertSame(555, (int) $this->itemViewRow($dirView->getIid())['start_time']);
        $this->assertSame(555, (int) $this->itemViewRow($firstView->getIid())['start_time']);
        $this->assertSame(0, (int) $this->itemViewRow($secondView->getIid())['start_time']);
    }

    public function testQuizAttemptLoaderWritesTheTrackExerciseWithTheCourseOfTheView(): void
    {
        $session = $this->newSession();
        $quiz = $this->newQuiz();
        $lp = $this->newLp();
        $item = $this->newItem($lp, 'quiz item', 'quiz', 1, (string) $quiz->getIid());
        $itemView = $this->newItemView($this->newView($lp, $session), $item);

        $exeId = $this->loader('UserQuizAttemptLoader')->load([
            'exo_id' => $quiz->getIid(),
            'user_id' => $this->user->getId(),
            'session_id' => $session->getId(),
            'status' => 'finished',
            'data_tracking' => '1,2',
            'date' => new DateTime('2021-03-04 10:00:00', new DateTimeZone('UTC')),
            'duration' => 90,
            'weighting' => 10,
            'result' => '7.5',
        ]);

        $row = $this->conn()->fetchAssociative('SELECT * FROM track_e_exercises WHERE exe_id = ?', [$exeId]);
        $this->assertSame($this->course->getId(), (int) $row['c_id']);
        $this->assertSame($quiz->getIid(), (int) $row['exe_exo_id']);
        $this->assertSame($session->getId(), (int) $row['session_id']);
        $this->assertSame($lp->getIid(), (int) $row['orig_lp_id']);
        $this->assertSame($item->getIid(), (int) $row['orig_lp_item_id']);
        $this->assertSame($itemView->getIid(), (int) $row['orig_lp_item_view_id']);
        $this->assertSame('', $row['status']);
        $this->assertSame(7.5, (float) $row['score']);
        $this->assertSame(10.0, (float) $row['max_score']);
        $this->assertSame('2021-03-04 10:00:00', $row['start_date']);
        $this->assertSame('2021-03-04 10:01:30', $row['exe_date']);
        $this->assertSame(90, (int) $row['exe_duration']);
    }

    public function testQuizAttemptLoaderMarksUnfinishedAttemptsIncomplete(): void
    {
        $quiz = $this->newQuiz();
        $lp = $this->newLp();
        $item = $this->newItem($lp, 'quiz item', 'quiz', 1, (string) $quiz->getIid());
        $this->newItemView($this->newView($lp, null), $item);

        $exeId = $this->loader('UserQuizAttemptLoader')->load([
            'exo_id' => $quiz->getIid(),
            'user_id' => $this->user->getId(),
            'session_id' => 0,
            'status' => 'inprogress',
            'data_tracking' => '',
            'date' => new DateTime('2021-03-04 10:00:00', new DateTimeZone('UTC')),
            'duration' => 0,
            'weighting' => 0,
            'result' => 0,
        ]);

        $row = $this->conn()->fetchAssociative('SELECT status, session_id FROM track_e_exercises WHERE exe_id = ?', [$exeId]);
        $this->assertSame('incomplete', $row['status']);
        $this->assertNull($row['session_id']);
    }

    public function testQuizzesScoresLoaderSumsTheQuestionsOfThatQuizOnly(): void
    {
        $quiz = $this->newQuiz('scored');
        $otherQuiz = $this->newQuiz('other');
        $em = $this->getEntityManager();
        foreach ([[$quiz, 10.0, 1], [$quiz, 2.5, 2], [$otherQuiz, 99.0, 1]] as [$q, $points, $order]) {
            $question = (new CQuizQuestion())
                ->setQuestion('q')
                ->setType(1)
                ->setLevel(1)
                ->setPosition($order)
                ->setPonderation($points)
                ->setCreator($this->getUser('admin'))
                ->setParent($this->course)
                ->addCourseLink($this->course)
            ;
            self::getContainer()->get(CQuizQuestionRepository::class)->create($question);
            $em->persist((new CQuizRelQuestion())->setQuiz($q)->setQuestion($question)->setQuestionOrder($order));
        }
        $em->flush();
        $lp = $this->newLp();
        $item = $this->newItem($lp, 'quiz item', 'quiz');
        $empty = $this->newItem($lp, 'empty quiz item', 'quiz', 2);

        $returned = $this->loader('QuizzesScoresLoader')->load(['quiz_id' => $quiz->getIid(), 'item_id' => $item->getIid()]);
        $this->loader('QuizzesScoresLoader')->load(['quiz_id' => 999999, 'item_id' => $empty->getIid()]);

        $this->assertSame($item->getIid(), $returned);
        $this->assertSame(12.5, (float) $this->conn()->fetchOne('SELECT max_score FROM c_lp_item WHERE iid = ?', [$item->getIid()]));
        $this->assertSame(0.0, (float) $this->conn()->fetchOne('SELECT max_score FROM c_lp_item WHERE iid = ?', [$empty->getIid()]));
    }

    public function testCourseIntroductionLoaderCreatesTheHomepageIntroOfTheCourse(): void
    {
        $introId = $this->loader('CourseIntroductionLoader')->load([
            'c_id' => $this->course->getId(),
            'description' => '<p>Welcome</p>',
        ]);

        $row = $this->conn()->fetchAssociative(
            'SELECT i.intro_text, t.title, t.c_id, t.session_id FROM c_tool_intro i INNER JOIN c_tool t ON t.iid = i.c_tool_id WHERE i.iid = ?',
            [$introId]
        );
        $this->assertSame('<p>Welcome</p>', $row['intro_text']);
        $this->assertSame('course_homepage', $row['title']);
        $this->assertSame($this->course->getId(), (int) $row['c_id']);
        $this->assertNull($row['session_id']);
        $this->assertSame(
            1,
            (int) $this->conn()->fetchOne(
                'SELECT COUNT(*) FROM resource_link rl INNER JOIN c_tool_intro i ON i.resource_node_id = rl.resource_node_id WHERE i.iid = ? AND rl.c_id = ?',
                [$introId, $this->course->getId()]
            )
        );
    }

    public function testSortSectionModuleLoaderPutsTheDescriptionDocumentFirstThenTheOrderList(): void
    {
        $lp = $this->newLp();
        $a = $this->newItem($lp, 'a', 'quiz', 5);
        $b = $this->newItem($lp, 'b', 'quiz', 6);
        $description = $this->newItem($lp, 'Description', 'document', 7);
        // A document inside a directory is not "the description document".
        $dir = $this->newItem($lp, 'dir', 'dir', 8);
        $this->newItem($lp, 'nested', 'document', 9, '0', $dir);

        $returned = $this->loader('SortSectionModuleLoader')->load([
            'lp_id' => $lp->getIid(),
            'order_list' => [$b->getIid() => 0, $a->getIid() => 0],
        ]);

        $this->assertSame($lp->getIid(), $returned);
        $orders = $this->conn()->fetchAllAssociative(
            'SELECT iid, display_order FROM c_lp_item WHERE iid IN (?, ?, ?) ORDER BY display_order',
            [$a->getIid(), $b->getIid(), $description->getIid()]
        );
        $this->assertSame([$description->getIid(), $b->getIid(), $a->getIid()], array_map('intval', array_column($orders, 'iid')));
    }

    public function testSessionLoaderReusesTheSessionWithTheSameTitle(): void
    {
        $session = $this->createSession('same title');
        $loader = $this->loader('UserSessionLoader');
        $property = (new ReflectionClass($loader))->getProperty('loadMode');
        $property->setAccessible(true);
        $property->setValue($loader, 'reuse');

        $returned = $loader->load(['name' => 'same title', 'course_ids' => [1], 'user_id' => $this->user->getId()]);

        $this->assertSame($session->getId(), (int) $returned);
    }

    /**
     * @dataProvider progressScripts
     */
    public function testProgressScriptsCountTheCompletedItemsOfTheUserInTheCourseOfTheView(
        string $class,
        int $lpType,
        int $expectedForThisLp
    ): void {
        $lp = $this->newLp($lpType);
        $other = $this->newLp($lpType, 'other');
        $a = $this->newItem($lp, 'a', 'sco', 1);
        $b = $this->newItem($lp, 'b', 'sco', 2);
        $c = $this->newItem($lp, 'c', 'sco', 3);
        $view = $this->newView($lp, null);
        $this->newItemView($view, $a, 'completed');
        $this->newItemView($view, $b, 'completed');
        $this->newItemView($view, $c, 'incomplete');
        $otherItem = $this->newItem($other, 'o', 'sco', 1);
        $this->newItemView($this->newView($other, null), $otherItem, 'completed');

        $script = new $class();
        $method = (new ReflectionClass($script))->getMethod('countCompletedItems');
        $method->setAccessible(true);

        $this->assertSame(
            $expectedForThisLp,
            (int) $method->invoke($script, $this->user->getId(), $lp->getIid(), $this->course->getId())
        );
        $this->assertSame(
            0,
            (int) $method->invoke($script, $this->user->getId(), $lp->getIid(), $this->course->getId() + 1000),
            'Another course counts nothing'
        );
        $this->assertSame(
            0,
            (int) $method->invoke($script, $this->user->getId() + 1000, $lp->getIid(), $this->course->getId()),
            'Another user counts nothing'
        );
    }

    public static function progressScripts(): array
    {
        $ns = 'Chamilo\PluginBundle\MigrationMoodle\Script\\';

        // Both only count the lp type they migrate: a type 1 learning path for the first, a SCORM for the second.
        return [
            'learning paths' => [$ns.'UserLearnPathsProgressScript', 1, 2],
            'scorms' => [$ns.'UserScormsProgressScript', 2, 2],
        ];
    }
}
