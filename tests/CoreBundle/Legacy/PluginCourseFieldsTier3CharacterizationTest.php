<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Plugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization of the course-field helpers of public/main/inc/lib/plugin.class.php: install_course_fields,
 * uninstall_course_fields and their *_in_all_courses loops (course settings rows in c_course_setting, tool links in
 * c_tool, courses of the current access URL).
 */
class PluginCourseFieldsTier3CharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $httpSession = new HttpSession(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($httpSession);
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        Container::setSession($httpSession);
        Container::setRequest($request);
        Database::setManager($this->getEntityManager());
        Database::setConnection($this->getEntityManager()->getConnection());
        $_GET = [];
        $this->conn()->executeStatement("DELETE FROM c_course_setting WHERE category = 'plugins' OR variable LIKE 'xp\\_%'");
    }

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function stub(array $settings): Plugin
    {
        $plugin = new class() extends Plugin {
            public function __construct()
            {
                parent::__construct('1.0', 'test');
            }

            public function get_name(): string
            {
                return 'xpstub';
            }
        };
        $plugin->addCourseTool = false;
        $plugin->course_settings = $settings;

        return $plugin;
    }

    private function settings(int $courseId): array
    {
        return $this->conn()->fetchAllAssociative(
            'SELECT variable, subkey, value, category, type, title FROM c_course_setting
             WHERE c_id = ? AND variable LIKE \'xp\\_%\' ORDER BY variable, subkey',
            [$courseId]
        );
    }

    public function testInstallInsertsTheSettingsOnceAndKeepsExistingOnes(): void
    {
        $plugin = $this->stub([
            ['name' => 'xp_plain', 'type' => 'checkbox', 'init_value' => 'on'],
            ['name' => 'xp_member', 'group' => 'xp_group'],
        ]);

        $plugin->install_course_fields(1, false);

        $this->assertSame(
            [
                ['variable' => 'xp_group', 'subkey' => 'xp_member', 'value' => '', 'category' => 'plugins', 'type' => 'textfield', 'title' => ''],
                ['variable' => 'xp_plain', 'subkey' => 'xpstub', 'value' => 'on', 'category' => 'plugins', 'type' => 'checkbox', 'title' => ''],
            ],
            $this->settings(1)
        );

        $this->conn()->executeStatement("UPDATE c_course_setting SET value = 'kept' WHERE variable = 'xp_plain'");
        $plugin->install_course_fields(1, false);

        $rows = $this->settings(1);
        $this->assertCount(2, $rows, 'Installing twice does not duplicate.');
        $this->assertSame('kept', $rows[1]['value']);
        $this->assertSame([], $this->settings(2), 'Only the given course.');
    }

    public function testInstallTreatsAGroupAndAPlainSettingAsDifferentKeys(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_course_setting (c_id, variable, subkey, value, category, type, title) VALUES (1, 'xp_a', 'other', 'v', 'plugins', 'textfield', '')"
        );
        $this->conn()->executeStatement(
            "INSERT INTO c_course_setting (c_id, variable, subkey, value, category, type, title) VALUES (1, 'xp_g', 'zz', 'v', 'plugins', 'textfield', '')"
        );
        $plugin = $this->stub([
            ['name' => 'xp_a'],
            ['name' => 'xp_g', 'group' => 'xp_g'],
        ]);

        $plugin->install_course_fields(1, false);

        $rows = $this->settings(1);
        $this->assertSame(
            [['xp_a', 'other'], ['xp_g', 'xp_g'], ['xp_g', 'zz']],
            array_map(static fn ($r) => [$r['variable'], $r['subkey']], $rows),
            'A plain setting is skipped when its variable exists with any subkey; a group one needs group+subkey.'
        );
    }

    public function testInstallRefusesAnEmptyCourseId(): void
    {
        $this->assertFalse($this->stub([['name' => 'xp_x']])->install_course_fields(0, false));
        $this->assertSame([], $this->settings(0));
    }

    public function testUninstallDeletesTheSettingsOfThatCourseAndThePluginToolLinks(): void
    {
        $plugin = $this->stub([['name' => 'xp_plain'], ['name' => 'xp_member', 'group' => 'xp_group']]);
        $plugin->install_course_fields(1, false);
        $plugin->install_course_fields(2, false);
        $this->conn()->executeStatement(
            "INSERT INTO c_course_setting (c_id, variable, subkey, value, category, type, title) VALUES (1, 'xp_unrelated', NULL, 'v', 'plugins', 'textfield', '')"
        );
        foreach (['xpstub', 'xpstub:student', 'xpstub:teacher', 'xpstub:other', 'xpstub2'] as $i => $title) {
            $this->conn()->executeStatement(
                'INSERT INTO c_tool (c_id, tool_id, title, position) VALUES (1, 1, ?, ?)',
                [$title, $i]
            );
        }
        $this->conn()->executeStatement('INSERT INTO c_tool (c_id, tool_id, title, position) VALUES (2, 1, \'xpstub\', 9)');

        $plugin->uninstall_course_fields(1);

        $this->assertSame(['xp_unrelated'], array_column($this->settings(1), 'variable'));
        $this->assertCount(2, $this->settings(2), 'Other courses keep their settings.');
        $this->assertSame(
            ['xpstub:other', 'xpstub2'],
            $this->conn()->fetchFirstColumn("SELECT title FROM c_tool WHERE c_id = 1 AND title LIKE 'xpstub%' ORDER BY title")
        );
        $this->assertSame(1, (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM c_tool WHERE c_id = 2 AND title = 'xpstub'"));
        $this->assertFalse($plugin->uninstall_course_fields(0));
    }

    public function testInAllCoursesLoopsTheCoursesOfTheCurrentAccessUrlOnly(): void
    {
        $this->conn()->executeStatement('DELETE FROM access_url_rel_course WHERE c_id = 2');
        $plugin = $this->stub([['name' => 'xp_loop']]);

        $plugin->install_course_fields_in_all_courses(false);

        $this->assertCount(1, $this->settings(1));
        $this->assertSame([], $this->settings(2), 'Course 2 is not on this access URL.');

        $this->conn()->executeStatement(
            "INSERT INTO c_course_setting (c_id, variable, subkey, value, category, type, title) VALUES (2, 'xp_loop', 'xpstub', 'v', 'plugins', 'textfield', '')"
        );
        $plugin->uninstall_course_fields_in_all_courses();

        $this->assertSame([], $this->settings(1));
        $this->assertCount(1, $this->settings(2));
    }
}
