<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use JsonException;
use RuntimeException;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;

use const JSON_THROW_ON_ERROR;

final readonly class UpdatePackageRemovalManifest
{
    public const string FILE_NAME = 'UPDATE-METADATA.json';

    private const int FORMAT_VERSION = 1;
    private const array PROTECTED_EXACT_PATHS = [
        '.env',
        '.env.local',
        'app/config/configuration.php',
        self::FILE_NAME,
    ];
    private const array PROTECTED_PREFIXES = [
        '.git',
        'node_modules',
        'vendor',
        'var',
        'public/courses',
        'public/upload',
    ];

    private TranslatorInterface $translator;

    public function __construct(?TranslatorInterface $translator = null)
    {
        $this->translator = $translator ?? new IdentityTranslator();
    }

    /**
     * @return array{
     *     present: bool,
     *     file: string,
     *     format: ?int,
     *     sha256: ?string,
     *     remove: string[]
     * }
     */
    public function load(string $applicationPath): array
    {
        $metadataPath = rtrim($applicationPath, '/').'/'.self::FILE_NAME;

        if (!is_file($metadataPath)) {
            return [
                'present' => false,
                'file' => self::FILE_NAME,
                'format' => null,
                'sha256' => null,
                'remove' => [],
            ];
        }

        if (!is_readable($metadataPath)) {
            throw new RuntimeException(\sprintf($this->translator->trans('Update package metadata is not readable: %s'), $metadataPath));
        }

        $content = file_get_contents($metadataPath);

        if (false === $content) {
            throw new RuntimeException(\sprintf($this->translator->trans('Unable to read update package metadata: %s'), $metadataPath));
        }

        try {
            $metadata = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(\sprintf($this->translator->trans('Update package metadata is not valid JSON: %s'), $exception->getMessage()), 0, $exception);
        }

        if (!\is_array($metadata)) {
            throw new RuntimeException($this->translator->trans('Update package metadata must be a JSON object.'));
        }

        $format = $metadata['format'] ?? null;

        if (self::FORMAT_VERSION !== $format) {
            throw new RuntimeException(\sprintf($this->translator->trans('Update package metadata format must be %d.'), self::FORMAT_VERSION));
        }

        $remove = $metadata['remove'] ?? null;

        if (!\is_array($remove)) {
            throw new RuntimeException($this->translator->trans('Update package metadata field "remove" must be an array.'));
        }

        $removalPaths = [];

        foreach ($remove as $value) {
            if (!\is_string($value)) {
                throw new RuntimeException($this->translator->trans('Update package removal paths must be strings.'));
            }

            $relativePath = $this->normalizeRemovalPath($value);

            if ($this->isProtectedPath($relativePath)) {
                throw new RuntimeException(\sprintf($this->translator->trans('Update package metadata cannot remove protected path: %s'), $relativePath));
            }

            $stagedPath = rtrim($applicationPath, '/').'/'.$relativePath;

            if (file_exists($stagedPath) || is_link($stagedPath)) {
                throw new RuntimeException(\sprintf($this->translator->trans('Update package cannot include and remove the same path: %s'), $relativePath));
            }

            $removalPaths[$relativePath] = true;
        }

        $sha256 = hash_file('sha256', $metadataPath);

        if (false === $sha256) {
            throw new RuntimeException($this->translator->trans('Unable to calculate update package metadata sha256.'));
        }

        return [
            'present' => true,
            'file' => self::FILE_NAME,
            'format' => self::FORMAT_VERSION,
            'sha256' => strtolower($sha256),
            'remove' => array_keys($removalPaths),
        ];
    }

    private function normalizeRemovalPath(string $relativePath): string
    {
        $relativePath = trim($relativePath);
        $relativePath = str_replace('\\', '/', $relativePath);

        if ('' === $relativePath) {
            throw new RuntimeException($this->translator->trans('Update package removal path cannot be empty.'));
        }

        if (str_contains($relativePath, "\0")) {
            throw new RuntimeException($this->translator->trans('Update package removal path contains a null byte.'));
        }

        if (str_starts_with($relativePath, '/') || str_starts_with($relativePath, '//') || 1 === preg_match('/^[A-Za-z]:\//', $relativePath)) {
            throw new RuntimeException(\sprintf($this->translator->trans('Update package removal path must be relative: %s'), $relativePath));
        }

        $segments = explode('/', $relativePath);

        foreach ($segments as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw new RuntimeException(\sprintf($this->translator->trans('Update package removal path contains an unsafe segment: %s'), $relativePath));
            }
        }

        return implode('/', $segments);
    }

    private function isProtectedPath(string $relativePath): bool
    {
        if (\in_array($relativePath, self::PROTECTED_EXACT_PATHS, true)) {
            return true;
        }

        foreach (self::PROTECTED_PREFIXES as $prefix) {
            if ($relativePath === $prefix || str_starts_with($relativePath, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
