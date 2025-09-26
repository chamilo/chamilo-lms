<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Tool\ToolChain;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Query;

final class Version20250923205900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Seed missing course tools into existing courses (one tool).';
    }

    public function up(Schema $schema): void
    {
        /** @var ToolChain $toolChain */
        $toolChain = $this->container->get(ToolChain::class);
        /** @var SettingsManager $settings */
        $settings  = $this->container->get(SettingsManager::class);

        // Ensure global catalog is seeded (tool + resource_types)
        $toolChain->createTools();

        $targetTitle = 'dropbox';

        // Get Tool ID by title (fail fast if not found)
        $toolId = $this->entityManager
            ->createQuery('SELECT t.id FROM Chamilo\CoreBundle\Entity\Tool t WHERE t.title = :title')
            ->setParameter('title', $targetTitle)
            ->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR);

        if ($toolId === null) {
            $this->write('Tool "'.$targetTitle.'" does not exist; aborting migration.');
            return;
        }
        $toolId = (int) $toolId;

        $activeOnCreate = $settings->getSetting('course.active_tools_on_create') ?? [];
        $visible        = \in_array($targetTitle, $activeOnCreate, true);
        $linkVisibility = $visible ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT;

        $batchSize = self::BATCH_SIZE;
        $i = 0;

        // Iterate over course IDs only (no heavy hydration)
        $q = $this->entityManager->createQuery('SELECT c.id FROM Chamilo\CoreBundle\Entity\Course c');

        foreach ($q->toIterable([], Query::HYDRATE_SCALAR) as $row) {
            $courseId = (int) $row['id'];

            // Does the course already have this tool?
            $exists = (int) $this->entityManager
                ->createQuery(
                    'SELECT COUNT(ct.iid)
                       FROM Chamilo\CourseBundle\Entity\CTool ct
                      WHERE ct.title = :title
                        AND ct.course = :course'
                )
                ->setParameter('title', $targetTitle)
                ->setParameter('course', $this->entityManager->getReference(Course::class, $courseId))
                ->getSingleScalarResult();

            if ($exists > 0) {
                continue;
            }

            $toolRef   = $this->entityManager->getReference(Tool::class, $toolId);
            $courseRef = $this->entityManager->getReference(Course::class, $courseId);

            $ctool = new CTool()
                ->setTool($toolRef)
                ->setTitle($targetTitle)
                ->setVisibility($visible)
                ->setCourse($courseRef)
                ->setParent($courseRef)
                ->setCreator($courseRef->getCreator())
                ->addCourseLink($courseRef, null, null, $linkVisibility);

            $this->entityManager->persist($ctool);

            if ((++$i % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function down(Schema $schema): void {}
}
