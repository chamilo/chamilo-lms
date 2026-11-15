<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Doctrine\Filter\DeletedCourseFilter;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use CourseManager;
use CoursesAndSessionsCatalog;
use Database;
use Doctrine\DBAL\Connection;
use Statistics;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * A deleted course keeps its row (with deleted_at set) so that its id is never
 * handed out again: track_e_* rows and other history that point at the old c_id
 * must not end up attached to a brand new course. See issue #2250.
 */
class CourseDeletedRowTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testDeletionKeepsTheRowAndRemovesSubscriptionsAndUrlLinks(): void
    {
        $student = $this->createUser('student2250');
        $course = $this->createCourse('Deleted row 2250');
        $course->addSubscriptionForUser($student, 0, '', CourseRelUser::STUDENT);
        self::getContainer()->get(CourseRepository::class)->update($course);

        $courseId = $course->getId();
        $code = $course->getCode();
        $connection = $this->getConnection();

        $this->assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM course_rel_user WHERE c_id = ?', [$courseId]));
        $this->assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM access_url_rel_course WHERE c_id = ?', [$courseId]));
        $this->assertGreaterThan(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM c_tool WHERE c_id = ?', [$courseId]));

        $connection->insert('track_e_default', [
            'default_user_id' => $student->getId(),
            'c_id' => $courseId,
            'default_date' => gmdate('Y-m-d H:i:s'),
            'default_event_type' => 'test_2250',
            'default_value_type' => 'test',
            'default_value' => 'kept',
        ]);

        $this->deleteThroughHelper($course);

        $row = $connection->fetchAssociative('SELECT * FROM course WHERE id = ?', [$courseId]);
        $this->assertIsArray($row, 'The course row must survive the deletion so its id is never reused.');
        $this->assertNotNull($row['deleted_at']);
        $this->assertSame($code, $row['code']);
        $this->assertSame('Deleted row 2250', $row['title']);
        $this->assertNull($row['resource_node_id']);

        $this->assertSame(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM course_rel_user WHERE c_id = ?', [$courseId]));
        $this->assertSame(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM access_url_rel_course WHERE c_id = ?', [$courseId]));
        $this->assertSame(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM c_tool WHERE c_id = ?', [$courseId]));

        $this->assertSame(
            1,
            (int) $connection->fetchOne("SELECT COUNT(*) FROM track_e_default WHERE c_id = ? AND default_event_type = 'test_2250'", [$courseId])
        );
        $this->assertSame(
            1,
            (int) $connection->fetchOne('SELECT COUNT(*) FROM track_e_default WHERE c_id = ? AND default_event_type = ?', [$courseId, LOG_COURSE_DELETE]),
            'The deletion log must point at the real id of the deleted course.'
        );
    }

    public function testDeletedCourseIsHiddenFromTheOrm(): void
    {
        $repo = self::getContainer()->get(CourseRepository::class);
        $countBefore = $repo->count([]);
        $course = $this->createCourse('Hidden from orm 2250');
        $courseId = $course->getId();
        $code = $course->getCode();

        $repo->deleteCourse($course);

        $this->assertNull($repo->find($courseId));
        $this->assertNull($repo->findOneByCode($code));
        $this->assertNull($repo->findOneBy(['code' => $code]));
        $this->assertSame($countBefore, $repo->count([]));
        $this->assertNull(api_get_course_entity($courseId));
        $this->assertSame([], api_get_course_info_by_id($courseId));

        $em = $this->getEntityManager();
        $found = DeletedCourseFilter::withDeletedCourses($em, fn () => $repo->find($courseId));
        $this->assertInstanceOf(Course::class, $found, 'Admin code can still read the row with the filter off.');
        $this->assertTrue($found->isDeleted());
        $this->assertTrue($em->getFilters()->isEnabled(DeletedCourseFilter::NAME), 'The filter must be back on afterwards.');
    }

    public function testDeletedCourseIsHiddenFromRawSqlListings(): void
    {
        $this->setUpLegacy();
        $kept = $this->createCourse('Kept raw 2250');
        $course = $this->createCourse('Deleted raw 2250');
        $courseId = $course->getId();
        $code = $course->getCode();

        $coursesBefore = CourseManager::count_courses();
        $statsBefore = Statistics::countCourses();
        $this->assertContains($courseId, array_column(CourseManager::get_courses_list(0, 0, 'id', 'ASC', -1, 'Deleted raw 2250'), 'id'));
        $this->assertSame($courseId, api_get_course_int_id($code));

        self::getContainer()->get(CourseRepository::class)->deleteCourse($course);

        $this->assertNotContains($courseId, array_map('intval', array_column(CourseManager::get_courses_list(0, 0, 'id', 'ASC', -1, 'Deleted raw 2250'), 'id')));
        $this->assertContains($kept->getId(), array_map('intval', array_column(CourseManager::get_courses_list(0, 0, 'id', 'ASC', -1, 'Kept raw 2250'), 'id')));
        $this->assertSame($coursesBefore - 1, (int) CourseManager::count_courses());
        $this->assertSame($statsBefore - 1, Statistics::countCourses());
        $this->assertFalse((bool) CourseManager::course_exists($code));
        $this->assertSame([], CourseManager::get_course_information($code) ?: []);
        $this->assertSame(0, (int) api_get_course_int_id($code));
        $this->assertStringContainsString('course.deleted_at IS NULL', CoursesAndSessionsCatalog::getAvoidCourseCondition());

        $catalogueCodes = array_column(
            $this->getConnection()->fetchAllAssociative(
                'SELECT course.code FROM course WHERE 1 = 1'.CoursesAndSessionsCatalog::getAvoidCourseCondition()
            ),
            'code'
        );
        $this->assertNotContains($code, $catalogueCodes);
        $this->assertContains($kept->getCode(), $catalogueCodes);
    }

    public function testDeletedCourseIsNotReachableThroughTheApi(): void
    {
        $course = $this->createCourse('Api hidden 2250');
        $courseId = $course->getId();
        $code = $course->getCode();
        $client = $this->createClientWithCredentials();

        $client->request('GET', '/api/courses/'.$courseId);
        $this->assertResponseIsSuccessful();
        $client->request('GET', '/course/'.$courseId.'/home.json', ['query' => ['cid' => $courseId]]);
        $this->assertResponseIsSuccessful();
        $this->assertSame([true, true, true], $this->listedEverywhere($client, $courseId));

        // The requests rebooted the kernel: load the course in the live entity manager.
        $repo = self::getContainer()->get(CourseRepository::class);
        $repo->deleteCourse($repo->find($courseId));

        $client->request('GET', '/api/courses/'.$courseId);
        $this->assertResponseStatusCodeSame(404);
        // Course context by id (cid) and by code (cidReq) both stop at CidReqListener.
        $client->request('GET', '/course/'.$courseId.'/home.json', ['query' => ['cid' => $courseId]]);
        $this->assertResponseStatusCodeSame(404);
        $client->request('GET', '/course/'.$courseId.'/home.json', ['query' => ['cidReq' => $code]]);
        $this->assertResponseStatusCodeSame(404);
        $this->assertSame([false, false, false], $this->listedEverywhere($client, $courseId));
    }

    /**
     * @param mixed $client
     *
     * @return bool[] whether the course shows in the API collection, the public catalogue and the admin course list
     */
    private function listedEverywhere($client, int $courseId): array
    {
        $listed = [];
        foreach (['/api/courses', '/api/public_courses'] as $url) {
            $data = $client->request('GET', $url, ['query' => ['title' => 'Api hidden 2250']])->toArray();
            $this->assertResponseIsSuccessful();
            $listed[] = \in_array('/api/courses/'.$courseId, array_column($data['hydra:member'] ?? $data['member'] ?? [], '@id'), true);
        }

        $data = $client->request('GET', '/admin/course-list-data', ['query' => ['keyword' => 'Api hidden 2250']])->toArray();
        $this->assertResponseIsSuccessful();
        $listed[] = \in_array($courseId, array_map('intval', array_column($data['items'] ?? $data['data'] ?? $data['rows'] ?? [], 'id')), true);

        return $listed;
    }

    public function testANewCourseGetsAFreshIdAndCodeAfterADeletion(): void
    {
        $repo = self::getContainer()->get(CourseRepository::class);
        $course = $this->createCourse('Reused code 2250');
        $deletedId = $course->getId();
        $deletedCode = $course->getCode();

        $repo->deleteCourse($course);

        $this->assertTrue($repo->courseCodeExists($deletedCode), 'The code of a deleted course stays reserved.');

        $sameCode = (new Course())
            ->setTitle('Reused code 2250')
            ->setCode($deletedCode)
            ->addAccessUrl($this->getAccessUrl())
            ->setCreator($this->getUser('admin'))
        ;
        $this->assertContains('code', array_map(
            static fn ($violation) => $violation->getPropertyPath(),
            iterator_to_array($this->getViolations($sameCode))
        ));

        $new = $this->createCourse('Reused code 2250');
        $this->assertGreaterThan($deletedId, $new->getId());
        $this->assertNotSame($deletedCode, $new->getCode());

        $this->setUpLegacy();
        $this->assertNotSame($deletedCode, CourseManager::generate_nice_next_course_code($deletedCode));
    }

    private function deleteThroughHelper(Course $course): void
    {
        $this->setUpLegacy();
        $this->assertTrue(self::getContainer()->get(CourseHelper::class)->deleteCourse($course));
    }

    private function setUpLegacy(): void
    {
        // Legacy code (GroupManager, Database, CourseManager) expects what
        // LegacyListener sets up on a real request.
        $request = new Request();
        $request->setSession(new HttpSession(new MockArraySessionStorage()));
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        Database::setManager($this->getEntityManager());
    }

    private function getConnection(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }
}
