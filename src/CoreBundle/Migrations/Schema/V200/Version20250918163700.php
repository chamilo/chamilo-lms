<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use DateTime;
use Doctrine\DBAL\Schema\Schema;
use Throwable;

use const CASE_UPPER;
use const JSON_ERROR_NONE;
use const JSON_UNESCAPED_UNICODE;
use const PREG_SPLIT_NO_EMPTY;

final class Version20250918163700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return "Remove SUPER_ADMIN role and grant 'user:loginas' to ADMIN. Migrate users' roles accordingly.";
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        $conn->beginTransaction();

        try {
            $users = $conn->fetchAllAssociative('SELECT id, roles FROM `user`');
            $updateStmt = $conn->prepare('UPDATE `user` SET roles = :roles WHERE id = :id');

            foreach ($users as $u) {
                $id = (int) $u['id'];
                $raw = $u['roles'];

                [$roles, $format] = $this->decodeRoles($raw);
                if (empty($roles)) {
                    continue;
                }

                $upper = array_values(array_unique(array_map(
                    fn ($r) => strtoupper(trim((string) $r)),
                    $roles
                )));

                $changed = false;

                if (\in_array('ROLE_SUPER_ADMIN', $upper, true)) {
                    if (!\in_array('ROLE_ADMIN', $upper, true)) {
                        $upper[] = 'ROLE_ADMIN';
                    }
                    $upper = array_values(array_diff($upper, ['ROLE_SUPER_ADMIN']));
                    $changed = true;
                }

                if ($changed) {
                    $encoded = $this->encodeRoles($upper, $format);
                    $updateStmt->executeStatement(['roles' => $encoded, 'id' => $id]);
                }
            }

            $adminRoleId = $this->ensureAdminRoleId();
            $permId = $this->ensurePermissionExists('user:loginas', 'Login as user', 'Login as another user');
            $this->ensurePermissionRelRole($permId, $adminRoleId);

            $superAdminId = $this->getRoleIdByCodes(['SUPER_ADMIN', 'SUA']);
            if (null !== $superAdminId) {
                $conn->executeStatement(
                    'DELETE FROM permission_rel_role WHERE role_id = :rid',
                    ['rid' => $superAdminId]
                );
                $conn->executeStatement('DELETE FROM role WHERE id = :rid', ['rid' => $superAdminId]);
            }

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollBack();

            throw $e;
        }
    }

    public function down(Schema $schema): void
    {
        $conn = $this->connection;
        $conn->beginTransaction();

        try {
            $superAdminId = $this->ensureSuperAdminRoleId();

            $permId = $this->ensurePermissionExists('user:loginas', 'Login as user', 'Login as another user');
            $this->ensurePermissionRelRole($permId, $superAdminId);

            $adminRoleId = $this->getRoleIdByCodes(['ADMIN', 'ADM']);
            if (null !== $adminRoleId) {
                $conn->executeStatement(
                    'DELETE FROM permission_rel_role WHERE permission_id = :pid AND role_id = :rid',
                    ['pid' => $permId, 'rid' => $adminRoleId]
                );
            }

            $users = $conn->fetchAllAssociative('SELECT id, roles FROM `user`');
            $updateStmt = $conn->prepare('UPDATE `user` SET roles = :roles WHERE id = :id');

            foreach ($users as $u) {
                $id = (int) $u['id'];
                $raw = $u['roles'];

                [$roles, $format] = $this->decodeRoles($raw);
                $upper = array_values(array_unique(array_map(
                    fn ($r) => strtoupper(trim((string) $r)),
                    $roles
                )));

                if (\in_array('ROLE_ADMIN', $upper, true) && !\in_array('ROLE_SUPER_ADMIN', $upper, true)) {
                    $upper[] = 'ROLE_SUPER_ADMIN';
                    $encoded = $this->encodeRoles($upper, $format);
                    $updateStmt->executeStatement(['roles' => $encoded, 'id' => $id]);
                }
            }

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollBack();

            throw $e;
        }
    }

    private function decodeRoles($raw): array
    {
        if (null === $raw || '' === $raw) {
            return [[], 'empty'];
        }

        if (\is_array($raw)) {
            return [$raw, 'array'];
        }

        $s = (string) $raw;

        $json = json_decode($s, true);
        if (JSON_ERROR_NONE === json_last_error() && \is_array($json)) {
            return [$json, 'json'];
        }

        $unser = @unserialize($s);
        if (false !== $unser && \is_array($unser)) {
            return [$unser, 'php'];
        }

        $parts = preg_split('/[,\s]+/', $s, -1, PREG_SPLIT_NO_EMPTY);

        return [$parts ?: [], 'text'];
    }

    private function encodeRoles(array $roles, string $format): string
    {
        return match ($format) {
            'json', 'array', 'empty' => json_encode(array_values($roles), JSON_UNESCAPED_UNICODE),
            'php' => serialize(array_values($roles)),
            'text' => implode(',', array_values($roles)),
            default => json_encode(array_values($roles), JSON_UNESCAPED_UNICODE),
        };
    }

    private function getRoleIdByCodes(array $codes): ?int
    {
        $placeholders = implode(',', array_fill(0, \count($codes), '?'));
        $row = $this->connection->fetchAssociative(
            "SELECT id FROM role WHERE code IN ($placeholders) LIMIT 1",
            $codes
        );

        return $row ? (int) $row['id'] : null;
    }

    private function ensureAdminRoleId(): int
    {
        $id = $this->getRoleIdByCodes(['ADMIN', 'ADM']);
        if (null !== $id) {
            return $id;
        }

        return $this->createRoleRow('ADMIN', 'Administrator', 'Platform administrator');
    }

    private function ensureSuperAdminRoleId(): int
    {
        $id = $this->getRoleIdByCodes(['SUPER_ADMIN', 'SUA']);
        if (null !== $id) {
            return $id;
        }

        return $this->createRoleRow('SUPER_ADMIN', 'Super Administrator', 'Full platform administrator');
    }

    private function createRoleRow(string $code, string $title, ?string $description = null): int
    {
        $conn = $this->connection;
        $sm = $conn->createSchemaManager();

        $cols = [];
        foreach ($sm->listTableColumns('role') as $c) {
            $cols[strtoupper($c->getName())] = $c;
        }

        $now = (new DateTime())->format('Y-m-d H:i:s');

        $data = [
            'code' => $code,
            'title' => $title,
        ];

        if (null !== $description && isset($cols['DESCRIPTION'])) {
            $data['description'] = $description;
        }

        if (isset($cols['CONSTANT_VALUE'])) {
            $type = strtolower($cols['CONSTANT_VALUE']->getType()->getName());
            $isNumeric = \in_array($type, ['integer', 'smallint', 'bigint', 'decimal', 'float', 'boolean'], true);
            $data['constant_value'] = $isNumeric ? 0 : ('ROLE_'.strtoupper($code));
        }

        if (isset($cols['SYSTEM_ROLE'])) {
            $data['system_role'] = 1;
        }

        if (isset($cols['CREATED_AT'])) {
            $data['created_at'] = $now;
        }
        if (isset($cols['UPDATED_AT'])) {
            $data['updated_at'] = $now;
        }

        $fields = array_keys($data);
        $placeholders = array_map(fn ($f) => ':'.$f, $fields);
        $sql = 'INSERT INTO role ('.implode(',', $fields).') VALUES ('.implode(',', $placeholders).')';
        $conn->executeStatement($sql, $data);

        return (int) $conn->lastInsertId();
    }

    private function ensurePermissionExists(string $slug, string $title, string $desc): int
    {
        $conn = $this->connection;

        $row = $conn->fetchAssociative('SELECT id FROM permission WHERE slug = :s', ['s' => $slug]);
        if ($row) {
            return (int) $row['id'];
        }

        $sm = $conn->createSchemaManager();
        $cols = array_change_key_case(
            array_map(fn ($c) => $c->getName(), $sm->listTableColumns('permission')),
            CASE_UPPER
        );

        $now = (new DateTime())->format('Y-m-d H:i:s');

        $data = ['slug' => $slug, 'title' => $title, 'description' => $desc];
        if (isset($cols['CREATED_AT'])) {
            $data['created_at'] = $now;
        }
        if (isset($cols['UPDATED_AT'])) {
            $data['updated_at'] = $now;
        }

        $fields = array_keys($data);
        $place = array_map(fn ($f) => ':'.$f, $fields);
        $sql = 'INSERT INTO permission ('.implode(',', $fields).') VALUES ('.implode(',', $place).')';
        $conn->executeStatement($sql, $data);

        return (int) $conn->lastInsertId();
    }

    private function ensurePermissionRelRole(int $permissionId, int $roleId): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT 1 FROM permission_rel_role WHERE permission_id = :p AND role_id = :r',
            ['p' => $permissionId, 'r' => $roleId]
        );
        if (!$row) {
            $now = (new DateTime())->format('Y-m-d H:i:s');
            $this->connection->executeStatement(
                'INSERT INTO permission_rel_role (permission_id, role_id, changeable, updated_at) VALUES (:p, :r, 1, :u)',
                ['p' => $permissionId, 'r' => $roleId, 'u' => $now]
            );
        }
    }
}
