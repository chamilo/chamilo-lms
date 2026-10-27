<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\State\UriVariablesResolverTrait;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionFeedbackReport;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeAnswer;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeAttemptClose;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeAttemptDelete;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeAttemptEmail;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeAttemptRecalculate;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeCorrection;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeFinish;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeResult;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeUploadAnswer;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ExerciseRuntimeIdentifierMetadataTest extends TestCase
{
    /**
     * @dataProvider multiIdentifierOperationProvider
     *
     * @param class-string $resourceClass
     * @param list<string> $expectedVariables
     */
    public function testRuntimeOperationsUseSeparateUriIdentifiers(
        string $resourceClass,
        string $operationName,
        array $expectedVariables,
        ?bool $expectedRead
    ): void {
        $resourceAttribute = (new ReflectionClass($resourceClass))->getAttributes(ApiResource::class);
        self::assertCount(1, $resourceAttribute);

        $resource = $resourceAttribute[0]->newInstance();
        $operation = null;
        foreach ($resource->getOperations() ?? [] as $candidate) {
            if ($candidate->getName() === $operationName) {
                $operation = $candidate;
                break;
            }
        }

        self::assertInstanceOf(HttpOperation::class, $operation, $operationName);
        self::assertSame($expectedRead, $operation->canRead(), $operationName);

        $uriVariables = $operation->getUriVariables();
        self::assertIsArray($uriVariables);
        self::assertSame($expectedVariables, array_keys($uriVariables), $operationName);

        foreach ($expectedVariables as $identifier) {
            $link = $uriVariables[$identifier];
            self::assertSame([$identifier], $link->getIdentifiers(), $identifier);
            self::assertFalse($link->getCompositeIdentifier(), $identifier);
            self::assertSame('integer', $link->getSchema()['type'] ?? null, $identifier);
        }

        // Resolve the actual route placeholders with API Platform's resolver (no database required).
        $resolver = new class {
            use UriVariablesResolverTrait {
                getOperationUriVariables as public resolveUriVariables;
            }
        };
        $routeVariables = array_fill_keys($expectedVariables, '123');
        self::assertSame(
            $routeVariables,
            $resolver->resolveUriVariables($operation, $routeVariables, $resourceClass),
            $operationName
        );
    }

    /**
     * @return iterable<string, array{class-string, string, list<string>, ?bool}>
     */
    public static function multiIdentifierOperationProvider(): iterable
    {
        $pair = ['exerciseId', 'attemptId'];

        yield 'Save draft answer' => [ExerciseRuntimeAnswer::class, 'post_exercise_runtime_answer', $pair, false];
        yield 'Finish attempt' => [ExerciseRuntimeFinish::class, 'post_exercise_runtime_finish', $pair, false];
        yield 'Upload answer' => [ExerciseRuntimeUploadAnswer::class, 'post_exercise_runtime_upload_answer', $pair, false];
        yield 'View result' => [ExerciseRuntimeResult::class, 'get_exercise_runtime_result', $pair, null];
        yield 'Correct attempt' => [ExerciseRuntimeCorrection::class, 'post_exercise_runtime_correction', $pair, false];
        yield 'Close attempt' => [ExerciseRuntimeAttemptClose::class, 'post_exercise_runtime_attempt_close', $pair, false];
        yield 'Delete attempt' => [ExerciseRuntimeAttemptDelete::class, 'post_exercise_runtime_attempt_delete', $pair, false];
        yield 'Email attempt' => [ExerciseRuntimeAttemptEmail::class, 'post_exercise_runtime_attempt_email', $pair, false];
        yield 'Recalculate attempt' => [ExerciseRuntimeAttemptRecalculate::class, 'post_exercise_runtime_attempt_recalculate', $pair, false];
        yield 'Report question issue' => [
            ExerciseQuestionFeedbackReport::class,
            'post_exercise_question_feedback_report',
            ['exerciseId', 'attemptId', 'questionId'],
            false,
        ];
    }
}
