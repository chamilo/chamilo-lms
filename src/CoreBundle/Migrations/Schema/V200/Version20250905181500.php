<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class Version20250905181500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Move legacy tool_enable from configuration JSON to active column and clean configuration.';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;

        $rows = $conn->fetchAllAssociative('SELECT id, active, configuration FROM access_url_rel_plugin');

        foreach ($rows as $row) {
            $id     = (int) $row['id'];
            $active = isset($row['active']) ? (int) $row['active'] : 0;
            $cfgRaw = $row['configuration'];
            $cfg    = [];

            // configuration may be a JSON string (common) or an array (driver dependent)
            if (is_string($cfgRaw) && $cfgRaw !== '') {
                $decoded = json_decode($cfgRaw, true);
                if (is_array($decoded)) {
                    $cfg = $decoded;
                }
            } elseif (is_array($cfgRaw)) {
                $cfg = $cfgRaw;
            }

            if (!array_key_exists('tool_enable', $cfg)) {
                continue; // nothing to migrate for this row
            }

            $val       = $cfg['tool_enable'];
            $newActive = null;

            // Normalize accepted legacy values
            if ($val === true || $val === 1 || $val === '1') {
                $newActive = 1;
            } elseif ($val === false || $val === 0 || $val === '0') {
                $newActive = 0;
            } elseif (is_string($val)) {
                $v = strtolower(trim($val));
                if (in_array($v, ['true', 'on', 'yes', 'y'], true)) {
                    $newActive = 1;
                } elseif (in_array($v, ['false', 'off', 'no', 'n'], true)) {
                    $newActive = 0;
                }
            }

            // Remove the legacy key from configuration
            unset($cfg['tool_enable']);
            $payload = json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // Update row: set active if we could infer it; otherwise just clean configuration
            if ($newActive !== null) {
                $conn->update(
                    'access_url_rel_plugin',
                    ['active' => $newActive, 'configuration' => $payload],
                    ['id' => $id],
                    ['configuration' => \PDO::PARAM_STR]
                );
                $this->write("Row {$id}: moved tool_enable => active={$newActive}; configuration cleaned.");
            } else {
                $conn->update(
                    'access_url_rel_plugin',
                    ['configuration' => $payload],
                    ['id' => $id],
                    ['configuration' => \PDO::PARAM_STR]
                );
                $this->write("Row {$id}: tool_enable removed from configuration; active left unchanged={$active}.");
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Re-introduce configuration.tool_enable from active as 'true'/'false'
        $conn = $this->connection;

        $rows = $conn->fetchAllAssociative('SELECT id, active, configuration FROM access_url_rel_plugin');

        foreach ($rows as $row) {
            $id     = (int) $row['id'];
            $active = isset($row['active']) ? (int) $row['active'] : 0;
            $cfgRaw = $row['configuration'];
            $cfg    = [];

            if (is_string($cfgRaw) && $cfgRaw !== '') {
                $decoded = json_decode($cfgRaw, true);
                if (is_array($decoded)) {
                    $cfg = $decoded;
                }
            } elseif (is_array($cfgRaw)) {
                $cfg = $cfgRaw;
            }

            // Do not overwrite if it already exists (unlikely after up())
            if (!array_key_exists('tool_enable', $cfg)) {
                $cfg['tool_enable'] = $active ? 'true' : 'false';
            }

            $payload = json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $conn->update(
                'access_url_rel_plugin',
                ['configuration' => $payload],
                ['id' => $id],
                ['configuration' => \PDO::PARAM_STR]
            );

            $this->write("Row {$id}: restored configuration.tool_enable='".($active ? 'true' : 'false')."' from active.");
        }
    }
}
