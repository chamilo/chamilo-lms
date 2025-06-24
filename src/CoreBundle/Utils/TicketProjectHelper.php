<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Utils;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Bundle\SecurityBundle\Security;

use const JSON_ERROR_NONE;

class TicketProjectHelper
{
    public function __construct(
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
    ) {}

    public function userIsAllowInProject(int $projectId): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $allowRoleList = self::getAllowedRolesFromProject($projectId);

        // Check if a role was set to the project.
        // Project 1 is considered the default and is accessible to all users
        if (!empty($allowRoleList)) {
            $result = false;

            foreach ($allowRoleList as $role) {
                if ($this->security->isGranted($role)) {
                    $result = true;

                    break;
                }
            }

            return $result;
        }

        return false;
    }

    public function getAllowedRolesFromProject(int $projectId): array
    {
        // Define a mapping from role IDs to role names
        $roleMap = [
            1 => 'ROLE_TEACHER',
            17 => 'ROLE_STUDENT_BOSS',
            4 => 'ROLE_HR',
            3 => 'ROLE_SESSION_MANAGER',
            // ... other mappings can be added as needed
        ];

        $jsonString = $this->settingsManager->getSetting('ticket.ticket_project_user_roles');

        if (empty($jsonString)) {
            return [];
        }

        $data = json_decode($jsonString, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            // Invalid JSON
            return [];
        }

        if (!isset($data['permissions'][$projectId])) {
            // No permissions for the given projectId
            return [];
        }

        $roleIds = $data['permissions'][$projectId];

        // Transform role IDs into role names using the defined mapping
        return array_map(fn ($roleId) => $roleMap[$roleId] ?? "$roleId", $roleIds);
    }
}
