<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for the template and checkbox functions of public/main/admin/settings.lib.php.
 * Fixtures start with "zzt3".
 */
final class SettingsLibTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/admin/settings.lib.php');
        $request->setSession($session);
        Container::setRequest($request);
        if (!function_exists('getNumberOfTemplates')) {
            require_once __DIR__.'/../../../public/main/admin/settings.lib.php';
        }
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function template(string $title): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO system_template (title, comment, content, language) VALUES (?, 'c', 'content', 'en')",
            [$title]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function setting(string $variable, ?string $subkey, string $category, string $title, string $comment, ?string $value, string $type = 'textfield', int $changeable = 0): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO settings (access_url, variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_locked, access_url_changeable, confidential)
             VALUES (1, ?, ?, ?, ?, ?, ?, ?, NULL, ?, 0, ?, 0)',
            [$variable, $subkey, $type, $category, $value, $title, $comment, null === $subkey ? null : 'txt_'.$subkey, $changeable]
        );

        return (int) $this->conn()->lastInsertId();
    }

    public function testGetNumberOfTemplatesCountsEveryRow(): void
    {
        $before = (int) getNumberOfTemplates();
        $this->template('zzt3_a');
        $this->template('zzt3_b');

        $this->assertSame($before + 2, (int) getNumberOfTemplates());
        $this->assertSame(\gettype($this->conn()->fetchOne('SELECT COUNT(id) FROM system_template')), \gettype(getNumberOfTemplates()));
    }

    public function testGetTemplateDataRowShapeAndTitleTranslation(): void
    {
        $id = $this->template('zzt3_shape');
        $this->conn()->executeStatement('DELETE FROM system_template WHERE id <> ?', [$id]);

        $rows = getTemplateData(0, 10, 1, 'ASC');

        $this->assertCount(1, $rows);
        $this->assertSame(['col0', 'col1', 'col2', 0, 1, 2], array_keys($rows[0]));
        $this->assertSame($id, (int) $rows[0]['col0']);
        $this->assertSame($id, (int) $rows[0][0]);
        $this->assertSame($id, (int) $rows[0]['col2']);
        $this->assertSame('zzt3_shape', $rows[0]['col1']);
        $this->assertSame(get_lang('zzt3_shape'), $rows[0][1]);
        $this->assertSame(\gettype($this->conn()->fetchOne('SELECT id FROM system_template WHERE id = ?', [$id])), \gettype($rows[0]['col0']));
    }

    public function testGetTemplateDataSortsByTheRequestedColumnAndDirection(): void
    {
        $this->conn()->executeStatement('DELETE FROM system_template');
        $b = $this->template('zzt3_b');
        $c = $this->template('zzt3_c');
        $a = $this->template('zzt3_a');

        $ids = static fn (array $rows): array => array_map(static fn ($r) => (int) $r['col0'], $rows);

        $this->assertSame([$a, $b, $c], $ids(getTemplateData(0, 10, 1, 'ASC')));
        $this->assertSame([$c, $b, $a], $ids(getTemplateData(0, 10, 1, 'DESC')));
        $this->assertSame([$c, $b, $a], $ids(getTemplateData(0, 10, 1, 'desc')));
        $this->assertSame([$b, $c, $a], $ids(getTemplateData(0, 10, 0, 'ASC')));
        $this->assertSame([$a, $c, $b], $ids(getTemplateData(0, 10, 2, 'DESC')));
        // an unknown direction falls back to ascending
        $this->assertSame([$a, $b, $c], $ids(getTemplateData(0, 10, 1, 'sideways')));
    }

    public function testGetTemplateDataAppliesTheLimit(): void
    {
        $this->conn()->executeStatement('DELETE FROM system_template');
        $a = $this->template('zzt3_a');
        $b = $this->template('zzt3_b');
        $c = $this->template('zzt3_c');
        $d = $this->template('zzt3_d');

        $ids = static fn (array $rows): array => array_map(static fn ($r) => (int) $r['col0'], $rows);

        $this->assertSame([$b, $c], $ids(getTemplateData(1, 2, 1, 'ASC')));
        $this->assertSame([$d], $ids(getTemplateData(3, 10, 1, 'ASC')));
        $this->assertSame([], getTemplateData(1, 0, 1, 'ASC'));
        $this->assertSame([$a], $ids(getTemplateData('0', '1', '1', 'ASC')));
    }

    public function testDeleteTemplateRemovesOnlyThatTemplateAndSaysSo(): void
    {
        $a = $this->template('zzt3_del_a');
        $b = $this->template('zzt3_del_b');

        ob_start();
        deleteTemplate((string) $a);
        $out = ob_get_clean();

        $this->assertStringContainsString(get_lang('Template deleted'), $out);
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM system_template WHERE id = ?', [$a]));
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM system_template WHERE id = ?', [$b]));
    }

    public function testDeleteTemplateOfAnUnknownIdStillReportsSuccess(): void
    {
        $b = $this->template('zzt3_del_c');

        ob_start();
        deleteTemplate(99999999);
        $out = ob_get_clean();

        $this->assertStringContainsString(get_lang('Template deleted'), $out);
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM system_template WHERE id = ?', [$b]));
    }

    public function testGenerateSettingsFormChecksTheCheckboxesStoredAsTrue(): void
    {
        $this->setting('zzt3_chk', 'sa', 'Platform', 'chk title', 'chk comment', 'true', 'checkbox', 0);
        $this->setting('zzt3_chk', 'sb', 'Platform', 'chk title', 'chk comment', 'false', 'checkbox', 0);

        $html = $this->checkboxFormHtml(0);

        $this->assertMatchesRegularExpression('/name="zzt3_chk\[sa\]"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="zzt3_chk\[sb\]"[^>]*checked/', $html);
    }

    public function testGenerateSettingsFormReadsTheStoredValueOfEachSubkeyWhenChangeable(): void
    {
        $this->setting('zzt3_chg', 'sa', 'Platform', 'chg title', 'chg comment', 'false', 'checkbox', 1);
        $this->setting('zzt3_chg', 'sb', 'Platform', 'chg title', 'chg comment', 'true', 'checkbox', 1);

        $html = $this->checkboxFormHtml(1, 'zzt3_chg');

        $this->assertDoesNotMatchRegularExpression('/name="zzt3_chg\[sa\]"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/name="zzt3_chg\[sb\]"[^>]*checked/', $html);
    }

    private function checkboxFormHtml(int $changeable, string $variable = 'zzt3_chk'): string
    {
        global $_configuration, $settings_to_avoid, $convert_byte_to_mega_list;
        $_configuration = ['access_url' => 1];
        $settings_to_avoid = [];
        $convert_byte_to_mega_list = [];
        $_GET['category'] = 'Platform';

        $row = [
            'id' => 1,
            'variable' => $variable,
            'subkey' => null,
            'type' => 'checkbox',
            'category' => 'Platform',
            'selected_value' => 'false',
            'title' => 'chk title',
            'comment' => 'chk comment',
            'access_url_locked' => 0,
            'access_url_changeable' => $changeable,
        ];
        $form = generateSettingsForm([$row], []);

        return $form->toHtml();
    }
}
