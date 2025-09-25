<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Throwable;

use const JSON_ERROR_NONE;
use const PHP_INT_MAX;
use const PREG_SPLIT_NO_EMPTY;

final class Version20250923214700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Normalize user.roles to canonical ROLE_* and serialize consistently';
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

                // Decode from any legacy format to array
                [$roles] = $this->decodeRoles($raw);

                // Normalize to canonical ROLE_* and ordering
                $normalized = $this->normalizeRoles($roles);

                // Re-encode ALWAYS as PHP serialized array (Doctrine array type compatibility)
                $encoded = serialize(array_values($normalized));

                // Update only if different to avoid unnecessary writes
                if ((string) $encoded !== (string) $raw) {
                    $updateStmt->executeStatement(['roles' => $encoded, 'id' => $id]);
                }
            }

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollBack();

            throw $e;
        }
    }

    public function down(Schema $schema): void {}

    /**
     * Decode roles from various legacy formats into an array.
     * Supported: json, php-serialized, comma/space-separated text, array, empty.
     *
     * @param mixed $raw
     *
     * @return array{0: array}
     */
    private function decodeRoles($raw): array
    {
        if (null === $raw || '' === $raw) {
            return [[]];
        }
        if (\is_array($raw)) {
            return [$raw];
        }

        $s = (string) $raw;

        // Try JSON
        $json = json_decode($s, true);
        if (JSON_ERROR_NONE === json_last_error() && \is_array($json)) {
            return [$json];
        }

        // Try PHP serialized
        $unser = @unserialize($s);
        if (false !== $unser && \is_array($unser)) {
            return [$unser];
        }

        // Fallback: split plain text by commas/spaces
        $parts = preg_split('/[,\s]+/', $s, -1, PREG_SPLIT_NO_EMPTY);

        return [$parts ?: []];
    }

    /**
     * Normalize role codes to canonical ROLE_* form.
     * - Uppercase
     * - Add ROLE_ prefix when missing
     * - Unify legacy aliases (e.g., SUPER_ADMIN → ROLE_GLOBAL_ADMIN)
     * - Deduplicate
     * - Sort by meaningful priority.
     */
    private function normalizeRoles(array $roles): array
    {
        $out = [];
        foreach ($roles as $r) {
            $c = $this->normalizeRoleCode((string) $r);
            if ('' !== $c && !\in_array($c, $out, true)) {
                $out[] = $c;
            }
        }

        // Sort by priority (stable intent)
        $prio = array_flip([
            'ROLE_GLOBAL_ADMIN',
            'ROLE_ADMIN',
            'ROLE_SESSION_MANAGER',
            'ROLE_HR',
            'ROLE_TEACHER',
            'ROLE_STUDENT_BOSS',
            'ROLE_INVITEE',
            'ROLE_STUDENT',
        ]);

        usort($out, function ($a, $b) use ($prio) {
            $pa = $prio[$a] ?? PHP_INT_MAX;
            $pb = $prio[$b] ?? PHP_INT_MAX;
            if ($pa === $pb) {
                return strcmp($a, $b);
            }

            return $pa <=> $pb;
        });

        return $out;
    }

    private function normalizeRoleCode(string $code): string
    {
        $c = strtoupper(trim($code));
        static $map = [
            'STUDENT' => 'ROLE_STUDENT',
            'TEACHER' => 'ROLE_TEACHER',
            'HR' => 'ROLE_HR',
            'SESSION_MANAGER' => 'ROLE_SESSION_MANAGER',
            'STUDENT_BOSS' => 'ROLE_STUDENT_BOSS',
            'INVITEE' => 'ROLE_INVITEE',
            'QUESTION_MANAGER' => 'ROLE_QUESTION_MANAGER',
            'ADMIN' => 'ROLE_ADMIN',
            'GLOBAL_ADMIN' => 'ROLE_GLOBAL_ADMIN',
            'SUPER_ADMIN' => 'ROLE_GLOBAL_ADMIN',
            'ROLE_SUPER_ADMIN' => 'ROLE_GLOBAL_ADMIN',
        ];

        if (!str_starts_with($c, 'ROLE_')) {
            return $map[$c] ?? ('ROLE_'.$c);
        }

        return $map[$c] ?? $c;
    }
}
