<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use const JSON_THROW_ON_ERROR;

/**
 * Why: a ROLE_GLOBAL_ADMIN scoped to a URL subtree is only allowed to manage entities that live
 * in that subtree. UserVoter::EDIT/DELETE trust the access_url_rel_user rows, so letting a scoped
 * admin LINK a user (or course, group, category) that belongs to another branch to their own URL
 * would make that entity "managed" and hand over edit/delete (password reset included) rights on
 * it. The AccessUrl*Controller assign/bulk endpoints must therefore reject ids that are not
 * already inside the caller's managed URLs, not just check the URL ids.
 */
class AccessUrlScopeAssignTest extends WebTestCase
{
    use ChamiloTestTrait;

    private function conn(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function createUserOnUrl(string $username, AccessUrl $url, string $role = ''): User
    {
        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);

        $user = $repo->createUser()
            ->setLastname($username)
            ->setFirstname($username)
            ->setUsername($username)
            ->setStatus(1)
            ->setPlainPassword($username)
            ->setEmail($username.'@example.com')
            ->setCreator($this->getAdmin())
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
        $urlRepo = static::getContainer()->get(AccessUrlRepository::class);
        $child = (new AccessUrl())
            ->setUrl('https://au-scope-assign-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($this->getAdmin())
            ->setSuperior($this->getAccessUrl())
        ;
        $urlRepo->create($child);

        return $child;
    }

    private function post(KernelBrowser $client, string $uri, array $body): void
    {
        $client->request(
            'POST',
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_SEC_FETCH_SITE' => 'same-origin'],
            json_encode($body, JSON_THROW_ON_ERROR)
        );
    }

    private function linked(string $table, string $column, int $entityId, int $urlId): bool
    {
        return false !== $this->conn()->fetchOne(
            "SELECT 1 FROM $table WHERE $column = ? AND access_url_id = ?",
            [$entityId, $urlId]
        );
    }

    private function insertGroupOnUrl(int $urlId): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO usergroup (title, group_type, visibility, allow_members_leave_group, created_at, updated_at)
             VALUES (?, 0, '1', 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
            ['au_group_'.uniqid()]
        );
        $id = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            'INSERT INTO access_url_rel_usergroup (access_url_id, usergroup_id) VALUES (?, ?)',
            [$urlId, $id]
        );

