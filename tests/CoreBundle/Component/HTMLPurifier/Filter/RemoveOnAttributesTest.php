<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Component\HTMLPurifier\Filter;

use Chamilo\CoreBundle\Component\HTMLPurifier\Filter\RemoveOnAttributes;
use PHPUnit\Framework\TestCase;

/**
 * Event-handler attributes must not survive malformed HTML separators, while
 * legitimate attribute values and plain text must remain unchanged.
 */
final class RemoveOnAttributesTest extends TestCase
{
    public function testMalformedEventAttributesAreRemovedWithoutRequiringWhitespace(): void
    {
        $cases = [
            '<img src="x" onerror="test">' => '<img src="x">',
            '<img src="x"/onerror="test">' => '<img src="x">',
            '<img src="x"onerror="test">' => '<img src="x">',
            '<img src="x"/ONERROR = "test">' => '<img src="x">',
            '<input disabled/onfocus="test">' => '<input disabled>',
            '<img/onerror="test">' => '<img>',
            '<img src="x"//onerror="test">' => '<img src="x">',
            "<img src=\"x\" on\0error=\"test\">" => '<img src="x">',
        ];

        foreach ($cases as $html => $expected) {
            self::assertSame($expected, RemoveOnAttributes::filter($html), 'Failed to sanitize: '.$html);
        }
    }

    public function testLegitimateValuesAndPlainTextArePreserved(): void
    {
        $cases = [
            '<a href="/online=1">safe</a>',
            '<a href=https://example.com/online=1>safe</a>',
            '<img src=x/onerror=x>',
            'plain text online=value and /onerror=test',
            '<div data-onclick="keep">safe</div>',
            '<div title="hello onerror=x">safe</div>',
            '<div title="hello/onerror=x">safe</div>',
        ];

        foreach ($cases as $html) {
            self::assertSame($html, RemoveOnAttributes::filter($html), 'Legitimate content changed: '.$html);
        }
    }
}
