<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use BbbPlugin;
use Chamilo\CoreBundle\Entity\AccessUrlRelPlugin;
use Chamilo\CoreBundle\Entity\Plugin;
use Chamilo\CoreBundle\Framework\Container;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for BbbPlugin::install() and uninstall(): they register the plugin row (re-using the one that
 * exists) and link it, with its default configuration, to every access URL. A plugin row that already exists used to go
 * through EntityManager::merge(), which no longer exists in Doctrine ORM 3.
 */
final class BbbPluginInstallTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new HttpSession(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/plugin/Bbb/index.php');
        $request->setSession($session);
        $request->setLocale('en_US');
        Container::setRequest($request);

        require_once api_get_path(SYS_PLUGIN_PATH).'Bbb/lib/bbb_plugin.class.php';
        $this->em = $container->get('doctrine')->getManager();
    }

    public function testInstallRegistersThePluginAndLinksItToEveryAccessUrl(): void
    {
        $this->assertNull($this->pluginRow(), 'The test database has no Bbb plugin row to begin with');

        BbbPlugin::create()->install();

        $plugin = $this->pluginRow();
        $this->assertNotNull($plugin);
        $this->assertTrue($plugin->isInstalled());
        $this->assertSame(BbbPlugin::create()->get_version(), $plugin->getInstalledVersion());
        $this->assertSame(Plugin::SOURCE_OFFICIAL, $plugin->getSource());
        $urls = (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM access_url');
        $this->assertGreaterThan(0, $urls);
        $rels = $this->em->getRepository(AccessUrlRelPlugin::class)->findBy(['plugin' => $plugin]);
        $this->assertCount($urls, $rels);
        $this->assertTrue($rels[0]->isActive());
        $configuration = $rels[0]->getConfiguration();
        $this->assertSame('false', $configuration['webhooks_enabled']);
        // The webhook settings are plugin fields of a non-boolean type: they start empty (the explicit defaults of install()
        // only apply to a setting that is not a plugin field).
        $this->assertSame('', $configuration['webhooks_scope']);
        $this->assertSame('', $configuration['webhooks_hash_algo']);
        $this->assertSame('', $configuration['webhooks_event_filter']);
    }

    public function testInstallReusesTheExistingPluginRowAndDoesNotLinkTwice(): void
    {
        $existing = new Plugin();
        $existing->setTitle(BbbPlugin::create()->get_name());
        $existing->setInstalled(false);
        $existing->setInstalledVersion('0.0.1');
        $existing->setSource(Plugin::SOURCE_THIRD_PARTY);
        $this->em->persist($existing);
        $this->em->flush();
        $existingId = $existing->getId();

        BbbPlugin::create()->install();
        BbbPlugin::create()->install();

        $this->em->clear();
        $plugin = $this->pluginRow();
        $this->assertSame($existingId, $plugin->getId(), 'The row that existed is the one updated');
        $this->assertTrue($plugin->isInstalled());
        $this->assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM plugin WHERE title = ?', [BbbPlugin::create()->get_name()]));
        $urls = (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM access_url');
        $this->assertCount($urls, $this->em->getRepository(AccessUrlRelPlugin::class)->findBy(['plugin' => $plugin]));
    }

    public function testUninstallUnlinksTheAccessUrlsAndMarksThePluginAsNotInstalled(): void
    {
        BbbPlugin::create()->install();
        BbbPlugin::create()->uninstall();

        $this->em->clear();
        $plugin = $this->pluginRow();
        $this->assertNotNull($plugin, 'The row is kept');
        $this->assertFalse($plugin->isInstalled());
        $this->assertCount(0, $this->em->getRepository(AccessUrlRelPlugin::class)->findBy(['plugin' => $plugin]));
    }

    private function pluginRow(): ?Plugin
    {
        return $this->em->getRepository(Plugin::class)->findOneBy(['title' => BbbPlugin::create()->get_name()]);
    }
}
