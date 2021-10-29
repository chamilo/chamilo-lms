<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Component\Utils\CreateDefaultPages;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20211029123419 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Page entity';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('page')) {
            $container = $this->getContainer();
            $createDefaultPages = $container->get(CreateDefaultPages::class);

            $urlRepo = $container->get(AccessUrlRepository::class);
            $urlList = $urlRepo->findAll();
            /** @var AccessUrl $url */
            $url = $urlList[0];
            $createDefaultPages->createDefaultPages($this->getAdmin(), $url, 'en_US');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
