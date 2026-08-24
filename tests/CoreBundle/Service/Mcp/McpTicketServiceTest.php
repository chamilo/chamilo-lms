<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Mcp;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\TicketMessageAttachmentRepository;
use Chamilo\CoreBundle\Service\Mcp\McpTicketService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Every ticket MCP tool routes through McpTicketService::assertPortalAdmin() first: only
 * administrators who manage the current portal (access URL) may read or change its tickets.
 * These tests exercise that gate in isolation, using a real AccessUrlScopeHelper/AccessUrlHelper
 * (both are declared final/readonly and cannot be mocked) backed by mocked repositories/connection
 * so the exact scoping rule already covered by AccessUrlScopeHelperTest is not re-tested here.
 */
final class McpTicketServiceTest extends TestCase
{
    public function testAssertPortalAdminDeniesAnUnauthenticatedCaller(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $service = $this->createService($security);

        $this->expectException(AccessDeniedException::class);
        $service->assertPortalAdmin();
    }

    public function testAssertPortalAdminDeniesANonAdminUser(): void
    {
        $user = $this->createUser(7);
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        $service = $this->createService($security);

        $this->expectException(AccessDeniedException::class);
        $service->assertPortalAdmin();
    }

    public function testAssertPortalAdminDeniesAnAdminWhoDoesNotManageTheCurrentPortal(): void
    {
        $user = $this->createUser(7);
        $accessUrl = $this->createAccessUrl(5);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);

        // Not registered at a root URL (not unrestricted) and manages no other URL either,
        // so URL #5 falls outside this admin's managed subtree.
        $service = $this->createService(
            $security,
            $this->createAccessUrlHelper($accessUrl),
            new AccessUrlScopeHelper($this->createConnection(false, [])),
        );

        $this->expectException(AccessDeniedException::class);
        $service->assertPortalAdmin();
    }

    public function testAssertPortalAdminAllowsAnAdminWhoManagesTheCurrentPortal(): void
    {
        $user = $this->createUser(7);
        $accessUrl = $this->createAccessUrl(5);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);

        // Registered at a root URL: unrestricted, manages every portal.
        $service = $this->createService(
            $security,
            $this->createAccessUrlHelper($accessUrl),
            new AccessUrlScopeHelper($this->createConnection(true, [])),
        );

        self::assertSame($user, $service->assertPortalAdmin());
    }

    public function testAssertPortalAdminFailsWhenTheCurrentPortalCannotBeResolved(): void
    {
        $user = $this->createUser(7);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->createService($security, $this->createAccessUrlHelper(null))->assertPortalAdmin();
    }

    private function createService(
        Security $security,
        ?AccessUrlHelper $accessUrlHelper = null,
        ?AccessUrlScopeHelper $accessUrlScopeHelper = null,
    ): McpTicketService {
        return new McpTicketService(
            $security,
            $accessUrlHelper ?? $this->createAccessUrlHelper(null),
            $accessUrlScopeHelper ?? new AccessUrlScopeHelper($this->createConnection(false, [])),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(TicketMessageAttachmentRepository::class),
        );
    }

    private function createUser(int $id): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }

    private function createAccessUrl(int $id): AccessUrl
    {
        $accessUrl = $this->createMock(AccessUrl::class);
        $accessUrl->method('getId')->willReturn($id);

        return $accessUrl;
    }

    private function createAccessUrlHelper(?AccessUrl $accessUrl): AccessUrlHelper
    {
        $repository = $this->createMock(AccessUrlRepository::class);
        $repository->method('getFirstId')->willReturn($accessUrl?->getId() ?? 0);
        $repository->method('find')->willReturn($accessUrl);

        return new AccessUrlHelper($repository, new RequestStack());
    }

    /**
     * @param int[] $managedUrlIds
     */
    private function createConnection(bool $unrestricted, array $managedUrlIds): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn($unrestricted ? 1 : false);
        $connection->method('fetchFirstColumn')->willReturn($managedUrlIds);

        return $connection;
    }
}
