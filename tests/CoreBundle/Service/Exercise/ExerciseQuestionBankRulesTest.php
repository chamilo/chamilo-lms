<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Service\Exercise\ExerciseQuestionBankMetrics;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQuestionBankMutationGuard;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use const INF;

final class ExerciseQuestionBankRulesTest extends TestCase
{
    public function testCompletedAttemptClassification(): void
    {
        self::assertSame('correctAttempts', ExerciseQuestionBankMetrics::classifyAttempt(12, 5.0, 5.0, ''));
        self::assertSame('incorrectAttempts', ExerciseQuestionBankMetrics::classifyAttempt(12, 2.5, 5.0, ''));
        self::assertSame('incorrectAttempts', ExerciseQuestionBankMetrics::classifyAttempt(12, -1.0, 5.0, ''));
        self::assertNull(ExerciseQuestionBankMetrics::classifyAttempt(12, 0.0, 5.0, '11,12,13'));
        self::assertNull(ExerciseQuestionBankMetrics::classifyAttempt(12, 0.0, 0.0, ''));
    }

    public function testMetricRankingUsesWholeBankAndStableTies(): void
    {
        $metrics = [1 => ['occurrences' => 2], 2 => ['occurrences' => 8], 3 => ['occurrences' => 8], 4 => ['occurrences' => 0]];
        self::assertSame([2, 3, 1, 4], ExerciseQuestionBankMetrics::sortIds([4, 3, 2, 1], $metrics, 'occurrences', 'DESC'));
        self::assertSame([4, 1, 2, 3], ExerciseQuestionBankMetrics::sortIds([4, 3, 2, 1], $metrics, 'occurrences', 'ASC'));
    }

    public function testScopeMustMatchExactly(): void
    {
        $link = ['linkId' => 1, 'courseId' => 2, 'sessionId' => null, 'groupId' => null, 'userGroupId' => null, 'userId' => null];
        self::assertTrue(ExerciseQuestionBankMutationGuard::matchesContext($link, 2, null));
        self::assertFalse(ExerciseQuestionBankMutationGuard::matchesContext($link, 2, 3));
        self::assertFalse(ExerciseQuestionBankMutationGuard::matchesContext($link, 3, null));
        self::assertFalse(ExerciseQuestionBankMutationGuard::matchesContext(array_replace($link, ['groupId' => 1]), 2, null));
        self::assertFalse(ExerciseQuestionBankMutationGuard::matchesContext(array_replace($link, ['linkId' => null]), 2, null));
    }

    public function testScoreValidation(): void
    {
        ExerciseQuestionBankMutationGuard::validateScores(2.0, -0.25);
        ExerciseQuestionBankMutationGuard::validateScores(0.01, 0.0);
        foreach ([[0.0, 0.0], [2.0, 0.1], [2.001, -1.0], [INF, 0.0], [2.0, -10000.0], [null, 0.0]] as [$correct, $incorrect]) {
            try {
                ExerciseQuestionBankMutationGuard::validateScores($correct, $incorrect);
                self::fail('Invalid scores were accepted.');
            } catch (BadRequestHttpException $exception) {
                self::assertSame(400, $exception->getStatusCode());
            }
        }
    }

    public function testOnlyOneCorrectAnswerIsAllowed(): void
    {
        $correct = (new CQuizAnswer())->setCorrect(1);
        $wrong = (new CQuizAnswer())->setCorrect(0);
        self::assertTrue(ExerciseQuestionBankMutationGuard::hasValidSingleAnswer([$correct, $wrong]));
        self::assertFalse(ExerciseQuestionBankMutationGuard::hasValidSingleAnswer([$correct, $correct, $wrong]));
        self::assertFalse(ExerciseQuestionBankMutationGuard::hasValidSingleAnswer([$correct]));
        self::assertFalse(ExerciseQuestionBankMutationGuard::hasValidSingleAnswer([$wrong, $wrong]));
        self::assertFalse(ExerciseQuestionBankMutationGuard::hasValidSingleAnswer([$correct, new CQuizAnswer()]));
    }
}
