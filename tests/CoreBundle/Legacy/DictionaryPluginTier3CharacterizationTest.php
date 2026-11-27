<?php

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use DictionaryPlugin;

/**
 * Pins the read queries of DictionaryPlugin (getTerms, getTermById): keyword filter on term OR definition, ordering by
 * term, limit, the row shape and the "no row" results.
 */
final class DictionaryPluginTier3CharacterizationTest extends PluginCharacterizationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // The plugin creates its table lazily with DDL, which cannot run inside a test (DAMA savepoint).
        PluginTestDatabase::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS plugin_dictionary (
                id INT NOT NULL AUTO_INCREMENT,
                term VARCHAR(255) NOT NULL,
                definition LONGTEXT NOT NULL,
                PRIMARY KEY (id),
                INDEX idx_plugin_dictionary_term (term)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        require_once api_get_path(SYS_PLUGIN_PATH).'Dictionary/DictionaryPlugin.php';
        $this->conn->executeStatement('DELETE FROM plugin_dictionary');
    }

    /**
     * ensureTableExists() runs CREATE TABLE IF NOT EXISTS, an implicit commit that would break the DAMA transaction:
     * the table is created in setUpBeforeClass, so the plugin used here skips it.
     */
    private function plugin(): DictionaryPlugin
    {
        return new class extends DictionaryPlugin {
            public function __construct()
            {
                parent::__construct();
            }

            public function ensureTableExists(): void {}
        };
    }

    private function term(string $term, string $definition): int
    {
        $this->conn->executeStatement(
            'INSERT INTO plugin_dictionary (term, definition) VALUES (?, ?)',
            [$term, $definition]
        );

        return (int) $this->conn->lastInsertId();
    }

    public function testGetTermsIsOrderedByTermAndHasTheLegacyRowShape(): void
    {
        $b = $this->term('Beta', 'second');
        $a = $this->term('Alpha', 'first');

        $rows = $this->plugin()->getTerms();

        $this->assertSame(
            [
                ['id' => (string) $a, 'term' => 'Alpha', 'definition' => 'first'],
                ['id' => (string) $b, 'term' => 'Beta', 'definition' => 'second'],
            ],
            $this->normalize($rows)
        );
        $this->assertSame(['id', 'term', 'definition'], array_keys($rows[0]));
    }

    public function testGetTermsKeywordMatchesTermOrDefinitionAndIsTrimmed(): void
    {
        $this->term('Apple', 'a fruit');
        $this->term('Car', 'has an engine');
        $this->term('Zed', 'the last apple letter');

        $rows = $this->plugin()->getTerms('  apple ');

        $this->assertSame(['Apple', 'Zed'], array_column($rows, 'term'));
        $this->assertSame([], $this->plugin()->getTerms('nomatch'));
    }

    public function testGetTermsKeywordWithQuotesIsHandledLiterally(): void
    {
        $this->term("O'Brien", 'a name');
        $this->term('Other', 'x');

        $this->assertSame(["O'Brien"], array_column($this->plugin()->getTerms("O'B"), 'term'));
        $this->assertSame([], $this->plugin()->getTerms("' OR 1=1 -- "));
    }

    public function testGetTermsLimit(): void
    {
        $this->term('C', 'c');
        $this->term('A', 'a');
        $this->term('B', 'b');

        $this->assertSame(['A', 'B'], array_column($this->plugin()->getTerms('', 2), 'term'));
        $this->assertSame(['A', 'B', 'C'], array_column($this->plugin()->getTerms('', 0), 'term'));
    }

    public function testGetTermByIdReturnsTheRowOrNull(): void
    {
        $id = $this->term('Gamma', 'third');
        $plugin = $this->plugin();

        $row = $plugin->getTermById($id);

        $this->assertSame(['id' => (string) $id, 'term' => 'Gamma', 'definition' => 'third'], $this->normalize([$row])[0]);
        $this->assertNull($plugin->getTermById($id + 1000));
        $this->assertNull($plugin->getTermById(0));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, string>>
     */
    private function normalize(array $rows): array
    {
        return array_map(static fn (array $row): array => array_map('strval', $row), $rows);
    }
}
