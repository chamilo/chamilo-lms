<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Security;

use Chamilo\CoreBundle\Security\CourseToolSvgSanitizer;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class CourseToolSvgSanitizerTest extends TestCase
{
    public function testYannicksInkscapeIconIsAcceptedAndNormalized(): void
    {
        $original = file_get_contents(\dirname(__DIR__, 2).'/Fixtures/CourseTool/chameleon.svg');
        self::assertIsString($original);

        $sanitized = (new CourseToolSvgSanitizer())->sanitize($original);

        self::assertNotNull($sanitized);
        self::assertStringContainsString('fill="#606060"', $sanitized);
        self::assertStringNotContainsString('<metadata', $sanitized);
        self::assertStringNotContainsString('inkscape:', $sanitized);
        self::assertStringNotContainsString('sodipodi:', $sanitized);
        self::assertStringNotContainsString('style=', $sanitized);

        $document = new DOMDocument();
        self::assertTrue($document->loadXML($sanitized));
        self::assertSame(7, $document->getElementsByTagName('path')->length);
        self::assertSame($sanitized, (new CourseToolSvgSanitizer())->sanitize($sanitized));
    }

    public function testSimpleSvgIsAccepted(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24">'
            .'<rect width="24" height="24" style="fill:#ccc;stroke:#111;stroke-width:2"/></svg>';
        $sanitized = (new CourseToolSvgSanitizer())->sanitize($svg);

        self::assertNotNull($sanitized);
        self::assertStringContainsString('fill="#ccc"', $sanitized);
        self::assertStringContainsString('stroke-width="2"', $sanitized);
    }

    public function testViewBoxOnlyIconGetsNumericDimensions(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
            .'<path d="M 0 0 L 20 20 Z"/></svg>';
        $sanitized = (new CourseToolSvgSanitizer())->sanitize($svg);

        self::assertNotNull($sanitized);
        self::assertStringContainsString('width="24"', $sanitized);
        self::assertStringContainsString('height="24"', $sanitized);
    }

    /**
     * @dataProvider unsafeSvg
     */
    public function testUnsafeOrUnsupportedSvgIsRejected(string $svg): void
    {
        self::assertNull((new CourseToolSvgSanitizer())->sanitize($svg));
    }

    public static function unsafeSvg(): iterable
    {
        $open = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24">';

        yield 'script' => [$open.'<script>alert(1)</script></svg>'];

        yield 'event handler' => [$open.'<path d="M0 0" onload="alert(1)"/></svg>'];

        yield 'remote image' => [$open.'<image href="https://example.org/icon.svg"/></svg>'];

        yield 'foreign object' => [$open.'<foreignObject><body xmlns="http://www.w3.org/1999/xhtml"/></foreignObject></svg>'];

        yield 'stylesheet' => [$open.'<style>path { fill: red; }</style></svg>'];

        yield 'css remote resource' => [$open.'<path d="M0 0" style="fill:url(https://example.org/x.svg)"/></svg>'];

        yield 'css custom property' => [$open.'<path d="M0 0" style="fill:var(--color)"/></svg>'];

        yield 'css expression' => [$open.'<path d="M0 0" style="fill:expression(alert(1))"/></svg>'];

        yield 'xlink reference' => ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24" height="24"><path d="M0 0" xlink:href="https://example.org"/></svg>'];

        yield 'doctype' => ['<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]>'.$open.'<text>&x;</text></svg>'];

        yield 'xml stylesheet' => ['<?xml-stylesheet href="https://example.org/a.css"?>'.$open.'</svg>'];

        yield 'external namespace element' => [$open.'<x:script xmlns:x="https://example.org"/></svg>'];

        yield 'no dimensions or viewbox' => ['<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0"/></svg>'];

        yield 'too many bytes' => [$open.str_repeat(' ', 262144).'</svg>'];

        yield 'invalid dimensions' => ['<svg xmlns="http://www.w3.org/2000/svg" width="0" height="24"/>'];

        yield 'invalid xml' => [$open.'<path></svg>'];
    }
}
