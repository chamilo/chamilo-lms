<?php

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use UserRemoteServicePlugin;

/**
 * Pins the service CRUD of the UserRemoteService plugin (table plugin_user_remote_service): ordering by title then
 * id, the value round trip (quotes included), the "no row" results and the rejection of invalid input.
 */
final class UserRemoteServicePluginTier3CharacterizationTest extends PluginCharacterizationTestCase
{
    private const TABLE = 'plugin_user_remote_service';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::ensurePluginTables(
            [self::TABLE],
            'UserRemoteService',
            'UserRemoteService/src/user_remote_service_plugin.class.php',
            'UserRemoteServicePlugin'
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        require_once api_get_path(SYS_PLUGIN_PATH).'UserRemoteService/Entity/UserRemoteService.php';
        require_once api_get_path(SYS_PLUGIN_PATH).'UserRemoteService/src/user_remote_service_plugin.class.php';
    }

    private function plugin(): UserRemoteServicePlugin
    {
        return UserRemoteServicePlugin::create();
    }

    private function lastId(): int
    {
        return (int) $this->conn->fetchOne('SELECT MAX(id) FROM '.self::TABLE);
    }

    public function testServicesAreListedByTitleThenId(): void
    {
        $this->conn->executeStatement('DELETE FROM '.self::TABLE);
        $this->assertTrue($this->plugin()->addService('urs_b', 'https://b.example.org'));
        $b1 = $this->lastId();
        $this->assertTrue($this->plugin()->addService('urs_a', 'https://a.example.org/x?y=1&z=2'));
        $a = $this->lastId();
        $this->assertTrue($this->plugin()->addService('urs_b', 'https://b2.example.org'));
        $b2 = $this->lastId();

        $services = $this->plugin()->getServices();
        $this->assertSame([$a, $b1, $b2], array_map(static fn ($s) => $s->getId(), $services));
        $this->assertSame('urs_a', $services[0]->getTitle());
        $this->assertSame('https://a.example.org/x?y=1&z=2', $services[0]->getURL());
        $this->assertIsInt($services[0]->getId());
    }

    public function testGetServiceReturnsTheRowOrNull(): void
    {
        $this->assertTrue($this->plugin()->addService('urs_get', 'https://get.example.org'));
        $id = $this->lastId();

        $service = $this->plugin()->getService((string) $id);
        $this->assertSame($id, $service->getId());
        $this->assertSame('urs_get', $service->getTitle());
        $this->assertSame('https://get.example.org', $service->getURL());
        $this->assertNull($this->plugin()->getService($id + 1000));
        $this->assertNull($this->plugin()->getService(0));
    }

    public function testQuotesInTheTitleSurviveTheRoundTrip(): void
    {
        $this->assertTrue($this->plugin()->addService("urs_O'Re\"il\\ly", 'https://q.example.org'));
        $service = $this->plugin()->getService($this->lastId());
        $this->assertSame("urs_O'Re\"il\\ly", $service->getTitle());
    }

    public function testInvalidInputIsRejectedAndNothingIsInserted(): void
    {
        $before = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM '.self::TABLE);
        $this->assertFalse($this->plugin()->addService('   ', 'https://x.example.org'));
        $this->assertFalse($this->plugin()->addService('urs_bad', 'not a url'));
        $this->assertSame($before, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM '.self::TABLE));
    }

    public function testUpdateChangesOnlyTheGivenService(): void
    {
        $this->assertTrue($this->plugin()->addService('urs_u1', 'https://u1.example.org'));
        $one = $this->lastId();
        $this->assertTrue($this->plugin()->addService('urs_u2', 'https://u2.example.org'));
        $two = $this->lastId();

        $service = $this->plugin()->getService($one);
        $service->setTitle("urs_u1 'new'")->setURL('https://new.example.org');
        $this->assertTrue($this->plugin()->updateService($service));

        $this->assertSame("urs_u1 'new'", $this->plugin()->getService($one)->getTitle());
        $this->assertSame('https://new.example.org', $this->plugin()->getService($one)->getURL());
        $this->assertSame('urs_u2', $this->plugin()->getService($two)->getTitle());

        $service->setURL('bad url');
        $this->assertFalse($this->plugin()->updateService($service));
        $this->assertFalse($this->plugin()->updateService('not a service'));
        $this->assertSame('https://new.example.org', $this->plugin()->getService($one)->getURL());
    }

    public function testRemoveDeletesOnlyTheGivenService(): void
    {
        $this->assertTrue($this->plugin()->addService('urs_r1', 'https://r1.example.org'));
        $one = $this->lastId();
        $this->assertTrue($this->plugin()->addService('urs_r2', 'https://r2.example.org'));
        $two = $this->lastId();

        $this->assertTrue($this->plugin()->removeService($one));
        $this->assertNull($this->plugin()->getService($one));
        $this->assertNotNull($this->plugin()->getService($two));
        $this->assertTrue($this->plugin()->removeService($this->plugin()->getService($two)));
        $this->assertNull($this->plugin()->getService($two));
        $this->assertFalse($this->plugin()->removeService(0));
    }
}
