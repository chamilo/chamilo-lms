<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\ApiResource\Mobile;

use Chamilo\CoreBundle\ApiResource\Mobile\MobilePushInstallation;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints\Choice;

final class MobilePushInstallationTest extends TestCase
{
    public function testPlatformContractAcceptsAndroidAndIos(): void
    {
        self::assertSame('android', MobilePushInstallation::PLATFORM_ANDROID);
        self::assertSame('ios', MobilePushInstallation::PLATFORM_IOS);

        $property = new ReflectionProperty(MobilePushInstallation::class, 'platform');
        $attributes = $property->getAttributes(Choice::class);

        self::assertCount(1, $attributes);

        /** @var Choice $choice */
        $choice = $attributes[0]->newInstance();

        self::assertSame(
            [MobilePushInstallation::PLATFORM_ANDROID, MobilePushInstallation::PLATFORM_IOS],
            $choice->choices
        );
    }
}
