<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelCourse;
use Chamilo\CoreBundle\Entity\UsergroupRelSession;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * #1713: the class members preview of the admin class list and of the course Users > Classes screen.
 */
final class UsergroupMembersControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testAdminPagesThroughTheMembersWithNameAndUsernameOnly(): void
    {
        $class = $this->createClass('ugm_admin_class', ['ugm_carol', 'ugm_alice', 'ugm_bob']);
        $this->client->loginUser($this->getUser('admin'));

        $first = $this->members($class, ['page' => 1, 'limit' => 2]);
        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $first['totalItems']);
        $this->assertSame(['ugm_alice', 'ugm_bob'], array_column($first['items'], 'username'));
        $this->assertSame(['id', 'name', 'username'], array_keys($first['items'][0]), 'No e-mail or other PII.');
        $this->assertStringContainsString('ugm_alice', $first['items'][0]['name']);
        $this->assertSame('ugm_admin_class', $first['group']['title']);

        $second = $this->members($class, ['page' => 2, 'limit' => 2]);
        $this->assertSame(['ugm_carol'], array_column($second['items'], 'username'));
    }

    public function testTheAdminClassListCountMatchesTheMembersPreview(): void
    {
        $class = $this->createClass('ugm_count_class', ['ugm_count_1', 'ugm_count_2']);
        $this->createClass('ugm_count_empty_class', []);
        $this->client->loginUser($this->getUser('admin'));

        $this->client->request('GET', '/admin/usergroups-data', ['search' => 'ugm_count_']);
        $this->assertResponseIsSuccessful();
        $counts = array_column(json_decode((string) $this->client->getResponse()->getContent(), true)['items'], 'userCount', 'title');

        $this->assertSame(['ugm_count_class' => 2, 'ugm_count_empty_class' => 0], $counts);
        $this->assertSame(2, $this->members($class)['totalItems']);
    }

    public function testThePageSizeIsCapped(): void
    {
        $class = $this->createClass('ugm_cap_class', ['ugm_cap_1']);
        $this->client->loginUser($this->getUser('admin'));

        $this->assertSame(50, $this->members($class, ['limit' => 100000])['limit']);
    }

    public function testCourseTeacherSeesAClassLinkedToTheirCourse(): void
    {
        [$teacher, $course] = $this->courseWithTeacher('ugm_teacher_linked');
        $class = $this->createClass('ugm_linked_class', ['ugm_linked_member']);
        $this->linkToCourse($class, $course);
        $this->client->loginUser($teacher);

        $data = $this->members($class, ['cid' => $course->getId(), 'sid' => 0]);
        $this->assertResponseIsSuccessful();
        $this->assertSame(['ugm_linked_member'], array_column($data['items'], 'username'));
    }

    public function testCourseTeacherCannotSeeAClassNotLinkedToTheCourse(): void
    {
        [$teacher, $course] = $this->courseWithTeacher('ugm_teacher_unlinked');
        $class = $this->createClass('ugm_unlinked_class', ['ugm_unlinked_member']);
        $this->client->loginUser($teacher);

        $this->members($class, ['cid' => $course->getId()]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCourseTeacherNeedsTheCourseContext(): void
    {
        [$teacher, $course] = $this->courseWithTeacher('ugm_teacher_nocid');
        $class = $this->createClass('ugm_nocid_class', ['ugm_nocid_member']);
        $this->linkToCourse($class, $course);
        $this->client->loginUser($teacher);

        $this->members($class);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testStudentOfTheCourseCannotSeeTheMembers(): void
    {
        [, $course] = $this->courseWithTeacher('ugm_teacher_student');
        $student = $this->createUser('ugm_student');
        $course->addUserAsStudent($student);
        $this->getEntityManager()->flush();
        $class = $this->createClass('ugm_student_class', ['ugm_student_member']);
        $this->linkToCourse($class, $course);
        $this->client->loginUser($student);

        $this->members($class, ['cid' => $course->getId()]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testInsideASessionTheCoachSeesOnlyClassesLinkedToTheSession(): void
    {
        [, $course] = $this->courseWithTeacher('ugm_teacher_session');
        $coach = $this->createUser('ugm_session_coach', '', '', 'ROLE_TEACHER');
        $session = $this->createSession('ugm_session')->addCourse($course);
        $session->addUserInCourse(Session::COURSE_COACH, $coach, $course);
        $em = $this->getEntityManager();
        $em->flush();

        $sessionClass = $this->createClass('ugm_session_class', ['ugm_session_member']);
        $em->persist((new UsergroupRelSession())->setUsergroup($sessionClass)->setSession($session));
        $courseClass = $this->createClass('ugm_course_only_class', ['ugm_course_only_member']);
        $this->linkToCourse($courseClass, $course);
        $em->flush();
        $this->client->loginUser($coach);

        $this->members($sessionClass, ['cid' => $course->getId(), 'sid' => $session->getId()]);
        $this->assertResponseIsSuccessful();

        $this->members($courseClass, ['cid' => $course->getId(), 'sid' => $session->getId()]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSessionAdminFollowsLimitSessionAdminRole(): void
    {
        $class = $this->createClass('ugm_sessionadmin_class', ['ugm_sessionadmin_member']);
        $sessionAdmin = $this->createUser('ugm_session_admin', '', '', 'ROLE_SESSION_MANAGER');
        $this->client->loginUser($sessionAdmin);

        $this->members($class);
        $this->assertResponseIsSuccessful();

        self::getContainer()->get(SettingsManager::class)->updateSetting('session.limit_session_admin_role', 'true');
        $this->members($class);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAnonymousIsRejectedAndUnknownClassIs404(): void
    {
        $class = $this->createClass('ugm_anon_class', ['ugm_anon_member']);
        $this->members($class);
        $this->assertContains($this->client->getResponse()->getStatusCode(), [302, 401, 403]);

        $this->client->loginUser($this->getUser('admin'));
        $this->client->request('GET', '/usergroup-members-data/999999999');
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @param list<string> $usernames
     */
    private function createClass(string $title, array $usernames): Usergroup
    {
        $class = (new Usergroup())
            ->setTitle($title)
            ->setDescription('')
            ->setCreator($this->getUser('admin'))
            ->addAccessUrl($this->getAccessUrl())
        ;
        foreach ($usernames as $username) {
            $class->addUser($this->createUser($username));
        }

        $em = $this->getEntityManager();
        $em->persist($class);
        $em->flush();

        return $class;
    }

    private function linkToCourse(Usergroup $class, Course $course): void
    {
        $em = $this->getEntityManager();
        $em->persist((new UsergroupRelCourse())->setUsergroup($class)->setCourse($course));
        $em->flush();
    }

    /**
     * @return array{0: User, 1: Course}
     */
    private function courseWithTeacher(string $username): array
    {
        $teacher = $this->createUser($username, '', '', 'ROLE_TEACHER');
        $course = $this->createCourse($username.'_course')
            ->setVisibility(Course::REGISTERED)
            ->addUserAsTeacher($teacher)
        ;
        $this->getEntityManager()->flush();

        return [$teacher, $course];
    }

    /**
     * @param array<string, int|string> $query
     *
     * @return array<string, mixed>
     */
    private function members(Usergroup $class, array $query = []): array
    {
        $this->client->request('GET', '/usergroup-members-data/'.$class->getId(), $query, [], ['HTTP_ACCEPT' => 'application/json']);

        return (array) json_decode((string) $this->client->getResponse()->getContent(), true);
    }
}
