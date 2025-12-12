<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251201173000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Pre-fill resource_link.parent_id using the existing resource_node parent hierarchy per context.';
    }

    public function up(Schema $schema): void
    {
        /**
         * This query copies the existing tree structure from resource_node.parent_id
         * into resource_link.parent_id, per context
         * (course, session, usergroup, group, user, resource_type_group).
         *
         * For each resource_link (rl), we:
         *  - join its node (rn),
         *  - locate the parent node (rn.parent_id),
         *  - then find the corresponding resource_link (parent_rl) of that parent node
         *    in the same context,
         *  - and store parent_rl.id into rl.parent_id.
         *
         * Root nodes (rn.parent_id IS NULL) are left with parent_id = NULL.
         *
         * This keeps the same visible hierarchy as resource_node for all existing links,
         * and will later allow the tree to diverge per context when moving shared documents.
         */
        $sql = <<<'SQL'
UPDATE resource_link rl
INNER JOIN resource_node rn ON rn.id = rl.resource_node_id
LEFT JOIN resource_link parent_rl
    ON parent_rl.resource_node_id = rn.parent_id
   AND (
        (parent_rl.c_id = rl.c_id)
        OR (parent_rl.c_id IS NULL AND rl.c_id IS NULL)
   )
   AND (
        (parent_rl.session_id = rl.session_id)
        OR (parent_rl.session_id IS NULL AND rl.session_id IS NULL)
   )
   AND (
        (parent_rl.usergroup_id = rl.usergroup_id)
        OR (parent_rl.usergroup_id IS NULL AND rl.usergroup_id IS NULL)
   )
   AND (
        (parent_rl.group_id = rl.group_id)
        OR (parent_rl.group_id IS NULL AND rl.group_id IS NULL)
   )
   AND (
        (parent_rl.user_id = rl.user_id)
        OR (parent_rl.user_id IS NULL AND rl.user_id IS NULL)
   )
   AND parent_rl.resource_type_group = rl.resource_type_group
SET rl.parent_id = parent_rl.id
WHERE rn.parent_id IS NOT NULL
  AND rl.parent_id IS NULL
SQL;

        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
        // Reset parent_id to NULL if we rollback this pre-fill.
        // This does not touch the schema (column and FK remain).
        $this->addSql('UPDATE resource_link SET parent_id = NULL');
    }
}
