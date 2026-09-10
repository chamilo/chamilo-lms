<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Push;

use Chamilo\CoreBundle\Push\ApnsMobilePushProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ApnsMobilePushProviderTest extends TestCase
{
    public function testSupportsOnlyIosInstallations(): void
    {
        $provider = new ApnsMobilePushProvider(
            $this->createMock(HttpClientInterface::class),
            $this->createMock(CacheInterface::class),
            $this->createMock(LoggerInterface::class),
        );

        self::assertTrue($provider->supports('ios'));
        self::assertFalse($provider->supports('android'));
    }
}
