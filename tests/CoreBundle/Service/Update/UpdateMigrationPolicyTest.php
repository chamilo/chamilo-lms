<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\UpdateMigrationPolicy;
use PHPUnit\Framework\TestCase;

final class UpdateMigrationPolicyTest extends TestCase
{
    public function testCurrentMigrationSeriesUsesV300(): void
    {
        $policy = new UpdateMigrationPolicy();

        self::assertSame('V300', $policy->getMigrationSeries());
        self::assertSame('src/CoreBundle/Migrations/Schema/V300/', $policy->getMigrationPathPrefix());
        self::assertSame('Chamilo\CoreBundle\Migrations\Schema\V300\\', $policy->getMigrationClassPrefix());
    }

    public function testCurrentV300MigrationPathAndClassAreAccepted(): void
    {
        $policy = new UpdateMigrationPolicy();

        self::assertTrue($policy->isSupportedMigrationPath('src/CoreBundle/Migrations/Schema/V300/Version20260808132848.php'));
        self::assertTrue($policy->isSupportedMigrationClass('Chamilo\CoreBundle\Migrations\Schema\V300\Version20260808132848'));
    }

    public function testLegacyAndArbitraryMigrationPathsAreRejected(): void
    {
        $policy = new UpdateMigrationPolicy();

        self::assertFalse($policy->isSupportedMigrationPath('src/CoreBundle/Migrations/Schema/V210/Version20260731124500.php'));
        self::assertFalse($policy->isSupportedMigrationPath('src/CoreBundle/Migrations/Schema/V300/Unexpected.php'));
        self::assertFalse($policy->isSupportedMigrationPath('src/OtherBundle/Migrations/Schema/V300/Version20260808132848.php'));
        self::assertTrue($policy->isMigrationPath('src/CoreBundle/Migrations/Schema/V210/Version20260731124500.php'));
        self::assertFalse($policy->isMigrationPath('src/OtherBundle/Migrations/Schema/V300/Version20260808132848.php'));
    }

    public function testLegacyMigrationClassIsRejected(): void
    {
        $policy = new UpdateMigrationPolicy();

        self::assertFalse($policy->isSupportedMigrationClass('Chamilo\CoreBundle\Migrations\Schema\V210\Version20260731124500'));
        self::assertFalse($policy->isSupportedMigrationClass('Other\Migrations\Schema\V300\Version20260808132848'));
    }
}
