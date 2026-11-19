<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * Teachers may store any HTML in a course description, and the editor must get it back intact so
 * they do not lose it when they reopen the form. The list, however, is rendered as HTML for every
 * course member, so it must be filtered with the reader's profile (as 1.11.x did when displaying
 * it): otherwise a stored event handler runs in the session of whoever opens the page.
 */
final class CourseDescriptionHtmlOutputApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const CONTENT = '<p>cdhtml-marker</p><img src="x.png" alt="cdhtml" onerror="alert(1)">';

    public function testListFiltersStoredHtmlForEveryReader(): void
    {
        $ctx = $this->createScenario('list');
        $this->storeDescription($ctx);

        $readers = [
            'student' => $ctx['student'],
            'teacher' => $ctx['teacher'],
            'admin' => $this->createUser('cdhtml_admin_'.bin2hex(random_bytes(3)), '', '', 'ROLE_ADMIN'),
        ];

        foreach ($readers as $reader => $user) {
            $client = $this->createClientWithCredentials($this->getUserTokenFromUser($user));
            $response = $client->request('GET', '/api/course-description/list?cid='.$ctx['course']->getId());
            $this->assertResponseStatusCodeSame(200, $reader);

            $items = $response->toArray()['items'] ?? [];
            $this->assertCount(1, $items, $reader);
            $this->assertStringContainsString('cdhtml-marker', $items[0]['content'], $reader);
            $this->assertStringNotContainsString('onerror', $items[0]['content'], $reader);
        }
    }

    public function testSessionAdminCourseViewFiltersStoredHtml(): void
    {
        $ctx = $this->createScenario('sessionadmin');
        $this->storeDescription($ctx);

        $admin = $this->createUser('cdhtml_sadmin_'.bin2hex(random_bytes(3)), '', '', 'ROLE_ADMIN');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($admin));
        $response = $client->request('GET', '/admin/sessionadmin/courses/'.$ctx['course']->getId());
        $this->assertResponseStatusCodeSame(200);

        $content = json_encode($response->toArray()['descriptions'] ?? []);
        $this->assertStringContainsString('cdhtml-marker', $content);
        $this->assertStringNotContainsString('onerror', $content);
    }

    public function testEditorFormKeepsTheStoredHtml(): void
    {
        $ctx = $this->createScenario('form');
        $iid = $this->storeDescription($ctx);

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']));
        $response = $client->request(
            'GET',
            '/api/course-description/form?cid='.$ctx['course']->getId().'&id='.$iid,
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(self::CONTENT, $response->toArray()['content']);
    }

    /**
     * @return array{course: Course, teacher: User, student: User}
     */
    private function createScenario(string $suffix): array
    {
        $suffix .= '_'.bin2hex(random_bytes(3));
        $course = $this->createCourse('CDHTML '.$suffix);
        $teacher = $this->createUser('cdhtml_teacher_'.$suffix);
        $student = $this->createUser('cdhtml_student_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addSubscriptionForUser($student, 0, null, CourseRelUser::STUDENT);
        self::getContainer()->get(CourseRepository::class)->update($course);

        return ['course' => $course, 'teacher' => $teacher, 'student' => $student];
    }

    /**
     * @param array{course: Course, teacher: User, student: User} $ctx
     */
    private function storeDescription(array $ctx): int
    {
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']));
        $response = $client->request('POST', '/api/course-description?cid='.$ctx['course']->getId(), [
            'json' => [
                'descriptionType' => 8,
                'title' => 'cdhtml',
                'content' => self::CONTENT,
                'progress' => 0,
                'language' => '',
                'enableSearch' => false,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        return (int) $response->toArray()['iid'];
    }
}
