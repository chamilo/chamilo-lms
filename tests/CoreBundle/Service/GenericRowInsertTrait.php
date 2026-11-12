<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

/**
 * Inserts a row into any table from information_schema: the given values are used, every other NOT NULL column
 * without default gets a filler (0, a fixed date, "sv3<seq>"). Needs conn() (RawReportingFixturesTrait) and the
 * test to switch foreign keys off when the parents do not matter. Filler strings are unique per call, so unique
 * keys on text columns do not collide.
 */
trait GenericRowInsertTrait
{
    private int $fillSeq = 0;

    private function insert(string $table, array $values): void
    {
        $cols = $this->conn()->fetchAllAssociative(
            'SELECT column_name AS n, data_type AS t, column_type AS ct, is_nullable AS nul, column_default AS d, extra AS x
             FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ordinal_position',
            [$table]
        );
        $this->assertNotEmpty($cols, "Table {$table} must exist");
        $names = [];
        $params = [];
        foreach ($cols as $c) {
            $n = $c['n'];
            if (\array_key_exists($n, $values)) {
                $names[] = '`'.$n.'`';
                $params[] = $values[$n];

                continue;
            }
            if (str_contains((string) $c['x'], 'auto_increment') || 'YES' === $c['nul'] || null !== $c['d']) {
                continue;
            }
            $names[] = '`'.$n.'`';
            $params[] = $this->filler($c, ++$this->fillSeq);
        }
        $this->conn()->executeStatement(
            'INSERT INTO `'.$table.'` ('.implode(', ', $names).') VALUES ('.implode(', ', array_fill(0, \count($params), '?')).')',
            $params
        );
    }

    private function filler(array $c, int $seq): mixed
    {
        return match (true) {
            \in_array($c['t'], ['int', 'bigint', 'smallint', 'tinyint', 'mediumint', 'double', 'float', 'decimal'], true) => 0,
            'date' === $c['t'] => '2020-01-01',
            \in_array($c['t'], ['datetime', 'timestamp'], true) => '2020-01-01 00:00:00',
            'json' === $c['t'] => '[]',
            'enum' === $c['t'] => explode("','", trim(substr($c['ct'], 5, -1), "'"))[0],
            \in_array($c['t'], ['binary', 'varbinary', 'blob', 'longblob'], true) => 'x'.$seq,
            default => 'sv3'.$seq,
        };
    }
}