        return $id;
    }

    private function insertCategoryOnUrl(int $urlId): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_category (title, code) VALUES (?, ?)',
            ['au_cat', 'AU'.substr(uniqid(), -10)]
        );
        $id = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            'INSERT INTO access_url_rel_course_category (access_url_id, course_category_id) VALUES (?, ?)',
            [$urlId, $id]
        );

        return $id;
    }

    /**
     * @return array{0: KernelBrowser, 1: AccessUrl, 2: AccessUrl}
     */
    private function loginScopedAdmin(): array
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $root = $this->getAccessUrl();
        $client->loginUser($this->createUserOnUrl('au_scoped_'.uniqid(), $child, 'ROLE_GLOBAL_ADMIN'));

        return [$client, $child, $root];
    }

    public function testUsersAssignIgnoresAUserOfAnotherBranch(): void
    {
        [$client, $child, $root] = $this->loginScopedAdmin();
        $victim = $this->createUserOnUrl('au_victim_'.uniqid(), $root);
        $insider = $this->createUserOnUrl('au_insider_'.uniqid(), $child);

        $this->post($client, '/admin/access-urls-users-data', [
            'access_url_id' => $child->getId(),
            'user_ids' => [$victim->getId(), $insider->getId()],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertFalse($this->linked('access_url_rel_user', 'user_id', (int) $victim->getId(), (int) $child->getId()));
        $this->assertTrue($this->linked('access_url_rel_user', 'user_id', (int) $insider->getId(), (int) $child->getId()));
    }

    public function testUsersBulkAddIgnoresAUserOfAnotherBranch(): void
    {
        [$client, $child, $root] = $this->loginScopedAdmin();
        $victim = $this->createUserOnUrl('au_victim_'.uniqid(), $root);

        $this->post($client, '/admin/access-urls-users-data/bulk', [
            'action' => 'add',
            'user_ids' => [$victim->getId()],
            'url_ids' => [$child->getId()],
        ]);

        $this->assertFalse($this->linked('access_url_rel_user', 'user_id', (int) $victim->getId(), (int) $child->getId()));
    }

    public function testUsersListDoesNotExposeUsersOfAnotherBranch(): void
    {
        [$client, $child, $root] = $this->loginScopedAdmin();
        $victim = $this->createUserOnUrl('au_victim_'.uniqid(), $root);

        $client->request('GET', '/admin/access-urls-users-data?access_url_id='.$child->getId());
        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertNotContains($victim->getId(), array_column($data['available'], 'id'));

        $client->request('GET', '/admin/access-urls-users-data/all-users?first_letter=__all__');
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertNotContains($victim->getId(), array_column($data['items'], 'id'));
    }

    public function testUnrestrictedAdminStillAssignsAnyUser(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $victim = $this->createUserOnUrl('au_victim_'.uniqid(), $this->getAccessUrl());
        $client->loginUser($this->createUserOnUrl('au_rootadm_'.uniqid(), $this->getAccessUrl(), 'ROLE_GLOBAL_ADMIN'));

        $this->post($client, '/admin/access-urls-users-data', [
            'access_url_id' => $child->getId(),
            'user_ids' => [$victim->getId()],
        ]);

        $this->assertTrue($this->linked('access_url_rel_user', 'user_id', (int) $victim->getId(), (int) $child->getId()));
    }

    public function testCoursesAssignAndBulkIgnoreACourseOfAnotherBranch(): void
    {
        [$client, $child] = $this->loginScopedAdmin();
        $childId = (int) $child->getId();
        // Course 1 is also on the child URL (managed); course 2 only lives on the root URL.
        $this->conn()->executeStatement('INSERT INTO access_url_rel_course (c_id, access_url_id) VALUES (1, ?)', [$childId]);

        $this->post($client, '/admin/access-urls-courses-data', ['access_url_id' => $childId, 'course_ids' => [1, 2]]);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->linked('access_url_rel_course', 'c_id', 1, $childId));
        $this->assertFalse($this->linked('access_url_rel_course', 'c_id', 2, $childId));

        $this->post($client, '/admin/access-urls-courses-data/bulk', ['action' => 'add', 'course_ids' => [2], 'url_ids' => [$childId]]);
        $this->assertFalse($this->linked('access_url_rel_course', 'c_id', 2, $childId));
    }

    public function testCoursesListDoesNotExposeACourseOfAnotherBranch(): void
    {
        [$client, $child] = $this->loginScopedAdmin();

        $client->request('GET', '/admin/access-urls-courses-data?access_url_id='.$child->getId());
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertNotContains(2, array_column($data['available'], 'id'));
    }

    public function testUnrestrictedAdminStillAssignsAnyCourse(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $client->loginUser($this->createUserOnUrl('au_rootadm_'.uniqid(), $this->getAccessUrl(), 'ROLE_GLOBAL_ADMIN'));

        $this->post($client, '/admin/access-urls-courses-data', ['access_url_id' => $child->getId(), 'course_ids' => [2]]);

        $this->assertTrue($this->linked('access_url_rel_course', 'c_id', 2, (int) $child->getId()));
    }

    public function testUserGroupsAssignAndBulkIgnoreAGroupOfAnotherBranch(): void
    {
        [$client, $child, $root] = $this->loginScopedAdmin();
        $childId = (int) $child->getId();
        $foreign = $this->insertGroupOnUrl((int) $root->getId());
        $own = $this->insertGroupOnUrl($childId);

        $this->post($client, '/admin/access-urls-usergroups-data', ['access_url_id' => $childId, 'group_ids' => [$foreign, $own]]);
        $this->assertResponseIsSuccessful();
        $this->assertFalse($this->linked('access_url_rel_usergroup', 'usergroup_id', $foreign, $childId));
        $this->assertTrue($this->linked('access_url_rel_usergroup', 'usergroup_id', $own, $childId));

        $this->post($client, '/admin/access-urls-usergroups-data/bulk', ['action' => 'add', 'group_ids' => [$foreign], 'url_ids' => [$childId]]);
        $this->assertFalse($this->linked('access_url_rel_usergroup', 'usergroup_id', $foreign, $childId));

        $client->request('GET', '/admin/access-urls-usergroups-data?access_url_id='.$childId);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertNotContains($foreign, array_column($data['available'], 'id'));
    }

    public function testCourseCategoriesAssignIgnoresACategoryOfAnotherBranch(): void
    {
        [$client, $child, $root] = $this->loginScopedAdmin();
        $childId = (int) $child->getId();
        $foreign = $this->insertCategoryOnUrl((int) $root->getId());
        $own = $this->insertCategoryOnUrl($childId);

        $this->post($client, '/admin/access-urls-course-categories-data', ['access_url_id' => $childId, 'category_ids' => [$foreign, $own]]);
        $this->assertResponseIsSuccessful();
        $this->assertFalse($this->linked('access_url_rel_course_category', 'course_category_id', $foreign, $childId));
        $this->assertTrue($this->linked('access_url_rel_course_category', 'course_category_id', $own, $childId));

        $client->request('GET', '/admin/access-urls-course-categories-data?access_url_id='.$childId);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertNotContains($foreign, array_column($data['available'], 'id'));
    }
}
