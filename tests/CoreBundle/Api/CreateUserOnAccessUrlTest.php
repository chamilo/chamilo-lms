<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\User;
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

    private function postUser(array $payload): void
    {
        $admin = $this->createUser('api_admin', '', '', 'ROLE_ADMIN');

        $this->createClientWithCredentials($this->getUserTokenFromUser($admin))->request(
            'POST',
            '/api/access_urls/'.$this->getAccessUrl()->getId().'/user',
            ['json' => $payload + ['password' => 'Secret123!', 'sendEmail' => false]]
        );
    }

    /**
     * @return User[]
     */
    private function findUsersByEmail(string $email): array
    {
        return $this->getEntityManager()->getRepository(User::class)->findBy(['email' => $email]);
    }
}
