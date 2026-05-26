<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\PageCategory;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260204142200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Adds the CMS PageCategory "menu_links" used to display legal information links in the sidebar and public homepage.';
    }

    public function up(Schema $schema): void
    {
        $pageCategoryRepo = $this->entityManager->getRepository(PageCategory::class);
        $adminUser = $this->getAdmin();

        // Create the "menu_links" category if it does not exist yet.
        $existing = $pageCategoryRepo->findOneBy(['title' => 'menu_links']);
        if ($existing) {
            error_log('[MIGRATION] PageCategory "menu_links" already exists. Skipping.');

            return;
        }

        $category = new PageCategory();
        $category
            ->setTitle('menu_links')
            ->setType('grid')
            ->setCreator($adminUser)
        ;

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        error_log('[MIGRATION] PageCategory "menu_links" created successfully.');
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty (non-destructive migration).
    }
}
