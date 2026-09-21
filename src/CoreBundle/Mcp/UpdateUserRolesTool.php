<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Helpers\PermissionHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

/**
 * Grants or revokes Chamilo platform roles for another user. Restricted to administrators
 * acting within their own access-URL scope -- see AccessUrlScopeHelper::canEditUser(), the
 * same rule PATCH /api/users/{id} enforces via UserVoter::EDIT, reused here rather than
 * reimplemented so the two paths can never diverge.
 */
final readonly class UpdateUserRolesTool
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private AccessUrlScopeHelper $accessUrlScope,
        private PermissionHelper $permissionHelper,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param list<string> $addRoles
     * @param list<string> $removeRoles
     *
     * @return array{updated: true, added_roles: list<string>, removed_roles: list<string>, user: array<string, mixed>}
     */
    #[McpTool(
        name: 'update_user_roles',
        description: 'Grant or revoke platform roles for another user (e.g. ROLE_TEACHER, ROLE_ADMIN, ROLE_HR, '
            .'ROLE_SESSION_MANAGER, ROLE_STUDENT_BOSS, ROLE_INVITEE, ROLE_QUESTION_MANAGER, ROLE_GLOBAL_ADMIN -- '
            .'short forms such as TEACHER or ADMIN are also accepted). Restricted to administrators (ROLE_ADMIN or '
            .'ROLE_GLOBAL_ADMIN) who manage the target user\'s access URL: a plain administrator is confined to the '
            .'exact URL(s) they are themselves registered on, a global administrator additionally manages that '
            .'URL\'s descendants, and an administrator registered at the top of a URL tree manages every portal. '
            .'Only an unrestricted top-of-tree global administrator may grant ROLE_GLOBAL_ADMIN. Identify the '
            .'target user with exactly one of userId or username. Provide at least one of addRoles or removeRoles '
            .'(a role cannot appear in both). This tool always refuses to change the caller\'s own roles -- call '
            .'get_current_user first to confirm the caller has administrator access.',
    )]
    public function updateUserRoles(
        ?int $userId = null,
        ?string $username = null,
        array $addRoles = [],
        array $removeRoles = [],
    ): array {
        try {
            $actor = $this->security->getUser();
            if (!$actor instanceof User || null === $actor->getId()) {
                throw new AccessDeniedException('An authenticated Chamilo user is required.');
            }

            if (!$this->security->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedException('Only administrators can change user roles.');
            }

            if ((null === $userId) === (null === $username)) {
                throw new InvalidArgumentException('Provide exactly one of userId or username to identify the target user.');
            }

            $target = null !== $userId
                ? $this->userRepository->find($userId)
                : $this->userRepository->findOneBy(['username' => $username]);

            // Same "not found" message whether the user genuinely doesn't exist or merely
            // isn't in the actor's access-URL scope, so a scoped admin can't use this tool to
            // enumerate users on portals they don't manage.
            if (!$target instanceof User || null === $target->getId()) {
                throw new AccessDeniedException('No user found for the given identifier, or you do not manage their access URL.');
            }

            if ((int) $actor->getId() === (int) $target->getId()) {
                throw new AccessDeniedException('You cannot change your own roles with this tool.');
            }

            if (!$this->accessUrlScope->canEditUser($actor, $target)) {
                throw new AccessDeniedException('No user found for the given identifier, or you do not manage their access URL.');
            }

            if ([] === $addRoles && [] === $removeRoles) {
                throw new InvalidArgumentException('Provide at least one role in addRoles or removeRoles.');
            }

            $addRoles = $this->normalizeRoles($addRoles);
            $removeRoles = $this->normalizeRoles($removeRoles);

            $overlap = array_intersect($addRoles, $removeRoles);
            if ([] !== $overlap) {
                throw new InvalidArgumentException(\sprintf('A role cannot be both added and removed in the same call: %s.', implode(', ', $overlap)));
            }

            $hadGlobalAdminBefore = $target->hasRole('ROLE_GLOBAL_ADMIN');

            $actuallyAdded = [];
            foreach ($addRoles as $role) {
                if (!$target->hasRole($role)) {
                    $actuallyAdded[] = $role;
                }
                $target->addRole($role);
            }

            $actuallyRemoved = [];
            foreach ($removeRoles as $role) {
                if ($target->hasRole($role)) {
                    $actuallyRemoved[] = $role;
                }
                $target->removeRole($role);
            }

            if ([] === $actuallyAdded && [] === $actuallyRemoved) {
                throw new InvalidArgumentException('No role change was made: the requested roles already match the user\'s current roles.');
            }

            if (
                $target->hasRole('ROLE_GLOBAL_ADMIN')
                && !$hadGlobalAdminBefore
                && !$this->accessUrlScope->canGrantGlobalAdminRole($actor)
            ) {
                throw new AccessDeniedException('Only an administrator registered at the top of a URL tree may grant the global admin role.');
            }

            $this->entityManager->persist($target);
            $this->entityManager->flush();

            $roles = $target->getRoles();
            sort($roles);

            return [
                'updated' => true,
                'added_roles' => $actuallyAdded,
                'removed_roles' => $actuallyRemoved,
                'user' => [
                    'user_id' => $target->getId(),
                    'username' => $target->getUsername(),
                    'full_name' => $target->getFullName(),
                    'roles' => $roles,
                ],
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The user roles could not be updated because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @param list<string> $roles
     *
     * @return list<string>
     */
    private function normalizeRoles(array $roles): array
    {
        $validRoles = array_map('api_normalize_role_code', $this->permissionHelper->getUserRoles());

        $normalized = [];
        foreach ($roles as $role) {
            if (!\is_string($role) || '' === trim($role)) {
                throw new InvalidArgumentException('Role codes must be non-empty strings.');
            }

            $canonical = api_normalize_role_code($role);
            if (!\in_array($canonical, $validRoles, true)) {
                throw new InvalidArgumentException(\sprintf('"%s" is not a recognized Chamilo role.', $role));
            }

            $normalized[] = $canonical;
        }

        return array_values(array_unique($normalized));
    }
}
