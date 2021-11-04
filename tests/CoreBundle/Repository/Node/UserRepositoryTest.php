<?php

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Group;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class UserRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCount(): void
    {
        $count = self::getContainer()->get(UserRepository::class)->count([]);
        // Admin + anon (registered in the DataFixture\AccessUrlAdminFixtures.php)
        $this->assertSame(2, $count);
    }

    public function testLoadUserByIdentifier(): void
    {
        $student = $this->createUser('student');
        $userRepo = self::getContainer()->get(UserRepository::class);

        $user = $userRepo->loadUserByIdentifier($student->getUsername());
        $this->assertInstanceOf(User::class, $user);
    }

    public function testUpgradePassword(): void
    {
        $student = $this->createUser('student');
        $userRepo = self::getContainer()->get(UserRepository::class);
        $oldPassword = $student->getPassword();
        $userRepo->upgradePassword($student, 'password');

        $this->assertTrue($oldPassword !== $student->getPassword());
    }

    public function testGetRootUser(): void
    {
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->getRootUser();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testDeleteUser(): void
    {
        $userRepo = self::getContainer()->get(UserRepository::class);
        $student = $this->createUser('student');
        $defaultCount = $userRepo->count([]);
        $userRepo->deleteUser($student);
        $this->assertSame($defaultCount - 1, $userRepo->count([]));
    }

    public function testFindByUsername(): void
    {
        $userRepo = self::getContainer()->get(UserRepository::class);
        $this->expectException(UserNotFoundException::class);
        $userRepo->findByUsername('no exist');
    }

    public function testGetCountUsersByUrl(): void
    {
        $userRepo = self::getContainer()->get(UserRepository::class);
        $count = $userRepo->getCountUsersByUrl($this->getAccessUrl());
        $this->assertNotEmpty($count);
    }

    public function testGetCountTeachersByUrl(): void
    {
        $userRepo = self::getContainer()->get(UserRepository::class);
        $count = $userRepo->getCountTeachersByUrl($this->getAccessUrl());
        $this->assertSame(0, $count);

        $this->createUser('teacher', '', '', 'ROLE_TEACHER');

        $count = $userRepo->getCountTeachersByUrl($this->getAccessUrl());
        $this->assertSame(1, $count);
    }

    public function testGetAssignedHrmUserList(): void
    {
        $userRepo = self::getContainer()->get(UserRepository::class);

        $student = $this->createUser('student');

        $drh = $this->createUser('drh');
        $drh->setRoles(['ROLE_RRHH']);
        $userRepo->update($drh);

        $student->addUserRelUser($drh, UserRelUser::USER_RELATION_TYPE_RRHH);
        $userRepo->update($student);

        $list = $userRepo->getAssignedHrmUserList($student->getId(), $this->getAccessUrl()->getId());
        $this->assertCount(1, $list);
    }

    public function testCreateUser(): void
    {
        $student = $this->createUser('student');
        $userRepo = self::getContainer()->get(UserRepository::class);

        $count = $userRepo->count([]);
        // By default, there are 2 users: admin + anon.
        $this->assertSame(3, $count);
        $this->assertHasNoEntityViolations($student);

        $this->assertCount(1, $student->getRoles());
        $this->assertContains('ROLE_USER', $student->getRoles());

        $this->assertSame('ROLE_TEACHER', User::getRoleFromStatus(1));
        $this->assertSame('ROLE_STUDENT', User::getRoleFromStatus(5));
        $this->assertSame('ROLE_RRHH', User::getRoleFromStatus(4));
        $this->assertSame('ROLE_SESSION_MANAGER', User::getRoleFromStatus(3));
        $this->assertSame('ROLE_STUDENT_BOSS', User::getRoleFromStatus(17));
        $this->assertSame('ROLE_INVITEE', User::getRoleFromStatus(20));

        $student->addRole('ROLE_STUDENT');
        $userRepo->update($student);

        $this->assertTrue($student->hasRole('ROLE_STUDENT'));
        $this->assertTrue($student->isEqualTo($student));

        $this->assertCount(2, $student->getRoles());

        $student->addRole('ROLE_STUDENT');
        $userRepo->update($student);

        $this->assertTrue($student->isStudent());
        $this->assertCount(2, $student->getRoles());

        $student->removeRole('ROLE_STUDENT');
        $userRepo->update($student);

        $this->assertCount(1, $student->getRoles());

        $this->assertTrue($student->isAccountNonExpired());
        $this->assertTrue($student->isAccountNonLocked());
        $this->assertTrue($student->isActive());
        $this->assertTrue($student->isEnabled());
        $this->assertFalse($student->isAdmin());
        $this->assertFalse($student->isStudentBoss());
        $this->assertFalse($student->isSuperAdmin());
        $this->assertTrue($student->isCredentialsNonExpired());

        $this->assertSame(1, $student->getPortals()->count());
    }

    public function testCreateAdmin(): void
    {
        $admin = $this->createUser('admin2');
        $userRepo = self::getContainer()->get(UserRepository::class);

        $em = $this->getEntityManager();

        $this->assertHasNoEntityViolations($admin);
        $admin->addUserAsAdmin();
        $userRepo->update($admin);

        $this->assertTrue($admin->isActive());
        $this->assertTrue($admin->isEnabled());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isStudentBoss());
        $this->assertFalse($admin->isSuperAdmin());
        $this->assertTrue($admin->isCredentialsNonExpired());
        $this->assertFalse($admin->getCredentialsExpired());
        $this->assertFalse($admin->getLocked());

        // Group.
        $this->assertEmpty($admin->getGroupNames());
        $this->assertFalse($admin->hasGroup('test'));

        $group = (new Group('test'))
            ->setCode('test')
        ;
        $em->persist($group);
        $em->flush();

        $admin->addGroup($group);
        $userRepo->update($admin);
        /** @var User $admin */
        $admin = $userRepo->find($admin->getId());

        $this->assertTrue($admin->hasGroup('test'));
        $this->assertCount(2, $admin->getRoles());
    }

    public function testCreateUserSkipResourceNode(): void
    {
        $em = $this->getEntityManager();
        $userRepo = self::getContainer()->get(UserRepository::class);

        $user = (new User())
            ->setLastname('Doe')
            ->setFirstname('Joe')
            ->setUsername('admin2')
            ->setEnabled(true)
            ->setSalt('')
            ->setRegistrationDate(new DateTime())
            ->setExpirationDate(new DateTime())
            ->setOpenid('')
            ->setSlug('admin2')
            ->setConfirmationToken('conf')
            ->setRoles(['ROLE_TEST'])
            ->setStatus(1)
            ->setActive(true)
            ->setDateOfBirth(new DateTime())
            ->setBiography('bio')
            ->setExpired(false)
            ->setTeach('teach')
            ->setApiToken('tok')
            ->setAuthSource('auth')
            ->setProductions('prod')
            ->setCompetences('comp')
            ->setDiplomas('diploma')
            ->setOpenarea('open')
            ->setGender('m')
            ->setTheme('chamilo')
            ->setPlainPassword('admin2')
            ->setEmail('admin@example.org')
            ->setOfficialCode('ADMIN')
            ->setCreatorId(1)
            ->setSkipResourceNode(true)
            ->addUserAsAdmin()//->addGroup($group)
        ;

        $user->setRoleFromStatus(COURSEMANAGER);
        $em->persist($user);

        $userRepo->updateUser($user);
        $userRepo->addUserToResourceNode($user->getId(), $user->getId());
        $em->flush();

        $this->assertSame(3, $userRepo->count([]));
        $this->assertCount(4, $user->getRoles());

        $this->assertSame('Joe Doe', $user->getCompleteNameWithClasses());
        $this->assertSame('Joe Doe', $user->getFullname());

        $this->assertCount(0, $user->getCurrentlyAccessibleSessions());
        $this->assertSame('/img/icons/svg/identifier_admin.svg', $user->getIconStatus());
    }

    public function testCreateUserWithApi(): void
    {
        $token = $this->getUserToken([]);
        $username = 'test';

        $iri = $this->findIriBy(AccessUrl::class, ['id' => $this->getAccessUrl()->getId()]);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/users',
            [
                'json' => [
                    'username' => $username,
                    'firstname' => 'test',
                    'lastname' => 'test',
                    'website' => '',
                    'biography' => '',
                    'locale' => 'en',
                    'plainPassword' => 'test',
                    'timezone' => 'Europe\Paris',
                    'email' => 'test@example.com',
                    'phone' => '123456',
                    'address' => 'Paris',
                    'roles' => [
                        'ROLE_USER',
                    ],
                    'currentUrl' => $iri,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/User',
            'username' => $username,
            'roles' => [
                'ROLE_USER',
            ],
        ]);

        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findByUsername($username);
        $this->assertNotNull($user);

        $this->assertSame(1, $user->getPortals()->count());
    }

    public function testFindByRole(): void
    {
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $this->createUser('user', 'user');

        $user->addRole('ROLE_SESSION_MANAGER');
        $userRepo->update($user);
        $urlId = $this->getAccessUrl()->getId();
        $users = $userRepo->findByRole('ROLE_SESSION_MANAGER', '', $urlId);
        $this->assertNotNull($users);
        $this->assertCount(1, $users);
    }

    public function testFindByRoleList(): void
    {
        $userRepo = self::getContainer()->get(UserRepository::class);

        // Create user.
        $user = $this->createUser('session_manager', 'session_manager');
        $user->addRole('ROLE_SESSION_MANAGER');
        $userRepo->update($user);

        $user = $this->createUser('question_manager', 'question_manager');
        $user->addRole('ROLE_QUESTION_MANAGER');
        $userRepo->update($user);

        $urlId = $this->getAccessUrl()->getId();

        $users = $userRepo->findByRoleList(['ROLE_SESSION_MANAGER'], '', $urlId);
        $this->assertNotNull($users);
        $this->assertCount(1, $users);

        $users = $userRepo->findByRoleList(['ROLE_SESSION_MANAGER'], 'session_manager', $urlId);
        $this->assertNotNull($users);
        $this->assertCount(1, $users);

        $users = $userRepo->findByRoleList(['ROLE_QUESTION_MANAGER'], 'question_manager', $urlId);
        $this->assertCount(1, $users);

        $users = $userRepo->findByRoleList(['ROLE_QUESTION_MANAGER'], 'abc', $urlId);
        $this->assertCount(0, $users);

        $users = $userRepo->findByRoleList(['ROLE_QUESTION_MANAGER', 'ROLE_SESSION_MANAGER'], '', $urlId);
        $this->assertCount(2, $users);
    }

    public function testAddFriendToUser(): void
    {
        $em = $this->getEntityManager();

        $user = $this->createUser('user', 'user');
        $friend = $this->createUser('friend', 'friend');

        $userRepo = self::getContainer()->get(UserRepository::class);

        // user -> friend
        $user->addFriend($friend);
        $userRepo->update($user);

        $this->assertSame(1, $user->getFriends()->count());
        $this->assertSame('friend', $user->getFriends()->first()->getFriend()->getUsername());
        $this->assertSame(0, $user->getFriendsWithMe()->count());

        $em->clear();

        // Check friend
        $friend = $userRepo->find($friend->getId());
        $this->assertSame(1, $friend->getFriendsWithMe()->count());
        $this->assertSame(0, $friend->getFriends()->count());

        // another_friend -> user
        $anotherFriend = $this->createUser('anotherfriend', 'anotherfriend');
        $user = $userRepo->find($user->getId());

        $anotherFriend->addFriend($user);
        $userRepo->update($anotherFriend);

        $this->assertSame(1, $anotherFriend->getFriends()->count());
        $this->assertSame('user', $anotherFriend->getFriends()->first()->getFriend()->getUsername());
        $this->assertSame(0, $anotherFriend->getFriendsWithMe()->count());

        $em->clear();

        /** @var User $user */
        $user = $userRepo->find($user->getId());

        $this->assertSame(1, $user->getFriends()->count());
        $this->assertSame(1, $user->getFriendsWithMe()->count());

        $em->clear();

        // Delete friend
        $friend = $userRepo->find($friend->getId());
        $userRepo->delete($friend);

        $user = $userRepo->find($user->getId());
        $this->assertSame(0, $user->getFriends()->count());
        $this->assertSame(1, $user->getFriendsWithMe()->count());
    }

    public function testUpdateUserWithApi(): void
    {
        $user = $this->createUser('test');
        $token = $this->getUserToken([]);

        $this->createClientWithCredentials($token)->request(
            'PUT',
            '/api/users/'.$user->getId(),
            [
                'json' => [
                    'firstname' => 'updated',
                    'lastname' => 'updated',
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/User',
            'firstname' => 'updated',
            'lastname' => 'updated',
        ]);
    }

    public function testUserCreationAsStudent(): void
    {
        $this->createUser('pillo');
        $tokenTest = $this->getUserToken(
            [
                'username' => 'pillo',
                'password' => 'pillo',
            ]
        );

        // Try to create user.
        $username = 'test';
        $this->createClientWithCredentials($tokenTest)->request(
            'POST',
            '/api/users',
            [
                'json' => [
                    'username' => $username,
                    'firstname' => 'test',
                    'lastname' => 'test',
                    'website' => '',
                    'biography' => '',
                    'locale' => 'en',
                    'plainPassword' => 'test',
                    'timezone' => 'Europe\Paris',
                    'email' => 'test@example.com',
                    'phone' => '123456',
                    'address' => 'Paris',
                    'roles' => [
                        'ROLE_USER',
                    ],
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(403);

        $admin = $this->getUser('admin');
        $adminIri = $admin->getIri();

        // Try to update admin!
        $this->createClientWithCredentials($tokenTest)->request(
            'PUT',
            $adminIri,
            [
                'json' => [
                    'firstname' => 'updated',
                    'lastname' => 'updated',
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(403);

        // Try to delete admin!
        $this->createClientWithCredentials($tokenTest)->request(
            'DELETE',
            $adminIri
        );
        $this->assertResponseStatusCodeSame(403);
    }
}
