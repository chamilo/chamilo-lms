<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\CourseSession;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * The session users tool opens a session from its overview, then lists its users. Both reads are
 * addressed by the session id in the URL, which the operations must hand to their providers.
 */
final class CourseSessionReadTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        self::getContainer()->get(SettingsManager::class)->updateSetting('session.allow_tutors_to_assign_students_to_session', 'true');
    }

    public function testAdministratorOpensTheOverviewAndTheUsersOfASession(): void
    {
        $session = $this->createSession('Course session read');
        $admin = $this->createUser('course_session_read_admin', '', '', 'ROLE_ADMIN');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($admin));

        // Both reads answered 404 for every session, whatever its id
        $client->request('GET', '/api/course-sessions/'.$session->getId().'/overview');
        $this->assertResponseIsSuccessful();

        $users = $client->request('GET', '/api/course-sessions/'.$session->getId().'/users')->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertSame($session->getId(), $users['sessionId']);
    }
}
