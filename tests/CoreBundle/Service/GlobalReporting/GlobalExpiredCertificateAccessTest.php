<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\GlobalReporting;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingContext;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingSectionQueryService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class GlobalExpiredCertificateAccessTest extends TestCase
{
    public function testNonAdministratorsCannotQueryExpiredCertificates(): void
    {
        $service = (new ReflectionClass(GlobalReportingSectionQueryService::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(GlobalReportingSectionQueryService::class, 'assertSectionAccess');
        $context = $this->context(false);

        $this->expectException(AccessDeniedHttpException::class);
        $method->invoke($service, $context, 'expired-certificates');
    }

    public function testAdministratorsCanQueryExpiredCertificates(): void
    {
        $service = (new ReflectionClass(GlobalReportingSectionQueryService::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(GlobalReportingSectionQueryService::class, 'assertSectionAccess');

        $method->invoke($service, $this->context(true), 'expired-certificates');
        $this->addToAssertionCount(1);
    }

    private function context(bool $isAdministrator): GlobalReportingContext
    {
        return new GlobalReportingContext(
            $this->createMock(User::class),
            1,
            $isAdministrator,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
        );
    }
}
