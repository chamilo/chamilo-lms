<?php

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

/**
 * Pins the live queries of public/plugin/CStudio/0_dal/dal.chamilo_object.php: get_lp_Id(), update_lp_infos(),
 * getCollectionPages() and getAlonePages(). get_directory() and getDirectoryRender() are not covered: they join
 * c_lp.c_id, a column that no longer exists (they fail with an SQL error today).
 */
final class CStudioChamiloObjectTier3CharacterizationTest extends PluginCharacterizationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // Same columns as teachdoc_hub::install(); DDL cannot run inside a test (DAMA savepoint).
        PluginTestDatabase::pdo()->exec(
            "CREATE TABLE IF NOT EXISTS plugin_oel_tools_teachdoc(
                id INT NOT NULL AUTO_INCREMENT,
                id_user INT,
                title LONGTEXT NOT NULL,
                id_parent INT,
                order_lst INT,
                type_node INT,
                type_base INT,
                colors VARCHAR(25) NOT NULL,
                quizztheme VARCHAR(25) NOT NULL,
                id_url INT,
                lp_id INT,
                behavior TINYINT NOT NULL DEFAULT '0',
                leveldoc TINYINT NOT NULL DEFAULT '0',
                local_folder VARCHAR(255) NOT NULL,
                date_create VARCHAR(12) NOT NULL,
                base_html LONGTEXT NOT NULL,
                base_css LONGTEXT NOT NULL,
                gpscomps LONGTEXT NOT NULL,
                gpsstyle LONGTEXT NOT NULL,
                recent_save TINYINT NOT NULL DEFAULT '0',
                options LONGTEXT NOT NULL,
                PRIMARY KEY (id))"
        );
    }

    public static function tearDownAfterClass(): void
    {
        PluginTestDatabase::pdo()->exec('DROP TABLE IF EXISTS plugin_oel_tools_teachdoc');
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        require_once api_get_path(SYS_PLUGIN_PATH).'CStudio/ajax/inc/functions.php';

        require_once api_get_path(SYS_PLUGIN_PATH).'CStudio/0_dal/dal.chamilo_object.php';
        $this->conn->executeStatement('DELETE FROM plugin_oel_tools_teachdoc');
    }

    private function page(
        string $title,
        int $parent = 0,
        int $order = 0,
        int $type = 1,
        int $behavior = 0,
        int $leveldoc = 0,
        string $colors = '',
        string $quizz = '',
        int $lpId = 0
    ): int {
        $this->conn->executeStatement(
            "INSERT INTO plugin_oel_tools_teachdoc
                (title, id_parent, order_lst, type_node, colors, quizztheme, lp_id, behavior, leveldoc, local_folder,
                 date_create, base_html, base_css, gpscomps, gpsstyle, options)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '', '', '', '', '', '', '')",
            [$title, $parent, $order, $type, $colors, $quizz, $lpId, $behavior, $leveldoc]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function lp(string $title = 'lp title', string $path = 'old/path', string $mode = 'embedded'): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_lp (lp_type, title, path, force_commit, default_view_mod, content_maker, content_license,
                js_lib, debug, theme, author, prerequisite, hide_toc_frame, seriousgame_mode, autolaunch,
                max_attempts, subscribe_users, created_on, modified_on)
             VALUES (1, ?, ?, 0, ?, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0, NOW(), NOW())",
            [$title, $path, $mode]
        );

        return (int) $this->conn->lastInsertId();
    }

    public function testGetLpIdReturnsTheLinkedLpOrZero(): void
    {
        $id = $this->page('Top', 0, 0, 1, 0, 0, '', '', 4242);

        $this->assertEquals(4242, get_lp_Id($id));
        $this->assertSame(0, get_lp_Id($id + 1000));
        $this->assertSame(0, get_lp_Id('x'));
    }

    public function testUpdateLpInfosSetsPathModeAndTitleOfThatLpOnly(): void
    {
        $lp = $this->lp();
        $other = $this->lp('other', 'other/path', 'fullscreen');

        update_lp_infos($lp, "O'Brien \"q\" \\ x", "folder'1");

        $row = $this->conn->fetchAssociative('SELECT title, path, default_view_mod FROM c_lp WHERE iid = ?', [$lp]);
        $this->assertSame("O'Brien \"q\" \\ x", $row['title']);
        $this->assertSame("folder'1/.", $row['path']);
        $this->assertSame('embedframe', $row['default_view_mod']);
        $this->assertSame(
            ['title' => 'other', 'path' => 'other/path', 'default_view_mod' => 'fullscreen'],
            $this->conn->fetchAssociative('SELECT title, path, default_view_mod FROM c_lp WHERE iid = ?', [$other])
        );
    }

    public function testGetCollectionPagesDefaultsOrderAndExcludes(): void
    {
        $top = $this->page('Top', 0, 0, 1, 0, 0, '', '');
        $second = $this->page('Second', $top, 2, 2, 1, 5, 'dark.css', 'q.css');
        $first = $this->page('First', $top, 1, 2, 0, 0, '', '');
        $this->page('Hidden type', $top, 3, -1);
        $this->page('Alone', $top, 4, 2, 3);
        $folder = $this->page('Folder', $top, 5, 3, 0, 2, 'c.css', 'z.css');
        $this->page('Other parent', $second, 1);

        $pages = getCollectionPages($top);

        $this->assertCount(4, $pages);
        $this->assertEquals(
            [
                'id' => $top, 'next_id' => 0, 'prev_id' => 0, 'title' => 'Top', 'type_node' => 1, 'behavior' => 0,
                'leveldoc' => 2, 'colors' => 'white-chami.css', 'quizztheme' => 'white-quizz.css', 'index' => 0,
            ],
            $pages[0]
        );
        $this->assertEquals(
            ['id' => $first, 'title' => 'First', 'type_node' => 2, 'behavior' => 0, 'leveldoc' => 2,
                'colors' => 'white-chami.css', 'quizztheme' => 'white-quizz.css', 'index' => 1],
            $pages[1]
        );
        $this->assertEquals(
            ['id' => $second, 'title' => 'Second', 'type_node' => 2, 'behavior' => 1, 'leveldoc' => 5,
                'colors' => 'dark.css', 'quizztheme' => 'q.css', 'index' => 2],
            $pages[2]
        );
        $this->assertEquals(
            ['id' => $folder, 'title' => 'Folder', 'type_node' => 3, 'behavior' => 0, 'leveldoc' => 2,
                'colors' => 'c.css', 'quizztheme' => 'z.css', 'index' => 3],
            $pages[3]
        );
        $this->assertSame([], getCollectionPages($top + 100000));
    }

    public function testGetAlonePagesReturnsOnlyBehaviorThreeChildren(): void
    {
        $top = $this->page('Top');
        $alone = $this->page('Alone', $top, 1, 2, 3);
        $this->page('Normal', $top, 2, 2, 0);
        $this->page('Alone elsewhere', $alone, 1, 2, 3);

        $pages = getAlonePages($top);

        $this->assertCount(1, $pages);
        $this->assertEquals(
            ['id' => $alone, 'title' => 'Alone', 'type_node' => 2, 'behavior' => 3, 'leveldoc' => 2,
                'colors' => 'white-chami.css', 'quizztheme' => 'white-quizz.css', 'index' => 0],
            $pages[0]
        );
        $this->assertSame([], getAlonePages($top + 100000));
    }
}
