<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\CatalogueCourseRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Doctrine\DBAL\Schema\Schema;
use Exception;

final class Version20250321000100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrates courses using the show_in_catalogue extra field into the catalogue_course_rel_access_url_rel_usergroup table.';
    }

    public function up(Schema $schema): void
    {
        $this->entityManager->beginTransaction();

        try {
            if (!$this->tableExists('extra_field') || !$this->tableExists('extra_field_values')) {
                return;
            }

            /** @var AccessUrlRepository $accessUrlRepo */
            $accessUrlRepo = $this->container->get(AccessUrlRepository::class);
            $accessUrlId = $accessUrlRepo->getFirstId();

            if (0 === $accessUrlId) {
                throw new Exception('No AccessUrl found for migration');
            }

            /** @var AccessUrl|null $accessUrl */
            $accessUrl = $this->entityManager->find(AccessUrl::class, $accessUrlId);
            if (!$accessUrl) {
                throw new Exception('AccessUrl entity not found for ID: '.$accessUrlId);
            }

            $courseRepo = $this->entityManager->getRepository(Course::class);

            $courseIds = $this->connection->fetchFirstColumn('
                SELECT fv.item_id
                FROM extra_field_values fv
                INNER JOIN extra_field f ON f.id = fv.field_id
                WHERE f.item_type = 2
                  AND f.variable = "show_in_catalogue"
                  AND fv.field_value = 1
            ');

            foreach ($courseIds as $courseId) {
                $course = $courseRepo->find($courseId);

                if (!$course) {
                    continue;
                }

                $rel = new CatalogueCourseRelAccessUrlRelUsergroup();
                $rel->setAccessUrl($accessUrl);
                $rel->setCourse($course);
                $rel->setUsergroup(null);

                $this->entityManager->persist($rel);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollBack();
            error_log('Migration failed: '.$e->getMessage());
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM catalogue_course_rel_access_url_rel_usergroup');
    }

    private function tableExists(string $tableName): bool
    {
        try {
            $this->connection->executeQuery("SELECT 1 FROM $tableName LIMIT 1");

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
