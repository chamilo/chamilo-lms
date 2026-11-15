<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Finder\Finder;

/**
 * In 1.11 the Chamilo Studio plugin (adv_oel_tools_teachdoc) kept its images in its public
 * editor/img_cache/ folder, and its pages link them relatively ("img_cache/<project>/<file>").
 * CStudio keeps them in the plugins filesystem and serves them through img-cache.php, so
 * the copied pages showed no image in the editor and its image library was empty.
 */
final class Version20261113170000 extends AbstractMigrationChamilo
{
    private const string TABLE = 'plugin_oel_tools_teachdoc';
    private const array CONTENT_COLUMNS = ['base_html', 'base_css', 'gpscomps', 'gpsstyle'];
    private const string LEGACY_IMG_CACHE = '/plugin/adv_oel_tools_teachdoc/editor/img_cache';
    private const int CONTENT_BATCH_SIZE = 100;

    public function getDescription(): string
    {
        return 'Copy the Chamilo Studio images of 1.11 to the plugins filesystem and point the pages to them';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(self::TABLE)) {
            return;
        }

        // 1.11 created them as TEXT: the img-cache.php links are longer and overflow 64 KB
        $table = $schema->getTable(self::TABLE);
        foreach (self::CONTENT_COLUMNS as $column) {
            if ($table->hasColumn($column)) {
                $this->addSql('ALTER TABLE '.self::TABLE.' MODIFY '.$column.' LONGTEXT NOT NULL');
            }
        }
    }

    public function postUp(Schema $schema): void
    {
        if (!$schema->hasTable(self::TABLE)) {
            return;
        }

        // The content is rewritten after the columns were enlarged by up()
        $this->copyLegacyImages();
        $this->rewriteLinks();
    }

    public function down(Schema $schema): void {}

    private function copyLegacyImages(): void
    {
        $sourceDir = $this->getUpdateRootPath().self::LEGACY_IMG_CACHE;

        if (!is_dir($sourceDir)) {
            $this->write('No 1.11 Chamilo Studio images found in '.$sourceDir);

            return;
        }

        $filesystem = $this->container->get('oneup_flysystem.plugins_filesystem');

        // Only the project folders: the root holds the 1.11 scripts (getfiles.php, index.html)
        $finder = (new Finder())->files()->in($sourceDir)->depth('>= 1')->notName('*.php');

        foreach ($finder as $file) {
            $targetPath = 'CStudio/editor/img_cache/'.str_replace('\\', '/', $file->getRelativePathname());

            if ($filesystem->fileExists($targetPath)) {
                continue;
            }

            $stream = fopen($file->getRealPath(), 'rb');

            if (false === $stream) {
                $this->write('Unable to read '.$file->getRealPath());

                continue;
            }

            try {
                $filesystem->writeStream($targetPath, $stream);
            } finally {
                fclose($stream);
            }
        }
    }

    private function rewriteLinks(): void
    {
        $urls = [];
        foreach ($this->connection->fetchAllAssociative('SELECT id, url FROM access_url') as $row) {
            $urls[(int) $row['id']] = rtrim((string) $row['url'], '/');
        }

        $columns = implode(', ', self::CONTENT_COLUMNS);
        $lastId = 0;

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                \sprintf('SELECT id, id_url, %s FROM %s WHERE id > :lastId ORDER BY id LIMIT %d', $columns, self::TABLE, self::CONTENT_BATCH_SIZE),
                ['lastId' => $lastId]
            );

            if ([] === $rows) {
                break;
            }

            foreach ($rows as $row) {
                $lastId = (int) $row['id'];

                // img-cache.php requires a session on the portal that serves the editor
                $url = $urls[(int) $row['id_url']] ?? $urls[1] ?? '';
                $changes = [];
                foreach (self::CONTENT_COLUMNS as $column) {
                    $content = (string) $row[$column];
                    $updated = $this->rewriteContent($content, $url);

                    if ($updated !== $content) {
                        $changes[$column] = $updated;
                    }
                }

                if ([] !== $changes) {
                    $this->connection->update(self::TABLE, $changes, ['id' => $lastId]);
                }
            }
        }
    }

    private function rewriteContent(string $content, string $url): string
    {
        // Absolute links to the 1.11 image folder, on whatever domain the platform had then
        $content = preg_replace(
            '~https?://[^\s"\'<>)]*'.preg_quote(self::LEGACY_IMG_CACHE, '~').'/~i',
            'img_cache/',
            $content
        ) ?? $content;

        // Relative links ("img_cache/...", "../img_cache/...") of the editor, HTML, CSS and JSON alike
        $content = preg_replace(
            '~(?<![A-Za-z0-9_./-])(?:\.\.?/)*img_cache/~',
            $url.'/plugin/CStudio/img-cache.php?path=',
            $content
        ) ?? $content;

        // Static files of the plugin itself (editor/img/classique/...), at the same place in CStudio
        return preg_replace(
            '~(?:https?://[^\s"\'<>)/]+)?/plugin/adv_oel_tools_teachdoc/~i',
            $url.'/plugin/CStudio/',
            $content
        ) ?? $content;
    }
}
