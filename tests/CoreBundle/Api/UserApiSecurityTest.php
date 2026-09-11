<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * Regression tests for the GET /api/users security audit.
 *
 * Asserts the expected post-fix behaviour of the collection endpoint:
 *  - the "email" search filter must NOT act for an unprivileged caller, because
 *    the collection hides the email field for them (user:read:public group). A
 *    working email filter over a scoped result set is an enumeration oracle: the
 *    attacker probes "?email=<substring>" and reads which users match off their
 *    public username/name, reconstructing every reachable user's email — the same
 *    class of leak as GHSA-h2qc (message recipient autocomplete, <= 1.11.40).
 *  - the collection response never exposes email to an unprivileged caller.
 *  - an administrator keeps the email filter (the fix must not over-restrict).
 */
final class UserApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * The email filter must be a no-op for an unprivileged caller. Observable on
     * the caller's own row: a garbage email substring that matches nobody must not
     * remove the caller from their own scoped collection. Pre-fix the filter
     * applies and the caller vanishes (total drops); post-fix the total is
     * unchanged.
     */
    public function testEmailFilterIsIgnoredForUnprivilegedUsers(): void
    {
        $attacker = $this->createUser('user_sec_email_oracle');
        $token = $this->getUserTokenFromUser($attacker);

        $unfiltered = $this->collectionTotalItems($token, '/api/users');
        $withGarbageEmail = $this->collectionTotalItems($token, '/api/users?email=zz-no-such-email-zz');

        self::assertGreaterThanOrEqual(1, $unfiltered, 'The caller must at least see their own row.');
        self::assertSame(
            $unfiltered,
            $withGarbageEmail,
            'The email search filter must not narrow the collection for an unprivileged user.'
        );
    }

    /**
     * The unprivileged collection response must not carry the email field
     * (documents the existing user:read:public defense the fix relies on).
     */
    public function testCollectionHidesEmailFromUnprivilegedUsers(): void
    {
        $attacker = $this->createUser('user_sec_email_hidden');
        $token = $this->getUserTokenFromUser($attacker);

        $data = $this->createClientWithCredentials($token)
            ->request('GET', '/api/users')
            ->toArray()
        ;

        foreach ($data['hydra:member'] ?? [] as $member) {
            self::assertArrayNotHasKey('email', $member, 'Email must not be exposed to unprivileged callers.');
        }
    }

    /**
     * An administrator must still be able to filter users by email: the fix only
     * disables the filter for unprivileged callers, never for privileged ones.
     */
    public function testAdminCanStillFilterUsersByEmail(): void
    {
        $this->createUser('user_sec_email_target');
        $admin = $this->createUser('user_sec_email_admin', '', '', 'ROLE_ADMIN');
        $token = $this->getUserTokenFromUser($admin);

        $total = $this->collectionTotalItems($token, '/api/users?email=user_sec_email_target@example.com');

        self::assertGreaterThanOrEqual(1, $total, 'Admin email filtering must keep returning the matching user.');
    }

    private function collectionTotalItems(string $token, string $url): int
    {
        $data = $this->createClientWithCredentials($token)
            ->request('GET', $url)
            ->toArray()
        ;

        return (int) ($data['hydra:totalItems'] ?? 0);
    }
}
