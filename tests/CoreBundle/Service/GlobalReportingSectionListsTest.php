<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingContext;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingSectionQueryService;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Second pass over GlobalReportingSectionQueryService::getSection(): the admin and tracking lists that
 * GlobalReportingSectionTest does not reach (certificates, exams, company, learning results, tracked courses,
 * tools, ...), including the student-boss and HR scopes. Fixtures are "rp_" rows (RawReportingFixturesTrait) rolled
 * back with the transaction, dated at fixed timestamps.
 */
final class GlobalReportingSectionListsTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private const TEACHER = 1;
    private const STUDENT = 5;

    private GlobalReportingSectionQueryService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $httpSession = new HttpSession(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($httpSession);
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        Container::setSession($httpSession);
        Container::setRequest($request);
        \Database::setManager($this->getEntityManager());
        \Database::setConnection($this->getEntityManager()->getConnection());
        $this->service = self::getContainer()->get(GlobalReportingSectionQueryService::class);
    }

    /**
     * @param array<string, bool> $flags
     */
    private function ctx(int $userId, array $flags = [], int $urlId = 1): GlobalReportingContext
    {
        $this->fresh();
        $f = $flags + [
            'admin' => false, 'hr' => false, 'sessionAdminOnly' => false, 'canViewGlobal' => true, 'boss' => false,
            'hrAll' => false, 'blockMyProgress' => false, 'email' => false,
        ];

        return new GlobalReportingContext(
            $this->entityUser($userId),
            $urlId,
            $f['admin'],
            $f['hr'],
            $f['sessionAdminOnly'],
            $f['canViewGlobal'],
            $f['boss'],
            $f['hrAll'],
            false,
            $f['email'],
            $f['blockMyProgress'],
            false,
            false,
            false,
            false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function section(GlobalReportingContext $ctx, string $name, array $filters = []): array
    {
        return $this->service->getSection($ctx, $name, $filters);
    }

    private function admin(): GlobalReportingContext
    {
        return $this->ctx(1, ['admin' => true]);
    }

    // ------------------------------------------------------ admin sessions

    /**
     * The learners column of the admin sessions overview counts the learners subscribed to the session's courses
     * who are live accounts of the current URL (or of no URL, as in the tracking list): never soft-deleted users
     * and never learners that only exist on another portal.
     */
    public function testAdminSessionsLearnerCountIgnoresSoftDeletedUsersAndLearnersOfOtherUrls(): void
    {
        $other = $this->secondUrl();
        $s = $this->rawSession('r2as1');
        $this->sessionCourse($s, 1);
        $live = $this->rawUser('r2as1a');
        $deleted = $this->rawUser('r2as1b', 5, -2);
        $foreign = $this->rawUser('r2as1c', 5, 1, $other);
        $noUrl = $this->rawUser('r2as1d', 5, 1, null);
        foreach ([$live, $deleted, $foreign, $noUrl] as $uid) {
            $this->sessionCourseUser($s, 1, $uid, 0);
        }
        $result = $this->section($this->admin(), 'admin-sessions', ['keyword' => 'rp_r2as1_']);
        $this->assertSame(1, $result['total']);
        $this->assertSame(2, $result['items'][0]['learners']);
        $this->assertSame(2, $result['summary'][1]['value']);
    }
}
