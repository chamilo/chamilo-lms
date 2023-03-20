<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\Kernel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

class Version20210221082033 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_lp images';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();
        $doctrine = $container->get('doctrine');

        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();
        $lpRepo = $container->get(CLpRepository::class);

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $sql = "SELECT * FROM c_lp WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                $path = $itemData['preview_image'];
                $lp = $lpRepo->find($id);
                if ($lp && !empty($path)) {
                    $filePath = $rootPath.'/app/courses/'.$course->getDirectory().'/upload/learning_path/images/'.$path;
                    if ($this->fileExists($filePath)) {
                        $this->addLegacyFileToResource($filePath, $lpRepo, $lp, $lp->getIid(), $path);
                        $em->persist($lp);
                        $em->flush();
                    }
                }
            }

            $em->flush();
            $em->clear();
        }
    }
}
