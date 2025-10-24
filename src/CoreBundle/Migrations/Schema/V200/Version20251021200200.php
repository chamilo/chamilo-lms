<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251021200200 extends AbstractMigrationChamilo
{
    private const DEBUG = true;

    public function getDescription(): string
    {
        return 'Consolidate show_tabs and show_tabs_per_role to category "display" and remove duplicates from "platform" or other categories.';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        $vars = ['show_tabs', 'show_tabs_per_role'];

        foreach ($vars as $var) {
            $rows = $conn->fetchAllAssociative(
                'SELECT id, access_url, category, selected_value
                   FROM settings
                  WHERE variable = ?',
                [$var]
            );

            if (empty($rows)) {
                $this->dbg("No '{$var}' entries found, skipping.");
                continue;
            }

            $byUrl = [];
            foreach ($rows as $r) {
                $urlKey = $r['access_url'] === null ? 'NULL' : (string)$r['access_url'];
                $byUrl[$urlKey][] = $r;
            }

            foreach ($byUrl as $urlKey => $group) {
                $main = null;
                foreach ($group as $r) {
                    if ($r['category'] === 'display') {
                        $main = $r;
                        break;
                    }
                }

                if (!$main) {
                    $main = $group[0];
                    $conn->update('settings', ['category' => 'display'], ['id' => $main['id']]);
                    $this->dbg("Moved '{$var}' id={$main['id']} to category 'display'.");
                }

                foreach ($group as $r) {
                    if ($r['id'] === $main['id']) {
                        continue;
                    }

                    if (empty($main['selected_value']) && !empty($r['selected_value'])) {
                        $conn->update('settings', ['selected_value' => $r['selected_value']], ['id' => $main['id']]);
                        $this->dbg("Updated '{$var}' id={$main['id']} with non-empty value from id={$r['id']}.");
                    }

                    $conn->delete('settings', ['id' => $r['id']]);
                    $this->dbg("Deleted duplicate '{$var}' id={$r['id']} (category={$r['category']}).");
                }
            }
        }

        $this->dbg('--- [END] show_tabs consolidation done ---');
    }

    public function down(Schema $schema): void
    {
        $this->dbg('No down migration: duplicates will not be restored.');
    }

    private function dbg(string $msg): void
    {
        if (self::DEBUG) {
            error_log('[MIG][show_tabs] ' . $msg);
        }
    }
}
