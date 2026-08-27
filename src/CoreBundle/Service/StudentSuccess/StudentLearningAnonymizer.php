<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\StudentSuccess;

use Chamilo\CoreBundle\Entity\User;

/**
 * Removes known learner identifiers from data before it can be sent to an
 * external AI provider. The collector intentionally keeps free text local;
 * this service is the privacy boundary between local data and AI-bound data.
 */
final class StudentLearningAnonymizer
{
    private const string REDACTION = '[redacted]';
    private const int MAX_TEACHER_PROMPT_LENGTH = 6000;

    /**
     * @param array<string, mixed> $data
     *
     * @return array{data: array<string, mixed>, redactions: int}
     */
    public function sanitize(array $data, User $student): array
    {
        $redactions = 0;
        $identifiers = $this->buildIdentifiers($student);
        $sanitized = $this->sanitizeValue($data, $identifiers, $redactions);

        return [
            'data' => \is_array($sanitized) ? $sanitized : [],
            'redactions' => $redactions,
        ];
    }

    /**
     * @return array{text: string, redactions: int}
     */
    public function sanitizeTeacherPrompt(string $prompt, User $student): array
    {
        $prompt = mb_substr(trim($prompt), 0, self::MAX_TEACHER_PROMPT_LENGTH);
        $redactions = 0;
        $sanitized = $this->sanitizeString($prompt, $this->buildIdentifiers($student), $redactions);

        return [
            'text' => $sanitized,
            'redactions' => $redactions,
        ];
    }

    /**
     * @return string[]
     */
    private function buildIdentifiers(User $student): array
    {
        $firstname = trim((string) $student->getFirstname());
        $lastname = trim((string) $student->getLastname());
        $username = trim($student->getUsername());
        $email = trim($student->getEmail());
        $phone = trim((string) $student->getPhone());
        $officialCode = trim((string) $student->getOfficialCode());

        $values = [
            $email,
            $phone,
            $officialCode,
            $username,
            trim($firstname.' '.$lastname),
            trim($lastname.' '.$firstname),
            $firstname,
            $lastname,
        ];

        $identifiers = [];
        foreach ($values as $value) {
            $value = trim($value);
            if ('' === $value) {
                continue;
            }

            // Avoid replacing very short common words. E-mail and phone-like
            // values are retained regardless of length by the checks below.
            $isEmail = str_contains($value, '@');
            $digitCount = preg_match_all('/\d/u', $value);
            $isPhoneLike = false !== $digitCount && $digitCount >= 7;
            if (!$isEmail && !$isPhoneLike && mb_strlen($value) < 3) {
                continue;
            }

            $identifiers[mb_strtolower($value)] = $value;
        }

        // Replace longer values first so a full name is redacted before its
        // individual parts.
        $identifiers = array_values($identifiers);
        usort(
            $identifiers,
            static fn (string $left, string $right): int => mb_strlen($right) <=> mb_strlen($left),
        );

        return $identifiers;
    }

    /**
     * @param string[] $identifiers
     */
    private function sanitizeValue(mixed $value, array $identifiers, int &$redactions): mixed
    {
        if (\is_string($value)) {
            return $this->sanitizeString($value, $identifiers, $redactions);
        }

        if (!\is_array($value)) {
            return $value;
        }

        $sanitized = [];
        foreach ($value as $key => $item) {
            $sanitized[$key] = $this->sanitizeValue($item, $identifiers, $redactions);
        }

        return $sanitized;
    }

    /**
     * @param string[] $identifiers
     */
    private function sanitizeString(string $text, array $identifiers, int &$redactions): string
    {
        foreach ($identifiers as $identifier) {
            $pattern = '/'.preg_quote($identifier, '/').'/iu';
            $text = preg_replace_callback(
                $pattern,
                static function () use (&$redactions): string {
                    ++$redactions;

                    return self::REDACTION;
                },
                $text,
            ) ?? $text;
        }

        // Free-text fields may contain an e-mail address that is not the
        // learner's profile e-mail. Remove it before the external boundary.
        $text = preg_replace_callback(
            '/(?<![\p{L}\p{N}._%+\-])[\p{L}\p{N}._%+\-]+@[\p{L}\p{N}.\-]+\.[\p{L}]{2,}(?![\p{L}\p{N}._%+\-])/iu',
            static function () use (&$redactions): string {
                ++$redactions;

                return self::REDACTION;
            },
            $text,
        ) ?? $text;

        return $text;
    }
}
