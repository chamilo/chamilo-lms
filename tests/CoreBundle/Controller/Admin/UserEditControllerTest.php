<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserEditControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testDataEndpointAsAdmin(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));
        $target = $this->createUser('user_edit_ctrl_target_data');

        $client->request('GET', '/admin/user-edit-data', ['user_id' => $target->getId()], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($target->getId(), $data['user']['id']);
        $this->assertArrayHasKey('roleOptions', $data);
        $this->assertArrayHasKey('studentBossOptions', $data);
    }

    public function testDataEndpointDeniedForStudent(): void
    {
        $client = static::createClient();
        $student = $this->createUser('user_edit_ctrl_student');
        $client->loginUser($student);
        $target = $this->createUser('user_edit_ctrl_target_denied');

        $client->request('GET', '/admin/user-edit-data', ['user_id' => $target->getId()], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditNonExistentUserReturns404(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));

        $client->request('GET', '/admin/user-edit-data', ['user_id' => 999999999], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAdminEditingOwnAccountSucceeds(): void
    {
        // admin/user_edit.php is an admin-only tool (api_protect_admin_script(true) never
        // exempts self-edits) -- the "self" bypass only affects the escalation check inside
        // it, once an already-admitted admin/session-admin edits their own row.
        $client = static::createClient();
        $self = $this->createUser('user_edit_ctrl_self', '', '', 'ROLE_ADMIN');
        $client->loginUser($self);

        $client->request(
            'POST',
            '/admin/user-edit-action',
            [
                'user_id' => $self->getId(),
                'firstname' => 'SelfEdited',
                'lastname' => $self->getLastname(),
                'email' => $self->getEmail(),
                'username' => $self->getUsername(),
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                'sendMail' => '0',
                'resetPassword' => '0',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testUpdateAsAdminSucceeds(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));
        $target = $this->createUser('user_edit_ctrl_target_update');

        $client->request(
            'POST',
            '/admin/user-edit-action',
            [
                'user_id' => $target->getId(),
                'firstname' => 'UpdatedFirst',
                'lastname' => 'UpdatedLast',
                'email' => $target->getEmail(),
                'username' => $target->getUsername(),
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                'sendMail' => '0',
                'active' => '1',
                'resetPassword' => '0',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();

        $em = $this->getEntityManager();
        $em->clear();
        $refreshed = $em->getRepository(User::class)->find($target->getId());
        $this->assertSame('UpdatedFirst', $refreshed->getFirstname());
        $this->assertSame('UpdatedLast', $refreshed->getLastname());
    }

    public function testResubmittingOwnUsernameDoesNotTriggerDuplicateCheck(): void
    {
        // Regression: excluding the target's own current username from the
        // availability check must not be confused with skipping the check
        // entirely -- see the equivalent fix in UserAddController's history.
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));
        $target = $this->createUser('user_edit_ctrl_same_username');

        $client->request(
            'POST',
            '/admin/user-edit-action',
            [
                'user_id' => $target->getId(),
                'firstname' => $target->getFirstname(),
                'lastname' => $target->getLastname(),
                'email' => $target->getEmail(),
                'username' => $target->getUsername(),
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                'sendMail' => '0',
                'active' => '1',
                'resetPassword' => '0',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();
    }

    public function testChangingUsernameToAnotherExistingOneFails(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));
        $other = $this->createUser('user_edit_ctrl_other_username');
        $target = $this->createUser('user_edit_ctrl_wants_dup_username');

        $client->request(
            'POST',
            '/admin/user-edit-action',
            [
                'user_id' => $target->getId(),
                'firstname' => $target->getFirstname(),
                'lastname' => $target->getLastname(),
                'email' => $target->getEmail(),
                'username' => $other->getUsername(),
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                'sendMail' => '0',
                'active' => '1',
                'resetPassword' => '0',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseStatusCodeSame(409);
    }

    public function testHideNeverExpireOptionForcesExpirationForSessionAdminEvenIfClientSaysOtherwise(): void
    {
        $client = static::createClient();
        static::getContainer()->get(SettingsManager::class)
            ->updateSetting('registration.user_hide_never_expire_option', 'true')
        ;

        $sessionAdmin = $this->createUser('user_edit_ctrl_session_admin_expiry', '', '', 'ROLE_SESSION_MANAGER');
        $client->loginUser($sessionAdmin);
        $target = $this->createUser('user_edit_ctrl_target_expiry');

        $client->request(
            'POST',
            '/admin/user-edit-action',
            [
                'user_id' => $target->getId(),
                'firstname' => $target->getFirstname(),
                'lastname' => $target->getLastname(),
                'email' => $target->getEmail(),
                'username' => $target->getUsername(),
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                'sendMail' => '0',
                'active' => '1',
                // A malicious/naive client claims "never expires" -- must be ignored server-side.
                'hasExpirationDate' => '0',
                'resetPassword' => '0',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();

        $em = $this->getEntityManager();
        $em->clear();
        $refreshed = $em->getRepository(User::class)->find($target->getId());
        $this->assertNotNull($refreshed->getExpirationDate());
    }

    public function testManualPasswordResetIgnoredWhenAdminsCanSetUsersPassIsDisabled(): void
    {
        $client = static::createClient();
        static::getContainer()->get(SettingsManager::class)
            ->updateSetting('security.admins_can_set_users_pass', 'false')
        ;

        $client->loginUser($this->getUser('admin'));
        $target = $this->createUser('user_edit_ctrl_pass_disabled');

        $client->request(
            'POST',
            '/admin/user-edit-action',
            [
                'user_id' => $target->getId(),
                'firstname' => $target->getFirstname(),
                'lastname' => $target->getLastname(),
                'email' => $target->getEmail(),
                'username' => $target->getUsername(),
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                'sendMail' => '0',
                'active' => '1',
                'resetPassword' => '2',
                'password' => 'AttackerChosen123!',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertFalse($hasher->isPasswordValid($target, 'AttackerChosen123!'));
    }

    public function testManualPasswordTooWeakFailsWhenCheckPasswordEnabled(): void
    {
        $client = static::createClient();
        $settingsManager = static::getContainer()->get(SettingsManager::class);
        $settingsManager->updateSetting('security.admins_can_set_users_pass', 'true');
        $settingsManager->updateSetting('security.check_password', 'true');

        $client->loginUser($this->getUser('admin'));
        $target = $this->createUser('user_edit_ctrl_pass_weak');

        $client->request(
            'POST',
            '/admin/user-edit-action',
            [
                'user_id' => $target->getId(),
                'firstname' => $target->getFirstname(),
                'lastname' => $target->getLastname(),
                'email' => $target->getEmail(),
                'username' => $target->getUsername(),
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                'sendMail' => '0',
                'active' => '1',
                'resetPassword' => '2',
                'password' => '123',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testManualPasswordStrongEnoughIsUsable(): void
    {
        $client = static::createClient();
        $settingsManager = static::getContainer()->get(SettingsManager::class);
        $settingsManager->updateSetting('security.admins_can_set_users_pass', 'true');
        $settingsManager->updateSetting('security.check_password', 'true');

        $client->loginUser($this->getUser('admin'));
        $target = $this->createUser('user_edit_ctrl_pass_strong');

        $client->request(
            'POST',
            '/admin/user-edit-action',
            [
                'user_id' => $target->getId(),
                'firstname' => $target->getFirstname(),
                'lastname' => $target->getLastname(),
                'email' => $target->getEmail(),
                'username' => $target->getUsername(),
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                'sendMail' => '0',
                'active' => '1',
                'resetPassword' => '2',
                'password' => 'Str0ng#Passw0rd!',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();

        $em = $this->getEntityManager();
        $em->clear();
        $refreshed = $em->getRepository(User::class)->find($target->getId());
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertTrue($hasher->isPasswordValid($refreshed, 'Str0ng#Passw0rd!'));
    }

    public function testDrhConflictRejectedWhenUserIsSubscribedInCourse(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));
        $target = $this->createUser('user_edit_ctrl_drh_conflict');
        $course = $this->createCourse('User edit DRH conflict course');

        $em = $this->getEntityManager();
        $subscription = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($target)
            ->setStatus(CourseRelUser::STUDENT)
            ->setRelationType(0)
        ;
        $em->persist($subscription);
        $em->flush();

        $client->request(
            'POST',
            '/admin/user-edit-action',
            [
                'user_id' => $target->getId(),
                'firstname' => $target->getFirstname(),
                'lastname' => $target->getLastname(),
                'email' => $target->getEmail(),
                'username' => $target->getUsername(),
                'authSource' => ['platform'],
                'roles' => ['ROLE_HR'],
                'locale' => 'en_US',
                'sendMail' => '0',
                'active' => '1',
                'resetPassword' => '0',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseStatusCodeSame(409);
    }

    public function testRoleDowngradeToStudentRejectedWhenUserTeachesACourse(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));
        $target = $this->createUser('user_edit_ctrl_teacher_conflict', '', '', 'ROLE_TEACHER');
        $course = $this->createCourse('User edit role downgrade conflict course');

        $em = $this->getEntityManager();
        $subscription = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($target)
            ->setStatus(CourseRelUser::TEACHER)
            ->setRelationType(0)
        ;
        $em->persist($subscription);
        $em->flush();

        $client->request(
            'POST',
            '/admin/user-edit-action',
            [
                'user_id' => $target->getId(),
                'firstname' => $target->getFirstname(),
                'lastname' => $target->getLastname(),
                'email' => $target->getEmail(),
                'username' => $target->getUsername(),
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                'sendMail' => '0',
                'active' => '1',
                'resetPassword' => '0',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseStatusCodeSame(409);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertNotEmpty($data['conflicts']);
    }

    public function testSessionAdminCannotEditGlobalAdminEvenWithinSameAccessUrlScope(): void
    {
        // "Combine both" access model: AccessUrlScopeHelper::canEditUser() alone would allow
        // this (same access URL), but the legacy role-escalation check must still block it.
        $client = static::createClient();
        $sessionAdmin = $this->createUser('user_edit_ctrl_escalation_actor', '', '', 'ROLE_SESSION_MANAGER');
        $client->loginUser($sessionAdmin);
        $globalAdminTarget = $this->createUser('user_edit_ctrl_escalation_target', '', '', 'ROLE_GLOBAL_ADMIN');

        $client->request('GET', '/admin/user-edit-data', ['user_id' => $globalAdminTarget->getId()], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseStatusCodeSame(403);
    }
}
