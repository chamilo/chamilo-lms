<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use const JSON_THROW_ON_ERROR;

/**
 * Functional coverage for PATCH /api/users/{id}/password (issue #6695: "Add possibility to
 * set user password using webservice API") and, more broadly, for UserVoter::EDIT's
 * access-URL scoping of ALL user edits (AccessUrlScopeHelper::canEditUser()): an admin must
 * not be able to edit a user (password or any other field) registered on a URL outside
 * their scope -- neither through this dedicated endpoint nor the generic
 * PATCH /api/users/{id}. A plain ROLE_ADMIN is confined to exactly the URL(s) they are
 * registered on; a ROLE_GLOBAL_ADMIN additionally manages that URL's descendants.
 */
class UpdateUserPasswordActionTest extends WebTestCase
{
    use ChamiloTestTrait;

    private function createUserOnUrl(string $username, AccessUrl $url, string $role = ''): User
    {
        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);
        $admin = $this->getAdmin();

        $user = $repo->createUser()
            ->setLastname($username)
            ->setFirstname($username)
            ->setUsername($username)
            ->setStatus(1)
            ->setPlainPassword('Original-Pass-1!')
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
        $urlRepo = static::getContainer()->get(AccessUrlRepository::class);
        $admin = $this->getAdmin();
        $root = $this->getAccessUrl();

        $child = (new AccessUrl())
            ->setUrl('https://update-password-child-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($admin)
            ->setSuperior($root)
        ;
        $urlRepo->create($child);

        return $child;
    }

