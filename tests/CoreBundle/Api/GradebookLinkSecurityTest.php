<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * Regression tests for the gradebook link advisories.
 *
 * The contextual teacher role describes the course named by `cid`, not the course the link
 * belongs to, so a teacher of course A could change or delete the links of course B by sending
 * cid=A. Writes are now authorized through the link's own gradebook category.
 */
final class GradebookLinkSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * Builds two courses with a teacher each, a student of the second one, and a gradebook
     * category with one link in the second course.
     *
     * @return array{courseA: Course, courseB: Course, teacherA: User, teacherB: User, studentB: User, category: GradebookCategory, link: GradebookLink}
     */
    private function bootstrap(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $courseA = $this->createCourse('Link Sec A '.$suffix);
        $courseA->setVisibility(Course::REGISTERED);
        $courseB = $this->createCourse('Link Sec B '.$suffix);
        $courseB->setVisibility(Course::REGISTERED);

        $teacherA = $this->createUser('link_sec_teacher_a_'.$suffix);
        $teacherB = $this->createUser('link_sec_teacher_b_'.$suffix);
        $studentB = $this->createUser('link_sec_student_b_'.$suffix);
        $courseA->addUserAsTeacher($teacherA);
        $courseB->addUserAsTeacher($teacherB);
        $courseB->addUserAsStudent($studentB);
        $courseRepo->update($courseA);
        $courseRepo->update($courseB);

        $em = $this->getEntityManager();

        $category = (new GradebookCategory())
            ->setTitle('Link Sec Category '.$suffix)
            ->setCourse($courseB)
            ->setWeight(100)
            ->setVisible(true)
            ->setLocked(0)
        ;
        $em->persist($category);

        $link = (new GradebookLink())
            ->setType(1)
            ->setResourceNode($courseB->getResourceNode())
            ->setCourse($courseB)
            ->setCategory($category)
            ->setWeight(10)
            ->setVisible(1)
            ->setLocked(0)
            ->setCreatedAt(new DateTime())
        ;
        $em->persist($link);
        $em->flush();

        return [
            'courseA' => $courseA,
            'courseB' => $courseB,
            'teacherA' => $teacherA,
            'teacherB' => $teacherB,
            'studentB' => $studentB,
            'category' => $category,
            'link' => $link,
        ];
    }

    /**
     * @param array<string, mixed> $json
     */
    private function request(User $user, string $method, string $uri, array $json = []): void
    {
        $options = [];
        if ([] !== $json) {
            $options['json'] = $json;
            $options['headers'] = ['Content-Type' => 'application/ld+json'];
        }

        $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request($method, $uri, $options);
    }

    private function linkExists(GradebookLink $link): bool
    {
        $em = $this->getEntityManager();
        $em->clear();

        return null !== $em->getRepository(GradebookLink::class)->find($link->getId());
    }

    private function freshWeight(GradebookLink $link): ?float
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->getRepository(GradebookLink::class)->find($link->getId())?->getWeight();
    }

    public function testTeacherOfAnotherCourseCannotDeleteALinkUsingOwnCid(): void
    {
        $ctx = $this->bootstrap('delete_foreign');

        $this->request($ctx['teacherA'], 'DELETE', '/api/gradebook_links/'.$ctx['link']->getId().'?cid='.$ctx['courseA']->getId());

        $this->assertResponseStatusCodeSame(403);
        $this->assertTrue($this->linkExists($ctx['link']));
    }

    public function testTeacherOfAnotherCourseCannotChangeALinkUsingOwnCid(): void
    {
        $ctx = $this->bootstrap('put_foreign');

        $this->request($ctx['teacherA'], 'PUT', '/api/gradebook_links/'.$ctx['link']->getId().'?cid='.$ctx['courseA']->getId(), ['weight' => 99]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(10.0, $this->freshWeight($ctx['link']));
    }

    public function testTeacherOfAnotherCourseCannotCreateALinkInTheCategoryOfAnotherCourse(): void
    {
        $ctx = $this->bootstrap('post_foreign');
        $countBefore = $this->getEntityManager()->getRepository(GradebookLink::class)->count([]);

        $this->request($ctx['teacherA'], 'POST', '/api/gradebook_links?cid='.$ctx['courseA']->getId(), [
            'type' => 1,
            'weight' => 5,
            'course' => '/api/courses/'.$ctx['courseB']->getId(),
            'category' => '/api/gradebook_categories/'.$ctx['category']->getId(),
            'resourceNode' => '/api/resource_nodes/'.$ctx['courseB']->getResourceNode()->getId(),
        ]);

        $this->assertContains(self::getClient()->getResponse()->getStatusCode(), [400, 403]);
        $this->assertSame($countBefore, $this->getEntityManager()->getRepository(GradebookLink::class)->count([]));
    }

    public function testTeacherOfTheLinkCourseIsNotDeniedWhenChangingIt(): void
    {
        $ctx = $this->bootstrap('put_own');

        $this->request($ctx['teacherB'], 'PUT', '/api/gradebook_links/'.$ctx['link']->getId().'?cid='.$ctx['courseB']->getId(), [
            'type' => 1,
            'weight' => 25,
            'course' => '/api/courses/'.$ctx['courseB']->getId(),
            'category' => '/api/gradebook_categories/'.$ctx['category']->getId(),
            'resourceNode' => '/api/resource_nodes/'.$ctx['courseB']->getResourceNode()->getId(),
        ]);

        // Only the security check is asserted: a PUT on this resource fails later, on an
        // uninitialized createdAt, with or without this authorization.
        $this->assertNotSame(403, self::getClient()->getResponse()->getStatusCode());
    }

    public function testTeacherCannotMoveTheirOwnLinkIntoTheCategoryOfAnotherCourse(): void
    {
        $ctx = $this->bootstrap('put_move');

        $em = $this->getEntityManager();
        $ownCategory = (new GradebookCategory())
            ->setTitle('Link Sec Own Category put_move')
            ->setCourse($ctx['courseA'])
            ->setWeight(100)
            ->setVisible(true)
            ->setLocked(0)
        ;
        $em->persist($ownCategory);
        $ownLink = (new GradebookLink())
            ->setType(1)
            ->setResourceNode($ctx['courseA']->getResourceNode())
            ->setCourse($ctx['courseA'])
            ->setCategory($ownCategory)
            ->setWeight(10)
            ->setVisible(1)
            ->setLocked(0)
            ->setCreatedAt(new DateTime())
        ;
        $em->persist($ownLink);
        $em->flush();
        $ownLinkId = $ownLink->getId();

        // The teacher passes the check on the link as it is, then names a category of another course.
        $this->request($ctx['teacherA'], 'PUT', '/api/gradebook_links/'.$ownLinkId.'?cid='.$ctx['courseA']->getId(), [
            'type' => 1,
            'weight' => 10,
            'course' => '/api/courses/'.$ctx['courseB']->getId(),
            'category' => '/api/gradebook_categories/'.$ctx['category']->getId(),
            'resourceNode' => '/api/resource_nodes/'.$ctx['courseA']->getResourceNode()->getId(),
        ]);

        $this->assertResponseStatusCodeSame(403);

        $em = $this->getEntityManager();
        $em->clear();
        $fresh = $em->getRepository(GradebookLink::class)->find($ownLinkId);
        $this->assertSame($ctx['courseA']->getId(), $fresh?->getCourse()?->getId());
        $this->assertSame($ownCategory->getId(), $fresh?->getCategory()->getId());
    }

    public function testTeacherOfTheLinkCourseCanDeleteIt(): void
    {
        $ctx = $this->bootstrap('delete_own');

        $this->request($ctx['teacherB'], 'DELETE', '/api/gradebook_links/'.$ctx['link']->getId().'?cid='.$ctx['courseB']->getId());

        $this->assertResponseStatusCodeSame(204);
        $this->assertFalse($this->linkExists($ctx['link']));
    }

    public function testStudentCannotDeleteALinkOfTheirCourse(): void
    {
        $ctx = $this->bootstrap('delete_student');

        $this->request($ctx['studentB'], 'DELETE', '/api/gradebook_links/'.$ctx['link']->getId().'?cid='.$ctx['courseB']->getId());

        $this->assertResponseStatusCodeSame(403);
        $this->assertTrue($this->linkExists($ctx['link']));
    }

    public function testTeacherOfAnotherCourseCannotReadALinkOfAnotherCourse(): void
    {
        $ctx = $this->bootstrap('get_foreign');

        $this->request($ctx['teacherA'], 'GET', '/api/gradebook_links/'.$ctx['link']->getId().'?cid='.$ctx['courseA']->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testTeacherAndStudentOfTheLinkCourseCanReadIt(): void
    {
        $ctx = $this->bootstrap('get_own');

        foreach (['teacherB', 'studentB'] as $key) {
            $this->request($ctx[$key], 'GET', '/api/gradebook_links/'.$ctx['link']->getId().'?cid='.$ctx['courseB']->getId());

            $this->assertResponseStatusCodeSame(200);
        }
    }

    public function testCollectionOnlyListsTheLinksOfTheCoursesOfTheUser(): void
    {
        $ctx = $this->bootstrap('get_collection');
        $linkId = (int) $ctx['link']->getId();

        $listedIds = function (User $user, Course $course): array {
            $this->request($user, 'GET', '/api/gradebook_links?cid='.$course->getId());
            $this->assertResponseStatusCodeSame(200);
            $members = json_decode(self::getClient()->getResponse()->getContent(), true)['hydra:member'] ?? [];

            return array_map(static fn (array $member): int => (int) $member['id'], $members);
        };

        $this->assertNotContains($linkId, $listedIds($ctx['teacherA'], $ctx['courseA']));
        $this->assertContains($linkId, $listedIds($ctx['teacherB'], $ctx['courseB']));
        $this->assertContains($linkId, $listedIds($ctx['studentB'], $ctx['courseB']));
    }
}
