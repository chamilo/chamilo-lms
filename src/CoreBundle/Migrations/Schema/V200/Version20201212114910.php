<?php

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201212114910 extends AbstractMigrationChamilo
{
    public function getDescription() : string
    {
        return 'Migrate content';
    }

    public function up(Schema $schema) : void
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = 'SELECT * FROM course';
        $result = $connection->executeQuery($sql);
        $data = $result->fetchAllAssociative();
        foreach ($data as $course) {
            $courseId = $course['id'];
            $sql = "SELECT * FROM c_document WHERE c_id = $courseId";
            $result = $connection->executeQuery($sql);
            $documents = $result->fetchAllAssociative();
            foreach ($documents as $document) {
                //$repo
            }

            //$this->addSql($sql);
        }

    }
}
