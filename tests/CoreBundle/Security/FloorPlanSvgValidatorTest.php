<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Security;

use Chamilo\CoreBundle\Security\FloorPlanSvgValidator;
use PHPUnit\Framework\TestCase;

final class FloorPlanSvgValidatorTest extends TestCase
{
    public function testStaticVectorFloorPlanIsAccepted(): void
    {
        self::assertTrue((new FloorPlanSvgValidator())->isSafe(
            '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="300" viewBox="0 0 600 300">'.
            '<rect x="0" y="0" width="600" height="300" fill="#fff"/>'.
            '<path d="M 10 10 L 200 10 L 200 200 Z" stroke="black" fill="none"/>'.
            '<text x="20" y="20">Room A</text></svg>'
        ));
    }

    /**
     * @dataProvider unsafeSvg
     */
    public function testActiveSvgIsRejected(string $body): void
    {
        self::assertFalse((new FloorPlanSvgValidator())->isSafe($body));
    }

    public static function unsafeSvg(): iterable
    {
        $open = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="300">';

        yield 'script element' => [$open.'<script>alert(1)</script></svg>'];

        yield 'event handler' => [$open.'<rect width="20" height="20" onload="alert(1)"/></svg>'];

        yield 'external image' => [$open.'<image href="https://evil.example/test.svg"/></svg>'];

        yield 'foreign object' => [$open.'<foreignObject><body xmlns="http://www.w3.org/1999/xhtml"/></foreignObject></svg>'];

        yield 'inline style' => [$open.'<rect width="20" height="20" style="fill:red"/></svg>'];

        yield 'remote paint' => [$open.'<rect width="20" height="20" fill="url(https://evil.example/x)"/></svg>'];

        yield 'doctype' => ['<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]>'.$open.'<text>&x;</text></svg>'];

        yield 'processing instruction' => ['<?xml-stylesheet href="https://evil.example/a.css"?>'.$open.'</svg>'];

        yield 'missing dimensions' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 300"></svg>'];
    }
}
