<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Plugin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PluginRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plugin::class);
    }

    /**
     * Get all installed plugins.
     *
     * @return Plugin[]
     */
    public function getInstalledPlugins(): array
    {
        return $this->findBy(['installed' => true]);
    }

    /**
     * Get all active plugins.
     *
     * @return Plugin[]
     */
    public function getActivePlugins(): array
    {
        return $this->findBy(['installed' => true, 'active' => true]);
    }

    /**
     * Check if a plugin is installed.
     *
     * @param string $pluginName
     * @return bool
     */
    public function isInstalled(string $pluginName): bool
    {
        return $this->findOneBy(['title' => $pluginName, 'installed' => true]) !== null;
    }

    /**
     * Check if a plugin is active.
     *
     * @param string $pluginName
     * @return bool
     */
    public function isActive(string $pluginName): bool
    {
        $plugin = $this->findOneBy(['title' => $pluginName, 'installed' => true]);
        return $plugin ? $plugin->isActive() : false;
    }

    /**
     * Install a plugin.
     *
     * @param string $pluginName
     */
    public function installPlugin(string $pluginName): void
    {
        $plugin = $this->findOneBy(['title' => $pluginName]);

        if (!$plugin) {
            $plugin = new Plugin();
            $plugin->setTitle($pluginName)
                ->setInstalled(true)
                ->setActive(false)
                ->setVersion('1.0')
                ->setAccessUrlId(api_get_current_access_url_id())
                ->setConfiguration([]);
        } else {
            $plugin->setInstalled(true);
        }

        $this->getEntityManager()->persist($plugin);
        $this->getEntityManager()->flush();
    }

    /**
     * Uninstall a plugin.
     *
     * @param string $pluginName
     */
    public function uninstallPlugin(string $pluginName): void
    {
        $plugin = $this->findOneBy(['title' => $pluginName]);

        if ($plugin) {
            $plugin->setInstalled(false);
            $this->getEntityManager()->persist($plugin);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Enable a plugin.
     *
     * @param string $pluginName
     */
    public function enablePlugin(string $pluginName): void
    {
        $plugin = $this->findOneBy(['title' => $pluginName]);

        if ($plugin && $plugin->isInstalled()) {
            $plugin->setActive(true);
            $this->getEntityManager()->persist($plugin);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Disable a plugin.
     *
     * @param string $pluginName
     */
    public function disablePlugin(string $pluginName): void
    {
        $plugin = $this->findOneBy(['title' => $pluginName]);

        if ($plugin && $plugin->isInstalled()) {
            $plugin->setActive(false);
            $this->getEntityManager()->persist($plugin);
            $this->getEntityManager()->flush();
        }
    }
}
