<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20250709170000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add default Chamilo CSS theme';
    }

    public function up(Schema $schema): void
    {
        // Check if template already exists
        $name = 'chamilo';
        $json = '{"--color-primary-base":"46 117 163","--color-primary-gradient":"-1 86 130","--color-primary-button-text":"46 117 163","--color-primary-button-alternative-text":"255 255 255","--color-secondary-base":"243 126 47","--color-secondary-gradient":"193 81 -31","--color-secondary-button-text":"255 255 255","--color-tertiary-base":"51 51 51","--color-tertiary-gradient":"103 103 103","--color-tertiary-button-text":"51 51 51","--color-success-base":"119 170 12","--color-success-gradient":"80 128 -43","--color-success-button-text":"255 255 255","--color-info-base":"13 123 253","--color-info-gradient":"-33 83 211","--color-info-button-text":"255 255 255","--color-warning-base":"245 206 1","--color-warning-gradient":"189 151 -65","--color-warning-button-text":"0 0 0","--color-danger-base":"223 59 59","--color-danger-gradient":"180 -13 20","--color-danger-button-text":"255 255 255","--color-form-base":"46 117 163"}';
        $themeId = $this->connection->fetchOne(
            'SELECT id FROM color_theme WHERE slug = ?',
            [$name]
        );

        if ($themeId) {
            $this->write('Default Chamilo CSS theme already exists. Skipping insert.');
        } else {
            // Insert color theme
            $this->connection->executeStatement(
                'INSERT INTO color_theme (title, variables, slug, created_at, updated_at)
                 VALUES (?, ?, ?, NOW(), NOW())',
                [
                    'Chamilo',
                    $json,
                    $name,
                ]
            );

            // Get the new ID
            $themeId = $this->connection->fetchOne(
                'SELECT id FROM color_theme WHERE slug = ?',
                [$name]
            );

            if (!$themeId) {
                throw new RuntimeException('Could not retrieve the ID of the newly inserted color theme.');
            }

            // Insert relation into access_url_rel_color_theme
            $this->connection->executeStatement(
                'INSERT INTO access_url_rel_color_theme (url_id, color_theme_id, active, created_at, updated_at)
                 VALUES (?, ?, ?, NOW(), NOW())',
                [
                    1,
                    $themeId,
                    1,
                ]
            );

            $this->write('Added default Chamilo CSS theme and related access URL relation.');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM access_url_rel_color_theme
            WHERE color_theme_id IN (
                SELECT id FROM color_theme WHERE slug = 'chamilo'
            )
        ");

        $this->addSql("
            DELETE FROM color_theme WHERE slug = 'chamilo'
        ");

        $this->write('Removed default Chamilo CSS theme and related access URL relation.');
    }
}
