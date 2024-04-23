<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory as DoctrineMigrationFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MigrationFactory implements DoctrineMigrationFactory
{
    private DoctrineMigrationFactory $migrationFactory;
    private ContainerInterface $container;

    /**
     * @psalm-suppress ContainerDependency
     */
    public function __construct(
        DoctrineMigrationFactory $migrationFactory,
        ContainerInterface $container,
        protected readonly EntityManagerInterface $entityManager
    ) {
        $this->migrationFactory = $migrationFactory;
        $this->container = $container;
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $instance = $this->migrationFactory->createVersion($migrationClassName);

        if ($instance instanceof AbstractMigrationChamilo) {
            $instance->setContainer($this->container);
            $instance->setEntityManager($this->entityManager);
        }

        return $instance;
    }
}
