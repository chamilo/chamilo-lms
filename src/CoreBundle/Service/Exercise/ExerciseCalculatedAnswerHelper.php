<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionEditor;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;
use Webit\Util\EvalMath\EvalMath;

use const JSON_THROW_ON_ERROR;

/**
 * Keeps the original calculation definition alongside the generated answer.
 * The visible text/result stays before the first @@ and the formula stays last,
 * as expected by existing readers and course copy/backup code.
 */
final class ExerciseCalculatedAnswerHelper
{
    private const PREFIX = 'calculated-v1:';
    public const MAX_VARIATIONS = 1000;

    /**
     * @return list<string>
     */
    public static function tokens(string $text): array
    {
        preg_match_all('/\[[^\]]+\]/', $text, $matches);

        return array_values(array_unique($matches[0] ?? []));
    }

    /**
     * @return array<string, array{low: string, high: string}>
     */
    public static function ranges(ExerciseQuestionEditor $data): array
    {
        $submitted = [];
        foreach ($data->calculatedRanges as $range) {
            if (\is_array($range) && \is_string($range['token'] ?? null)) {
                $submitted[$range['token']] = $range;
            }
        }
        $ranges = [];
        foreach (self::tokens($data->calculatedText) as $token) {
            $range = $submitted[$token] ?? [];
            $rawLow = $range['low'] ?? '1';
            $rawHigh = $range['high'] ?? '20';
            if (!\is_scalar($rawLow) || !\is_scalar($rawHigh) || \is_bool($rawLow) || \is_bool($rawHigh)) {
                throw new BadRequestHttpException('Invalid calculated variable limits.');
            }
            $low = trim((string) $rawLow);
            $high = trim((string) $rawHigh);
            foreach ([$low, $high] as $limit) {
                if (!preg_match('/^[+-]?(?:\d+(?:\.\d{0,2})?|\.\d{1,2})$/D', $limit) || abs((float) $limit) > 1000000000) {
                    throw new BadRequestHttpException('Calculated variable limits must be finite numbers with up to two decimal places.');
                }
            }
            if ((float) $low > (float) $high) {
                throw new BadRequestHttpException('The lowest value must not exceed the highest value.');
            }
            $ranges[$token] = ['low' => $low, 'high' => $high];
        }

        return $ranges;
    }

    /**
     * @return array{text: string, formula: string, ranges: array, stored: bool}
     */
    public static function definition(string $answer): array
    {
        $parts = explode('@@', $answer, 3);
        $text = (string) preg_replace('/\s*\[[^\[\]]*\]\s*$/', '', $parts[0]);
        $formula = $parts[1] ?? '';
        if (3 === \count($parts) && str_starts_with($parts[1], self::PREFIX)) {
            $decoded = base64_decode(substr($parts[1], \strlen(self::PREFIX)), true);
            $metadata = false !== $decoded ? json_decode($decoded, true) : null;
            $formula = $parts[2];
            if (\is_array($metadata) && \is_string($metadata['text'] ?? null) && \is_array($metadata['ranges'] ?? null)) {
                $ranges = [];
                foreach (self::tokens($metadata['text']) as $token) {
                    $range = $metadata['ranges'][$token] ?? null;
                    if (!\is_array($range) || !\is_string($range['low'] ?? null) || !\is_string($range['high'] ?? null)) {
                        return ['text' => $text, 'formula' => $formula, 'ranges' => [], 'stored' => false];
                    }
                    $ranges[$token] = $range;
                }

                return ['text' => $metadata['text'], 'formula' => $formula, 'ranges' => $ranges, 'stored' => true];
            }
        }

        return ['text' => $text, 'formula' => $formula, 'ranges' => [], 'stored' => false];
    }

    public static function matchesDefinition(ExerciseQuestionEditor $data, array $answers): bool
    {
        $first = $answers[0] ?? null;
        if (!$first instanceof CQuizAnswer || \count($answers) !== $data->calculatedVariations || !str_contains($first->getAnswer(), '@@')) {
            return false;
        }
        $definition = self::definition($first->getAnswer());
        if (trim($data->calculatedText) !== trim($definition['text']) || trim($data->calculatedFormula) !== trim($definition['formula'])) {
            return false;
        }

        return !$definition['stored'] || self::ranges($data) === $definition['ranges'];
    }

