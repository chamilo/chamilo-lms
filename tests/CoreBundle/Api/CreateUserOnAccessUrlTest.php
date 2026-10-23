<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * The platform lets several accounts share an e-mail address (a learner re-enrolled under a new
 * person number keeps the same one), so the endpoint only refuses a known address when the
 * caller asks for it, as check_email_duplicates did in the 1.11 save_user_json webservice.
 */
final class CreateUserOnAccessUrlTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testKnownEmailIsAcceptedByDefault(): void
    {
        $this->createUser('email_owner', '', 'shared@example.com');

        $this->postUser(['username' => 'email_second', 'email' => 'shared@example.com']);

        $this->assertResponseStatusCodeSame(201);
        $this->assertCount(2, $this->findUsersByEmail('shared@example.com'));
    }

    public function testKnownEmailIsRefusedWhenTheCallerChecksDuplicates(): void
    {
        $this->createUser('email_owner', '', 'shared@example.com');

        $this->postUser([
            'username' => 'email_second',
            'email' => 'SHARED@example.com',
            'checkEmailDuplicates' => true,
        ]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertCount(1, $this->findUsersByEmail('shared@example.com'));
    }

    public function testKnownUsernameIsAlwaysRefused(): void
    {
        $this->createUser('taken_login');

        $this->postUser(['username' => 'taken_login', 'email' => 'other@example.com']);

        $this->assertResponseStatusCodeSame(409);
    }

    /**
     * Session managers reach this endpoint to register learners, so that is all they may create:
     * an account with a higher status would hand them (or whoever gets the password they chose)
     * teacher, HR or session-manager rights they were never given the power to grant.
     *
     * @dataProvider higherStatusProvider
     */
    public function testSessionManagerCannotCreateAccountsAboveLearner(int $status): void
    {
        $sessionManager = $this->createUser('api_session_manager', '', '', 'ROLE_SESSION_MANAGER');

        $this->postUserAs($sessionManager, ['username' => 'sm_created', 'email' => 'sm_created@example.com', 'status' => $status]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertNull($this->findUserByUsername('sm_created'));
    }

    public static function higherStatusProvider(): array
    {
        return [
            'teacher' => [1],
            'session manager' => [3],
            'human resources manager' => [4],
            'superior' => [17],
        ];
    }

    public function testSessionManagerStillRegistersLearners(): void
    {
        $sessionManager = $this->createUser('api_session_manager', '', '', 'ROLE_SESSION_MANAGER');

        $this->postUserAs($sessionManager, ['username' => 'sm_learner', 'email' => 'sm_learner@example.com']);

        $this->assertResponseStatusCodeSame(201);
        $learner = $this->getUser('sm_learner');
        $this->assertSame(5, $learner->getStatus());
        $this->assertSame(['ROLE_STUDENT', 'ROLE_USER'], $learner->getRoles());
    }

    public function testAdminMayCreateAccountsWithAHigherStatus(): void
    {
        $this->postUser(['username' => 'admin_created_hr', 'email' => 'admin_created_hr@example.com', 'status' => 4]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertContains('ROLE_HR', $this->getUser('admin_created_hr')->getRoles());
    }

    public function testUnknownStatusIsRefused(): void
    {
        $this->postUser(['username' => 'admin_created_odd', 'email' => 'admin_created_odd@example.com', 'status' => 11]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertNull($this->findUserByUsername('admin_created_odd'));
    }

    /**
     * The access URL comes from the path, so it must be one the caller works on: otherwise anyone
     * allowed to call the endpoint could plant accounts on any portal of a multi-URL install.
     */
    public function testSessionManagerCannotCreateAccountsOnAnotherAccessUrl(): void
    {
        $sessionManager = $this->createUser('api_session_manager', '', '', 'ROLE_SESSION_MANAGER');
        $otherUrl = $this->createChildAccessUrl();

        $this->postUserAs($sessionManager, ['username' => 'sm_elsewhere', 'email' => 'sm_elsewhere@example.com'], $otherUrl);

        $this->assertResponseStatusCodeSame(403);
        $this->assertNull($this->findUserByUsername('sm_elsewhere'));
    }

    private function postUser(array $payload): void
    {
        $this->postUserAs($this->createUser('api_admin', '', '', 'ROLE_ADMIN'), $payload);
    }

    private function postUserAs(User $actor, array $payload, ?AccessUrl $url = null): void
    {
        $url ??= $this->getAccessUrl();

        $this->createClientWithCredentials($this->getUserTokenFromUser($actor))->request(
            'POST',
            '/api/access_urls/'.$url->getId().'/user',
            ['json' => $payload + ['password' => 'Secret123!', 'sendEmail' => false]]
        );
    }

    private function createChildAccessUrl(): AccessUrl
    {
        $url = (new AccessUrl())
            ->setUrl('https://create-user-scope-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($this->getAdmin())
            ->setSuperior($this->getAccessUrl())
        ;
        self::getContainer()->get(AccessUrlRepository::class)->create($url);

        return $url;
    }

    private function findUserByUsername(string $username): ?User
    {
        return $this->getEntityManager()->getRepository(User::class)->findOneBy(['username' => $username]);
    }

    /**
     * @return User[]
     */
    private function findUsersByEmail(string $email): array
    {
        return $this->getEntityManager()->getRepository(User::class)->findBy(['email' => $email]);
    }
}
