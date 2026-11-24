<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use RuntimeException;

use const PHP_BINARY;

/**
 * Makes sure the table of a plugin without entity mapping exists, by running its installer in a child process
 * (PluginTier3InstallSchema.php): DDL cannot run inside a test, it breaks the DAMA savepoint.
 */
trait PluginTier3SchemaTrait
{
    protected static function ensurePluginTable(string $plugin, string $table): void
    {
        $pdo = PluginTestDatabase::pdo();
        $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $statement->execute([$table]);
        if ((int) $statement->fetchColumn() > 0) {
            return;
        }

        $script = __DIR__.'/PluginTier3InstallSchema.php';
        exec('APP_ENV=test '.escapeshellarg(PHP_BINARY).' -d memory_limit=2G '.escapeshellarg($script).' '.escapeshellarg($plugin).' 2>&1', $out, $status);
        if (0 !== $status) {
            throw new RuntimeException($plugin.' schema install failed: '.implode("\n", $out));
        }
    }
}