    /**
     * @return list<string>
     */
    public static function generate(ExerciseQuestionEditor $data): array
    {
        $text = $data->calculatedText;
        $formula = trim($data->calculatedFormula);
        $ranges = self::ranges($data);
        if ([] === $ranges || '' === $formula || array_diff(self::tokens($formula), array_keys($ranges))) {
            throw new BadRequestHttpException('Define the calculated variables in the text and use only those variables in the formula.');
        }
        if (str_contains($text, '@@') || str_contains($formula, '@@') || $data->calculatedVariations < 1 || $data->calculatedVariations > self::MAX_VARIATIONS) {
            throw new BadRequestHttpException('Invalid calculated question definition or number of variations (maximum 1000).');
        }
        $metadata = self::PREFIX.base64_encode(json_encode(['text' => $text, 'ranges' => $ranges], JSON_THROW_ON_ERROR));
        // MySQL TEXT is limited to 65535 bytes. Fail before creating/removing any answers.
        if (\strlen($text) + \strlen($metadata) + \strlen($formula) > 60000) {
            throw new BadRequestHttpException('The calculated question definition is too long.');
        }
        $answers = [];
        for ($position = 0; $position < $data->calculatedVariations; ++$position) {
            $result = null;
            // Some random combinations (e.g. a zero denominator) are invalid.
            for ($try = 0; $try < 20; ++$try) {
                $values = [];
                $formulaValues = [];
                foreach ($ranges as $token => $range) {
                    $decimal = str_contains($range['low'], '.') || str_contains($range['high'], '.');
                    $factor = $decimal ? 100 : 1;
                    $value = random_int((int) round((float) $range['low'] * $factor), (int) round((float) $range['high'] * $factor)) / $factor;
                    $values[$token] = self::formatNumber((float) $value);
                    $formulaValues[$token] = '('.$values[$token].')';
                }

                try {
                    $evaluated = (new EvalMath())->evaluate(strtr($formula, $formulaValues));
                    if (is_numeric($evaluated) && is_finite((float) $evaluated)) {
                        $result = self::formatNumber((float) $evaluated);

                        break;
                    }
                } catch (Throwable) {
                    // Try a different random combination, never cast an error to zero.
                }
            }
            if (null === $result) {
                throw new BadRequestHttpException('The formula cannot produce a valid finite result with the configured variable ranges.');
            }
            $encoded = strtr($text, $values).' ['.$result.']@@'.$metadata.'@@'.$formula;
            if (\strlen($encoded) > 65535) {
                throw new BadRequestHttpException('The calculated question definition is too long.');
            }
            $answers[] = $encoded;
        }

        return $answers;
    }

    private static function formatNumber(float $value): string
    {
        $formatted = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        return '-0' === $formatted ? '0' : $formatted;
    }

    /**
     * Select exactly the same ordered variation when displaying and saving an attempt.
     */
    public static function selectVariationId(EntityManagerInterface $em, CQuizQuestion $question, array $ids, ?TrackEExercise $attempt): ?int
    {
        if ([] === $ids) {
            return null;
        }
        if (null === $attempt?->getExeId() || null === $question->getIid()) {
            return $ids[0];
        }
        $row = $em->getRepository(TrackEAttempt::class)->findOneBy(['trackExercise' => $attempt, 'questionId' => $question->getIid()]);
        if ($row instanceof TrackEAttempt) {
            [$savedId] = self::parseStudentAnswer($row->getAnswer());
            if (\in_array($savedId, $ids, true)) {
                return $savedId;
            }
        }
        $index = abs((int) crc32(\sprintf('%d:%d:%d', $attempt->getExeId(), $question->getIid(), \count($ids)))) % \count($ids);

        return $ids[$index];
    }

    /**
     * @return array{0: int, 1: string}
     */
    private static function parseStudentAnswer(string $value): array
    {
        $parts = explode(':', $value, 2);

        return 2 === \count($parts) ? [ctype_digit($parts[0]) ? (int) $parts[0] : 0, trim($parts[1])] : [0, trim($value)];
    }

    /**
     * @param array<int, CQuizAnswer> $answers keyed by answer IID
     */
    public static function score(CQuizQuestion $question, array $answers, string $value): float
    {
        [$id, $student] = self::parseStudentAnswer($value);
        // Keep the legacy unprefixed format, but never fall back for an invalid explicit ID.
        $answer = str_contains($value, ':') ? ($id > 0 ? ($answers[$id] ?? null) : null) : reset($answers);
        if (!$answer instanceof CQuizAnswer) {
            return 0.0;
        }
        $text = explode('@@', $answer->getAnswer(), 2)[0];
        if (1 !== preg_match('/\[([^\[\]]*)\]\s*$/', $text, $matches)) {
            return 0.0;
        }
        $expected = self::normalizeNumber(trim($matches[1]));
        $submitted = self::normalizeNumber($student);

        return null !== $expected && $expected === $submitted ? (float) $question->getPonderation() : 0.0;
    }

    /**
     * Exact decimal comparison, without floating-point coercion or an invented tolerance.
     */
    private static function normalizeNumber(string $value): ?string
    {
        if (!preg_match('/^([+-]?)(\d*)(?:\.(\d*))?$/D', $value, $parts) || !preg_match('/\d/', $value)) {
            return null;
        }
        $integer = ltrim($parts[2], '0');
        $decimal = rtrim($parts[3] ?? '', '0');
        $sign = '-' === $parts[1] && ('' !== $integer || '' !== $decimal) ? '-' : '';

        return $sign.('' === $integer ? '0' : $integer).('' !== $decimal ? '.'.$decimal : '');
    }
}
