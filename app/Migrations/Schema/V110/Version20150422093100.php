<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150422093100
 *
 * @package Application\Migrations\Schema\V110
 */
class Version20150422093100 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        // Fix ids

        // Fix c_lp_item
        $connection = $this->connection;

        $sql = "SELECT * FROM c_lp_item";
        $result = $connection->fetchAll($sql);
        foreach ($result as $item) {
            $courseId = $item['c_id'];
            $iid = isset($item['iid']) ? $item['iid'] : 0;
            $ref = isset($item['ref']) ? $item['ref'] : 0;
            $sql = null;

            $newId = '';

            switch ($item['item_type']) {
                case TOOL_LINK:
                    $sql = "SELECT * c_link WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    if ($data) {
                        $newId = $data['iid'];
                    }
                    break;
                case TOOL_STUDENTPUBLICATION:
                    $sql = "SELECT * c_student_publication WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    if ($data) {
                        $newId = $data['iid'];
                    }
                    break;
                case TOOL_QUIZ:
                    $sql = "SELECT * c_quiz WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    if ($data) {
                        $newId = $data['iid'];
                    }
                    break;
                case TOOL_DOCUMENT:
                    $sql = "SELECT * c_document WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    if ($data) {
                        $newId = $data['iid'];
                    }
                    break;
                case TOOL_FORUM:
                    $sql = "SELECT * c_forum_forum WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    if ($data) {
                        $newId = $data['iid'];
                    }
                    break;
                case 'thread':
                    $sql = "SELECT * c_forum_thread WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    if ($data) {
                        $newId = $data['iid'];
                    }
                    break;
            }

            if (!empty($sql) && !empty($newId) && !empty($iid)) {
                $sql = "UPDATE c_lp_item SET ref = $newId WHERE iid = $iid";
                $connection->executeQuery($sql);
            }
        }

        // Set NULL if session = 0
        $sql = "UPDATE c_item_property SET session_id = NULL WHERE session_id = 0";
        $connection->executeQuery($sql);

        // Set NULL if group = 0
        $sql = "UPDATE c_item_property SET to_group_id = NULL WHERE to_group_id = 0";
        $connection->executeQuery($sql);

        // Set NULL if insert_user_id = 0
        $sql = "UPDATE c_item_property SET insert_user_id = NULL WHERE insert_user_id = 0";
        $connection->executeQuery($sql);

        // Delete session data of sessions that don't exist.
        $sql = "DELETE FROM c_item_property
                WHERE session_id IS NOT NULL AND session_id NOT IN (SELECT id FROM session)";
        $connection->executeQuery($sql);

        // Delete group data of groups that don't exist.
        $sql = "DELETE FROM c_item_property
                WHERE to_group_id IS NOT NULL AND to_group_id NOT IN (SELECT DISTINCT id FROM c_group_info)";
        $connection->executeQuery($sql);

        // This updates the group_id with c_group_info.iid instead of c_group_info.id

        $groupTableTofix = [
            'c_group_rel_user',
            'c_group_rel_tutor',
            'c_permission_group',
            'c_role_group',
            'c_survey_invitation',
            'c_attendance_calendar_rel_group'
        ];

        foreach ($groupTableTofix as $table) {
            $sql = "SELECT * FROM $table";
            $result = $connection->fetchAll($sql);
            foreach ($result as $item) {
                $iid = $item['iid'];
                $courseId = $item['c_id'];
                $groupId = intval($item['group_id']);

                // Fix group id
                if (!empty($groupId)) {
                    $sql = "SELECT * c_group_info
                            WHERE c_id = $courseId AND id = $groupId LIMIT 1";
                    $data = $connection->fetchArray($sql);
                    if (!empty($data)) {
                        $newGroupId = $data['iid'];
                        $sql = "UPDATE $table SET group_id = $newGroupId
                                WHERE iid = $iid";
                        $connection->executeQuery($sql);
                    } else {
                        // The group does not exists clean this record
                        $sql = "DELETE FROM $table WHERE iid = $iid";
                        $connection->executeQuery($sql);
                    }
                }
            }
        }

        // Fix c_item_property

        $sql = "SELECT * FROM c_item_property";
        $result = $connection->fetchAll($sql);
        foreach ($result as $item) {
            $courseId = $item['c_id'];
            $sessionId = intval($item['session_id']);
            $groupId = intval($item['to_group_id']);
            $iid = $item['iid'];
            $ref = $item['ref'];

            // Fix group id

            if (!empty($groupId)) {
                $sql = "SELECT * c_group_info WHERE c_id = $courseId AND id = $groupId";
                $data = $connection->fetchArray($sql);
                if (!empty($data)) {
                    $newGroupId = $data['iid'];
                    $sql = "UPDATE c_item_property SET to_group_id = $newGroupId
                            WHERE iid = $iid";
                    $connection->executeQuery($sql);
                } else {
                    // The group does not exists clean this record
                    $sql = "DELETE FROM c_item_property WHERE iid = $iid";
                    $connection->executeQuery($sql);
                }
            }

            $sql = null;

            switch ($item['tool']) {
                case TOOL_LINK:
                    $sql = "SELECT * c_link WHERE c_id = $courseId AND id = $ref ";
                    $data = $connection->fetchArray($sql);
                    $newId = $data['iid'];
                    break;
                case TOOL_STUDENTPUBLICATION:
                    $sql = "SELECT * c_student_publication WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    $newId = $data['iid'];
                    break;
                case TOOL_QUIZ:
                    $sql = "SELECT * c_quiz WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    $newId = $data['iid'];
                    break;
                case TOOL_DOCUMENT:
                    $sql = "SELECT * c_document WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    $newId = $data['iid'];
                    break;
                case TOOL_FORUM:
                    $sql = "SELECT * c_forum_forum WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    $newId = $data['iid'];
                    break;
                case 'thread':
                    $sql = "SELECT * c_forum_thread WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    $newId = $data['iid'];
                    break;
            }

            if (!empty($sql)) {
                $sql = "UPDATE c_item_property SET ref = $newId WHERE iid = $iid";
                $connection->executeQuery($sql);
            }
        }

        // Fix gradebook_link
        $sql = "SELECT * FROM gradebook_link";
        $result = $connection->fetchAll($sql);
        foreach ($result as $item) {
            $courseId = $item['c_id'];
            $ref = $item['ref_id'];
            $sql = null;

            switch ($item['tool']) {
                case TOOL_LINK:
                    $sql = "SELECT * c_link WHERE c_id = $courseId AND id = $ref ";
                    $data = $connection->fetchArray($sql);
                    $newId = $data['iid'];
                    break;
                case TOOL_STUDENTPUBLICATION:
                    $sql = "SELECT * c_student_publication WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    $newId = $data['iid'];
                    break;
                case TOOL_QUIZ:
                    $sql = "SELECT * c_quiz WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    $newId = $data['iid'];
                    break;
                case TOOL_DOCUMENT:
                    $sql = "SELECT * c_document WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    $newId = $data['iid'];
                    break;
                case TOOL_FORUM:
                    $sql = "SELECT * c_forum_forum WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    $newId = $data['iid'];
                    break;
                case 'thread':
                    $sql = "SELECT * c_forum_thread WHERE c_id = $courseId AND id = $ref";
                    $data = $connection->fetchArray($sql);
                    $newId = $data['iid'];
                    break;
            }

            if (!empty($sql)) {
                $sql = "UPDATE c_item_property SET ref_id = $newId WHERE iid = $iid";
                $connection->executeQuery($sql);
            }
        }
    }

    /**
     * We don't allow downgrades yet
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
