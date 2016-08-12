<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo,
    Doctrine\DBAL\Schema\Schema;
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
        $em = $this->getEntityManager();

        $cont = $em
            ->createQuery('SELECT COUNT(bs.id) FROM ChamiloCoreBundle:BranchSync AS bs')
            ->getSingleScalarResult();

        if (!$cont) {
            $branchSync = new BranchSync();
            $branchSync
                ->setBranchName('localhost')
                ->setAccessUrlId(1);

            $em->persist($branchSync);
            $em->flush();
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