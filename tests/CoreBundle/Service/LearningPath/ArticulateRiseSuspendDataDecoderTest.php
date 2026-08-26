<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Service\LearningPath\ArticulateRiseSuspendDataDecoder;
use PHPUnit\Framework\TestCase;

final class ArticulateRiseSuspendDataDecoderTest extends TestCase
{
    private ArticulateRiseSuspendDataDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = new ArticulateRiseSuspendDataDecoder();
    }

    public function testItExtractsProgressFromRiseV2SuspendData(): void
    {
        $suspendData = '{"v":2,"d":[123,34,112,114,111,103,114,101,115,115,34,58,256,112,266,54,52,44,34,108,263,115,111,110,265,267,274,276,278,49,266,256,99,266,49,125,291,273,99,112,118,266,34,116,263,116,34,125],"cpv":"test"}';

        self::assertSame(64, $this->decoder->extractProgress($suspendData));
        self::assertTrue($this->decoder->isRiseSuspendData($suspendData));
    }

    public function testItExtractsProgressFromRiseV3SuspendData(): void
    {
        $suspendData = '{"v":3,"d":[123,34,112,114,111,103,114,101,115,115,34,58,256,112,266,50,51,44,34,108,263,115,111,110,265,267,274,276,278,49,266,256,99,266,49,125,291,273,99,112,118,266,34,116,263,116,34,125]}';

        self::assertSame(23, $this->decoder->extractProgress($suspendData));
        self::assertTrue($this->decoder->isRiseSuspendData($suspendData));
    }

    public function testItAcceptsUncompressedRiseProgressPayloads(): void
    {
        $suspendData = '{"progress":{"percentComplete":47,"lessons":{}}}';

        self::assertSame(47, $this->decoder->extractProgress($suspendData));
        self::assertFalse($this->decoder->isRiseSuspendData($suspendData));
    }

    public function testItFailsSafelyForUnknownSuspendData(): void
    {
        self::assertNull($this->decoder->extractProgress('not-json'));
        self::assertNull($this->decoder->extractProgress('{"v":3,"d":[999]}'));
        self::assertNull($this->decoder->extractProgress('{"progress":{"p":150}}'));
        self::assertFalse($this->decoder->isRiseSuspendData('{"progress":{"p":23},"cpv":""}'));
        self::assertFalse($this->decoder->isRiseSuspendData('{"progress":{"p":23}}')); 
    }
}
