<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251015073500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Clean role table: ensure ANONYMOUS exists, drop SUPER_ADMIN, and keep only canonical roles.';
    }

    public function up(Schema $schema): void
    {
        $canonical = [
            'ANONYMOUS',
            'INVITEE',
            'STUDENT',
            'TEACHER',
            'ADMIN',
            'GLOBAL_ADMIN',
            'HR',
            'QUESTION_MANAGER',
            'SESSION_MANAGER',
            'STUDENT_BOSS',
        ];
        $inList = "'" . implode("','", array_map('addslashes', $canonical)) . "'";

        $this->addSql("
            INSERT INTO role (code, constant_value, title, description, system_role, created_at)
            SELECT 'ANONYMOUS', 0, 'Anonymous', 'Unauthenticated users', 1, NOW()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM role WHERE code = 'ANONYMOUS')
        ");
        $this->write('Ensured ANONYMOUS role exists.');

        $this->addSql("
            DELETE prr FROM permission_rel_role prr
            INNER JOIN role r ON r.id = prr.role_id
            WHERE r.code = 'SUPER_ADMIN'
        ");
        $this->addSql("DELETE FROM role WHERE code = 'SUPER_ADMIN'");
        $this->write('Removed SUPER_ADMIN and related PRR rows.');

        $this->addSql("
            DELETE prr FROM permission_rel_role prr
            INNER JOIN role r ON r.id = prr.role_id
            WHERE r.code NOT IN ($inList)
        ");
        $this->addSql("DELETE FROM role WHERE code NOT IN ($inList)");
        $this->write('Removed non-canonical roles and related PRR rows.');

        $defaults = [
            'INVITEE'          => [1,  'Invitee',               'Invited users',                               1],
            'STUDENT'          => [2,  'Student',               'Students of courses or sessions',             1],
            'TEACHER'          => [3,  'Teacher',               'Teachers of courses or sessions',             1],
            'ADMIN'            => [4,  'Administrator',         'Platform administrators',                     1],
            'GLOBAL_ADMIN'     => [6,  'Global Administrator',  'Global admin users',                          1],
            'HR'               => [7,  'HR Manager',            'Human resources managers',                    0],
            'QUESTION_MANAGER' => [8,  'Question Bank Manager', 'Manages the question bank across courses',    0],
            'SESSION_MANAGER'  => [9,  'Session Manager',       'Manages sessions and session content',        0],
            'STUDENT_BOSS'     => [10, 'Student Boss',          'Manages groups of students',                  0],
        ];

        foreach ($defaults as $code => [$const, $title, $desc, $sys]) {
            $this->addSql("
                INSERT INTO role (code, constant_value, title, description, system_role, created_at)
                SELECT '$code', $const, '".addslashes($title)."', '".addslashes($desc)."', $sys, NOW()
                FROM DUAL
                WHERE NOT EXISTS (SELECT 1 FROM role WHERE code = '$code')
            ");
        }
        $this->write('Ensured all canonical roles exist.');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO role (code, constant_value, title, description, system_role, created_at)
            SELECT 'SUPER_ADMIN', 5, 'Super Administrator', 'Super admin users', 1, NOW()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM role WHERE code = 'SUPER_ADMIN')
        ");
        $this->addSql("DELETE FROM role WHERE code = 'ANONYMOUS'");
    }
}
