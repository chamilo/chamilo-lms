<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename persisted HRM settings without changing values or access-URL attributes.
 * A pre-existing canonical row always wins and the old row is left untouched
 * in that case, so no configured value is destroyed by a collision.
 */
final class Version20261019130000 extends AbstractMigrationChamilo
{
    private const array SETTINGS = [
        'drh_autosubscribe' => 'hrm_autosubscribe',
        'drh_can_access_all_session_content' => 'hrm_can_access_all_session_content',
        'drh_allow_access_to_all_students' => 'hrm_allow_access_to_all_students',
    ];

    public function getDescription(): string
    {
        return 'Rename legacy DRH settings to HRM while preserving per-URL values and metadata.';
    }

    public function up(Schema $schema): void
    {
        $this->renameSettings($schema, self::SETTINGS);
    }

    public function down(Schema $schema): void
    {
        $this->renameSettings($schema, array_flip(self::SETTINGS));
    }

    /**
     * Reuses each existing row (and its ID), preserving selected_value, subkey,
     * access_url, access_url_changeable, access_url_locked and metadata.
     * MariaDB/MySQL NULL-safe equality also handles global (NULL) access URLs.
     * Collision rows are retained for manual review rather than overwritten.
     *
     * @param array<string, string> $variables
     */
    private function renameSettings(Schema $schema, array $variables): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        foreach ($variables as $previous => $canonical) {
            $this->addSql(
                'UPDATE settings AS legacy '
                .'LEFT JOIN settings AS existing ON existing.variable = ? '
                .'AND existing.access_url <=> legacy.access_url '
                .'AND existing.subkey <=> legacy.subkey '
                .'SET legacy.variable = ? '
                .'WHERE legacy.variable = ? AND existing.id IS NULL',
                [$canonical, $canonical, $previous],
            );
        }
    }
}
