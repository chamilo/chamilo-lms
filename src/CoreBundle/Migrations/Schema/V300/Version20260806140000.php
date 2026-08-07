<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20260806140000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add the Chamilo3 CSS theme to the list of available color themes';
    }

    public function up(Schema $schema): void
    {
        $name = 'chamilo3';
        $json = '{"--color-primary-base":"32 62 97","--color-primary-gradient":"91 123 162","--color-primary-button-text":"32 62 97","--color-primary-button-alternative-text":"255 255 255","--color-secondary-base":"242 103 34","--color-secondary-gradient":"194 57 0","--color-secondary-button-text":"255 255 255","--color-tertiary-base":"68 68 68","--color-tertiary-gradient":"134 134 134","--color-tertiary-button-text":"68 68 68","--color-success-base":"122 166 12","--color-success-gradient":"84 125 0","--color-success-button-text":"255 255 255","--color-info-base":"53 132 228","--color-info-gradient":"0 94 187","--color-info-button-text":"255 255 255","--color-warning-base":"240 163 13","--color-warning-gradient":"188 115 0","--color-warning-button-text":"0 0 0","--color-danger-base":"224 27 36","--color-danger-gradient":"182 0 0","--color-danger-button-text":"255 255 255","--color-form-base":"32 62 97"}';

        $themeId = $this->connection->fetchOne(
            'SELECT id FROM color_theme WHERE slug = ?',
            [$name]
        );

        if ($themeId) {
            $this->write('Chamilo3 CSS theme already exists. Skipping insert.');

            return;
        }

        $this->connection->executeStatement(
            'INSERT INTO color_theme (title, variables, slug, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())',
            [
                'Chamilo3',
                $json,
                $name,
            ]
        );

        $themeId = $this->connection->fetchOne(
            'SELECT id FROM color_theme WHERE slug = ?',
            [$name]
        );

        if (!$themeId) {
            throw new RuntimeException('Could not retrieve the ID of the newly inserted Chamilo3 CSS theme.');
        }

        $chamiloThemeId = $this->connection->fetchOne('SELECT id FROM color_theme WHERE slug = ?', ['chamilo']);

        // Made available on every portal without disrupting a customized choice:
        // a portal still on the shipped 'chamilo' default is upgraded to the new
        // 'chamilo3' default; a portal already on any other theme only gets
        // 'chamilo3' added as a selectable (inactive) option, never switched to.
        $accessUrlIds = $this->connection->fetchFirstColumn('SELECT id FROM access_url');

        foreach ($accessUrlIds as $accessUrlId) {
            $wasOnChamiloDefault = false;

            if ($chamiloThemeId) {
                $wasOnChamiloDefault = (bool) $this->connection->fetchOne(
                    'SELECT 1 FROM access_url_rel_color_theme WHERE url_id = ? AND color_theme_id = ? AND active = 1',
                    [$accessUrlId, $chamiloThemeId]
                );
            }

            if ($wasOnChamiloDefault) {
                $this->connection->executeStatement(
                    'UPDATE access_url_rel_color_theme SET active = 0, updated_at = NOW() WHERE url_id = ? AND color_theme_id = ?',
                    [$accessUrlId, $chamiloThemeId]
                );
            }

            $this->connection->executeStatement(
                'INSERT INTO access_url_rel_color_theme (url_id, color_theme_id, active, created_at, updated_at)
                 VALUES (?, ?, ?, NOW(), NOW())',
                [
                    $accessUrlId,
                    $themeId,
                    $wasOnChamiloDefault ? 1 : 0,
                ]
            );
        }

        $this->write('Added Chamilo3 CSS theme and related access URL relations.');
    }

    public function down(Schema $schema): void
    {
        $chamiloThemeId = $this->connection->fetchOne('SELECT id FROM color_theme WHERE slug = ?', ['chamilo']);
        $chamilo3ThemeId = $this->connection->fetchOne('SELECT id FROM color_theme WHERE slug = ?', ['chamilo3']);

        if ($chamiloThemeId && $chamilo3ThemeId) {
            // Restore 'chamilo' as active wherever this migration had switched a
            // portal from it to 'chamilo3'.
            $switchedUrlIds = $this->connection->fetchFirstColumn(
                'SELECT url_id FROM access_url_rel_color_theme WHERE color_theme_id = ? AND active = 1',
                [$chamilo3ThemeId]
            );

            foreach ($switchedUrlIds as $urlId) {
                $this->connection->executeStatement(
                    'UPDATE access_url_rel_color_theme SET active = 1, updated_at = NOW() WHERE url_id = ? AND color_theme_id = ?',
                    [$urlId, $chamiloThemeId]
                );
            }
        }

        $this->addSql("
            DELETE FROM access_url_rel_color_theme
            WHERE color_theme_id IN (
                SELECT id FROM color_theme WHERE slug = 'chamilo3'
            )
        ");

        $this->addSql("
            DELETE FROM color_theme WHERE slug = 'chamilo3'
        ");

        $this->write('Removed Chamilo3 CSS theme and related access URL relations.');
    }
}
