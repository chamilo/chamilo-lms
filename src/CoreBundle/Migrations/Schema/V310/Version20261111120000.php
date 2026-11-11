<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * A user can belong to a class (usergroup) only once. An earlier bug of UserGroupModel::add_users_to_groups()
 * (and some bulk imports) inserted the same (usergroup_id, user_id) pair several times, because nothing in the
 * schema forbade it.
 *
 * up(): 1. removes the duplicate rows, 2. adds UNIQUE(usergroup_id, user_id). Idempotent: nothing is done when the
 * index already exists. down() drops the index only (the removed duplicates are not restorable by design).
 *
 * Which duplicate is kept, per (usergroup_id, user_id) pair: the row with the strongest relation, then the lowest id
 * (the oldest). The strength order follows what the relation lets the user do in the class:
 * admin (1) > moderator (5) > reader (2) > human resources manager (7) > pending invitation (3) >
 * pending request sent by the user (4) > anonymous (6) > anything else. Rows with a NULL user or usergroup are
 * orphans the unique index does not constrain, and are left alone.
 */
final class Version20261111120000 extends AbstractMigrationChamilo
{
    public const string TABLE = 'usergroup_rel_user';
    public const string INDEX = 'uniq_usergroup_user';

    /**
     * SQL ranking of relation_type: the lower, the stronger.
     */
    private const string RANK = 'CASE %s.relation_type WHEN 1 THEN 0 WHEN 5 THEN 1 WHEN 2 THEN 2 WHEN 7 THEN 3 WHEN 3 THEN 4 WHEN 4 THEN 5 WHEN 6 THEN 6 ELSE 9 END';

    public function getDescription(): string
    {
        return 'Remove duplicate usergroup_rel_user rows (keep strongest relation, then lowest id) and add UNIQUE(usergroup_id, user_id).';
    }

    /**
     * Deletes every row of a (usergroup_id, user_id) pair except the kept one. The self-join is wrapped in a derived
     * table so that MySQL/MariaDB accept deleting from the table the subquery reads.
     */
    public static function dedupeSql(string $table = self::TABLE): string
    {
        $dRank = \sprintf(self::RANK, 'd');
        $kRank = \sprintf(self::RANK, 'k');

        return "DELETE FROM $table WHERE id IN (
            SELECT dup_id FROM (
                SELECT d.id AS dup_id
                FROM $table d
                INNER JOIN $table k
                    ON k.usergroup_id = d.usergroup_id
                    AND k.user_id = d.user_id
                    AND k.id <> d.id
                WHERE $kRank < $dRank
                    OR ($kRank = $dRank AND k.id < d.id)
            ) AS duplicates
        )";
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(self::TABLE)) {
            return;
        }

        if ($schema->getTable(self::TABLE)->hasIndex(self::INDEX)) {
            return;
        }

        $this->addSql(self::dedupeSql());
        $this->addSql('ALTER TABLE '.self::TABLE.' ADD UNIQUE INDEX '.self::INDEX.' (usergroup_id, user_id)');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(self::TABLE) || !$schema->getTable(self::TABLE)->hasIndex(self::INDEX)) {
            return;
        }

        $this->addSql('ALTER TABLE '.self::TABLE.' DROP INDEX '.self::INDEX);
    }
}
