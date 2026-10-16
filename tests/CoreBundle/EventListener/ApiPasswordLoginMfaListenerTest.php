<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\EventListener;

use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * /api/authentication_token has no TOTP step, so for an account with 2FA a
 * password alone must not produce a JWT: otherwise the second factor protects
 * nothing, since an attacker holding the password just picks this login path.
 */
class ApiPasswordLoginMfaListenerTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const MFA_MESSAGE = 'Two-factor authentication is required for this account.';

    public function testMfaUserWithCorrectPasswordGetsNoToken(): void
    {
        $this->createMfaUser('jwt_mfa_ok');

        $response = $this->requestToken('jwt_mfa_ok', 'jwt_mfa_ok');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $data = $response->toArray(false);
        $this->assertArrayNotHasKey('token', $data);
        $this->assertSame(self::MFA_MESSAGE, $data['message']);
    }

    /**
     * The 2FA check runs after the password check, so a wrong password must
     * not reveal which accounts have 2FA enabled.
     */
    public function testMfaUserWithWrongPasswordGetsGenericError(): void
    {
        $this->createMfaUser('jwt_mfa_bad');

        $response = $this->requestToken('jwt_mfa_bad', 'wrong-password');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertNotSame(self::MFA_MESSAGE, $response->toArray(false)['message']);
    }

    /**
     * Accounts without 2FA, and the integrations built on them, keep working.
     */
    public function testUserWithoutMfaStillGetsToken(): void
    {
        $this->createUser('jwt_no_mfa');

        $response = $this->requestToken('jwt_no_mfa', 'jwt_no_mfa');

        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($response->toArray()['token']);
    }

    private function createMfaUser(string $username): void
    {
        $user = $this->createUser($username);
        $user->setMfaEnabled(true);

        static::getContainer()->get(UserRepository::class)->updateUser($user);
    }

    private function requestToken(string $username, string $password): ResponseInterface
    {
        return static::createClient()->request(
            'POST',
            '/api/authentication_token',
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['username' => $username, 'password' => $password]),
            ],
        );
    }
}
