<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * GET /api/course_rel_users previously delegated fully to the default CollectionProvider for any
 * ROLE_ADMIN/ROLE_GLOBAL_ADMIN/ROLE_SUPER_ADMIN, with no portal scoping at all -- unlike
 * PartialSearchOrFilter's handling of GET /api/users, no filter picked up the slack here, so any
 * admin could enumerate every course<->user subscription install-wide. Now:
 * CourseRelUserCollectionStateProvider itself scopes the privileged branch to the caller's
 * managed URLs (unrestricted admins unaffected, subtree admins confined to their own subtree).
 */
class CourseRelUserCollectionStateProviderTest extends WebTestCase
{
    use ChamiloTestTrait;

    private function createUserOnUrl(string $username, AccessUrl $url, string $role = ''): User
    {
        /** @var UserRepository $repo */
        $repo = self::getContainer()->get(UserRepository::class);
        $admin = $this->getAdmin();

        $user = $repo->createUser()
            ->setLastname($username)
            ->setFirstname($username)
            ->setUsername($username)
            ->setStatus(1)
            ->setPlainPassword($username)
            ->setEmail($username.'@example.com')
            ->setCreator($admin)
            ->setCurrentUrl($url)
            ->addAuthSourceByAuthentication(UserAuthSource::PLATFORM, $url)
        ;

        if ('' !== $role) {
            $user->addRole($role);
        }

        $repo->updateUser($user);

        return $user;
    }

    private function createChildUrl(): AccessUrl
    {
        /** @var AccessUrlRepository $urlRepo */
        $urlRepo = self::getContainer()->get(AccessUrlRepository::class);
        $admin = $this->getAdmin();
        $root = $this->getAccessUrl();

        $child = (new AccessUrl())
            ->setUrl('https://course-rel-user-state-provider-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($admin)
            ->setSuperior($root)
        ;
        $urlRepo->create($child);

        return $child;
    }

    private function createCourseOnUrl(string $title, AccessUrl $url): Course
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = (new Course())
            ->setTitle($title)
            ->addAccessUrl($url)
            ->setCreator($this->getAdmin())
            ->setVisibility(Course::OPEN_PLATFORM)
        ;
        $courseRepo->create($course);

        return $course;
    }

    private function subscribeUserToCourse(User $user, Course $course): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $subscription = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($user)
            ->setStatus(CourseRelUser::STUDENT)
            ->setRelationType(0)
        ;
        $em->persist($subscription);
        $em->flush();
    }

    /**
     * @return int[] course ids referenced by the collection's members
     */
    private function courseIdsInCollection(KernelBrowser $client): array
    {
        $client->request(
            'GET',
            '/api/course_rel_users',
            ['itemsPerPage' => 1000],
            [],
            ['HTTP_ACCEPT' => 'application/ld+json']
        );
        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);

        $ids = [];
        foreach ($data['hydra:member'] ?? [] as $member) {
            $courseField = $member['course'] ?? null;
            $courseIri = \is_array($courseField) ? ($courseField['@id'] ?? '') : (string) $courseField;

            if ('' !== $courseIri && preg_match('#/api/courses/(\d+)#', $courseIri, $matches)) {
                $ids[] = (int) $matches[1];
            }
        }

        return $ids;
    }

    public function testScopedGlobalAdminOnlySeesSubscriptionsInTheirOwnSubtree(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $scopedAdmin = $this->createUserOnUrl('cruc_scoped_admin', $child, 'ROLE_GLOBAL_ADMIN');
        $childCourse = $this->createCourseOnUrl('Cruc Child Course', $child);
        $rootCourse = $this->createCourseOnUrl('Cruc Root Course', $this->getAccessUrl());
        $childStudent = $this->createUserOnUrl('cruc_child_student', $child);

        $this->subscribeUserToCourse($childStudent, $childCourse);
        $this->subscribeUserToCourse($childStudent, $rootCourse);

        $client->loginUser($scopedAdmin);
        $courseIds = $this->courseIdsInCollection($client);

        $this->assertContains($childCourse->getId(), $courseIds);
        $this->assertNotContains($rootCourse->getId(), $courseIds);
    }

    public function testUnrestrictedGlobalAdminSeesSubscriptionsFromEveryUrl(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $rootAdmin = $this->createUserOnUrl('cruc_root_admin', $this->getAccessUrl(), 'ROLE_GLOBAL_ADMIN');
        $childCourse = $this->createCourseOnUrl('Cruc Child Course 2', $child);
        $rootCourse = $this->createCourseOnUrl('Cruc Root Course 2', $this->getAccessUrl());
        $childStudent = $this->createUserOnUrl('cruc_child_student_2', $child);

        $this->subscribeUserToCourse($childStudent, $childCourse);
        $this->subscribeUserToCourse($childStudent, $rootCourse);

        $client->loginUser($rootAdmin);
        $courseIds = $this->courseIdsInCollection($client);

        $this->assertContains($childCourse->getId(), $courseIds);
        $this->assertContains($rootCourse->getId(), $courseIds);
    }
}
