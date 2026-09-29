<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Session;

use Chamilo\CoreBundle\Session\ConfigurableSessionHandler;
use PHPUnit\Framework\TestCase;
use SessionHandlerInterface;

final class ConfigurableSessionHandlerTest extends TestCase
{
    public function testUsesNativeHandlerWhenDatabaseStorageIsDisabled(): void
    {
        $nativeHandler = $this->createMock(SessionHandlerInterface::class);
        $databaseHandler = $this->createMock(SessionHandlerInterface::class);

        $nativeHandler
            ->expects($this->once())
            ->method('read')
            ->with('session-id')
            ->willReturn('native-session')
        ;
        $databaseHandler
            ->expects($this->never())
            ->method('read')
        ;

        $handler = new ConfigurableSessionHandler($nativeHandler, $databaseHandler, false);

        $this->assertSame('native-session', $handler->read('session-id'));
    }

    public function testUsesDatabaseHandlerWhenDatabaseStorageIsEnabled(): void
    {
        $nativeHandler = $this->createMock(SessionHandlerInterface::class);
        $databaseHandler = $this->createMock(SessionHandlerInterface::class);

        $nativeHandler
            ->expects($this->never())
            ->method('read')
        ;
        $databaseHandler
            ->expects($this->once())
            ->method('read')
            ->with('session-id')
            ->willReturn('database-session')
        ;

        $handler = new ConfigurableSessionHandler($nativeHandler, $databaseHandler, true);

        $this->assertSame('database-session', $handler->read('session-id'));
    }
}
