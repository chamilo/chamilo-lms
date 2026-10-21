<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Twig;

use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Twig\Environment;

/**
 * Regression test for the skill description advisory.
 *
 * The skill list template turns autoescape off for its whole body, so the name, the short code,
 * the description and the tags must be escaped one by one: a skill described with markup must
 * not run it in the browser of the administrator who opens "Manage skills".
 */
final class SkillListEscapingTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testSkillTextIsEscapedInTheSkillList(): void
    {
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        $html = $twig->render('default/skill/list.html.twig', [
            'skills' => [[
                'id' => 1,
                'asset' => '',
                'status' => 1,
                'title' => '<img src=x onerror=alert(1)>',
                'short_code' => '<i>code</i>',
                'description' => '<svg onload=alert(2)>',
            ]],
            'tags' => [['id' => 1, 'tag' => '<b onmouseover=alert(3)>tag</b>']],
            'token' => 'token',
            'current_tag_id' => 0,
        ]);

        $this->assertStringContainsString('&lt;svg onload=alert(2)&gt;', $html, 'The description is shown, escaped.');
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html, 'The name is shown, escaped.');
        $this->assertStringNotContainsString('<img src=x onerror', $html);
        $this->assertStringNotContainsString('<svg onload', $html);
        $this->assertStringNotContainsString('<i>code</i>', $html);
        $this->assertStringNotContainsString('<b onmouseover', $html);
    }
}
