<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

final readonly class UpdateMigrationPolicy
{
    private const string CURRENT_MIGRATION_SERIES = 'V300';
    private const string MIGRATION_PATH_ROOT = 'src/CoreBundle/Migrations/Schema/';
    private const string MIGRATION_CLASS_ROOT = 'Chamilo\CoreBundle\Migrations\Schema\\';

    public function getMigrationSeries(): string
    {
        return self::CURRENT_MIGRATION_SERIES;
    }

    public function getMigrationPathPrefix(): string
    {
        return self::MIGRATION_PATH_ROOT.self::CURRENT_MIGRATION_SERIES.'/';
    }

    public function getMigrationClassPrefix(): string
    {
        return self::MIGRATION_CLASS_ROOT.self::CURRENT_MIGRATION_SERIES.'\\';
    }

    public function isSupportedMigrationPath(string $relativePath): bool
    {
        $pattern = '/^'.preg_quote($this->getMigrationPathPrefix(), '/').'Version[0-9]+\.php$/';

        return 1 === preg_match($pattern, $relativePath);
    }

    public function isMigrationPath(string $relativePath): bool
    {
        return 1 === preg_match('/^src\/CoreBundle\/Migrations\/Schema\/V[0-9]+\/Version[0-9]+\.php$/', $relativePath);
    }

    public function isSupportedMigrationClass(string $migrationClass): bool
    {
        return str_starts_with($migrationClass, $this->getMigrationClassPrefix());
    }
}
