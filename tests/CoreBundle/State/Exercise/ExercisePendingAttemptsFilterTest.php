<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\Exercise;

use Chamilo\CoreBundle\State\Exercise\ExercisePendingAttemptsProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

final class ExercisePendingAttemptsFilterTest extends TestCase
{
    public function testApiDefaultRemainsNotValidatedForLegacyClients(): void
    {
        $filters = $this->getFilters([]);

        self::assertSame(3, $filters['status']);
        self::assertSame(0, $filters['questionTypeId']);
    }

    /**
     * @dataProvider supportedStatuses
     */
    public function testExplicitStatusesKeepTheirOriginalMeaning(int $status): void
    {
        self::assertSame($status, $this->getFilters(['status' => $status])['status']);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function supportedStatuses(): iterable
    {
        yield 'all' => [1];

        yield 'validated' => [2];

        yield 'not validated' => [3];

        yield 'unclosed' => [4];

        yield 'ongoing' => [5];

        yield 'pending manual correction' => [6];
    }

    public function testUnknownStatusFallsBackToExistingNotValidatedFilter(): void
    {
        self::assertSame(3, $this->getFilters(['status' => 999])['status']);
    }

    public function testManualQuestionFilterRemainsIndependentFromStatus(): void
    {
        $filters = $this->getFilters(['status' => 1, 'questionTypeId' => 1]);

        self::assertSame(1, $filters['status']);
        self::assertSame(1, $filters['questionTypeId']);
    }

    public function testPendingCorrectionConditionExcludesUnfinishedAndAutomaticallyGradedAttempts(): void
    {
        $provider = (new ReflectionClass(ExercisePendingAttemptsProvider::class))->newInstanceWithoutConstructor();
        $condition = (new ReflectionMethod(ExercisePendingAttemptsProvider::class, 'pendingCorrectionPredicateSql'))
            ->invoke($provider)
        ;

        self::assertSame("te.status <> 'incomplete' AND TRIM(te.questions_to_check) <> ''", $condition);
    }

    /**
     * @param array<string, int> $query
     *
     * @return array<string, int|string>
     */
    private function getFilters(array $query): array
    {
        $provider = (new ReflectionClass(ExercisePendingAttemptsProvider::class))->newInstanceWithoutConstructor();

        return (new ReflectionMethod(ExercisePendingAttemptsProvider::class, 'getFilters'))
            ->invoke($provider, Request::create('/api/exercise/pending-attempts', 'GET', $query))
        ;
    }
}
