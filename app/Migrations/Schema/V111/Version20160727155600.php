<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Chamilo\CoreBundle\Entity\BranchSync;

/**
 * Class Version20160727155600
 * Add an initial branch_sync
 * @package Application\Migrations\Schema\V111
 */
class Version20160727155600 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = "SELECT COUNT(id) as count FROM branch_sync";
        $result = $this->connection->executeQuery($sql)->fetch();
        $count = $result['count'];

        if (!$count) {
            $unique = sha1(uniqid());
            $sql = "INSERT INTO branch_sync (branch_name, unique_id, access_url_id) VALUES ('localhost', '$unique', '1')";
            $this->addSql($sql);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $em = $this->getEntityManager();

        $branchSync = $em
            ->getRepository('ChamiloCoreBundle:BranchSync')
            ->findOneBy([
                'branchName' => 'localhost',
                'accessUrlId' => 1
            ]);

        $em->remove($branchSync);
        $em->flush();
    }
}