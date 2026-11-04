<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\Ticket;

use Chamilo\CoreBundle\State\Ticket\TicketDetailProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use const STUDENT;

final class TicketDetailProviderCreatorRolesTest extends TestCase
{
    public function testPersistedRolesAreUniqueAndDoNotIncludeImplicitOrContextRoles(): void
    {
        self::assertSame(['ROLE_TEACHER', 'ROLE_HR'], self::creatorRoles([
            'creatorUsername' => 'jane',
            'creatorStatus' => STUDENT,
            'creatorRoles' => [
                'ROLE_TEACHER',
                'ROLE_USER',
                'ROLE_CURRENT_COURSE_TEACHER',
                'role_teacher',
                'ROLE_HR',
            ],
        ]));
    }

    public function testLegacyStatusSuppliesRoleWhenNoRolesArePersisted(): void
    {
        self::assertSame(['ROLE_STUDENT'], self::creatorRoles([
            'creatorUsername' => 'student',
            'creatorStatus' => STUDENT,
            'creatorRoles' => [],
        ]));
    }

    public function testMissingCreatorDoesNotExposeStaleRoleData(): void
    {
        self::assertSame([], self::creatorRoles([
            'creatorUsername' => null,
            'creatorStatus' => STUDENT,
            'creatorRoles' => ['ROLE_GLOBAL_ADMIN'],
        ]));
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return list<string>
     */
    private static function creatorRoles(array $row): array
    {
        return (new ReflectionMethod(TicketDetailProvider::class, 'getCreatorRoles'))->invoke(null, $row);
    }
}
