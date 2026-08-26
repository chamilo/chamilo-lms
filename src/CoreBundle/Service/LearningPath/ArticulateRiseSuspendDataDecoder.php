<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use JsonException;

final class ArticulateRiseSuspendDataDecoder
{
    public const string CONTENT_MAKER = 'Articulate Rise';

    private const int MAX_COMPRESSED_CODES = 200000;
    private const int MAX_DECOMPRESSED_LENGTH = 4194304;
    private const int MAX_SUSPEND_DATA_LENGTH = 1048576;

    public function extractProgress(string $suspendData): ?int
    {
        $decoded = $this->decodeSuspendData($suspendData);
        if (!\is_array($decoded)) {
            return null;
        }

        return $this->extractProgressFromDecoded($decoded);
    }

    public function isRiseSuspendData(string $suspendData): bool
    {
        $decoded = $this->decodeSuspendData($suspendData);
        if (!\is_array($decoded)) {
            return false;
        }

        $coursePackageVersion = $decoded['cpv'] ?? null;
        $progress = $decoded['progress'] ?? null;

        return \is_string($coursePackageVersion)
            && '' !== trim($coursePackageVersion)
            && \is_array($progress)
            && $this->extractProgressFromDecoded($decoded) !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeSuspendData(string $suspendData): ?array
    {
        $suspendData = trim($suspendData);
        if ('' === $suspendData || self::MAX_SUSPEND_DATA_LENGTH < \strlen($suspendData)) {
            return null;
        }

        try {
            $wrapper = json_decode($suspendData, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!\is_array($wrapper)) {
            return null;
        }

        return $this->decodePayload($wrapper);
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private function extractProgressFromDecoded(array $decoded): ?int
    {
        $progress = $decoded['progress'] ?? null;
        if (!\is_array($progress)) {
            return null;
        }

        $percentage = $progress['p'] ?? $progress['percentComplete'] ?? null;
        if (!\is_int($percentage) && !\is_float($percentage) && !\is_string($percentage)) {
            return null;
        }
        if (!is_numeric($percentage)) {
            return null;
        }

        $percentage = (float) $percentage;
        if (0.0 > $percentage || 100.0 < $percentage) {
            return null;
        }

        return (int) round($percentage);
    }

    /**
     * @param array<string, mixed> $wrapper
     *
     * @return array<string, mixed>|null
     */
    private function decodePayload(array $wrapper): ?array
    {
        if (!array_key_exists('d', $wrapper)) {
            return $wrapper;
        }

        $payload = $wrapper['d'];
        if (\is_string($payload)) {
            $json = $payload;
        } elseif (\is_array($payload)) {
            $json = $this->decompressLzw($payload);
            if (null === $json) {
                return null;
            }
        } else {
            return null;
        }

        if (self::MAX_DECOMPRESSED_LENGTH < \strlen($json)) {
            return null;
        }

        try {
            $decoded = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return \is_array($decoded) ? $decoded : null;
    }

    /**
     * Articulate Rise uses the classic JavaScript LZW implementation bundled with its exports.
     *
     * @param array<int, mixed> $compressed
     */
    private function decompressLzw(array $compressed): ?string
    {
        $count = \count($compressed);
        if (0 === $count || self::MAX_COMPRESSED_CODES < $count) {
            return null;
        }

        $dictionary = [];
        for ($index = 0; $index < 256; ++$index) {
            $dictionary[$index] = chr($index);
        }

        $firstCode = $compressed[0] ?? null;
        if (!\is_int($firstCode) || 0 > $firstCode || 255 < $firstCode) {
            return null;
        }

        $phrase = $dictionary[$firstCode];
        $result = $phrase;
        $dictionarySize = 256;

        for ($index = 1; $index < $count; ++$index) {
            $code = $compressed[$index];
            if (!\is_int($code) || 0 > $code) {
                return null;
            }

            if (isset($dictionary[$code])) {
                $entry = $dictionary[$code];
            } elseif ($code === $dictionarySize) {
                $entry = $phrase.$phrase[0];
            } else {
                return null;
            }

            $result .= $entry;
            if (self::MAX_DECOMPRESSED_LENGTH < \strlen($result)) {
                return null;
            }

            $dictionary[$dictionarySize] = $phrase.$entry[0];
            ++$dictionarySize;
            $phrase = $entry;
        }

        return $result;
    }
}
