<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use CourseRequestManager;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization tests for CourseRequestManager (public/main/inc/lib/course_request.lib.php).
 * Fixtures start with "cr3_".
 */
final class CourseRequestTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/inc/lib/course_request.lib.php');
        $request->setSession($session);
        Container::setRequest($request);
    }

    private function enableMail(): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting('admin.administrator_email', 'cr3admin@example.com');
    }

    /**
     * create_course_request() mails the requesting teacher after saving the row and must hand the new id back.
     */
    private function createRequest(mixed ...$args): mixed
    {
        return CourseRequestManager::create_course_request(...$args);
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function loginAs(User $user): void
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($token);
        ChamiloSession::write('_user', ['id' => $user->getId()]);
    }

    private function insertRequest(string $code, int $userId, int $status = 0, int $info = 0, ?int $categoryId = null): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_request (user_id, category_id, code, course_language, title, description, tutor_name, visual_code, request_date, objetives, target_audience, status, info, exemplary_content)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, $categoryId, $code, 'english', 'Title '.$code, 'Desc', 'Tutor T', strtoupper($code), '2026-03-04 05:06:07', 'Obj', 'Aud', $status, $info, 1]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function row(int $id): array|false
    {
        return $this->conn()->fetchAssociative('SELECT * FROM course_request WHERE id = ?', [$id]);
    }

    public function testCourseCodeExistsLooksAtCoursesAndRequestVisualCode(): void
    {
        $user = $this->createUser('cr3_user_a');
        $course = $this->createCourse('cr3_course_a');
        $this->insertRequest('cr3_req_a', (int) $user->getId());

        $this->assertTrue((bool) CourseRequestManager::course_code_exists($course->getCode()));
        // Request visual_code is stored upper-case by the fixture; the comparison is collation-insensitive.
        $this->assertTrue(CourseRequestManager::course_code_exists('CR3_REQ_A'));
        $this->assertTrue(CourseRequestManager::course_code_exists('cr3_req_a'));
        // The request "code" column alone is not looked at.
        $this->conn()->executeStatement('UPDATE course_request SET visual_code = ? WHERE code = ?', ['OTHER_CR3', 'cr3_req_a']);
        $this->assertFalse(CourseRequestManager::course_code_exists('cr3_req_a'));
        $this->assertTrue(CourseRequestManager::course_code_exists('other_cr3'));
        $this->assertFalse(CourseRequestManager::course_code_exists('cr3_does_not_exist'));
    }

    public function testGetCourseRequestInfoShape(): void
    {
        $user = $this->createUser('cr3_user_b');
        $cat = $this->conn()->fetchAssociative('SELECT id, code FROM course_category LIMIT 1');
        $this->assertNotFalse($cat, 'fixture course_category row expected');
        $withCat = $this->insertRequest('cr3_info_a', (int) $user->getId(), 1, 1, (int) $cat['id']);
        $noCat = $this->insertRequest('cr3_info_b', (int) $user->getId());

        $info = CourseRequestManager::get_course_request_info($withCat);
        $this->assertIsArray($info);
        $this->assertSame('cr3_info_a', $info['code']);
        $this->assertSame($cat['code'], $info['category_code']);
        $this->assertSame((int) $cat['id'], $info['category_id']);
        $this->assertSame((int) $user->getId(), $info['user_id']);
        $this->assertSame('english', $info['course_language']);
        $this->assertSame('Title cr3_info_a', $info['title']);
        $this->assertSame('Desc', $info['description']);
        $this->assertSame('Tutor T', $info['tutor_name']);
        $this->assertSame('CR3_INFO_A', $info['visual_code']);
        $this->assertSame('2026-03-04 05:06:07', $info['request_date']);
        $this->assertSame('Obj', $info['objetives']);
        $this->assertSame('Aud', $info['target_audience']);
        $this->assertSame(1, $info['status']);
        $this->assertSame(1, $info['info']);
        $this->assertSame(1, $info['exemplary_content']);
        $this->assertSame($withCat, $info['id']);

        $info = CourseRequestManager::get_course_request_info($noCat);
        $this->assertNull($info['category_code']);
        $this->assertNull($info['category_id']);

        // Value types as PDO/mysqli deliver them to legacy callers.
        $this->assertSame($this->row($noCat), array_intersect_key($info, $this->row($noCat)));
        $this->assertSame(array_keys($this->row($noCat)), \array_slice(array_keys($info), 0, 15));

        // Legacy callers get both the associative and the numeric keys.
        $this->assertArrayHasKey(0, $info);
        $this->assertSame(\count($info) / 2, \count(array_filter(array_keys($info), 'is_string')));

        $this->assertFalse(CourseRequestManager::get_course_request_info(999999999));
    }

    public function testGetCourseRequestCode(): void
    {
        $user = $this->createUser('cr3_user_c');
        $id = $this->insertRequest('cr3_code_a', (int) $user->getId());
        $this->assertSame('cr3_code_a', CourseRequestManager::get_course_request_code($id));
        $this->assertFalse(CourseRequestManager::get_course_request_code(999999999));
    }

    public function testCountCourseRequestsByStatus(): void
    {
        $user = $this->createUser('cr3_user_d');
        $uid = (int) $user->getId();
        $before = [
            'all' => (int) CourseRequestManager::count_course_requests(),
            0 => (int) CourseRequestManager::count_course_requests(0),
            1 => (int) CourseRequestManager::count_course_requests(COURSE_REQUEST_ACCEPTED),
            2 => (int) CourseRequestManager::count_course_requests(COURSE_REQUEST_REJECTED),
        ];
        $this->insertRequest('cr3_cnt_a', $uid, 0);
        $this->insertRequest('cr3_cnt_b', $uid, 0);
        $this->insertRequest('cr3_cnt_c', $uid, 1);
        $this->insertRequest('cr3_cnt_d', $uid, 2);

        $this->assertSame($before['all'] + 4, (int) CourseRequestManager::count_course_requests());
        $this->assertSame($before['all'] + 4, (int) CourseRequestManager::count_course_requests(null));
        $this->assertSame($before[0] + 2, (int) CourseRequestManager::count_course_requests(0));
        $this->assertSame($before[1] + 1, (int) CourseRequestManager::count_course_requests(COURSE_REQUEST_ACCEPTED));
        $this->assertSame($before[2] + 1, (int) CourseRequestManager::count_course_requests(COURSE_REQUEST_REJECTED));
        // A numeric string is cast to int.
        $this->assertSame($before[2] + 1, (int) CourseRequestManager::count_course_requests('2'));
        // Return type as it is today: whatever the driver delivers for COUNT().
        $this->assertIsInt(CourseRequestManager::count_course_requests());
    }

    public function testDeleteCourseRequest(): void
    {
        $user = $this->createUser('cr3_user_e');
        $keep = $this->insertRequest('cr3_del_keep', (int) $user->getId());
        $gone = $this->insertRequest('cr3_del_gone', (int) $user->getId());

        $this->assertTrue(CourseRequestManager::delete_course_request($gone));
        $this->assertFalse($this->row($gone));
        $this->assertNotFalse($this->row($keep));
        // Deleting a missing id is still a success.
        $this->assertTrue(CourseRequestManager::delete_course_request($gone));
    }

    public function testAdditionalInfoAsked(): void
    {
        $user = $this->createUser('cr3_user_f');
        $asked = $this->insertRequest('cr3_ai_yes', (int) $user->getId(), 0, 1);
        $notAsked = $this->insertRequest('cr3_ai_no', (int) $user->getId(), 0, 0);

        $this->assertTrue(CourseRequestManager::additional_info_asked($asked));
        $this->assertFalse(CourseRequestManager::additional_info_asked($notAsked));
        $this->assertFalse(CourseRequestManager::additional_info_asked(999999999));
    }

    public function testRejectCourseRequestSetsStatusAndKeepsOthers(): void
    {
        $user = $this->createUser('cr3_user_g');
        $other = $this->insertRequest('cr3_rej_other', (int) $user->getId());
        $id = $this->insertRequest('cr3_rej_a', (int) $user->getId());

        $this->assertTrue(CourseRequestManager::reject_course_request($id));
        $this->assertSame(COURSE_REQUEST_REJECTED, (int) $this->row($id)['status']);
        $this->assertSame(COURSE_REQUEST_PENDING, (int) $this->row($other)['status']);

        $this->assertFalse(CourseRequestManager::reject_course_request(999999999));
    }

    public function testAskForAdditionalInfoSetsInfoOnceOnly(): void
    {
        $this->enableMail();
        $user = $this->createUser('cr3_user_h');
        $other = $this->insertRequest('cr3_ask_other', (int) $user->getId());
        $id = $this->insertRequest('cr3_ask_a', (int) $user->getId());

        $this->assertTrue(CourseRequestManager::ask_for_additional_info($id));
        $this->assertSame(1, (int) $this->row($id)['info']);
        $this->assertSame(0, (int) $this->row($other)['info']);
        // Repeated attempt is refused.
        $this->assertFalse(CourseRequestManager::ask_for_additional_info($id));
        $this->assertFalse(CourseRequestManager::ask_for_additional_info(999999999));
    }

    public function testCreateCourseRequestInsertsRow(): void
    {
        $user = $this->createUser('cr3_user_i');
        $this->loginAs($user);
        $this->enableMail();

        $id = $this->createRequest(
            'cr3_new_a',
            'A "quoted" \'title\'',
            "Multi\nline description",
            '',
            'english',
            'Objectives \ here',
            'Audience',
            (int) $user->getId(),
            1
        );
        $this->assertNotFalse($id);
        $row = $this->row((int) $id);
        $this->assertNotFalse($row);
        $this->assertSame('A "quoted" \'title\'', $row['title']);
        $this->assertSame("Multi\nline description", $row['description']);
        $this->assertSame('Objectives \ here', $row['objetives']);
        $this->assertSame('Audience', $row['target_audience']);
        $this->assertSame('english', $row['course_language']);
        $this->assertSame((int) $user->getId(), (int) $row['user_id']);
        $this->assertNull($row['category_id']);
        $this->assertSame(COURSE_REQUEST_PENDING, (int) $row['status']);
        $this->assertSame(0, (int) $row['info']);
        $this->assertSame(1, (int) $row['exemplary_content']);
        $this->assertNotSame('', (string) $row['code']);
        $this->assertNotSame('', (string) $row['visual_code']);
        $this->assertSame($row['code'], CourseRequestManager::get_course_request_code((int) $id));
        $this->assertMatchesRegularExpression('/^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$/', (string) $row['request_date']);
        $this->assertEqualsWithDelta(time(), strtotime((string) $row['request_date'].' UTC'), 300);
        $this->assertNotEmpty($row['tutor_name']);

        $this->assertSame('CR3NEWA', $row['code']);
        $this->assertSame('CR3NEWA', $row['visual_code']);
        $this->assertSame((int) $id, (int) $this->conn()->fetchOne('SELECT id FROM course_request WHERE visual_code = ?', ['CR3NEWA']));

        // Same code again is refused (the guard compares the generated visual_code, so the wanted code must be
        // given in its generated form; 'cr3_new_a' would reach the UNIQUE key instead), as are blank code and bad user.
        $this->assertFalse($this->createRequest('CR3NEWA', 't', 'd', '', 'english', 'o', 'a', (int) $user->getId()));
        $this->assertFalse($this->createRequest('  ', 't', 'd', '', 'english', 'o', 'a', (int) $user->getId()));
        $this->assertFalse($this->createRequest('cr3_new_b', 't', 'd', '', 'english', 'o', 'a', 0));
    }

    public function testCreateCourseRequestWithCategoryByCodeAndById(): void
    {
        $user = $this->createUser('cr3_user_j');
        $this->loginAs($user);
        $this->enableMail();
        $cat = $this->conn()->fetchAssociative('SELECT id, code FROM course_category LIMIT 1');
        $this->assertNotFalse($cat);

        $byCode = $this->createRequest('cr3_cat_a', 't', 'd', $cat['code'], 'english', 'o', 'a', (int) $user->getId());
        $this->assertNotFalse($byCode);
        $this->assertSame((int) $cat['id'], (int) $this->row((int) $byCode)['category_id']);

        $this->assertFalse($this->createRequest('cr3_cat_b', 't', 'd', 'no_such_cat_zz', 'english', 'o', 'a', (int) $user->getId()));
    }

    public function testAcceptCourseRequestMarksItAcceptedAndCreatesTheCourse(): void
    {
        $this->enableMail();
        $user = $this->createUser('cr3_user_k');
        $this->loginAs($user);
        // Course creation needs the access URL, which the resource listener reads from the request stack.
        $session = Container::getSession();
        $session->set('access_url_id', $this->getAccessUrl()->getId());
        $request = Request::create('/main/admin/course_request_review.php');
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);
        $other = $this->insertRequest('cr3_acc_other', (int) $user->getId());
        $id = $this->insertRequest('cr3_acc_a', (int) $user->getId());

        $code = CourseRequestManager::accept_course_request($id);
        $this->assertSame('CR3ACCA', strtoupper((string) $code));
        $this->assertSame(COURSE_REQUEST_ACCEPTED, (int) $this->row($id)['status']);
        $this->assertSame(COURSE_REQUEST_PENDING, (int) $this->row($other)['status']);
        $this->assertNotFalse($this->conn()->fetchOne('SELECT id FROM course WHERE code = ?', [$code]));

        $this->assertFalse(CourseRequestManager::accept_course_request(999999999));
    }

    public function testCreateCourseRequestReturnsTheIdOfTheSavedRow(): void
    {
        $user = $this->createUser('cr3_user_m');
        $this->loginAs($user);
        $this->enableMail();

        $id = CourseRequestManager::create_course_request('cr3_ret_a', 't', 'd', '', 'english', 'o', 'a', (int) $user->getId());

        $this->assertIsInt($id);
        $this->assertSame((int) $this->conn()->fetchOne('SELECT id FROM course_request WHERE code = ?', ['CR3RETA']), $id);
    }

    public function testUpdateCourseRequestChangesOnlyTheEditableColumns(): void
    {
        $user = $this->createUser('cr3_user_n');
        $other = $this->createUser('cr3_user_o');
        $cat = $this->conn()->fetchAssociative('SELECT id, code FROM course_category LIMIT 1');
        $this->assertNotFalse($cat);
        $id = $this->insertRequest('cr3_upd_a', (int) $user->getId(), 1, 1);

        $ok = CourseRequestManager::update_course_request(
            $id,
            'cr3_upd_b',
            'New title',
            'New desc',
            $cat['code'],
            'french',
            'New obj',
            'New aud',
            (int) $other->getId(),
            0
        );

        $this->assertTrue($ok);
        $row = $this->row($id);
        $this->assertSame('New title', $row['title']);
        $this->assertSame('New desc', $row['description']);
        $this->assertSame('New obj', $row['objetives']);
        $this->assertSame('New aud', $row['target_audience']);
        $this->assertSame('french', $row['course_language']);
        $this->assertSame((int) $cat['id'], (int) $row['category_id']);
        $this->assertSame((int) $other->getId(), (int) $row['user_id']);
        $this->assertSame(0, (int) $row['exemplary_content']);
        // A new wanted code regenerates the code and the visual code.
        $this->assertSame('CR3UPDB', $row['code']);
        $this->assertSame('CR3UPDB', $row['visual_code']);
        // The tutor name follows the new teacher.
        $this->assertStringContainsString('cr3_user_o', (string) $row['tutor_name']);
        // Workflow state and date are not touched by an edit.
        $this->assertSame(1, (int) $row['status']);
        $this->assertSame(1, (int) $row['info']);
        $this->assertSame('2026-03-04 05:06:07', $row['request_date']);
    }

    public function testUpdateCourseRequestKeepsTheCodeWhenUnchangedAndClearsTheCategory(): void
    {
        $user = $this->createUser('cr3_user_p');
        $cat = $this->conn()->fetchAssociative('SELECT id FROM course_category LIMIT 1');
        $id = $this->insertRequest('cr3_upd_c', (int) $user->getId(), 0, 0, (int) $cat['id']);

        $this->assertTrue(CourseRequestManager::update_course_request($id, 'cr3_upd_c', 't', 'd', '', 'english', 'o', 'a', (int) $user->getId(), 1));

        $row = $this->row($id);
        $this->assertSame('cr3_upd_c', $row['code']);
        $this->assertSame('CR3_UPD_C', $row['visual_code']);
        $this->assertNull($row['category_id']);
    }

    public function testUpdateCourseRequestRefusesAnOccupiedCodeAndBadInput(): void
    {
        $user = $this->createUser('cr3_user_q');
        $uid = (int) $user->getId();
        $this->insertRequest('cr3_upd_taken', $uid);
        $id = $this->insertRequest('cr3_upd_d', $uid);

        $this->assertFalse(CourseRequestManager::update_course_request($id, 'CR3_UPD_TAKEN', 't', 'd', '', 'english', 'o', 'a', $uid, 0));
        $this->assertFalse(CourseRequestManager::update_course_request($id, '  ', 't', 'd', '', 'english', 'o', 'a', $uid, 0));
        $this->assertFalse(CourseRequestManager::update_course_request($id, 'cr3_upd_d', 't', 'd', 'no_such_cat_zz', 'english', 'o', 'a', $uid, 0));
        $this->assertFalse(CourseRequestManager::update_course_request(999999999, 'cr3_upd_e', 't', 'd', '', 'english', 'o', 'a', $uid, 0));
        $this->assertSame('cr3_upd_d', $this->row($id)['code']);
    }

    public function testUpdateCourseRequestRenamesTheTutorWhenOnlyTheTeacherChanges(): void
    {
        $user = $this->createUser('cr3_user_r');
        $other = $this->createUser('cr3_user_s');
        $id = $this->insertRequest('cr3_upd_f', (int) $user->getId());

        $this->assertTrue(CourseRequestManager::update_course_request($id, 'cr3_upd_f', 't', 'd', '', 'english', 'o', 'a', (int) $other->getId(), 1));

        $row = $this->row($id);
        $this->assertSame((int) $other->getId(), (int) $row['user_id']);
        $this->assertStringContainsString('cr3_user_s', (string) $row['tutor_name']);
        // Unchanged teacher and language keep the stored tutor name.
        $this->assertTrue(CourseRequestManager::update_course_request($id, 'cr3_upd_f', 't', 'd', '', 'english', 'o', 'a', (int) $other->getId(), 1));
        $this->assertSame($row['tutor_name'], $this->row($id)['tutor_name']);
    }
}
