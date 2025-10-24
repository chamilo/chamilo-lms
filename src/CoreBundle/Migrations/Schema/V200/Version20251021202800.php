<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251021202800 extends AbstractMigrationChamilo
{
    private const DEBUG = true;

    public function getDescription(): string
    {
        return 'Update titles/comments for show_tabs and show_tabs_per_role and ensure they live under category "display".';
    }

    private const UPDATES = [
        'show_tabs' => [
            'title' => 'Main menu entries',
            'comment' => 'Check the entrie you want to see appear in the main menu',
        ],
        'show_tabs_per_role' => [
            'title' => 'Main menu entries per role',
            'comment' => 'Define header tabs visibility per role.',
        ],
    ];

    public function up(Schema $schema): void
    {
        $conn = $this->connection;

        foreach (self::UPDATES as $var => $meta) {
            $affected = $conn->executeStatement(
                "UPDATE settings
                    SET title = ?, comment = ?, category = 'display'
                  WHERE variable = ?",
                [$meta['title'], $meta['comment'], $var]
            );

            $this->dbg(\sprintf(
                "[UP] variable='%s' -> title='%s', comment='%s', category='display' (rows=%d)",
                $var,
                $meta['title'],
                $meta['comment'],
                $affected
            ));

            if (0 === $affected) {
                $this->dbg(\sprintf("[WARN] No rows found for variable='%s' to update.", $var));
            }
        }
    }

    private function dbg(string $msg): void
    {
        if (self::DEBUG) {
            error_log('[MIG][show_tabs_titles] '.$msg);
        }
    }
}
