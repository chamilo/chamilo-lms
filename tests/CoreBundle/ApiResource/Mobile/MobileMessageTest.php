<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\ApiResource\Mobile;

use Chamilo\CoreBundle\ApiResource\Mobile\MobileMessage;
use PHPUnit\Framework\TestCase;

final class MobileMessageTest extends TestCase
{
    public function testPreviewPreservesSpacingBetweenHtmlBlocks(): void
    {
        $content = '<p>Hello</p><p>World</p><div>Next<br>line</div>';

        self::assertSame(
            'Hello World Next line',
            MobileMessage::createPlainTextPreview($content)
        );
    }

    public function testPreviewCollapsesWhitespace(): void
    {
        $content = "<p>Hello   world</p>\n\n<section>Next\tline</section>";

        self::assertSame(
            'Hello world Next line',
            MobileMessage::createPlainTextPreview($content)
        );
    }
}
