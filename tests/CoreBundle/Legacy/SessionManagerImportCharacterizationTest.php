<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Monolog\Logger;
use SessionManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use const PHP_EOL;
use const PHP_SAPI;

/**
 * Characterization of SessionManager::importCSV() and SessionManager::importXML(): a small realistic file
 * is imported and every table the import writes is asserted with whole rows (session, session_rel_user,
 * session_rel_course, session_rel_course_rel_user, access_url_rel_session). Create, update-existing and the
 * error paths (unknown course, unknown user, unknown coach) are covered. Fixtures are raw inserts tagged "imp_".
 */
final class SessionManagerImportCharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private int $seq = 0;
    private array $userLabel = [];
    private array $courseLabel = [];
    private string $coach = '';
    private string $stu1 = '';
    private string $stu2 = '';
    private string $code1 = '';
    private string $code2 = '';

    protected function setUp(): void
    {
        parent::setUp();

        $httpSession = new HttpSession(new MockArraySessionStorage());
        $httpSession->set('_user', ['id' => $this->getAdmin()->getId()]);
        $httpSession->set('access_url_id', $this->getAccessUrl()->getId());
        $request = new Request();
        $request->setSession($httpSession);
        Container::setSession($httpSession);
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        $user = $this->getEntityManager()->getRepository(User::class)->find($this->getAdmin()->getId());
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles())
        );
        Container::getSession()->set('_user', ['id' => $this->getAdmin()->getId()]);
        $this->getEntityManager()->clear();
        Database::setManager($this->getEntityManager());
        Database::setConnection($this->getEntityManager()->getConnection());
    }

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function rawUser(string $suffix, int $status = 5): array
    {
        $name = 'imp_'.$suffix.'_'.getmypid().'_'.(++$this->seq);
        $this->conn()->executeStatement(
            'INSERT INTO user (username, username_canonical, email, email_canonical, firstname, lastname, locale,
                password, timezone, locked, expired, credentials_expired, salt, roles, status, active, uuid,
                mfa_enabled, created_at, updated_at, official_code)
             VALUES (?, ?, ?, ?, ?, ?, \'en_US\', \'x\', \'UTC\', 0, 0, 0, \'x\', ?, ?, 1,
                UNHEX(REPLACE(UUID(), \'-\', \'\')), 0, NOW(), NOW(), \'\')',
            [$name, $name, $name.'@example.com', $name.'@example.com', 'First'.$suffix, 'Last'.$suffix, json_encode([1 === $status ? 'ROLE_TEACHER' : 'ROLE_STUDENT']), $status]
        );
        $id = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (1, ?)', [$id]);

        return [$id, $name];
    }

    private function rawCourse(string $title): array
    {
        $code = strtoupper('imp_'.$title.'_'.getmypid().'_'.(++$this->seq));
        $this->conn()->executeStatement(
            'INSERT INTO course (language_id, title, code, visual_code, directory, visibility, video_url, sticky, creation_date, subscribe, unsubscribe, popularity)
             VALUES ((SELECT MIN(id) FROM language), ?, ?, ?, ?, 2, \'\', 0, NOW(), 1, 0, 0)',
            ['imp_'.$title, $code, $code, $code]
        );
        $id = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_course (access_url_id, c_id) VALUES (1, ?)', [$id]);

        return [$id, $code];
    }

    private function file(string $content, string $ext = 'csv'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imp').'.'.$ext;
        file_put_contents($path, $content);

        return $path;
    }

    private function utcLocal(?string $value): ?string
    {
        return null === $value ? null : api_get_local_time($value);
    }

    private function label(array $map, mixed $id): mixed
    {
        return null === $id ? null : ($map[(int) $id] ?? 'unknown#'.$id);
    }

    /**
     * Every row the import can write, with ids replaced by labels and timestamps by a marker, so the
     * expectations do not depend on auto-increment values or on the clock.
     */
    private function snapshot(): array
    {
        $out = [];
        $sessions = $this->conn()->fetchAllAssociative(
            'SELECT * FROM session WHERE title LIKE \'imp\_%\' ORDER BY title'
        );
        $sessionLabel = [];
        foreach ($sessions as $row) {
            $sessionLabel[(int) $row['id']] = $row['title'];
        }
        $out['session'] = [];
        foreach ($sessions as $row) {
            $out['session'][] = [
                'title' => $row['title'],
                'description' => $row['description'],
                'show_description' => $row['show_description'],
                'session_category_id' => $row['session_category_id'],
                'duration' => $row['duration'],
                'nbr_courses' => $row['nbr_courses'],
                'nbr_users' => $row['nbr_users'],
                'nbr_classes' => $row['nbr_classes'],
                'visibility' => $row['visibility'],
                'status' => $row['status'],
                'display_start_date' => $this->utcLocal($row['display_start_date']),
                'display_end_date' => $this->utcLocal($row['display_end_date']),
                'access_start_date' => $this->utcLocal($row['access_start_date']),
                'access_end_date' => $this->utcLocal($row['access_end_date']),
                'coach_access_start_date' => $this->utcLocal($row['coach_access_start_date']),
                'coach_access_end_date' => $this->utcLocal($row['coach_access_end_date']),
            ];
        }

        $ids = $sessionLabel ? implode(',', array_keys($sessionLabel)) : '0';
        $out['session_rel_user'] = [];
        foreach ($this->conn()->fetchAllAssociative("SELECT * FROM session_rel_user WHERE session_id IN ($ids)") as $row) {
            $out['session_rel_user'][] = [
                'session' => $sessionLabel[(int) $row['session_id']],
                'user' => $this->label($this->userLabel, $row['user_id']),
                'relation_type' => $row['relation_type'],
                'duration' => $row['duration'],
                'registered_at' => null === $row['registered_at'] ? null : 'NOW',
                'access_start_date' => $this->utcLocal($row['access_start_date']),
                'access_end_date' => $this->utcLocal($row['access_end_date']),
            ];
        }
        $out['session_rel_course'] = [];
        foreach ($this->conn()->fetchAllAssociative("SELECT * FROM session_rel_course WHERE session_id IN ($ids)") as $row) {
            $out['session_rel_course'][] = [
                'session' => $sessionLabel[(int) $row['session_id']],
                'course' => $this->label($this->courseLabel, $row['c_id']),
                'room_id' => $row['room_id'],
                'position' => $row['position'],
                'nbr_users' => $row['nbr_users'],
            ];
        }
        $out['session_rel_course_rel_user'] = [];
        foreach ($this->conn()->fetchAllAssociative("SELECT * FROM session_rel_course_rel_user WHERE session_id IN ($ids)") as $row) {
            $out['session_rel_course_rel_user'][] = [
                'session' => $sessionLabel[(int) $row['session_id']],
                'course' => $this->label($this->courseLabel, $row['c_id']),
                'user' => $this->label($this->userLabel, $row['user_id']),
                'status' => $row['status'],
                'visibility' => $row['visibility'],
                'legal_agreement' => $row['legal_agreement'],
                'progress' => $row['progress'],
                'subscription_date' => null === $row['subscription_date'] ? null : 'NOW',
            ];
        }
        $out['access_url_rel_session'] = [];
        foreach ($this->conn()->fetchAllAssociative("SELECT * FROM access_url_rel_session WHERE session_id IN ($ids)") as $row) {
            $out['access_url_rel_session'][] = [
                'session' => $sessionLabel[(int) $row['session_id']],
                'access_url_id' => $row['access_url_id'],
            ];
        }
        $cids = $this->courseLabel ? implode(',', array_keys($this->courseLabel)) : '0';
        $out['course_rel_user'] = [];
        foreach ($this->conn()->fetchAllAssociative("SELECT * FROM course_rel_user WHERE c_id IN ($cids)") as $row) {
            $out['course_rel_user'][] = [
                'course' => $this->label($this->courseLabel, $row['c_id']),
                'user' => $this->label($this->userLabel, $row['user_id']),
                'relation_type' => $row['relation_type'],
                'status' => $row['status'],
                'is_tutor' => $row['is_tutor'],
                'sort' => $row['sort'],
                'user_course_cat' => $row['user_course_cat'],
                'legal_agreement' => $row['legal_agreement'],
                'progress' => $row['progress'],
            ];
        }
        foreach ($out as $name => $rows) {
            usort($rows, static fn (array $a, array $b): int => strcmp(json_encode($a), json_encode($b)));
            $out[$name] = $rows;
        }

        return $out;
    }

    private function assertFrozen(string $name, mixed $actual): void
    {
        $expected = self::expected();
        $this->assertArrayHasKey($name, $expected, 'No frozen expectation for '.$name);
        $this->assertSame($expected[$name], $actual, $name);
    }

    private function importCsvFile(string $content, array $args = []): array
    {
        $csv = $this->file($content);

        try {
            $result = SessionManager::importCSV(
                $csv,
                $args['update'] ?? false,
                $args['defaultUserId'] ?? $this->getAdmin()->getId(),
                $args['logger'] ?? null,
                $args['extraFields'] ?? [],
                $args['extraFieldId'] ?? null,
                null,
                null,
                1,
                [],
                $args['deleteUsersNotInList'] ?? false,
                $args['updateCourseCoaches'] ?? false,
                $args['sessionWithCoursesModifier'] ?? false,
                $args['addOriginalTeachers'] ?? true,
                $args['removeAllTeachers'] ?? true,
                null,
                $args['teacherBackup'],
                $args['groupBackup']
            );
        } finally {
            unlink($csv);
        }
        $this->getEntityManager()->flush();

        return $result;
    }

    private function setUpPeople(): void
    {
        [$id, $this->coach] = $this->rawUser('coach', 1);
        $this->userLabel[$id] = 'coach';
        [$id, $this->stu1] = $this->rawUser('stu1');
        $this->userLabel[$id] = 'stu1';
        [$id, $this->stu2] = $this->rawUser('stu2');
        $this->userLabel[$id] = 'stu2';
        [$id, $this->code1] = $this->rawCourse('one');
        $this->courseLabel[$id] = 'c1';
        [$id, $this->code2] = $this->rawCourse('two');
        $this->courseLabel[$id] = 'c2';
        $this->userLabel[1] = 'admin';
    }

    private function userId(string $username): int
    {
        return (int) $this->conn()->fetchOne('SELECT id FROM user WHERE username = ?', [$username]);
    }

    private function courseId(string $code): int
    {
        return (int) $this->conn()->fetchOne('SELECT id FROM course WHERE code = ?', [$code]);
    }

    /**
     * A session that already exists, with students, two courses, a course student and a course coach per
     * course; the starting point of every "update existing" case.
     */
    private function existingSession(string $title): int
    {
        $start = api_get_utc_datetime('2020-01-01 00:00:00');
        $end = api_get_utc_datetime('2020-12-31 00:00:00');
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position, description,
                access_start_date, access_end_date, display_start_date, display_end_date, coach_access_start_date, coach_access_end_date)
             VALUES (?, 2, 1, 0, 2, 0, 0, \'old description\', ?, ?, ?, ?, ?, ?)',
            [$title, $start, $end, $start, $end, $start, $end]
        );
        $sid = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_session (session_id, access_url_id) VALUES (?, 1)', [$sid]);
        $rel = fn (int $user, int $type) => $this->conn()->executeStatement(
            'INSERT INTO session_rel_user (session_id, user_id, relation_type, duration, registered_at) VALUES (?, ?, ?, 0, NOW())',
            [$sid, $user, $type]
        );
        $rel($this->userId($this->stu1), 0);
        $rel($this->userId($this->coach), 3);
        foreach ([$this->code1, $this->code2] as $code) {
            $cid = $this->courseId($code);
            $this->conn()->executeStatement('INSERT INTO session_rel_course (session_id, c_id, position, nbr_users) VALUES (?, ?, 0, 1)', [$sid, $cid]);
            foreach ([[$this->stu1, 0], [$this->coach, 2]] as [$username, $status]) {
                $this->conn()->executeStatement(
                    'INSERT INTO session_rel_course_rel_user (session_id, c_id, user_id, status, visibility, legal_agreement, progress)
                     VALUES (?, ?, ?, ?, 1, 0, 0)',
                    [$sid, $cid, $this->userId($username), $status]
                );
            }
        }

        return $sid;
    }

    private function teacherOf(string $code, string $username, ?int $userCourseCat = null): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_rel_user (c_id, user_id, relation_type, status, is_tutor, sort, user_course_cat, legal_agreement, progress)
             VALUES (?, ?, 0, 1, 0, 5, ?, 0, 0)',
            [$this->courseId($code), $this->userId($username), $userCourseCat]
        );
    }

    private function group(string $code, string $title, string $member, string $tutor): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_group_info (title, status, max_student, doc_state, calendar_state, work_state, announcements_state,
                forum_state, wiki_state, chat_state, self_registration_allowed, self_unregistration_allowed, document_access)
             VALUES (?, 1, 10, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0)',
            ['imp_'.$title]
        );
        $gid = (int) $this->conn()->lastInsertId();
        $cid = $this->courseId($code);
        $this->conn()->executeStatement(
            'INSERT INTO c_group_rel_user (user_id, group_id, c_id, status, role) VALUES (?, ?, ?, 0, \'member\')',
            [$this->userId($member), $gid, $cid]
        );
        $this->conn()->executeStatement(
            'INSERT INTO c_group_rel_tutor (user_id, group_id, c_id) VALUES (?, ?, ?)',
            [$this->userId($tutor), $gid, $cid]
        );

        return $gid;
    }

    /**
     * The backup arrays filled by reference, with ids replaced by labels (group ids by the group title).
     */
    private function backups(array $teacherBackup, array $groupBackup, array $groupLabel): array
    {
        $norm = function (array $row) use ($groupLabel): array {
            unset($row['id'], $row['iid']);
            foreach (['user_id' => $this->userLabel, 'c_id' => $this->courseLabel] as $key => $map) {
                if (\array_key_exists($key, $row)) {
                    $row[$key] = $this->label($map, $row[$key]);
                }
            }
            if (\array_key_exists('group_id', $row)) {
                $row['group_id'] = $groupLabel[(int) $row['group_id']];
            }
            if (\array_key_exists('subscription_date', $row)) {
                $row['subscription_date'] = null === $row['subscription_date'] ? null : 'NOW';
            }

            return $row;
        };
        $out = ['teachers' => [], 'groups' => []];
        foreach ($teacherBackup as $uid => $perCourse) {
            foreach ($perCourse as $code => $row) {
                // Sorted by column name: the column order of course_rel_user depends on how the schema was built (a database
                // upgraded by migrations has the newer columns last, a fresh one in the order of the entity).
                $normalized = $norm($row);
                ksort($normalized);
                $out['teachers'][$this->userLabel[(int) $uid].'@'.$this->codeLabel($code)] = $normalized;
            }
        }
        foreach (['user', 'tutor'] as $kind) {
            foreach ($groupBackup[$kind] ?? [] as $uid => $perCourse) {
                foreach ($perCourse as $code => $perGroup) {
                    foreach ($perGroup as $gid => $row) {
                        $out['groups'][$kind.':'.$this->userLabel[(int) $uid].'@'.$this->codeLabel($code).'#'.$groupLabel[(int) $gid]] = $norm($row);
                    }
                }
            }
        }
        ksort($out['teachers']);
        ksort($out['groups']);

        return $out;
    }

    private function codeLabel(string $code): string
    {
        return $code === $this->code1 ? 'c1' : ($code === $this->code2 ? 'c2' : $code);
    }

    // ---------------------------------------------------------------- CSV

    /**
     * Create: sessions, general coach + admin, students, courses, course coach/student, counters, URL; the
     * unknown course and unknown user are skipped silently, the unknown course student and coach are reported.
     */
    public function testCsvCreate(): void
    {
        $this->setUpPeople();
        $tb = [];
        $gb = [];
        $result = $this->importCsvFile(
            "SessionName,Coach,DateStart,DateEnd,Users,Courses,SessionDescription,VisibilityAfterExpiration\n".
            'imp_sess_a,'.$this->coach.',2031-01-01,2031-06-30,'.$this->stu1.'|'.$this->stu2.'|ghostuser,"'.
            $this->code1.'['.$this->coach.',ghostcoach]['.$this->stu1.',ghoststu]|NOPE|'.$this->code2.'",a description,read_only'."\n".
            '',
            ['teacherBackup' => &$tb, 'groupBackup' => &$gb]
        );
        // A second row after one with a course coach is not imported in the same call: SessionManager then
        // calls EntityManager::merge(), which no longer exists (reported, not pinned).
        $second = $this->importCsvFile(
            "SessionName,Coach,DateStart,DateEnd,Users,Courses,SessionDescription,VisibilityAfterExpiration\n".
            "imp_sess_b,,2031-02-01,2031-03-01,,,,\n",
            ['teacherBackup' => &$tb, 'groupBackup' => &$gb]
        );
        $this->assertSame(1, $result['session_counter']);
        $this->assertSame(1, $second['session_counter']);
        $this->assertCount(1, $result['session_list']);
        $this->assertSame(
            'This user doesn\'t exist: "ghostcoach"'.(PHP_SAPI != 'cli' ? '<br />' : PHP_EOL).
            'This user doesn\'t exist: "ghoststu"'.(PHP_SAPI != 'cli' ? '<br />' : PHP_EOL),
            $result['error_message']
        );
        $this->assertFrozen('csv_create', $this->snapshot());
    }

    /**
     * One file, several rows, the first with a course coach: every row is imported, each with its general
     * coach and the default user as general coaches. Before the fix the second row fataled with
     * "Call to undefined method EntityManager::merge()" once the default user was detached from the
     * entity manager by the course coach processing of the first row.
     */
    public function testCsvCreateImportsEveryRowOfOneFileAfterARowWithACourseCoach(): void
    {
        $this->setUpPeople();
        $tb = [];
        $gb = [];
        $result = $this->importCsvFile(
            "SessionName,Coach,DateStart,DateEnd,Users,Courses\n".
            'imp_multi_a,'.$this->coach.',2031-01-01,2031-06-30,'.$this->stu1.',"'.$this->code1.'['.$this->coach."]\"\n".
            'imp_multi_b,'.$this->coach.',2031-02-01,2031-03-01,'.$this->stu2.",\n".
            "imp_multi_c,,2031-04-01,2031-05-01,,\n",
            ['teacherBackup' => &$tb, 'groupBackup' => &$gb]
        );
        $this->assertSame(3, $result['session_counter']);
        $this->assertCount(3, $result['session_list']);
        $adminId = $this->getAdmin()->getId();
        $generalCoaches = [];
        foreach (['imp_multi_a', 'imp_multi_b', 'imp_multi_c'] as $title) {
            $generalCoaches[$title] = $this->conn()->fetchFirstColumn(
                'SELECT srcu.user_id FROM session_rel_user srcu INNER JOIN session s ON s.id = srcu.session_id
                 WHERE s.title = ? AND srcu.relation_type = 3 ORDER BY srcu.user_id',
                [$title]
            );
        }
        $coachId = $this->userId($this->coach);
        $this->assertEquals([$adminId, $coachId], array_map('intval', $generalCoaches['imp_multi_a']), 'row 1');
        $this->assertEquals([$adminId, $coachId], array_map('intval', $generalCoaches['imp_multi_b']), 'row 2');
        $this->assertEquals([$adminId], array_map('intval', $generalCoaches['imp_multi_c']), 'row 3');
    }

    /**
     * Same, in update mode: the existing session of the second row gets its general coach back.
     */
    public function testCsvUpdateImportsEveryRowOfOneFileAfterARowWithACourseCoach(): void
    {
        $this->setUpPeople();
        $this->existingSession('imp_upd_a');
        $this->existingSession('imp_upd_b');
        $tb = [];
        $gb = [];
        $result = $this->importCsvFile(
            "SessionName,Coach,DateStart,DateEnd,Users,Courses\n".
            'imp_upd_a,'.$this->coach.',2031-01-01,2031-06-30,'.$this->stu2.',"'.$this->code1.'['.$this->coach."]\"\n".
            'imp_upd_b,'.$this->coach.',2031-02-01,2031-03-01,'.$this->stu2.",\n",
            ['update' => true, 'teacherBackup' => &$tb, 'groupBackup' => &$gb]
        );
        $this->assertSame(2, $result['session_counter']);
        foreach (['imp_upd_a', 'imp_upd_b'] as $title) {
            $this->assertSame(
                [$this->userId($this->coach)],
                array_map('intval', $this->conn()->fetchFirstColumn(
                    'SELECT srcu.user_id FROM session_rel_user srcu INNER JOIN session s ON s.id = srcu.session_id
                     WHERE s.title = ? AND srcu.relation_type = 3',
                    [$title]
                )),
                $title
            );
        }
    }

    /**
     * A name that already exists is made unique with " - 2", " - 3" (the loop retries the plain name once);
     * a course listed twice is inserted once (INSERT IGNORE) but counted twice.
     */
    public function testCsvCreateMakesNamesUniqueAndIgnoresDuplicateCourse(): void
    {
        $this->setUpPeople();
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position) VALUES (\'imp_dup\', 0, 0, 0, 1, 0, 0)'
        );
        $tb = [];
        $gb = [];
        $result = $this->importCsvFile(
            "SessionName,DateStart,DateEnd,Courses\n".
            'imp_dup,2031-01-01,2031-06-30,"'.$this->code1.'|'.$this->code1."\"\n".
            "imp_dup,2031-01-01,2031-06-30,\n",
            ['teacherBackup' => &$tb, 'groupBackup' => &$gb]
        );
        $this->assertSame(2, $result['session_counter']);
        $this->assertFrozen('csv_create_unique', $this->snapshot());
    }

    /**
     * Update with a name that does not exist creates the session.
     */
    public function testCsvUpdateOfUnknownNameCreatesTheSession(): void
    {
        $this->setUpPeople();
        $tb = [];
        $gb = [];
        $result = $this->importCsvFile(
            "SessionName,DateStart,DateEnd,Users,Courses\n".
            'imp_new_by_update,2031-01-01,2031-06-30,'.$this->stu1.','.$this->code1."\n",
            ['update' => true, 'teacherBackup' => &$tb, 'groupBackup' => &$gb]
        );
        $this->assertSame(1, $result['session_counter']);
        $this->assertFrozen('csv_update_unknown', $this->snapshot());
    }

    /**
     * Update existing, default flags, two courses: students and course students are removed and
     * re-added, course coaches are kept; the session fields change.
     */
    public function testCsvUpdateExistingKeepsCourseCoaches(): void
    {
        $this->setUpPeople();
        $this->existingSession('imp_upd');
        $tb = [];
        $gb = [];
        $result = $this->importCsvFile(
            "SessionName,Coach,DateStart,DateEnd,Users,Courses,SessionDescription\n".
            'imp_upd,'.$this->coach.',2031-01-01,2031-06-30,'.$this->stu2.',"'.$this->code1.'|'.$this->code2.'['.$this->coach.']['.$this->stu2."]\",new description\n",
            ['update' => true, 'teacherBackup' => &$tb, 'groupBackup' => &$gb]
        );
        $this->assertSame(1, $result['session_counter']);
        $this->assertFrozen('csv_update_keep_coaches', $this->snapshot());
    }

    /**
     * Update existing with updateCourseCoaches: course coaches are removed too.
     */
    public function testCsvUpdateExistingWithUpdateCourseCoachesRemovesCoaches(): void
    {
        $this->setUpPeople();
        $this->existingSession('imp_upd');
        $tb = [];
        $gb = [];
        $this->importCsvFile(
            "SessionName,DateStart,DateEnd,Users,Courses\n".
            'imp_upd,2031-01-01,2031-06-30,'.$this->stu2.',"'.$this->code1.'|'.$this->code2."\"\n",
            ['update' => true, 'updateCourseCoaches' => true, 'teacherBackup' => &$tb, 'groupBackup' => &$gb]
        );
        $this->assertFrozen('csv_update_coaches_removed', $this->snapshot());
    }

    /**
     * Update existing with a single course in the file: the course coaches are removed even without
     * updateCourseCoaches.
     */
    public function testCsvUpdateExistingWithOneCourseRemovesCourseCoaches(): void
    {
        $this->setUpPeople();
        $this->existingSession('imp_upd');
        $tb = [];
        $gb = [];
        $this->importCsvFile(
            "SessionName,DateStart,DateEnd,Courses\n".
            'imp_upd,2031-01-01,2031-06-30,'.$this->code1."\n",
            ['update' => true, 'teacherBackup' => &$tb, 'groupBackup' => &$gb]
        );
        $this->assertFrozen('csv_update_one_course', $this->snapshot());
    }

    /**
     * Update matched by an external id (session extra field): the title of the matched session is rewritten.
     */
    public function testCsvUpdateByExternalIdRenamesTheSession(): void
    {
        $this->setUpPeople();
        $sid = $this->existingSession('imp_old_title');
        $this->conn()->executeStatement(
            'INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, visible_to_others, changeable, filter, created_at, auto_remove)
             VALUES (3, 1, \'imp_external_id\', \'External id\', 1, 1, 1, 0, NOW(), 0)'
        );
        $fieldId = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            'INSERT INTO extra_field_values (field_id, field_value, item_id, created_at, updated_at) VALUES (?, \'EXT-1\', ?, NOW(), NOW())',
            [$fieldId, $sid]
        );
        $tb = [];
        $gb = [];
        $result = $this->importCsvFile(
            "SessionName,DateStart,DateEnd,Courses,extra_imp_external_id\n".
            'imp_new_title,2031-01-01,2031-06-30,'.$this->code1.",EXT-1\n",
            [
                'update' => true,
                'logger' => new Logger('imp'),
                'extraFields' => ['extra_imp_external_id' => 'extra_imp_external_id'],
                'extraFieldId' => 'imp_external_id',
                'teacherBackup' => &$tb,
                'groupBackup' => &$gb,
            ]
        );
        $this->assertSame(1, $result['session_counter']);
        $this->assertSame([$sid], array_map('intval', $result['session_list']));
        $this->assertFrozen('csv_update_by_external_id', $this->snapshot());
    }

    /**
     * Course teachers that are not in the coach list are unsubscribed from the base course and their course
     * row and group memberships are written to the by-reference backups (removeAllTeachersFromCourse).
     */
    public function testCsvRemoveAllTeachersBacksUpTheOnesItRemoves(): void
    {
        $this->setUpPeople();
        [$id, $other] = $this->rawUser('other', 1);
        $this->userLabel[$id] = 'other';
        $this->teacherOf($this->code1, $this->coach);
        $this->teacherOf($this->code1, $other);
        $g1 = $this->group($this->code1, 'g1', $other, $other);
        $groupLabel = [$g1 => 'g1'];
        $tb = [];
        $gb = [];
        $this->importCsvFile(
            "SessionName,DateStart,DateEnd,Courses\n".
            'imp_rm,2031-01-01,2031-06-30,"'.$this->code1.'['.$this->coach."]\"\n",
            ['addOriginalTeachers' => false, 'teacherBackup' => &$tb, 'groupBackup' => &$gb]
        );
        $this->assertFrozen('csv_remove_all_teachers', [
            'snapshot' => $this->snapshot(),
            'backups' => $this->backups($tb, $gb, $groupLabel),
        ]);
    }

    /**
     * sessionWithCoursesModifier with two courses: only the first listed coach stays teacher of each base
     * course, the other teachers are unsubscribed and backed up.
     */
    public function testCsvOnlyFirstCoachBacksUpTheTeachersItRemoves(): void
    {
        $this->setUpPeople();
        [$id, $other] = $this->rawUser('other', 1);
        $this->userLabel[$id] = 'other';
        foreach ([$this->code1, $this->code2] as $code) {
            $this->teacherOf($code, $this->coach);
            $this->teacherOf($code, $other, 0);
        }
        $g1 = $this->group($this->code1, 'g1', $other, $other);
        $g2 = $this->group($this->code2, 'g2', $other, $other);
        $groupLabel = [$g1 => 'g1', $g2 => 'g2'];
        $tb = [];
        $gb = [];
        $this->importCsvFile(
            "SessionName,DateStart,DateEnd,Courses\n".
            'imp_first,2031-01-01,2031-06-30,"'.$this->code1.'['.$this->coach.']|'.$this->code2.'['.$this->coach."]\"\n",
            ['sessionWithCoursesModifier' => true, 'addOriginalTeachers' => false, 'teacherBackup' => &$tb, 'groupBackup' => &$gb]
        );
        $this->assertFrozen('csv_only_first_coach', [
            'snapshot' => $this->snapshot(),
            'backups' => $this->backups($tb, $gb, $groupLabel),
        ]);
    }

    public function testCsvFileErrors(): void
    {
        $this->setUpPeople();
        $tb = [];
        $gb = [];
        $semi = $this->importCsvFile("SessionName;DateStart;DateEnd\nimp_x;2031-01-01;2031-02-01\n", ['teacherBackup' => &$tb, 'groupBackup' => &$gb]);
        $missing = $this->importCsvFile("SessionName,DateStart\nimp_x,2031-01-01\n", ['teacherBackup' => &$tb, 'groupBackup' => &$gb]);
        $this->assertFrozen('csv_errors', [
            'semicolon' => [0 === $semi['session_counter'], [] === $semi['session_list'], '' !== (string) $semi['error_message']],
            'missing' => [0 === $missing['session_counter'], [] === $missing['session_list'], '' !== (string) $missing['error_message']],
            'rows' => $this->snapshot()['session'],
        ]);
    }

    // ---------------------------------------------------------------- XML

    private function importXmlString(string $xml, bool $update = false): array
    {
        $file = $this->file($xml, 'xml');
        $inserted = [];
        $error = '';

        try {
            $result = SessionManager::importXML($file, $update, $this->getAdmin()->getId(), false, 1, $inserted, $error);
        } finally {
            unlink($file);
        }
        $this->getEntityManager()->flush();

        return [$result, $error];
    }

    private function xmlSession(string $name, string $tutor, string $courseXml, string $users): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Sessions><Session><SessionName>'.$name.'</SessionName><Tutor>'.$tutor.'</Tutor>'.
            '<DateStart>2031/01/01</DateStart><DateEnd>2031/06/30</DateEnd>'.$users.$courseXml.'</Session></Sessions>';
    }

    public function testXmlCreate(): void
    {
        $this->setUpPeople();
        $xml = $this->xmlSession(
            'imp_xml_a',
            $this->coach,
            '<Course><CourseCode>'.$this->code1.'</CourseCode><Tutor>'.$this->coach.',ghostcoach</Tutor><User>'.$this->stu1.'</User><User>ghostuser</User></Course>'.
            '<Course><CourseCode>NOPE</CourseCode></Course>'.
            '<Course><CourseCode>'.$this->code2.'</CourseCode></Course>',
            '<User>'.$this->stu1.'</User><User>'.$this->stu2.'</User><User>ghostuser</User>'
        );
        [$result, $error] = $this->importXmlString($xml);
        $this->assertSame(1, $result['session_counter']);
        $this->assertSame('', $error);
        $this->assertFrozen('xml_create', $this->snapshot());
    }

    public function testXmlUpdateExistingReplacesEveryRelation(): void
    {
        $this->setUpPeople();
        $sid = $this->existingSession('imp_xml_upd');
        $xml = $this->xmlSession(
            'imp_xml_upd',
            $this->coach,
            '<Course><CourseCode>'.$this->code2.'</CourseCode><User>'.$this->stu2.'</User></Course>',
            '<User>'.$this->stu2.'</User>'
        );
        [$result] = $this->importXmlString($xml, true);
        $this->assertSame([$sid], array_map('intval', $result['session_list']));
        $this->assertFrozen('xml_update', $this->snapshot());
    }

    public function testXmlUpdateOfUnknownNameCreatesTheSession(): void
    {
        $this->setUpPeople();
        [$created] = $this->importXmlString($this->xmlSession('imp_xml_new', $this->coach, '', ''), true);
        $this->assertFrozen('xml_misc', [
            'created' => $created['session_counter'],
            'snapshot' => $this->snapshot(),
        ]);
    }

    /**
     * Recorded on the pre-migration code; ids are replaced by labels, timestamps by NOW.
     */
    private static function expected(): array
    {
        return [
            'csv_create' => [
                'session' => [
                    [
                        'title' => 'imp_sess_a',
                        'description' => 'a description',
                        'show_description' => null,
                        'session_category_id' => null,
                        'duration' => 0,
                        'nbr_courses' => 2,
                        'nbr_users' => 2,
                        'nbr_classes' => 0,
                        'visibility' => 1,
                        'status' => 0,
                        'display_start_date' => '2031-01-01 00:00:00',
                        'display_end_date' => '2031-06-30 23:59:59',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                        'coach_access_start_date' => '2031-01-01 00:00:00',
                        'coach_access_end_date' => '2031-06-30 23:59:59',
                    ],
                    [
                        'title' => 'imp_sess_b',
                        'description' => null,
                        'show_description' => null,
                        'session_category_id' => null,
                        'duration' => 0,
                        'nbr_courses' => 0,
                        'nbr_users' => 0,
                        'nbr_classes' => 0,
                        'visibility' => 1,
                        'status' => 0,
                        'display_start_date' => '2031-02-01 00:00:00',
                        'display_end_date' => '2031-03-01 23:59:59',
                        'access_start_date' => '2031-02-01 00:00:00',
                        'access_end_date' => '2031-03-01 23:59:59',
                        'coach_access_start_date' => '2031-02-01 00:00:00',
                        'coach_access_end_date' => '2031-03-01 23:59:59',
                    ],
                ],
                'session_rel_user' => [
                    [
                        'session' => 'imp_sess_a',
                        'user' => 'admin',
                        'relation_type' => 3,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                    ],
                    [
                        'session' => 'imp_sess_a',
                        'user' => 'coach',
                        'relation_type' => 2,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                    ],
                    [
                        'session' => 'imp_sess_a',
                        'user' => 'coach',
                        'relation_type' => 3,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                    ],
                    [
                        'session' => 'imp_sess_a',
                        'user' => 'stu1',
                        'relation_type' => 0,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                    ],
                    [
                        'session' => 'imp_sess_a',
                        'user' => 'stu2',
                        'relation_type' => 0,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                    ],
                    [
                        'session' => 'imp_sess_b',
                        'user' => 'admin',
                        'relation_type' => 3,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2031-02-01 00:00:00',
                        'access_end_date' => '2031-03-01 23:59:59',
                    ],
                ],
                'session_rel_course' => [
                    [
                        'session' => 'imp_sess_a',
                        'course' => 'c1',
                        'room_id' => null,
                        'position' => 0,
                        'nbr_users' => 1,
                    ],
                    [
                        'session' => 'imp_sess_a',
                        'course' => 'c2',
                        'room_id' => null,
                        'position' => 0,
                        'nbr_users' => 0,
                    ],
                ],
                'session_rel_course_rel_user' => [
                    [
                        'session' => 'imp_sess_a',
                        'course' => 'c1',
                        'user' => 'coach',
                        'status' => 2,
                        'visibility' => 1,
                        'legal_agreement' => 0,
                        'progress' => 0,
                        'subscription_date' => 'NOW',
                    ],
                    [
                        'session' => 'imp_sess_a',
                        'course' => 'c1',
                        'user' => 'stu1',
                        'status' => 0,
                        'visibility' => 1,
                        'legal_agreement' => 0,
                        'progress' => 0,
                        'subscription_date' => 'NOW',
                    ],
                ],
                'access_url_rel_session' => [
                    [
                        'session' => 'imp_sess_a',
                        'access_url_id' => 1,
                    ],
                    [
                        'session' => 'imp_sess_b',
                        'access_url_id' => 1,
                    ],
                ],
                'course_rel_user' => [
                    [
                        'course' => 'c1',
                        'user' => 'coach',
                        'relation_type' => 0,
                        'status' => 1,
                        'is_tutor' => 0,
                        'sort' => 1,
                        'user_course_cat' => 0,
                        'legal_agreement' => null,
                        'progress' => 0,
                    ],
                ],
            ],
            'csv_create_unique' => [
                'session' => [
                    [
                        'title' => 'imp_dup - 2',
                        'description' => null,
                        'show_description' => null,
                        'session_category_id' => null,
                        'duration' => 0,
                        'nbr_courses' => 2,
                        'nbr_users' => 0,
                        'nbr_classes' => 0,
                        'visibility' => 1,
                        'status' => 0,
                        'display_start_date' => '2031-01-01 00:00:00',
                        'display_end_date' => '2031-06-30 23:59:59',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                        'coach_access_start_date' => '2031-01-01 00:00:00',
                        'coach_access_end_date' => '2031-06-30 23:59:59',
                    ],
                    [
                        'title' => 'imp_dup - 3',
                        'description' => null,
                        'show_description' => null,
                        'session_category_id' => null,
                        'duration' => 0,
                        'nbr_courses' => 0,
                        'nbr_users' => 0,
                        'nbr_classes' => 0,
                        'visibility' => 1,
                        'status' => 0,
                        'display_start_date' => '2031-01-01 00:00:00',
                        'display_end_date' => '2031-06-30 23:59:59',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                        'coach_access_start_date' => '2031-01-01 00:00:00',
                        'coach_access_end_date' => '2031-06-30 23:59:59',
                    ],
                    [
                        'title' => 'imp_dup',
                        'description' => null,
                        'show_description' => null,
                        'session_category_id' => null,
                        'duration' => null,
                        'nbr_courses' => 0,
                        'nbr_users' => 0,
                        'nbr_classes' => 0,
                        'visibility' => 1,
                        'status' => 0,
                        'display_start_date' => null,
                        'display_end_date' => null,
                        'access_start_date' => null,
                        'access_end_date' => null,
                        'coach_access_start_date' => null,
                        'coach_access_end_date' => null,
                    ],
                ],
                'session_rel_user' => [
                    [
                        'session' => 'imp_dup - 2',
                        'user' => 'admin',
                        'relation_type' => 3,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                    ],
                    [
                        'session' => 'imp_dup - 3',
                        'user' => 'admin',
                        'relation_type' => 3,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                    ],
                ],
                'session_rel_course' => [
                    [
                        'session' => 'imp_dup - 2',
                        'course' => 'c1',
                        'room_id' => null,
                        'position' => 0,
                        'nbr_users' => 0,
                    ],
                ],
                'session_rel_course_rel_user' => [],
                'access_url_rel_session' => [
                    [
                        'session' => 'imp_dup - 2',
                        'access_url_id' => 1,
                    ],
                    [
                        'session' => 'imp_dup - 3',
                        'access_url_id' => 1,
                    ],
                ],
                'course_rel_user' => [],
            ],
            'csv_update_unknown' => [
                'session' => [
                    [
                        'title' => 'imp_new_by_update',
                        'description' => null,
                        'show_description' => null,
                        'session_category_id' => null,
                        'duration' => 0,
                        'nbr_courses' => 1,
                        'nbr_users' => 2,
                        'nbr_classes' => 0,
                        'visibility' => 1,
                        'status' => 0,
                        'display_start_date' => '2031-01-01 00:00:00',
                        'display_end_date' => '2031-06-30 23:59:59',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                        'coach_access_start_date' => '2031-01-01 00:00:00',
                        'coach_access_end_date' => '2031-06-30 23:59:59',
                    ],
                ],
                'session_rel_user' => [
                    [
                        'session' => 'imp_new_by_update',
                        'user' => 'admin',
                        'relation_type' => 3,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                    ],
                    [
                        'session' => 'imp_new_by_update',
                        'user' => 'stu1',
                        'relation_type' => 0,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                    ],
                ],
                'session_rel_course' => [
                    [
                        'session' => 'imp_new_by_update',
                        'course' => 'c1',
                        'room_id' => null,
                        'position' => 0,
                        'nbr_users' => 0,
                    ],
                ],
                'session_rel_course_rel_user' => [],
                'access_url_rel_session' => [
                    [
                        'session' => 'imp_new_by_update',
                        'access_url_id' => 1,
                    ],
                ],
                'course_rel_user' => [],
            ],
            'csv_update_keep_coaches' => [
                'session' => [
                    [
                        'title' => 'imp_upd',
                        'description' => 'new description',
                        'show_description' => 0,
                        'session_category_id' => null,
                        'duration' => null,
                        'nbr_courses' => 2,
                        'nbr_users' => 1,
                        'nbr_classes' => 0,
                        'visibility' => 1,
                        'status' => 0,
                        'display_start_date' => '2031-01-01 00:00:00',
                        'display_end_date' => '2031-06-30 23:59:59',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                        'coach_access_start_date' => '2031-01-01 00:00:00',
                        'coach_access_end_date' => '2031-06-30 23:59:59',
                    ],
                ],
                'session_rel_user' => [
                    [
                        'session' => 'imp_upd',
                        'user' => 'coach',
                        'relation_type' => 3,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2020-01-01 00:00:00',
                        'access_end_date' => '2020-12-31 00:00:00',
                    ],
                    [
                        'session' => 'imp_upd',
                        'user' => 'stu2',
                        'relation_type' => 0,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2020-01-01 00:00:00',
                        'access_end_date' => '2020-12-31 00:00:00',
                    ],
                ],
                'session_rel_course' => [
                    [
                        'session' => 'imp_upd',
                        'course' => 'c1',
                        'room_id' => null,
                        'position' => 0,
                        'nbr_users' => 0,
                    ],
                    [
                        'session' => 'imp_upd',
                        'course' => 'c2',
                        'room_id' => null,
                        'position' => 0,
                        'nbr_users' => 1,
                    ],
                ],
                'session_rel_course_rel_user' => [
                    [
                        'session' => 'imp_upd',
                        'course' => 'c1',
                        'user' => 'coach',
                        'status' => 2,
                        'visibility' => 1,
                        'legal_agreement' => 0,
                        'progress' => 0,
                        'subscription_date' => 'NOW',
                    ],
                    [
                        'session' => 'imp_upd',
                        'course' => 'c2',
                        'user' => 'coach',
                        'status' => 2,
                        'visibility' => 1,
                        'legal_agreement' => 0,
                        'progress' => 0,
                        'subscription_date' => 'NOW',
                    ],
                    [
                        'session' => 'imp_upd',
                        'course' => 'c2',
                        'user' => 'stu2',
                        'status' => 0,
                        'visibility' => 1,
                        'legal_agreement' => 0,
                        'progress' => 0,
                        'subscription_date' => 'NOW',
                    ],
                ],
                'access_url_rel_session' => [
                    [
                        'session' => 'imp_upd',
                        'access_url_id' => 1,
                    ],
                ],
                'course_rel_user' => [
                    [
                        'course' => 'c2',
                        'user' => 'coach',
                        'relation_type' => 0,
                        'status' => 1,
                        'is_tutor' => 0,
                        'sort' => 1,
                        'user_course_cat' => 0,
                        'legal_agreement' => null,
                        'progress' => 0,
                    ],
                ],
            ],
            'csv_update_coaches_removed' => [
                'session' => [
                    [
                        'title' => 'imp_upd',
                        'description' => 'old description',
                        'show_description' => 0,
                        'session_category_id' => null,
                        'duration' => null,
                        'nbr_courses' => 2,
                        'nbr_users' => 3,
                        'nbr_classes' => 0,
                        'visibility' => 1,
                        'status' => 0,
                        'display_start_date' => '2031-01-01 00:00:00',
                        'display_end_date' => '2031-06-30 23:59:59',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                        'coach_access_start_date' => '2031-01-01 00:00:00',
                        'coach_access_end_date' => '2031-06-30 23:59:59',
                    ],
                ],
                'session_rel_user' => [
                    [
                        'session' => 'imp_upd',
                        'user' => 'admin',
                        'relation_type' => 3,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2020-01-01 00:00:00',
                        'access_end_date' => '2020-12-31 00:00:00',
                    ],
                    [
                        'session' => 'imp_upd',
                        'user' => 'stu2',
                        'relation_type' => 0,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2020-01-01 00:00:00',
                        'access_end_date' => '2020-12-31 00:00:00',
                    ],
                ],
                'session_rel_course' => [
                    [
                        'session' => 'imp_upd',
                        'course' => 'c1',
                        'room_id' => null,
                        'position' => 0,
                        'nbr_users' => 0,
                    ],
                    [
                        'session' => 'imp_upd',
                        'course' => 'c2',
                        'room_id' => null,
                        'position' => 0,
                        'nbr_users' => 0,
                    ],
                ],
                'session_rel_course_rel_user' => [],
                'access_url_rel_session' => [
                    [
                        'session' => 'imp_upd',
                        'access_url_id' => 1,
                    ],
                ],
                'course_rel_user' => [],
            ],
            'csv_update_one_course' => [
                'session' => [
                    [
                        'title' => 'imp_upd',
                        'description' => 'old description',
                        'show_description' => 0,
                        'session_category_id' => null,
                        'duration' => null,
                        'nbr_courses' => 1,
                        'nbr_users' => 0,
                        'nbr_classes' => 0,
                        'visibility' => 1,
                        'status' => 0,
                        'display_start_date' => '2031-01-01 00:00:00',
                        'display_end_date' => '2031-06-30 23:59:59',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                        'coach_access_start_date' => '2031-01-01 00:00:00',
                        'coach_access_end_date' => '2031-06-30 23:59:59',
                    ],
                ],
                'session_rel_user' => [
                    [
                        'session' => 'imp_upd',
                        'user' => 'admin',
                        'relation_type' => 3,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2020-01-01 00:00:00',
                        'access_end_date' => '2020-12-31 00:00:00',
                    ],
                ],
                'session_rel_course' => [
                    [
                        'session' => 'imp_upd',
                        'course' => 'c1',
                        'room_id' => null,
                        'position' => 0,
                        'nbr_users' => 0,
                    ],
                ],
                'session_rel_course_rel_user' => [],
                'access_url_rel_session' => [
                    [
                        'session' => 'imp_upd',
                        'access_url_id' => 1,
                    ],
                ],
                'course_rel_user' => [],
            ],
            'csv_update_by_external_id' => [
                'session' => [
                    [
                        'title' => 'imp_new_title',
                        'description' => 'old description',
                        'show_description' => 0,
                        'session_category_id' => null,
                        'duration' => null,
                        'nbr_courses' => 1,
                        'nbr_users' => 0,
                        'nbr_classes' => 0,
                        'visibility' => 1,
                        'status' => 0,
                        'display_start_date' => '2031-01-01 00:00:00',
                        'display_end_date' => '2031-06-30 23:59:59',
                        'access_start_date' => '2031-01-01 00:00:00',
                        'access_end_date' => '2031-06-30 23:59:59',
                        'coach_access_start_date' => '2031-01-01 00:00:00',
                        'coach_access_end_date' => '2031-06-30 23:59:59',
                    ],
                ],
                'session_rel_user' => [
                    [
                        'session' => 'imp_new_title',
                        'user' => 'admin',
                        'relation_type' => 3,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => '2020-01-01 00:00:00',
                        'access_end_date' => '2020-12-31 00:00:00',
                    ],
                ],
                'session_rel_course' => [
                    [
                        'session' => 'imp_new_title',
                        'course' => 'c1',
                        'room_id' => null,
                        'position' => 0,
                        'nbr_users' => 0,
                    ],
                ],
                'session_rel_course_rel_user' => [],
                'access_url_rel_session' => [
                    [
                        'session' => 'imp_new_title',
                        'access_url_id' => 1,
                    ],
                ],
                'course_rel_user' => [],
            ],
            'csv_remove_all_teachers' => [
                'snapshot' => [
                    'session' => [
                        [
                            'title' => 'imp_rm',
                            'description' => null,
                            'show_description' => null,
                            'session_category_id' => null,
                            'duration' => 0,
                            'nbr_courses' => 1,
                            'nbr_users' => 0,
                            'nbr_classes' => 0,
                            'visibility' => 1,
                            'status' => 0,
                            'display_start_date' => '2031-01-01 00:00:00',
                            'display_end_date' => '2031-06-30 23:59:59',
                            'access_start_date' => '2031-01-01 00:00:00',
                            'access_end_date' => '2031-06-30 23:59:59',
                            'coach_access_start_date' => '2031-01-01 00:00:00',
                            'coach_access_end_date' => '2031-06-30 23:59:59',
                        ],
                    ],
                    'session_rel_user' => [
                        [
                            'session' => 'imp_rm',
                            'user' => 'admin',
                            'relation_type' => 3,
                            'duration' => 0,
                            'registered_at' => 'NOW',
                            'access_start_date' => '2031-01-01 00:00:00',
                            'access_end_date' => '2031-06-30 23:59:59',
                        ],
                        [
                            'session' => 'imp_rm',
                            'user' => 'coach',
                            'relation_type' => 2,
                            'duration' => 0,
                            'registered_at' => 'NOW',
                            'access_start_date' => '2031-01-01 00:00:00',
                            'access_end_date' => '2031-06-30 23:59:59',
                        ],
                    ],
                    'session_rel_course' => [
                        [
                            'session' => 'imp_rm',
                            'course' => 'c1',
                            'room_id' => null,
                            'position' => 0,
                            'nbr_users' => 0,
                        ],
                    ],
                    'session_rel_course_rel_user' => [
                        [
                            'session' => 'imp_rm',
                            'course' => 'c1',
                            'user' => 'coach',
                            'status' => 2,
                            'visibility' => 1,
                            'legal_agreement' => 0,
                            'progress' => 0,
                            'subscription_date' => 'NOW',
                        ],
                    ],
                    'access_url_rel_session' => [
                        [
                            'session' => 'imp_rm',
                            'access_url_id' => 1,
                        ],
                    ],
                    'course_rel_user' => [
                        [
                            'course' => 'c1',
                            'user' => 'coach',
                            'relation_type' => 0,
                            'status' => 1,
                            'is_tutor' => 0,
                            'sort' => 5,
                            'user_course_cat' => null,
                            'legal_agreement' => 0,
                            'progress' => 0,
                        ],
                    ],
                ],
                'backups' => [
                    'teachers' => [
                        'other@c1' => [
                            'c_id' => 'c1',
                            'is_tutor' => 0,
                            'legal_agreement' => 0,
                            'progress' => 0,
                            'relation_type' => 0,
                            'sort' => 5,
                            'status' => 1,
                            'subscription_date' => 'NOW',
                            'user_course_cat' => null,
                            'user_id' => 'other',
                            'visibility' => 1,
                        ],
                    ],
                    'groups' => [
                        'tutor:other@c1#g1' => [
                            'user_id' => 'other',
                            'group_id' => 'g1',
                            'c_id' => 'c1',
                        ],
                        'user:other@c1#g1' => [
                            'user_id' => 'other',
                            'group_id' => 'g1',
                            'c_id' => 'c1',
                            'status' => 0,
                            'role' => 'member',
                            'ready_autogroup' => 0,
                        ],
                    ],
                ],
            ],
            'csv_only_first_coach' => [
                'snapshot' => [
                    'session' => [
                        [
                            'title' => 'imp_first',
                            'description' => null,
                            'show_description' => null,
                            'session_category_id' => null,
                            'duration' => 0,
                            'nbr_courses' => 2,
                            'nbr_users' => 0,
                            'nbr_classes' => 0,
                            'visibility' => 1,
                            'status' => 0,
                            'display_start_date' => '2031-01-01 00:00:00',
                            'display_end_date' => '2031-06-30 23:59:59',
                            'access_start_date' => '2031-01-01 00:00:00',
                            'access_end_date' => '2031-06-30 23:59:59',
                            'coach_access_start_date' => '2031-01-01 00:00:00',
                            'coach_access_end_date' => '2031-06-30 23:59:59',
                        ],
                    ],
                    'session_rel_user' => [
                        [
                            'session' => 'imp_first',
                            'user' => 'admin',
                            'relation_type' => 3,
                            'duration' => 0,
                            'registered_at' => 'NOW',
                            'access_start_date' => '2031-01-01 00:00:00',
                            'access_end_date' => '2031-06-30 23:59:59',
                        ],
                        [
                            'session' => 'imp_first',
                            'user' => 'coach',
                            'relation_type' => 2,
                            'duration' => 0,
                            'registered_at' => 'NOW',
                            'access_start_date' => '2031-01-01 00:00:00',
                            'access_end_date' => '2031-06-30 23:59:59',
                        ],
                    ],
                    'session_rel_course' => [
                        [
                            'session' => 'imp_first',
                            'course' => 'c1',
                            'room_id' => null,
                            'position' => 0,
                            'nbr_users' => 0,
                        ],
                        [
                            'session' => 'imp_first',
                            'course' => 'c2',
                            'room_id' => null,
                            'position' => 0,
                            'nbr_users' => 0,
                        ],
                    ],
                    'session_rel_course_rel_user' => [
                        [
                            'session' => 'imp_first',
                            'course' => 'c1',
                            'user' => 'coach',
                            'status' => 2,
                            'visibility' => 1,
                            'legal_agreement' => 0,
                            'progress' => 0,
                            'subscription_date' => 'NOW',
                        ],
                        [
                            'session' => 'imp_first',
                            'course' => 'c2',
                            'user' => 'coach',
                            'status' => 2,
                            'visibility' => 1,
                            'legal_agreement' => 0,
                            'progress' => 0,
                            'subscription_date' => 'NOW',
                        ],
                    ],
                    'access_url_rel_session' => [
                        [
                            'session' => 'imp_first',
                            'access_url_id' => 1,
                        ],
                    ],
                    'course_rel_user' => [
                        [
                            'course' => 'c1',
                            'user' => 'coach',
                            'relation_type' => 0,
                            'status' => 1,
                            'is_tutor' => 0,
                            'sort' => 5,
                            'user_course_cat' => null,
                            'legal_agreement' => 0,
                            'progress' => 0,
                        ],
                        [
                            'course' => 'c2',
                            'user' => 'coach',
                            'relation_type' => 0,
                            'status' => 1,
                            'is_tutor' => 0,
                            'sort' => 5,
                            'user_course_cat' => null,
                            'legal_agreement' => 0,
                            'progress' => 0,
                        ],
                    ],
                ],
                'backups' => [
                    'teachers' => [
                        'other@c1' => [
                            'c_id' => 'c1',
                            'is_tutor' => 0,
                            'legal_agreement' => 0,
                            'progress' => 0,
                            'relation_type' => 0,
                            'sort' => 5,
                            'status' => 1,
                            'subscription_date' => 'NOW',
                            'user_course_cat' => 0,
                            'user_id' => 'other',
                            'visibility' => 1,
                        ],
                        'other@c2' => [
                            'c_id' => 'c2',
                            'is_tutor' => 0,
                            'legal_agreement' => 0,
                            'progress' => 0,
                            'relation_type' => 0,
                            'sort' => 5,
                            'status' => 1,
                            'subscription_date' => 'NOW',
                            'user_course_cat' => 0,
                            'user_id' => 'other',
                            'visibility' => 1,
                        ],
                    ],
                    'groups' => [
                        'tutor:other@c1#g1' => [
                            'user_id' => 'other',
                            'group_id' => 'g1',
                            'c_id' => 'c1',
                        ],
                        'tutor:other@c2#g2' => [
                            'user_id' => 'other',
                            'group_id' => 'g2',
                            'c_id' => 'c2',
                        ],
                        'user:other@c1#g1' => [
                            'user_id' => 'other',
                            'group_id' => 'g1',
                            'c_id' => 'c1',
                            'status' => 0,
                            'role' => 'member',
                            'ready_autogroup' => 0,
                        ],
                        'user:other@c2#g2' => [
                            'user_id' => 'other',
                            'group_id' => 'g2',
                            'c_id' => 'c2',
                            'status' => 0,
                            'role' => 'member',
                            'ready_autogroup' => 0,
                        ],
                    ],
                ],
            ],
            'csv_errors' => [
                'semicolon' => [true, true, true],
                'missing' => [true, true, true],
                'rows' => [],
            ],
            'xml_create' => [
                'session' => [
                    [
                        'title' => 'imp_xml_a',
                        'description' => null,
                        'show_description' => null,
                        'session_category_id' => null,
                        'duration' => 0,
                        'nbr_courses' => 3,
                        'nbr_users' => 3,
                        'nbr_classes' => 0,
                        'visibility' => 1,
                        'status' => 0,
                        'display_start_date' => null,
                        'display_end_date' => null,
                        'access_start_date' => '2031-01-01 01:00:00',
                        'access_end_date' => '2031-06-30 02:00:00',
                        'coach_access_start_date' => null,
                        'coach_access_end_date' => null,
                    ],
                ],
                'session_rel_user' => [
                    [
                        'session' => 'imp_xml_a',
                        'user' => 'admin',
                        'relation_type' => 4,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => null,
                        'access_end_date' => null,
                    ],
                    [
                        'session' => 'imp_xml_a',
                        'user' => 'coach',
                        'relation_type' => 3,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => null,
                        'access_end_date' => null,
                    ],
                    [
                        'session' => 'imp_xml_a',
                        'user' => 'stu1',
                        'relation_type' => 0,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => null,
                        'access_end_date' => null,
                    ],
                    [
                        'session' => 'imp_xml_a',
                        'user' => 'stu2',
                        'relation_type' => 0,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => null,
                        'access_end_date' => null,
                    ],
                ],
                'session_rel_course' => [
                    [
                        'session' => 'imp_xml_a',
                        'course' => 'c1',
                        'room_id' => null,
                        'position' => 0,
                        'nbr_users' => 1,
                    ],
                    [
                        'session' => 'imp_xml_a',
                        'course' => 'c2',
                        'room_id' => null,
                        'position' => 0,
                        'nbr_users' => 0,
                    ],
                ],
                'session_rel_course_rel_user' => [
                    [
                        'session' => 'imp_xml_a',
                        'course' => 'c1',
                        'user' => 'coach',
                        'status' => 2,
                        'visibility' => 1,
                        'legal_agreement' => 0,
                        'progress' => 0,
                        'subscription_date' => 'NOW',
                    ],
                    [
                        'session' => 'imp_xml_a',
                        'course' => 'c1',
                        'user' => 'stu1',
                        'status' => 0,
                        'visibility' => 1,
                        'legal_agreement' => 0,
                        'progress' => 0,
                        'subscription_date' => 'NOW',
                    ],
                ],
                'access_url_rel_session' => [
                    [
                        'session' => 'imp_xml_a',
                        'access_url_id' => 1,
                    ],
                ],
                'course_rel_user' => [],
            ],
            'xml_update' => [
                'session' => [
                    [
                        'title' => 'imp_xml_upd',
                        'description' => 'old description',
                        'show_description' => null,
                        'session_category_id' => null,
                        'duration' => null,
                        'nbr_courses' => 1,
                        'nbr_users' => 1,
                        'nbr_classes' => 0,
                        'visibility' => 1,
                        'status' => 0,
                        'display_start_date' => '2020-01-01 00:00:00',
                        'display_end_date' => '2020-12-31 00:00:00',
                        'access_start_date' => '2031-01-01 01:00:00',
                        'access_end_date' => '2031-06-30 02:00:00',
                        'coach_access_start_date' => '2020-01-01 00:00:00',
                        'coach_access_end_date' => '2020-12-31 00:00:00',
                    ],
                ],
                'session_rel_user' => [
                    [
                        'session' => 'imp_xml_upd',
                        'user' => 'admin',
                        'relation_type' => 4,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => null,
                        'access_end_date' => null,
                    ],
                    [
                        'session' => 'imp_xml_upd',
                        'user' => 'coach',
                        'relation_type' => 3,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => null,
                        'access_end_date' => null,
                    ],
                    [
                        'session' => 'imp_xml_upd',
                        'user' => 'stu2',
                        'relation_type' => 0,
                        'duration' => 0,
                        'registered_at' => 'NOW',
                        'access_start_date' => null,
                        'access_end_date' => null,
                    ],
                ],
                'session_rel_course' => [
                    [
                        'session' => 'imp_xml_upd',
                        'course' => 'c2',
                        'room_id' => null,
                        'position' => 0,
                        'nbr_users' => 1,
                    ],
                ],
                'session_rel_course_rel_user' => [
                    [
                        'session' => 'imp_xml_upd',
                        'course' => 'c2',
                        'user' => 'stu2',
                        'status' => 0,
                        'visibility' => 1,
                        'legal_agreement' => 0,
                        'progress' => 0,
                        'subscription_date' => 'NOW',
                    ],
                ],
                'access_url_rel_session' => [
                    [
                        'session' => 'imp_xml_upd',
                        'access_url_id' => 1,
                    ],
                ],
                'course_rel_user' => [],
            ],
            'xml_misc' => [
                'created' => 1,
                'snapshot' => [
                    'session' => [
                        [
                            'title' => 'imp_xml_new',
                            'description' => null,
                            'show_description' => null,
                            'session_category_id' => null,
                            'duration' => 0,
                            'nbr_courses' => 0,
                            'nbr_users' => 0,
                            'nbr_classes' => 0,
                            'visibility' => 1,
                            'status' => 0,
                            'display_start_date' => null,
                            'display_end_date' => null,
                            'access_start_date' => '2031-01-01 01:00:00',
                            'access_end_date' => '2031-06-30 02:00:00',
                            'coach_access_start_date' => null,
                            'coach_access_end_date' => null,
                        ],
                    ],
                    'session_rel_user' => [
                        [
                            'session' => 'imp_xml_new',
                            'user' => 'admin',
                            'relation_type' => 4,
                            'duration' => 0,
                            'registered_at' => 'NOW',
                            'access_start_date' => null,
                            'access_end_date' => null,
                        ],
                        [
                            'session' => 'imp_xml_new',
                            'user' => 'coach',
                            'relation_type' => 3,
                            'duration' => 0,
                            'registered_at' => 'NOW',
                            'access_start_date' => null,
                            'access_end_date' => null,
                        ],
                    ],
                    'session_rel_course' => [],
                    'session_rel_course_rel_user' => [],
                    'access_url_rel_session' => [
                        [
                            'session' => 'imp_xml_new',
                            'access_url_id' => 1,
                        ],
                    ],
                    'course_rel_user' => [],
                ],
            ],
        ];
    }
}
