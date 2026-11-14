<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

/**
 * Normalises some extra field labels, merges every duplicated extra field and adds UNIQUE(variable, item_type).
 *
 * Portals upgraded from 1.9/1.10 can hold the same (variable, item_type) field more than once (tags, timezone,
 * legal_accept, ...; see #2012). The duplicates usually carry user values, which the foreign keys of
 * extra_field_values (and of the options, tags and saved searches) do not let us delete, so they are merged first:
 *
 * - kept field, per (variable, item_type): the one that has values, then the lowest id;
 * - extra_field_values move to the kept field; when an item has a value on several of the merged fields, the
 *   most recent non-empty value is kept (then the most recent, then the highest id) and the others are deleted;
 * - extra_field_options move to the kept field; an option whose value the kept field already has is merged into it,
 *   and the same goes for tags (same text, case-insensitive; the merged tag counts its remaining links);
 * - extra_field_option_rel_field_option, extra_field_saved_search and extra_field_rel_tag point to the kept field
 *   and options; links that become identical are removed;
 * - the duplicated fields are then deleted and the unique index is created.
 *
 * Variables are compared the way the unique index compares them (case-insensitive, trailing spaces ignored).
 * All statements only carry integer ids, computed when the migration runs.
 */
final class Version20251008180700 extends AbstractMigrationChamilo
{
    public const string INDEX = 'uniq_extra_field_variable_itemtype';

    public function getDescription(): string
    {
        return 'ExtraField: fix display_text, merge duplicated (variable, item_type) fields with their values, and add unique index on (variable, item_type).';
    }

    public function up(Schema $schema): void
    {
        // Normalize labels
        // USER (item_type = 1)
        $this->addSql("UPDATE extra_field SET display_text = 'LinkedIn profile URL' WHERE item_type = 1 AND variable = 'linkedin_url'");
        $this->addSql("UPDATE extra_field SET display_text = 'Tags' WHERE item_type = 1 AND variable = 'tags'");
        $this->addSql("UPDATE extra_field SET display_text = 'Legal agreement accepted' WHERE item_type = 1 AND variable = 'legal_accept'");
        $this->addSql("UPDATE extra_field SET display_text = 'GDPR' WHERE item_type = 1 AND variable = 'gdpr'");

        // COURSE (item_type = 2)
        $this->addSql("UPDATE extra_field SET display_text = 'Video URL' WHERE item_type = 2 AND variable = 'video_url'");

        // Azure-related fields (USER)
        $this->addSql("UPDATE extra_field SET display_text = 'Organisation e-mail' WHERE item_type = 1 AND variable = 'organisationemail'");
        $this->addSql("UPDATE extra_field SET display_text = 'Azure ID (mailNickname)' WHERE item_type = 1 AND variable = 'azure_id'");

        if ($schema->hasTable('extra_field') && $schema->getTable('extra_field')->hasIndex(self::INDEX)) {
            return;
        }

        foreach (self::mergeDuplicatesSql($this->connection) as $sql) {
            $this->addSql($sql);
        }

        // Unique index to prevent future duplicates
        $this->addSql('CREATE UNIQUE INDEX '.self::INDEX.' ON extra_field (variable, item_type)');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('extra_field') && $schema->getTable('extra_field')->hasIndex(self::INDEX)) {
            $this->addSql('DROP INDEX '.self::INDEX.' ON extra_field');
        }

