<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\DBAL\Schema\Schema;

final class Version20241120123300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove session-specific tools and related entities, keeping only course-base tools.';
    }

    public function up(Schema $schema): void
    {
        $repository = $this->entityManager->getRepository(CTool::class);

        $queryBuilder = $repository->createQueryBuilder('ct');
        $queryBuilder->where('ct.session IS NOT NULL');
        $sessionTools = $queryBuilder->getQuery()->getResult();

        foreach ($sessionTools as $tool) {
            $this->entityManager->remove($tool);
            error_log(sprintf("Removed tool: %s (ID: %d)", $tool->getTitle(), $tool->getIid()));
        }

        $this->entityManager->flush();
    }
}
