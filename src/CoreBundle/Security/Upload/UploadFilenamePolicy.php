<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Upload;

use Chamilo\CoreBundle\Settings\SettingsManager;

final class UploadFilenamePolicy
{
    public function __construct(
        private readonly SettingsManager $settingsManager
    ) {}

    /**
     * @return array{allowed:bool, filename:string, reason?:string}
     */
    public function filter(string $originalFilename): array
    {
        $filename = $this->sanitizeFilename($originalFilename);
        $filename = $this->disableDangerousFile($filename);

        return $this->filterExtension($filename);
    }

    private function sanitizeFilename(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        // Avoid path tricks
        $name = str_replace(['\\', '/', "\0"], '-', $name);

        if ('' === $name) {
            $name = 'file';
        }

        return $name;
    }

    private function disableDangerousFile(string $filename): string
    {
        $filename = $this->php2phps($filename);

        return $this->htaccess2txt($filename);
    }

    private function php2phps(string $fileName): string
    {
        return (string) preg_replace('/\.(phar.?|php.?|phtml.?)(\.){0,1}.*$/i', '.phps', $fileName);
    }

    private function htaccess2txt(string $filename): string
    {
        return str_ireplace('.htaccess', 'htaccess.txt', $filename);
    }

    /**
     * @return array{allowed:bool, filename:string, reason?:string}
     */
    private function filterExtension(string $filename): array
    {
        if (str_ends_with($filename, '/')) {
            return ['allowed' => true, 'filename' => $filename];
        }

        $listType = strtolower((string) $this->settingsManager->getSetting('document.upload_extensions_list_type', true));
        $skip = 'true' === strtolower((string) $this->settingsManager->getSetting('document.upload_extensions_skip', true));

        $ext = $this->getExtension($filename);

        // Allow empty extension in both modes
        if ('' === $ext) {
            return ['allowed' => true, 'filename' => $filename];
        }

        if ('whitelist' === $listType) {
            $whitelist = $this->splitList((string) $this->settingsManager->getSetting('document.upload_extensions_whitelist', true));
            $whitelist = $this->mergeWithBuiltInSafeExtensions($whitelist);

            if (!\in_array($ext, $whitelist, true)) {
                if ($skip) {
                    return ['allowed' => false, 'filename' => $filename, 'reason' => 'Extension not in whitelist'];
                }

                return [
                    'allowed' => true,
                    'filename' => $this->replaceExtension($filename),
                    'reason' => 'Extension replaced (whitelist)',
                ];
            }

            return ['allowed' => true, 'filename' => $filename];
        }

        // Default: blacklist mode
        $blacklist = $this->splitList((string) $this->settingsManager->getSetting('document.upload_extensions_blacklist', true));
        if (\in_array($ext, $blacklist, true)) {
            if ($skip) {
                return ['allowed' => false, 'filename' => $filename, 'reason' => 'Extension in blacklist'];
            }

            return [
                'allowed' => true,
                'filename' => $this->replaceExtension($filename),
                'reason' => 'Extension replaced (blacklist)',
            ];
        }

        return ['allowed' => true, 'filename' => $filename];
    }

    private function getExtension(string $filename): string
    {
        $pos = strrpos($filename, '.');
        if (false === $pos) {
            return '';
        }

        return strtolower(substr($filename, $pos + 1));
    }

    /**
     * @return string[]
     */
    private function splitList(string $raw): array
    {
        $raw = strtolower(trim($raw));
        if ('' === $raw) {
            return [];
        }

        $parts = array_map('trim', explode(';', $raw));
        $parts = array_filter($parts, static fn ($v) => '' !== $v);

        return array_values(array_unique($parts));
    }

    /**
     * @param string[] $extensions
     *
     * @return string[]
     */
    private function mergeWithBuiltInSafeExtensions(array $extensions): array
    {
        $builtInSafeExtensions = [
            'png',
            'jpg',
            'jpeg',
            'webp',
            'gif',
            'mp4',
            'webm',
            'h5p',
            'zip',
        ];

        return array_values(array_unique(array_merge($extensions, $builtInSafeExtensions)));
    }

    private function replaceExtension(string $filename): string
    {
        $replaceBy = (string) $this->settingsManager->getSetting('document.upload_extensions_replace_by', true);
        $replaceBy = str_replace('.', '', $replaceBy);
        $replaceBy = preg_replace('/[^a-zA-Z0-9_\-]/', '', $replaceBy) ?? $replaceBy;

        if ('' === $replaceBy) {
            $replaceBy = 'txt';
        }

        $newExt = 'REPLACED_'.$replaceBy;

        $pos = strrpos($filename, '.');
        if (false === $pos) {
            return $filename.'.'.$newExt;
        }

        return substr($filename, 0, $pos + 1).$newExt;
    }
}
