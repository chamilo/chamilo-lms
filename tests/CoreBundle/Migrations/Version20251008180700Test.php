<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Migrations;

use Chamilo\CoreBundle\Migrations\Schema\V200\Version20251008180700;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * #2012: portals upgraded from 1.9/1.10 hold duplicated extra fields (tags, timezone, legal_accept...), with user
 * values on both copies. The migration must merge them, keeping the users' values, before it creates
 * UNIQUE(variable, item_type). Runs on SQLite in memory with the foreign keys of the real schema (RESTRICT).
 */
final class Version20251008180700Test extends TestCase
{
    private const int USER = 1;
    private const int COURSE = 2;

    private Connection $connection;

    protected function setUp(): void
    {
        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $configuration);
        $this->connection->executeStatement('PRAGMA foreign_keys = ON');

        foreach ([
            'CREATE TABLE extra_field (id INTEGER PRIMARY KEY, variable VARCHAR(255) NOT NULL, item_type INTEGER NOT NULL, display_text VARCHAR(255))',
            'CREATE TABLE extra_field_values (id INTEGER PRIMARY KEY, field_id INTEGER REFERENCES extra_field (id), item_id INTEGER NOT NULL, field_value TEXT, updated_at DATETIME NOT NULL)',
            'CREATE TABLE extra_field_options (id INTEGER PRIMARY KEY, field_id INTEGER REFERENCES extra_field (id), option_value TEXT)',
            'CREATE TABLE extra_field_option_rel_field_option (id INTEGER PRIMARY KEY, field_option_id INTEGER REFERENCES extra_field_options (id), related_field_option_id INTEGER REFERENCES extra_field_options (id), field_id INTEGER REFERENCES extra_field (id), role_id INTEGER)',
            'CREATE UNIQUE INDEX idx ON extra_field_option_rel_field_option (field_id, role_id, field_option_id, related_field_option_id)',
            'CREATE TABLE extra_field_saved_search (id INTEGER PRIMARY KEY, field_id INTEGER REFERENCES extra_field (id), user_id INTEGER)',
            'CREATE TABLE tag (id INTEGER PRIMARY KEY, field_id INTEGER REFERENCES extra_field (id) ON DELETE CASCADE, tag VARCHAR(255) NOT NULL, count INTEGER NOT NULL)',
            'CREATE TABLE extra_field_rel_tag (id INTEGER PRIMARY KEY, field_id INTEGER REFERENCES extra_field (id) ON DELETE CASCADE, tag_id INTEGER REFERENCES tag (id) ON DELETE CASCADE, item_id INTEGER NOT NULL)',
        ] as $ddl) {
            $this->connection->executeStatement($ddl);
        }
    }

    protected function tearDown(): void
    {
        $this->connection->close();
    }

    public function testUsersKeepTheirValuesWhenTheirFieldIsMerged(): void
    {
        $this->field(1, 'timezone', self::USER);
        $this->field(2, 'timezone', self::USER);
        $this->field(3, 'timezone', self::COURSE); // other item type: not a duplicate
        $this->value(10, 1, 100, 'Europe/Brussels', '2020-01-01 10:00:00'); // only on the kept field
        $this->value(11, 2, 101, 'America/Lima', '2020-01-01 10:00:00');    // only on the duplicate
        $this->value(12, 1, 102, 'Europe/Paris', '2019-01-01 10:00:00');    // on both: the newer one wins
        $this->value(13, 2, 102, 'Europe/Madrid', '2021-01-01 10:00:00');
        $this->value(14, 1, 103, 'Asia/Tokyo', '2019-01-01 10:00:00');      // on both: a newer empty value loses
        $this->value(15, 2, 103, '', '2022-01-01 10:00:00');
        $this->value(16, 3, 100, 'course value', '2020-01-01 10:00:00');

        $this->migrate();

        $this->assertSame([[1, 'timezone', 1], [3, 'timezone', 2]], $this->fields());
        $this->assertSame(
            [
                100 => 'Europe/Brussels',
                101 => 'America/Lima',
                102 => 'Europe/Madrid',
                103 => 'Asia/Tokyo',
            ],
            $this->valuesOf(1),
        );
        $this->assertSame([100 => 'course value'], $this->valuesOf(3));
        $this->assertTrue($this->hasUniqueIndex());
    }

    public function testTheFieldWithValuesIsKeptEvenWhenItIsNotTheOldest(): void
    {
        $this->field(5, 'legal_accept', self::USER);
        $this->field(6, 'Legal_Accept ', self::USER); // same for the unique index: case and trailing space
        $this->field(7, 'legal_accept', self::USER);
        $this->value(20, 6, 100, '1:1:2017-01-01', '2017-01-01 00:00:00');
        $this->savedSearch(1, 5);

        $this->migrate();

        $this->assertSame([[6, 'Legal_Accept ', 1]], $this->fields());
        $this->assertSame([100 => '1:1:2017-01-01'], $this->valuesOf(6));
        $this->assertSame([6], array_map('intval', $this->connection->fetchFirstColumn('SELECT field_id FROM extra_field_saved_search')));
    }

    public function testOptionsAndTheirRelationsAreMerged(): void
    {
        $this->field(1, 'diet', self::USER);
        $this->field(2, 'diet', self::USER);
        $this->option(10, 1, 'vegan');
        $this->option(11, 2, 'vegan');      // same value: merged into 10
        $this->option(12, 2, 'halal');      // new value: moved to field 1
        $this->relation(1, 1, 10, 10, 5);
        $this->relation(2, 2, 11, 11, 5);   // becomes identical to relation 1: removed
        $this->relation(3, 2, 12, 11, 5);   // becomes (1, 12, 10)

        $this->migrate();

        $this->assertSame(
            [[10, 1, 'vegan'], [12, 1, 'halal']],
            array_map(
                static fn (array $row): array => [(int) $row['id'], (int) $row['field_id'], $row['option_value']],
                $this->connection->fetchAllAssociative('SELECT id, field_id, option_value FROM extra_field_options ORDER BY id')
            )
        );
        $this->assertSame(
            [[1, 1, 10, 10], [3, 1, 12, 10]],
            array_map(
                static fn (array $row): array => array_map('intval', array_values($row)),
                $this->connection->fetchAllAssociative('SELECT id, field_id, field_option_id, related_field_option_id FROM extra_field_option_rel_field_option ORDER BY id')
            )
        );
    }

    public function testTagsAreMergedWithoutLosingTheUsersTags(): void
    {
        $this->field(1, 'tags', self::USER);
        $this->field(2, 'tags', self::USER);
        $this->tag(10, 1, 'php', 1);
        $this->tag(11, 2, 'PHP', 2);  // same tag: merged into 10
        $this->tag(12, 2, 'vue', 1);  // moved to field 1
        $this->relTag(1, 1, 10, 100);
        $this->relTag(2, 2, 11, 100); // the same user and tag: removed
        $this->relTag(3, 2, 11, 101);
        $this->relTag(4, 2, 12, 101);

        $this->migrate();

        $this->assertSame(
            [[10, 1, 'php', 2], [12, 1, 'vue', 1]],
            array_map(
                static fn (array $row): array => [(int) $row['id'], (int) $row['field_id'], $row['tag'], (int) $row['count']],
                $this->connection->fetchAllAssociative('SELECT id, field_id, tag, count FROM tag ORDER BY id')
            )
        );
        $this->assertSame(
            [[1, 1, 10, 100], [3, 1, 10, 101], [4, 1, 12, 101]],
            array_map(
                static fn (array $row): array => array_map('intval', array_values($row)),
                $this->connection->fetchAllAssociative('SELECT id, field_id, tag_id, item_id FROM extra_field_rel_tag ORDER BY id')
            )
        );
    }

    public function testNothingIsMergedWithoutDuplicates(): void
    {
        $this->field(1, 'timezone', self::USER);
        $this->field(2, 'timezone', self::COURSE);
        $this->value(10, 1, 100, 'Europe/Brussels', '2020-01-01 10:00:00');

        $this->assertSame([], Version20251008180700::mergeDuplicatesSql($this->connection));
        $this->migrate();
        $this->assertSame([[1, 'timezone', 1], [2, 'timezone', 2]], $this->fields());
        $this->assertTrue($this->hasUniqueIndex());
    }

    public function testUpSkipsTheMergeAndTheIndexWhenTheIndexAlreadyExists(): void
    {
        $this->connection->executeStatement('CREATE UNIQUE INDEX '.Version20251008180700::INDEX.' ON extra_field (variable, item_type)');

        $statements = $this->upStatements();

        $this->assertNotEmpty($statements);
        foreach ($statements as $statement) {
            $this->assertStringStartsWith('UPDATE extra_field SET display_text', $statement);
        }
    }

    private function migrate(): void
    {
        foreach ($this->upStatements() as $statement) {
            $this->connection->executeStatement($statement);
        }
    }

    /**
     * @return list<string>
     */
    private function upStatements(): array
    {
        $migration = new Version20251008180700($this->connection, new NullLogger());
        $migration->up($this->connection->createSchemaManager()->introspectSchema());

        return array_map(static fn ($query): string => $query->getStatement(), $migration->getSql());
    }

    private function hasUniqueIndex(): bool
    {
        $indexes = $this->connection->createSchemaManager()->listTableIndexes('extra_field');

        return isset($indexes[Version20251008180700::INDEX]) && $indexes[Version20251008180700::INDEX]->isUnique();
    }

    /**
     * @return list<array{0: int, 1: string, 2: int}>
     */
    private function fields(): array
    {
        return array_map(
            static fn (array $row): array => [(int) $row['id'], $row['variable'], (int) $row['item_type']],
            $this->connection->fetchAllAssociative('SELECT id, variable, item_type FROM extra_field ORDER BY id')
        );
    }

    /**
     * @return array<int, string>
     */
    private function valuesOf(int $fieldId): array
    {
        $values = [];
        foreach ($this->connection->fetchAllAssociative('SELECT item_id, field_value FROM extra_field_values WHERE field_id = ? ORDER BY item_id', [$fieldId]) as $row) {
            $this->assertArrayNotHasKey((int) $row['item_id'], $values, 'One value per item and field.');
            $values[(int) $row['item_id']] = $row['field_value'];
        }

        return $values;
    }

    private function field(int $id, string $variable, int $itemType): void
    {
        $this->connection->insert('extra_field', ['id' => $id, 'variable' => $variable, 'item_type' => $itemType, 'display_text' => $variable]);
    }

    private function value(int $id, int $fieldId, int $itemId, string $value, string $updatedAt): void
    {
        $this->connection->insert('extra_field_values', ['id' => $id, 'field_id' => $fieldId, 'item_id' => $itemId, 'field_value' => $value, 'updated_at' => $updatedAt]);
    }

    private function option(int $id, int $fieldId, string $value): void
    {
        $this->connection->insert('extra_field_options', ['id' => $id, 'field_id' => $fieldId, 'option_value' => $value]);
    }

    private function relation(int $id, int $fieldId, int $optionId, int $relatedOptionId, int $roleId): void
    {
        $this->connection->insert('extra_field_option_rel_field_option', ['id' => $id, 'field_id' => $fieldId, 'field_option_id' => $optionId, 'related_field_option_id' => $relatedOptionId, 'role_id' => $roleId]);
    }

    private function savedSearch(int $id, int $fieldId): void
    {
        $this->connection->insert('extra_field_saved_search', ['id' => $id, 'field_id' => $fieldId, 'user_id' => 100]);
    }

    private function tag(int $id, int $fieldId, string $tag, int $count): void
    {
        $this->connection->insert('tag', ['id' => $id, 'field_id' => $fieldId, 'tag' => $tag, 'count' => $count]);
    }

    private function relTag(int $id, int $fieldId, int $tagId, int $itemId): void
    {
        $this->connection->insert('extra_field_rel_tag', ['id' => $id, 'field_id' => $fieldId, 'tag_id' => $tagId, 'item_id' => $itemId]);
    }
}
