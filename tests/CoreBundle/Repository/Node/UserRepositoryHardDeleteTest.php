<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Chamilo\Tests\CoreBundle\Service\GenericRowInsertTrait;
use Chamilo\Tests\CoreBundle\Service\RawReportingFixturesTrait;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The raw-SQL clean-up behind the permanent deletion of a user (UserRepository::deleteUser($user, true), reached
 * from the admin "Delete permanently" action and after "unify users"): every relation listed in getRelations()
 * is deleted or handed to the fallback user, and every table that references user.id without ON DELETE
 * CASCADE/SET NULL is covered, otherwise "DELETE FROM user" fails on a foreign key. Rows are generated from
 * information_schema (GenericRowInsertTrait), FK checks are off while inserting. Tag sv3_.
 */
final class UserRepositoryHardDeleteTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;
    use GenericRowInsertTrait;

    private UserRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repo = self::getContainer()->get(UserRepository::class);
    }

    private function relations(): array
    {
        return (new ReflectionMethod(UserRepository::class, 'getRelations'))->invoke($this->repo);
    }

    public function testEveryForeignKeyToUserWithoutCascadeIsCoveredByTheCleanUp(): void
    {
        $covered = array_map(static fn (array $r): string => $r['table'].'.'.$r['field'], $this->relations());
        // Handled by deleteUser() itself, not by getRelations().
        $explicit = ['usergroup_rel_user.user_id', 'resource_node.creator_id', 'message.user_sender_id'];
        $rows = $this->conn()->fetchAllAssociative(
            "SELECT k.table_name AS t, k.column_name AS c FROM information_schema.key_column_usage k
             INNER JOIN information_schema.referential_constraints r ON r.constraint_name = k.constraint_name AND r.constraint_schema = k.table_schema
             WHERE k.table_schema = DATABASE() AND k.referenced_table_name = 'user' AND k.referenced_column_name = 'id'
               AND r.delete_rule IN ('NO ACTION', 'RESTRICT') ORDER BY 1, 2"
        );

        $missing = [];
        foreach ($rows as $row) {
            $key = $row['t'].'.'.$row['c'];
            if (!\in_array($key, $covered, true) && !\in_array($key, $explicit, true)) {
                $missing[] = $key;
            }
        }

        $this->assertSame([], $missing, 'These foreign keys would make the permanent deletion of a user fail');
    }

    public function testEveryRelationIsDeletedOrReassignedToTheFallbackUser(): void
    {
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            // The method moves the resource nodes of the deleted user, so both users need one: borrow free nodes.
            $nodes = array_map('intval', $this->conn()->fetchFirstColumn('SELECT id FROM resource_node WHERE id NOT IN (SELECT resource_node_id FROM user WHERE resource_node_id IS NOT NULL) ORDER BY id DESC LIMIT 2'));
            $victim = $this->rawUser('victim', 5, 1, null);
            $fallback = $this->rawUser('fallback', 5, 1, null);
            $this->conn()->executeStatement('UPDATE user SET resource_node_id = ? WHERE id = ?', [$nodes[0], $victim]);
            $this->conn()->executeStatement('UPDATE user SET resource_node_id = ? WHERE id = ?', [$nodes[1], $fallback]);
            $this->fresh();

            $relations = $this->relations();
            $this->assertGreaterThan(80, \count($relations));
            foreach ($relations as $r) {
                $this->insert($r['table'], [$r['field'] => $victim]);
            }

            (new ReflectionMethod(UserRepository::class, 'reassignUserResourcesToFallbackSQL'))->invoke(
                $this->repo,
                $this->entityUser($victim),
                $this->entityUser($fallback),
                $this->conn()
            );

            foreach ($relations as $r) {
                $left = (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM `{$r['table']}` WHERE `{$r['field']}` = ?", [$victim]);
                $this->assertSame(0, $left, $r['table'].'.'.$r['field'].' still points to the deleted user');
                if ('update' === $r['action']) {
                    $this->assertGreaterThanOrEqual(1, (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM `{$r['table']}` WHERE `{$r['field']}` = ?", [$fallback]), $r['table'].'.'.$r['field'].' not handed to the fallback');
                }
            }
        } finally {
            $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
}
