<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const DIRECTORY_SEPARATOR;
use const PHP_URL_HOST;

final class Version20260118180100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Fix migrated tracking header/footer extra content settings: replace legacy .txt path with file content.';
    }

    public function up(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative(
            "
            SELECT id, access_url, variable, selected_value
            FROM settings
            WHERE category = 'tracking'
              AND variable IN ('header_extra_content', 'footer_extra_content')
            "
        );

        if (!$rows) {
            error_log('[MIGRATION] No tracking extra content settings found. Nothing to do.');

            return;
        }

        $updateRoot = rtrim((string) $this->getUpdateRootPath(), DIRECTORY_SEPARATOR);
        error_log('[MIGRATION] Tracking extra content fix started.');
        error_log('[MIGRATION] Update root path: '.$updateRoot);

        $accessUrlRepo = $this->entityManager->getRepository(AccessUrl::class);

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $accessUrlId = (int) ($row['access_url'] ?? 0);
            $variable = (string) ($row['variable'] ?? '');
            $value = trim((string) ($row['selected_value'] ?? ''));

            if ($id <= 0 || '' === $variable || '' === $value) {
                continue;
            }

            // If it already looks like HTML, keep it.
            if (str_contains($value, '<')) {
                error_log("[MIGRATION] settings#$id ($variable): already HTML, skipped.");

                continue;
            }

            // Only fix legacy file paths (typically "app/home/*.txt").
            if (!$this->looksLikeLegacyTxtPath($value)) {
                error_log("[MIGRATION] settings#$id ($variable): not a legacy .txt path, skipped.");

                continue;
            }

            $host = '';
            if ($accessUrlId > 0) {
                $accessUrl = $accessUrlRepo->find($accessUrlId);
                if ($accessUrl && method_exists($accessUrl, 'getUrl')) {
                    $host = (string) (parse_url((string) $accessUrl->getUrl(), PHP_URL_HOST) ?: '');
                }
            }

            $fileContent = $this->readLegacyExtraContentFile($updateRoot, $value, $host);

            if (null === $fileContent) {
                error_log("[MIGRATION] settings#$id ($variable): file not found for '$value'. Clearing setting.");
                $this->updateSettingValue($id, '');

                continue;
            }

            $fileContent = trim($fileContent);

            // Be conservative: header/footer snippets are injected as raw HTML in Twig.
            if ('' === $fileContent || !str_contains($fileContent, '<')) {
                error_log("[MIGRATION] settings#$id ($variable): file content is empty or not HTML. Clearing setting.");
                $this->updateSettingValue($id, '');

                continue;
            }

            error_log("[MIGRATION] settings#$id ($variable): content loaded, updating setting.");
            $this->updateSettingValue($id, $fileContent);
        }

        error_log('[MIGRATION] Tracking extra content fix completed.');
    }

    public function down(Schema $schema): void
    {
        // Not reversible: we can't safely restore the original legacy file paths.
    }

    private function updateSettingValue(int $id, string $value): void
    {
        $this->connection->update('settings', ['selected_value' => $value], ['id' => $id]);
    }

    private function looksLikeLegacyTxtPath(string $value): bool
    {
        $v = strtolower(trim($value));

        // Typical migrated values:
        // - app/home/header_extra_content.txt
        // - app/home/footer_extra_content.txt
        return str_ends_with($v, '.txt') && str_contains($v, 'app/home');
    }

    private function readLegacyExtraContentFile(string $updateRoot, string $storedValue, string $host): ?string
    {
        $storedValue = trim($storedValue);

        $basename = basename(str_replace('\\', '/', $storedValue));
        $relative = ltrim($storedValue, '/\\');
        $relative = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relative);

        $candidates = [];

        // 1) updateRoot + stored relative path (most common)
        $candidates[] = $updateRoot.DIRECTORY_SEPARATOR.$relative;

        // 2) updateRoot/app/home/<host>/<basename> (some upgrades store files per URL)
        if ('' !== $host) {
            $candidates[] = $updateRoot.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'home'.DIRECTORY_SEPARATOR.$host.DIRECTORY_SEPARATOR.$basename;
        }

        // 3) updateRoot/app/home/<basename> (your local test case)
        $candidates[] = $updateRoot.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'home'.DIRECTORY_SEPARATOR.$basename;

        foreach ($candidates as $path) {
            if (is_file($path) && is_readable($path)) {
                error_log('[MIGRATION] Reading extra content file: '.$path);
                $content = @file_get_contents($path);
                if (false !== $content) {
                    return (string) $content;
                }
            }
        }

        return null;
    }
}