    private function patchJson(KernelBrowser $client, string $uri, array $body): void
    {
        $client->request(
            'PATCH',
            $uri,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
            ],
            json_encode($body, JSON_THROW_ON_ERROR)
        );
    }

    public function testUnrestrictedAdminCanResetPasswordOfAnyUserViaDedicatedEndpoint(): void
    {
        $client = static::createClient();
        $root = $this->getAccessUrl();
        $rootAdmin = $this->createUserOnUrl('update_pwd_root_admin_'.uniqid(), $root, 'ROLE_GLOBAL_ADMIN');
        $target = $this->createUserOnUrl('update_pwd_target_'.uniqid(), $root);

        $client->loginUser($rootAdmin);
        $this->patchJson($client, '/api/users/'.$target->getId().'/password', ['password' => 'Brand-New-Pass-1!']);

        $this->assertResponseIsSuccessful();
    }

    public function testScopedAdminCannotResetPasswordOfUserOutsideItsUrlViaDedicatedEndpoint(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $root = $this->getAccessUrl();

        $scopedAdmin = $this->createUserOnUrl('update_pwd_scoped_admin_'.uniqid(), $child, 'ROLE_ADMIN');
        $outsideTarget = $this->createUserOnUrl('update_pwd_outside_target_'.uniqid(), $root);

        $client->loginUser($scopedAdmin);
        $this->patchJson($client, '/api/users/'.$outsideTarget->getId().'/password', ['password' => 'Brand-New-Pass-1!']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testScopedAdminCanResetPasswordOfUserWithinItsOwnUrlViaDedicatedEndpoint(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();

        $scopedAdmin = $this->createUserOnUrl('update_pwd_scoped_admin2_'.uniqid(), $child, 'ROLE_ADMIN');
        $insideTarget = $this->createUserOnUrl('update_pwd_inside_target_'.uniqid(), $child);

        $client->loginUser($scopedAdmin);
        $this->patchJson($client, '/api/users/'.$insideTarget->getId().'/password', ['password' => 'Brand-New-Pass-1!']);

        $this->assertResponseIsSuccessful();
    }

    public function testUserCanAlwaysResetTheirOwnPasswordViaDedicatedEndpoint(): void
    {
        $client = static::createClient();
        $root = $this->getAccessUrl();
        $user = $this->createUserOnUrl('update_pwd_self_'.uniqid(), $root);

        $client->loginUser($user);
        $this->patchJson($client, '/api/users/'.$user->getId().'/password', ['password' => 'Brand-New-Pass-1!']);

        $this->assertResponseIsSuccessful();
    }

    public function testWeakPasswordIsRejectedWhenPolicyCheckIsEnabled(): void
    {
        $client = static::createClient();

        /** @var SettingsManager $settingsManager */
        $settingsManager = static::getContainer()->get(SettingsManager::class);
        $settingsManager->updateSetting('security.check_password', 'true');

        $root = $this->getAccessUrl();
        $rootAdmin = $this->createUserOnUrl('update_pwd_weak_admin_'.uniqid(), $root, 'ROLE_GLOBAL_ADMIN');
        $target = $this->createUserOnUrl('update_pwd_weak_target_'.uniqid(), $root);

        $client->loginUser($rootAdmin);
        $this->patchJson($client, '/api/users/'.$target->getId().'/password', ['password' => 'a']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testGenericPatchCannotBeUsedToBypassTheUrlScopeGuard(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $root = $this->getAccessUrl();

        $scopedAdmin = $this->createUserOnUrl('update_pwd_bypass_admin_'.uniqid(), $child, 'ROLE_ADMIN');
        $outsideTarget = $this->createUserOnUrl('update_pwd_bypass_target_'.uniqid(), $root);

        $client->loginUser($scopedAdmin);
        $this->patchJson($client, '/api/users/'.$outsideTarget->getId(), ['plainPassword' => 'Brand-New-Pass-1!']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGenericPatchCannotEditAnyFieldOfAUserOutsideTheAdminsUrlScope(): void
    {
        // UserVoter::EDIT now guards EVERY field, not just "plainPassword" -- confirms the
        // scope check isn't accidentally narrowed to the password transition only.
        $client = static::createClient();
        $child = $this->createChildUrl();
        $root = $this->getAccessUrl();

        $scopedAdmin = $this->createUserOnUrl('update_pwd_nonbypass_admin_'.uniqid(), $child, 'ROLE_ADMIN');
        $outsideTarget = $this->createUserOnUrl('update_pwd_nonbypass_target_'.uniqid(), $root);

        $client->loginUser($scopedAdmin);
        $this->patchJson($client, '/api/users/'.$outsideTarget->getId(), ['firstname' => 'EditedByScopedAdmin']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGenericPatchStillAllowsAScopedAdminToEditOtherFieldsOfAUserWithinItsOwnUrl(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();

        $scopedAdmin = $this->createUserOnUrl('update_pwd_ownscope_admin_'.uniqid(), $child, 'ROLE_ADMIN');
        $insideTarget = $this->createUserOnUrl('update_pwd_ownscope_target_'.uniqid(), $child);

        $client->loginUser($scopedAdmin);
        $this->patchJson($client, '/api/users/'.$insideTarget->getId(), ['firstname' => 'EditedByScopedAdmin']);

        $this->assertResponseIsSuccessful();
    }

    public function testPlainAdminCannotEditAUserOnAChildUrlEvenThoughAGlobalAdminInTheSameSpotCould(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();

        $plainScopedAdmin = $this->createUserOnUrl('update_pwd_plain_admin_'.uniqid(), $child, 'ROLE_ADMIN');
        $onChildTarget = $this->createUserOnUrl('update_pwd_plain_target_'.uniqid(), $child);

        // A grandchild of "child" reachable only via subtree expansion.
        /** @var AccessUrlRepository $urlRepo */
        $urlRepo = static::getContainer()->get(AccessUrlRepository::class);
        $grandchild = (new AccessUrl())
            ->setUrl('https://update-password-grandchild-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($this->getAdmin())
            ->setSuperior($child)
        ;
        $urlRepo->create($grandchild);
        $onGrandchildTarget = $this->createUserOnUrl('update_pwd_plain_grandchild_target_'.uniqid(), $grandchild);

        $client->loginUser($plainScopedAdmin);

        // Same exact URL: allowed.
        $this->patchJson($client, '/api/users/'.$onChildTarget->getId(), ['firstname' => 'EditedByPlainAdmin']);
        $this->assertResponseIsSuccessful();

        // Descendant URL: a plain ROLE_ADMIN gets no subtree expansion, unlike ROLE_GLOBAL_ADMIN.
        $this->patchJson($client, '/api/users/'.$onGrandchildTarget->getId(), ['firstname' => 'EditedByPlainAdmin']);
        $this->assertResponseStatusCodeSame(403);
    }
}
