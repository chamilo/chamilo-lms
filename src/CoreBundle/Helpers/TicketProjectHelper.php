<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\TicketCategoryRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

use const JSON_ERROR_NONE;

class TicketProjectHelper
{
    public function __construct(
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
        private readonly EntityManagerInterface $entityManager,
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

    /**
     * Categories, within the given project, that the current user is allowed
     * to see beyond their own created/assigned tickets.
     *
     * Holding one of the roles configured in `ticket.ticket_project_user_roles`
     * makes a user *eligible* for elevated ticket visibility, but that role is
     * platform-wide (e.g. every teacher account) and not scoped to this
     * project on its own. The actual scope is the categories the user is
     * explicitly registered as responsible for (`ticket_category_rel_user`,
     * the same relation used to auto-assign new tickets and notify category
     * managers) — never every category in the project.
     *
     * @return int[]
     */
    public function getManagedCategoryIds(int $projectId): array
    {
        if ($this->security->isGranted('ROLE_ADMIN') || !$this->userIsAllowInProject($projectId)) {
            return [];
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            return [];
        }

        $categoryIds = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(relation.category) AS categoryId')
            ->from(TicketCategoryRelUser::class, 'relation')
            ->innerJoin('relation.category', 'category')
            ->andWhere('relation.user = :userId')
            ->andWhere('IDENTITY(category.project) = :projectId')
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setParameter('projectId', $projectId, Types::INTEGER)
            ->getQuery()
            ->getSingleColumnResult()
        ;

        return array_values(array_unique(array_map('intval', $categoryIds)));
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
