<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MigrationFactory implements \Doctrine\Migrations\Version\MigrationFactory
{
    private \Doctrine\Migrations\Version\MigrationFactory $migrationFactory;
    private ContainerInterface $container;

    public function __construct(\Doctrine\Migrations\Version\MigrationFactory $migrationFactory, ContainerInterface $container)
    {
        $this->migrationFactory = $migrationFactory;
        $this->container = $container;
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $instance = $this->migrationFactory->createVersion($migrationClassName);

        if ($instance instanceof ContainerAwareInterface) {
            $instance->setContainer($this->container);
        }

        return $instance;
    }
}
