<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Entity\PageCategory;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261029184000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create the public menu links page category on existing installations.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('page_category') || !$schema->hasTable('user')) {
            return;
        }

        $pageCategoryRepository = $this->entityManager->getRepository(PageCategory::class);
        if (null !== $pageCategoryRepository->findOneBy(['title' => 'menu_public_links'])) {
            return;
        }

        // On a fresh installation, fixtures create the administrator and all default pages.
        if (0 === (int) $this->connection->fetchOne('SELECT COUNT(*) FROM user')) {
            return;
        }

        $category = (new PageCategory())
            ->setTitle('menu_public_links')
            ->setType('grid')
            ->setCreator($this->getAdmin())
        ;

        $this->entityManager->persist($category);
        $this->entityManager->flush();
    }

    public function down(Schema $schema): void
    {
        // Do not remove administrator-managed pages or their category on rollback.
    }
}
