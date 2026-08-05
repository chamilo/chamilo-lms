<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Mcp;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use JsonException;

use const JSON_THROW_ON_ERROR;

final readonly class McpAccessPolicy
{
    /**
     * The fixture/template uses Chamilo's existing user-status names instead
     * of Symfony role names so administrators can configure the setting in the
     * same format as other per-role JSON settings.
     *
     * @var array<string, bool>
     */
    public const array DEFAULT_ALLOWED_ROLES = [
        'ADMIN' => true,
        'COURSEMANAGER' => true,
        'STUDENT' => false,
        'DRH' => false,
        'SESSIONADMIN' => false,
        'STUDENT_BOSS' => false,
        'INVITEE' => false,
    ];

    public function __construct(
        private SettingsManager $settingsManager,
    ) {}

    public function isEnabled(): bool
    {
        return $this->toBoolean($this->settingsManager->getSetting('security.mcp_enabled', true));
    }

    public function canUse(User $user): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $allowedRoles = $this->getAllowedRoles();

        foreach ($this->resolveRoleKeys($user) as $roleKey) {
            if (true === ($allowedRoles[$roleKey] ?? false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, bool>
     */
    public function getAllowedRoles(): array
    {
        $configuredValue = $this->settingsManager->getSetting('security.mcp_allowed_roles', true);
        if (!\is_string($configuredValue) || '' === trim($configuredValue)) {
            return self::DEFAULT_ALLOWED_ROLES;
        }

        try {
            $decoded = json_decode($configuredValue, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->denyAllRoles();
        }

        if (!\is_array($decoded)) {
            return $this->denyAllRoles();
        }

        $result = self::DEFAULT_ALLOWED_ROLES;
        foreach ($result as $roleKey => $defaultValue) {
            if (\array_key_exists($roleKey, $decoded)) {
                $result[$roleKey] = $this->toBoolean($decoded[$roleKey]);
            }
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private function resolveRoleKeys(User $user): array
    {
        $roleKeys = [];

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            $roleKeys[] = 'ADMIN';
        }
        if ($user->isTeacher()) {
            $roleKeys[] = 'COURSEMANAGER';
        }
        if ($user->isStudent()) {
            $roleKeys[] = 'STUDENT';
        }
        if ($user->isHRM()) {
            $roleKeys[] = 'DRH';
        }
        if ($user->isSessionAdmin()) {
            $roleKeys[] = 'SESSIONADMIN';
        }
        if ($user->isStudentBoss()) {
            $roleKeys[] = 'STUDENT_BOSS';
        }
        if ($user->isInvitee()) {
            $roleKeys[] = 'INVITEE';
        }

        return array_values(array_unique($roleKeys));
    }

    /**
     * Invalid role configuration fails closed. This prevents a malformed JSON
     * value from silently restoring teacher access while MCP is enabled.
     *
     * @return array<string, bool>
     */
    private function denyAllRoles(): array
    {
        return array_fill_keys(array_keys(self::DEFAULT_ALLOWED_ROLES), false);
    }

    private function toBoolean(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return 1 === $value;
        }

        if (\is_string($value)) {
            return \in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
