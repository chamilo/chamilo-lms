<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260216144100 extends AbstractMigrationChamilo
{
    private const DEBUG = false;

    public function getDescription(): string
    {
        return 'Fix title/comment for course_catalog_published (publish catalogue for anonymous users).';
    }

    private const UPDATES = [
        'course_catalog_published' => [
            'title' => 'Publish course catalogue',
            'comment' => 'Make the courses catalogue available to anonymous users (the general public) without the need to login.',
            'category' => 'catalog',
        ],
    ];

    public function up(Schema $schema): void
    {
        $conn = $this->connection;

        foreach (self::UPDATES as $var => $meta) {
            $sql = "UPDATE settings
                       SET title = ?, comment = ?, category = ?
                     WHERE variable = ?";

            $params = [
                $meta['title'],
                $meta['comment'],
                $meta['category'],
                $var,
            ];

            $affected = $conn->executeStatement($sql, $params);

            $this->dbg(\sprintf(
                "[UP] variable='%s' -> title='%s', comment='%s', category='%s' (rows=%d)",
                $var,
                $meta['title'],
                $meta['comment'],
                $meta['category'],
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
            error_log('[MIG][course_catalog_published_meta] '.$msg);
        }
    }
}
