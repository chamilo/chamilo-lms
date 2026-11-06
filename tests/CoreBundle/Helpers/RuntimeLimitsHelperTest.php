<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\RuntimeLimitsHelper;
use PHPUnit\Framework\TestCase;

/**
 * The limits a long task raises for itself outlive the request inside a single
 * PHP process. PHPUnit is that process: a download endpoint lowering them here
 * used to leave every test running after it with the endpoint's own budget,
 * which is how the suite started dying on "Maximum execution time of 300
 * seconds exceeded" while every test passed.
 */
final class RuntimeLimitsHelperTest extends TestCase
{
    private string $executionTime;
    private string $memoryLimit;

    protected function setUp(): void
    {
        $this->executionTime = (string) \ini_get('max_execution_time');
        $this->memoryLimit = (string) \ini_get('memory_limit');
    }

    protected function tearDown(): void
    {
        ini_set('max_execution_time', $this->executionTime);
        ini_set('memory_limit', $this->memoryLimit);
    }

    public function testALargerExecutionTimeSurvives(): void
    {
        ini_set('max_execution_time', '600');

        RuntimeLimitsHelper::raiseExecutionTime(300);

        self::assertSame('600', \ini_get('max_execution_time'));
    }

    public function testASmallerExecutionTimeIsRaised(): void
    {
        ini_set('max_execution_time', '30');

        RuntimeLimitsHelper::raiseExecutionTime(300);

        self::assertSame('300', \ini_get('max_execution_time'));
    }

    public function testAnUnlimitedExecutionTimeSurvives(): void
    {
        ini_set('max_execution_time', '0');

        RuntimeLimitsHelper::raiseExecutionTime(300);

        self::assertSame('0', \ini_get('max_execution_time'));
    }

    public function testALargerMemoryLimitSurvives(): void
    {
        ini_set('memory_limit', '4096M');

        RuntimeLimitsHelper::raiseMemoryLimit('512M');

        self::assertSame('4096M', \ini_get('memory_limit'));
    }

    public function testASmallerMemoryLimitIsRaised(): void
    {
        ini_set('memory_limit', '128M');

        RuntimeLimitsHelper::raiseMemoryLimit('512M');

        self::assertSame('512M', \ini_get('memory_limit'));
    }

    public function testAMemoryLimitIsComparedInBytesAcrossUnits(): void
    {
        ini_set('memory_limit', '1G');

        RuntimeLimitsHelper::raiseMemoryLimit('512M');

        self::assertSame('1G', \ini_get('memory_limit'));
    }

    public function testAnUnlimitedMemoryLimitSurvives(): void
    {
        ini_set('memory_limit', '-1');

        RuntimeLimitsHelper::raiseMemoryLimit('512M');

        self::assertSame('-1', \ini_get('memory_limit'));
    }
}
