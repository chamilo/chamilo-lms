<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Toolbox;

use Chamilo\CoreBundle\Service\Toolbox\ToolboxJavascriptStructureValidator;
use PHPUnit\Framework\TestCase;

final class ToolboxJavascriptStructureValidatorTest extends TestCase
{
    private ToolboxJavascriptStructureValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ToolboxJavascriptStructureValidator();
    }

    public function testItAcceptsBalancedGeneratedJavascript(): void
    {
        $javascript = <<<'JS'
const words = ["cat", "dog"];
const grid = Array.from({ length: 10 }, () => Array(10).fill(""));
const message = `Found ${words.length} animals`;
// Delimiters in comments such as ((( are ignored.
window.API.LMSInitialize("");
if (grid.length === 10) {
  window.API.LMSSetValue("cmi.core.lesson_status", "incomplete");
}
JS;

        self::assertNull($this->validator->findViolation($javascript));
    }

    public function testItDetectsTheMissingClosingParenthesisSeenInGeneratedGames(): void
    {
        $javascript = <<<'JS'
const words = ["cat", "dog"];
const grid = Array.from({ length: 10 }, () => Array(10).fill("");
renderGrid(grid);
JS;

        $violation = $this->validator->findViolation($javascript);

        self::assertNotNull($violation);
        self::assertStringContainsString('JavaScript', $violation);
    }

    public function testItDetectsMismatchedDelimiters(): void
    {
        $violation = $this->validator->findViolation('if (ready] { start(); }');

        self::assertNotNull($violation);
        self::assertStringContainsString('JavaScript', $violation);
    }

    public function testItDetectsUnterminatedStringsAndComments(): void
    {
        self::assertStringContainsString(
            'JavaScript',
            (string) $this->validator->findViolation("const label = 'animals;"),
        );
        self::assertStringContainsString(
            'JavaScript',
            (string) $this->validator->findViolation('const total = 10; /* unfinished'),
        );
    }
}
