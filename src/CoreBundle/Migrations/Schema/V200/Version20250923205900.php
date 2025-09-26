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

        $toolRepo   = $this->entityManager->getRepository(Tool::class);
        $courseRepo = $this->entityManager->getRepository(Course::class);

        /** @var Tool|null $tool */
        $tool = $toolRepo->findOneBy(['title' => $targetTitle]);
        if (!$tool) {
            $this->write('Tool "'.$targetTitle.'" does not exist; aborting migration.');
            return;
        }

        $activeOnCreate = $settings->getSetting('course.active_tools_on_create') ?? [];
        $batchSize = self::BATCH_SIZE;
        $i = 0;

        // Iterate over all courses using a memory-efficient cursor
        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            // Skip if the course already has a CTool with the same title
            $already = false;
            foreach ($course->getTools() as $ct) {
                if (0 === strcasecmp($ct->getTitle(), $targetTitle)) {
                    $already = true;
                    break;
                }
            }
            if ($already) {
                continue;
            }

            // Initial visibility and link visibility (same rule as addToolsInCourse)
            $visible = \in_array($targetTitle, $activeOnCreate, true);
            $linkVisibility = $visible ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT;

            // Create CTool and its ResourceLink
            $ctool = (new CTool())
                ->setTool($tool)
                ->setTitle($targetTitle)
                ->setVisibility($visible)
                ->setParent($course)
                ->setCreator($course->getCreator())
                ->addCourseLink($course, null, null, $linkVisibility);

            $course->addTool($ctool);
            $this->entityManager->persist($ctool);

            // Batch flush
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
