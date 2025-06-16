<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250612070100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Replace per-profile login redirection settings with a unified redirect_after_login JSON setting';
    }

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement("
            DELETE FROM settings
            WHERE variable IN (
                'page_after_login',
                'student_page_after_login',
                'teacher_page_after_login',
                'drh_page_after_login',
                'sessionadmin_page_after_login'
            )
        ");

        $jsonValue = json_encode([
            'COURSEMANAGER' => '',
            'STUDENT' => '',
            'DRH' => '',
            'SESSIONADMIN' => 'admin-dashboard',
            'STUDENT_BOSS' => '',
            'INVITEE' => '',
            'ADMIN' => 'admin-dashboard',
        ]);

        $existing = $this->connection->fetchOne("
            SELECT COUNT(*)
            FROM settings
            WHERE variable = 'redirect_after_login'
        ");

        if ($existing > 0) {
            $this->connection->executeStatement("
                UPDATE settings
                SET selected_value = :value
                WHERE variable = 'redirect_after_login'
            ", ['value' => $jsonValue]);
        } else {
            $this->connection->insert('settings', [
                'variable' => 'redirect_after_login',
                'type' => 'textfield',
                'category' => 'registration',
                'selected_value' => $jsonValue,
                'title' => 'Redirect after login (per profile)',
                'comment' => 'Define redirection per profile after login using a JSON object',
                'scope' => null,
                'access_url' => 1,
                'access_url_changeable' => 0,
                'access_url_locked' => 0,
            ]);
        }
    }
}
