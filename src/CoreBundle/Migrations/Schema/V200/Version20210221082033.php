<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\Kernel;
use Doctrine\DBAL\Schema\Schema;

class Version20210221082033 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_lp images';
    }

    public function up(Schema $schema): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $lpRepo = $this->container->get(CLpRepository::class);

        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $sql = "SELECT * FROM c_lp WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                $path = $itemData['preview_image'];
                $lp = $lpRepo->find($id);
                if ($lp && !empty($path)) {
                    $filePath = $this->getUpdateRootPath().'/app/courses/'.$course->getDirectory().'/upload/learning_path/images/'.$path;
                    error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');
                    if ($this->fileExists($filePath)) {
                        $this->addLegacyFileToResource($filePath, $lpRepo, $lp, $lp->getIid(), $path);
                        $this->entityManager->persist($lp);
                        $this->entityManager->flush();
                    }
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }
    }
}
