<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

final class SkillLevelNamesTest extends TestCase
{
    public function testLevelListKeepsCustomNamesAndEscapesDescriptions(): void
    {
        $html = $this->twig()->render('skills/skill_level.html.twig', [
            'tab' => 'levels',
            'list' => [[
                'id' => 10,
                'title' => 'Or',
                'shortTitle' => 'OR',
                'description' => '<img src=x onerror=alert(1)>',
                'profile' => null,
            ]],
        ]);

        $this->assertMatchesRegularExpression('/>\s*Or\s*</', $html);
        $this->assertDoesNotMatchRegularExpression('/>\s*Ou\s*</', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    public function testProfileListKeepsCustomLevelNames(): void
    {
        $html = $this->twig()->render('skills/skill_profile.html.twig', [
            'tab' => 'profiles',
            'list' => [[
                'id' => 1,
                'title' => 'A custom profile',
                'levels' => [[
                    'id' => 10,
                    'title' => 'Or',
                    'description' => '<script>alert(1)</script>',
                ]],
            ]],
        ]);

        $this->assertMatchesRegularExpression('/>\s*Or\s*</', $html);
        $this->assertDoesNotMatchRegularExpression('/>\s*Ou\s*</', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    private function twig(): Environment
    {
        $loader = new FilesystemLoader(\dirname(__DIR__, 3).'/public/main/template/default');
        $twig = new Environment($loader, ['autoescape' => 'html']);
        $twig->addFilter(new TwigFilter('trans', static fn (string $value): string => 'Or' === $value ? 'Ou' : $value));
        $twig->addFilter(new TwigFilter('mdi_icon', static fn (...$args): string => ''));

        return $twig;
    }
}
