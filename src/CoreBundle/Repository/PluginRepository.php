<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Plugin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plugin>
 * @method Plugin|null findOneByTitle(string $title)
 */
class PluginRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plugin::class);
    }

    /**
     * Get all installed plugins.
     *
     * @return array<int, Plugin>
     */
    public function getInstalledPlugins(): array
    {
        return $this->findBy(['installed' => true]);
    }

    /**
     * Get all active plugins.
     *
     * @return array<int, Plugin>
     */
    public function getActivePlugins(): array
    {
        return $this->findBy(['installed' => true, 'active' => true]);
    }

    /**
     * Get an installed plugin.
     */
    public function getInstalledByName(string $pluginName): ?Plugin
    {
        return $this->findOneBy(['title' => $pluginName, 'installed' => true]);
    }

    /**
     * Check if a plugin is installed.
     */
    public function isInstalledByName(string $pluginName): bool
    {
        return null !== $this->getInstalledByName($pluginName);
    }

    /**
     * Check if a plugin is active.
     */
    public function isActiveByName(string $pluginName): bool
    {
        $plugin = $this->findOneBy(['title' => $pluginName, 'installed' => true]);

        return $plugin ? $plugin->isActive() : false;
    }
}
