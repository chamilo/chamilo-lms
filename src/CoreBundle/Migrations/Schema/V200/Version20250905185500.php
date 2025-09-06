<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class Version20250905185500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return "Purge all legacy plugin entries from `settings` (category 'plugins'/'Plugins') and provide best-effort down().";
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;

        // Delete everything under category 'plugins' (case variants included).
        $deleted = $conn->executeStatement(
            "DELETE FROM settings WHERE category IN ('plugins','Plugins')"
        );

        $this->write("Removed {$deleted} rows from settings with category IN ('plugins','Plugins').");
    }

    public function down(Schema $schema): void
    {
        $conn = $this->connection;

        // Fetch all plugin + url configs
        $rows = $conn->fetchAllAssociative(
            'SELECT p.title AS plugin_title,
                    p.installed AS plugin_installed,
                    r.url_id AS access_url,
                    r.active AS rel_active,
                    r.configuration AS cfg
               FROM plugin p
          LEFT JOIN access_url_rel_plugin r ON r.plugin_id = p.id
           ORDER BY p.title, r.url_id'
        );

        $recreated = 0;

        foreach ($rows as $row) {
            $title      = (string) $row['plugin_title'];
            $installed  = (int) ($row['plugin_installed'] ?? 0);
            $accessUrl  = $row['access_url'] !== null ? (int) $row['access_url'] : 1; // default URL=1 if missing
            $cfgRaw     = $row['cfg'];

            // Restore 'status' row (legacy semantics)
            $conn->insert('settings', [
                'variable'              => 'status',
                'subkey'                => $title,
                'type'                  => 'setting',
                'category'              => 'plugins',
                'selected_value'        => $installed ? 'installed' : 'uninstalled',
                'title'                 => $title,
                'comment'               => '',
                'access_url_changeable' => 1,
                'access_url_locked'     => 0,
                'access_url'            => $accessUrl,
            ]);
            $recreated++;

            // Restore configuration rows from JSON
            $cfg = [];
            if (is_string($cfgRaw) && $cfgRaw !== '') {
                $decoded = json_decode($cfgRaw, true);
                if (is_array($decoded)) {
                    $cfg = $decoded;
                }
            } elseif (is_array($cfgRaw)) {
                $cfg = $cfgRaw;
            }

            if (!empty($cfg)) {
                foreach ($cfg as $key => $val) {
                    $variable      = $title . '_' . (string) $key;
                    $selectedValue =
                        is_bool($val) ? ($val ? 'true' : 'false') :
                            (is_scalar($val) ? (string) $val : json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                    $conn->insert('settings', [
                        'variable'              => $variable,
                        'subkey'                => $title,
                        'type'                  => 'setting',
                        'category'              => 'plugins',
                        'selected_value'        => $selectedValue,
                        'title'                 => $title,
                        'comment'               => '',
                        'access_url_changeable' => 1,
                        'access_url_locked'     => 0,
                        'access_url'            => $accessUrl,
                    ]);
                    $recreated++;
                }
            }
        }

        $this->write("Down(): recreated {$recreated} legacy 'plugins' setting rows in `settings`. Non-setting rows were not restored.");
    }
}