        // Revert labels. The merged duplicates are not restored.
        $this->addSql("UPDATE extra_field SET display_text = 'LinkedInUrl' WHERE item_type = 1 AND variable = 'linkedin_url'");
        $this->addSql("UPDATE extra_field SET display_text = 'tags' WHERE item_type = 1 AND variable = 'tags'");
        $this->addSql("UPDATE extra_field SET display_text = 'Legal' WHERE item_type = 1 AND variable = 'legal_accept'");
        $this->addSql("UPDATE extra_field SET display_text = 'GDPR compliance' WHERE item_type = 1 AND variable = 'gdpr'");
        $this->addSql("UPDATE extra_field SET display_text = 'VideoUrl' WHERE item_type = 2 AND variable = 'video_url'");
    }

    /**
     * The statements that merge every duplicated extra field into the kept one, in execution order.
     *
     * @return list<string>
     */
    public static function mergeDuplicatesSql(Connection $connection): array
    {
        $groups = [];
        foreach ($connection->fetchAllAssociative('SELECT id, variable, item_type FROM extra_field ORDER BY id') as $field) {
            $key = (int) $field['item_type'].'|'.mb_strtolower(rtrim((string) $field['variable']));
            $groups[$key][] = (int) $field['id'];
        }

        $groups = array_filter($groups, static fn (array $ids): bool => \count($ids) > 1);
        if ([] === $groups) {
            return [];
        }

        $valueCounts = [];
        foreach ($connection->fetchAllAssociative('SELECT field_id, COUNT(*) AS total FROM extra_field_values WHERE field_id IS NOT NULL GROUP BY field_id') as $row) {
            $valueCounts[(int) $row['field_id']] = (int) $row['total'];
        }

        $sql = [];
        foreach ($groups as $ids) {
            $keptId = self::pickKeptField($ids, $valueCounts);
            $duplicateIds = array_values(array_diff($ids, [$keptId]));

            array_push(
                $sql,
                ...self::mergeValuesSql($connection, $keptId, $duplicateIds),
                ...self::mergeOptionsSql($connection, $keptId, $duplicateIds),
                ...self::mergeTagsSql($connection, $keptId, $duplicateIds),
            );

            $in = implode(', ', $duplicateIds);
            $sql[] = "UPDATE extra_field_saved_search SET field_id = $keptId WHERE field_id IN ($in)";
            $sql[] = "DELETE FROM extra_field WHERE id IN ($in)";
        }

        return $sql;
    }

    /**
     * @param list<int>       $ids         ascending
     * @param array<int, int> $valueCounts
     */
    private static function pickKeptField(array $ids, array $valueCounts): int
    {
        foreach ($ids as $id) {
            if (($valueCounts[$id] ?? 0) > 0) {
                return $id;
            }
        }

        return $ids[0];
    }

    /**
     * @param list<int> $duplicateIds
     *
     * @return list<string>
     */
    private static function mergeValuesSql(Connection $connection, int $keptId, array $duplicateIds): array
    {
        $fieldIds = implode(', ', [$keptId, ...$duplicateIds]);
        $rows = $connection->fetchAllAssociative(
            "SELECT id, field_id, item_id, field_value, updated_at FROM extra_field_values WHERE field_id IN ($fieldIds)"
        );

        $byItem = [];
        foreach ($rows as $row) {
            $byItem[(int) $row['item_id']][] = $row;
        }

        $moveIds = [];
        $deleteIds = [];
        foreach ($byItem as $itemRows) {
            $onDuplicate = array_filter($itemRows, static fn (array $row): bool => (int) $row['field_id'] !== $keptId);
            if ([] === $onDuplicate) {
                // Values only on the kept field are left as they are.
                continue;
            }

            usort($itemRows, [self::class, 'compareValues']);
            $winner = array_shift($itemRows);
            if ((int) $winner['field_id'] !== $keptId) {
                $moveIds[] = (int) $winner['id'];
            }

            foreach ($itemRows as $loser) {
                $deleteIds[] = (int) $loser['id'];
            }
        }

        $sql = [];
        if ([] !== $deleteIds) {
            $sql[] = 'DELETE FROM extra_field_values WHERE id IN ('.implode(', ', $deleteIds).')';
        }

        if ([] !== $moveIds) {
            $sql[] = "UPDATE extra_field_values SET field_id = $keptId WHERE id IN (".implode(', ', $moveIds).')';
        }

        return $sql;
    }

    /**
     * Best value first: non-empty, then the most recently updated, then the highest id.
     *
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    private static function compareValues(array $a, array $b): int
    {
        $aEmpty = '' === trim((string) $a['field_value']);
        $bEmpty = '' === trim((string) $b['field_value']);
        if ($aEmpty !== $bEmpty) {
            return $aEmpty ? 1 : -1;
        }

        return [(string) $b['updated_at'], (int) $b['id']] <=> [(string) $a['updated_at'], (int) $a['id']];
    }

    /**
     * @param list<int> $duplicateIds
     *
     * @return list<string>
     */
    private static function mergeOptionsSql(Connection $connection, int $keptId, array $duplicateIds): array
    {
        $in = implode(', ', $duplicateIds);
        $keptOptions = [];
        foreach ($connection->fetchAllAssociative("SELECT id, option_value FROM extra_field_options WHERE field_id = $keptId ORDER BY id") as $option) {
            $keptOptions[(string) $option['option_value']] ??= (int) $option['id'];
        }

        $moveIds = [];
        $optionMap = [];
        foreach ($connection->fetchAllAssociative("SELECT id, option_value FROM extra_field_options WHERE field_id IN ($in) ORDER BY id") as $option) {
            $optionId = (int) $option['id'];
            $value = (string) $option['option_value'];
            if (isset($keptOptions[$value])) {
                $optionMap[$optionId] = $keptOptions[$value];

                continue;
            }

            $keptOptions[$value] = $optionId;
            $moveIds[] = $optionId;
        }

        $sql = [];
        if ([] !== $moveIds) {
            $sql[] = "UPDATE extra_field_options SET field_id = $keptId WHERE id IN (".implode(', ', $moveIds).')';
        }

        array_push($sql, ...self::mergeOptionRelationsSql($connection, $keptId, $duplicateIds, $optionMap));

        if ([] !== $optionMap) {
            $sql[] = 'DELETE FROM extra_field_options WHERE id IN ('.implode(', ', array_keys($optionMap)).')';
        }

        return $sql;
    }

    /**
     * Points the option relations at the kept field and options. A relation that would then repeat another one
     * (their unique index covers field, role and both options) is deleted instead, before anything is updated.
     *
     * @param list<int>       $duplicateIds
     * @param array<int, int> $optionMap    merged option id => kept option id
     *
     * @return list<string>
     */
    private static function mergeOptionRelationsSql(Connection $connection, int $keptId, array $duplicateIds, array $optionMap): array
    {
        $where = 'field_id IN ('.implode(', ', [$keptId, ...$duplicateIds]).')';
        if ([] !== $optionMap) {
            $merged = implode(', ', array_keys($optionMap));
            $where .= " OR field_option_id IN ($merged) OR related_field_option_id IN ($merged)";
        }

        $relations = $connection->fetchAllAssociative(
            "SELECT id, field_id, role_id, field_option_id, related_field_option_id FROM extra_field_option_rel_field_option WHERE $where ORDER BY id"
        );

        $remap = static function (mixed $optionId) use ($optionMap): ?int {
            return null === $optionId ? null : ($optionMap[(int) $optionId] ?? (int) $optionId);
        };

        $taken = [];
        $changed = [];
        foreach ($relations as $relation) {
            $fieldId = null === $relation['field_id'] ? null : (int) $relation['field_id'];
            $target = [
                \in_array($fieldId, $duplicateIds, true) ? $keptId : $fieldId,
                null === $relation['role_id'] ? null : (int) $relation['role_id'],
                $remap($relation['field_option_id']),
                $remap($relation['related_field_option_id']),
            ];
            $current = [
                $fieldId,
                $target[1],
                null === $relation['field_option_id'] ? null : (int) $relation['field_option_id'],
                null === $relation['related_field_option_id'] ? null : (int) $relation['related_field_option_id'],
            ];

            if ($target === $current) {
                $taken[serialize($target)] = true;

                continue;
            }

            $changed[(int) $relation['id']] = $target;
        }

        $sql = [];
        foreach ($changed as $relationId => $target) {
            $key = serialize($target);
            if (isset($taken[$key])) {
                $sql[] = "DELETE FROM extra_field_option_rel_field_option WHERE id = $relationId";

                continue;
            }

            $taken[$key] = true;
            $sql[] = \sprintf(
                'UPDATE extra_field_option_rel_field_option SET field_id = %s, field_option_id = %s, related_field_option_id = %s WHERE id = %d',
                self::sqlInt($target[0]),
                self::sqlInt($target[2]),
                self::sqlInt($target[3]),
                $relationId,
            );
        }

        return $sql;
    }

    private static function sqlInt(?int $value): string
    {
        return null === $value ? 'NULL' : (string) $value;
    }

    /**
     * @param list<int> $duplicateIds
     *
     * @return list<string>
     */
    private static function mergeTagsSql(Connection $connection, int $keptId, array $duplicateIds): array
    {
        $in = implode(', ', $duplicateIds);
        $keptTags = [];
        foreach ($connection->fetchAllAssociative("SELECT id, tag FROM tag WHERE field_id = $keptId ORDER BY id") as $tag) {
            $keptTags[mb_strtolower((string) $tag['tag'])] ??= (int) $tag['id'];
        }

        $sql = [];
        $moveIds = [];
        $mergedIds = [];
        $mergedInto = [];
        foreach ($connection->fetchAllAssociative("SELECT id, tag FROM tag WHERE field_id IN ($in) ORDER BY id") as $tag) {
            $tagId = (int) $tag['id'];
            $key = mb_strtolower((string) $tag['tag']);
            if (isset($keptTags[$key])) {
                $target = $keptTags[$key];
                $sql[] = "UPDATE extra_field_rel_tag SET tag_id = $target WHERE tag_id = $tagId";
                $mergedInto[$target] = $target;
                $mergedIds[] = $tagId;

                continue;
            }

            $keptTags[$key] = $tagId;
            $moveIds[] = $tagId;
        }

        if ([] !== $moveIds) {
            $sql[] = "UPDATE tag SET field_id = $keptId WHERE id IN (".implode(', ', $moveIds).')';
        }

        $sql[] = "UPDATE extra_field_rel_tag SET field_id = $keptId WHERE field_id IN ($in)";
        // The same item can now carry the same tag twice: keep the oldest link.
        $sql[] = 'DELETE FROM extra_field_rel_tag WHERE id IN (
            SELECT dup_id FROM (
                SELECT d.id AS dup_id
                FROM extra_field_rel_tag d
                INNER JOIN extra_field_rel_tag k
                    ON k.field_id = d.field_id
                    AND k.item_id = d.item_id
                    AND k.tag_id = d.tag_id
                    AND k.id < d.id
                WHERE d.field_id = '.$keptId.'
            ) AS duplicates
        )';

        if ([] !== $mergedIds) {
            $sql[] = 'DELETE FROM tag WHERE id IN ('.implode(', ', $mergedIds).')';
            // A merged tag counts the links it now has.
            $sql[] = 'UPDATE tag SET count = (SELECT COUNT(*) FROM extra_field_rel_tag r WHERE r.tag_id = tag.id) WHERE id IN ('.implode(', ', $mergedInto).')';
        }

        return $sql;
    }
}
