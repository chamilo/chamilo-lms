<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201212203600 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Validate and delete orphaned records from c_document with path starting with /shared_folder_session_';
    }

    public function up(Schema $schema): void
    {
        // Validate orphaned records
        $sql = "SELECT cd1.iid, cd1.path, cd1.c_id
                FROM c_document cd1
                LEFT JOIN c_document cd2 ON cd2.c_id = cd1.c_id AND cd2.path = SUBSTRING_INDEX(cd1.path, '/', 2)
                WHERE cd1.path LIKE '/shared_folder_session_%'
                AND cd2.iid IS NULL";
        $result = $this->connection->executeQuery($sql);
        $orphans = $result->fetchAllAssociative();

        if (!empty($orphans)) {
            // Delete orphaned records
            $deleteSql = "DELETE cd1 FROM c_document cd1
                    LEFT JOIN c_document cd2 ON cd2.c_id = cd1.c_id AND cd2.path = SUBSTRING_INDEX(cd1.path, '/', 2)
                    WHERE cd1.path LIKE '/shared_folder_session_%'
                    AND cd2.iid IS NULL";
            $this->addSql($deleteSql);
        }
    }

    public function down(Schema $schema): void
    {
        // No down migration provided
    }
}
