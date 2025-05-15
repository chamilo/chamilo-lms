<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;
use Chamilo\UserBundle\Entity\User;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;

/**
 * Class UserStatus.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class UserStatus implements TransformPropertyInterface
{
    public const ROLES = [
        User::TEACHER => ['manager', 'coursecreator', 'editingteacher', 'teacher'],
        User::STUDENT => ['student', 'user'],
        INVITEE => ['guest'],
    ];

    /**
     * @throws \Exception
     *
     * @return int
     */
    public function transform(array $sourceData)
    {
        try {
            $connection = \MigrationMoodlePlugin::create()->getConnection();
        } catch (DBALException $e) {
            throw new \Exception('Unable to start connection.', 0, $e);
        }

        $query = "SELECT DISTINCT r.archetype FROM mdl_role r
            INNER JOIN mdl_role_assignments ra ON r.id = ra.roleid WHERE ra.userid = ?";

        $userId = (int) $sourceData['id'];

        try {
            $statement = $connection->executeQuery($query, [$userId]);
        } catch (DBALException $e) {
            throw new \Exception("Unable to execute query \"$query\".", 0, $e);
        }

        $userRoles = $statement->fetchAll(FetchMode::ASSOCIATIVE);

        $connection->close();

        foreach (self::ROLES as $chamiloRole => $moodleRoles) {
            foreach ($userRoles as $userRole) {
                if (in_array($userRole['archetype'], $moodleRoles)) {
                    return $chamiloRole;
                }
            }
        }

        return User::STUDENT;
    }
}
