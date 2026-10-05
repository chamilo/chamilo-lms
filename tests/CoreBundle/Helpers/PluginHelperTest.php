<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Plugin as PluginEntity;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Repository\AccessUrlRelPluginRepository;
use Chamilo\CoreBundle\Repository\PluginRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * PluginHelper::loadLegacyPlugin() is what api_get_plugin_setting() relies on.
 *
 * Legacy plugin classes are named "<Title>Plugin" (BbbPlugin, BuyCoursesPlugin...),
 * and the bare "<Title>" can be an unrelated library class (Bbb in bbb.lib.php).
 * When the helper only looked for "<Title>", every plugin setting read back as null,
 * which silently hid the BBB "Videoconference" menu entry although the plugin was
 * enabled and configured. These cases pin the class-name resolution.
 */
final class PluginHelperTest extends TestCase
{
    public function testPluginSuffixedClassWinsOverLibraryClassOfTheSameTitle(): void
    {
        $plugin = $this->helper(PluginHelperTestBbb::class)->loadLegacyPlugin(strtolower(PluginHelperTestBbb::class));

        self::assertInstanceOf(PluginHelperTestBbbPlugin::class, $plugin);
    }

    public function testSettingIsReadFromThePluginClass(): void
    {
        // The exact call path of api_get_plugin_setting('bbb', 'enable_global_conference').
        $value = $this->helper(PluginHelperTestBbb::class)
            ->getPluginSetting(strtolower(PluginHelperTestBbb::class), 'enable_global_conference')
        ;

        self::assertSame('true', $value);
    }

    public function testClassNamedExactlyAsTheTitleIsStillLoaded(): void
    {
        // Some plugins (Positioning, Tour, Justification...) have no "Plugin" suffix.
        $plugin = $this->helper(PluginHelperTestUnsuffixed::class)->loadLegacyPlugin(PluginHelperTestUnsuffixed::class);

        self::assertInstanceOf(PluginHelperTestUnsuffixed::class, $plugin);
    }

    public function testUnknownPluginGivesNoSetting(): void
    {
        self::assertNull($this->helper(PluginHelperTestBbb::class)->getPluginSetting('nonexistent', 'anything'));
    }

    private function helper(string $installedTitle): PluginHelper
    {
        $pluginRepo = $this->createMock(PluginRepository::class);
        $pluginRepo->method('findAll')->willReturn([(new PluginEntity())->setTitle($installedTitle)]);

        return new PluginHelper(
            $this->createMock(ParameterBagInterface::class),
            $this->createMock(AccessUrlRelPluginRepository::class),
            $pluginRepo,
            // Readonly, so it cannot be doubled; class-name resolution never touches it.
            (new ReflectionClass(AccessUrlHelper::class))->newInstanceWithoutConstructor(),
        );
    }
}

/**
 * Same shape as Bbb (bbb.lib.php): shares the plugin title but is not the plugin.
 */
class PluginHelperTestBbb {}

class PluginHelperTestBbbPlugin
{
    public static function create(): self
    {
        return new self();
    }

    public function get(string $name): mixed
    {
        return 'enable_global_conference' === $name ? 'true' : null;
    }
}

class PluginHelperTestUnsuffixed
{
    public static function create(): self
    {
        return new self();
    }
}
