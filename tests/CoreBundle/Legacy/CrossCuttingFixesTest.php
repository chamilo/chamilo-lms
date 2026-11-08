<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Exercise;
use ExerciseLib;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use TestCategory;

/**
 * Regression tests for bugs that cut across several legacy libraries (tag "cc2_").
 */
final class CrossCuttingFixesTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        Container::setSession(new Session(new MockArraySessionStorage()));
        $container->get(SettingsManager::class)->updateSetting('exercise.allow_quiz_results_page_config', 'true');
    }

    /**
     * The Vue ExerciseConfigurationProcessor stores the results page flags as 'on' / '' (legacy mirror keys);
     * the legacy form stores '1'. Both must hide the piece of the page, and '' / '0' must not.
     *
     * @return array<string, array{mixed, bool}>
     */
    public static function flagProvider(): array
    {
        return [
            'on (stored by Vue)' => ['on', true],
            'ON' => ['ON', true],
            '1' => ['1', true],
            'int 1' => [1, true],
            'true' => [true, true],
            'true string' => ['true', true],
            'empty (not hidden)' => ['', false],
            '0' => ['0', false],
            'off' => ['off', false],
            'false' => [false, false],
            'null' => [null, false],
        ];
    }

    /**
     * @dataProvider flagProvider
     */
    public function testApiIsFlagEnabled(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, api_is_flag_enabled($value));
    }

    /**
     * @dataProvider flagProvider
     */
    public function testHideExpectedAnswerAndQuestionScoreFollowTheStoredFlag(mixed $value, bool $hidden): void
    {
        $e = new Exercise();
        $e->updateTitle('cc2_quiz');
        $e->results_disabled = RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS;
        $e->setPageResultConfiguration([
            'hide_expected_answer' => $value,
            'hide_question_score' => $value,
        ]);

        $this->assertSame(!$hidden, $e->showExpectedChoiceColumn());
        $ribbon = $e->getQuestionRibbon('success', 'Right', '1/1', ['used' => 1, 'missing' => 1]);
        $this->assertSame($hidden, '' === $ribbon);
    }

    public function testHideTotalScoreAndCategoryTableAcceptTheVueValue(): void
    {
        $e = new Exercise();
        $e->updateTitle('cc2_quiz');
        $e->setPageResultConfiguration(['hide_total_score' => 'on', 'hide_category_table' => 'on']);

        $this->assertSame('', ExerciseLib::getTotalScoreRibbon($e, 1, 2));
        $this->assertSame('', TestCategory::get_stats_table_by_attempt($e, ['cc2' => 1]));
    }
}
