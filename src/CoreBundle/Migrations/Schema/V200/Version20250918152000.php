<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250918152000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Mail settings: fix titles/comments and upsert defaults for mailer_from_email & mailer_from_name in settings.';
    }

    public function up(Schema $schema): void
    {
        $pairs = [
            [
                'variable' => 'mailer_from_email',
                'title' => 'Send all e-mails from this e-mail address',
                'comment' => 'Sets the default email address used in the "from" field of emails.',
                'category' => 'mail',
                'default' => '',
            ],
            [
                'variable' => 'mailer_from_name',
                'title' => 'Send all e-mails as originating from this (organizational) name',
                'comment' => 'Sets the default display name used for sending platform emails. e.g. "Support team".',
                'category' => 'mail',
                'default' => '',
            ],
        ];

        foreach ($pairs as $p) {
            $exists = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM settings
                 WHERE variable = ? AND subkey IS NULL AND access_url = 1',
                [$p['variable']]
            );

            if (0 === $exists) {
                $this->connection->executeStatement(
                    'INSERT INTO settings
                        (variable, subkey, type, category, selected_value, title, comment,
                         access_url_changeable, access_url_locked, access_url)
                     VALUES
                        (?, NULL, NULL, ?, ?, ?, ?, 1, 0, 1)',
                    [$p['variable'], $p['category'], $p['default'], $p['title'], $p['comment']]
                );
                $this->write(\sprintf('Inserted missing setting: %s', $p['variable']));
            } else {
                $this->connection->executeStatement(
                    'UPDATE settings
                     SET title = ?, comment = ?, category = ?
                     WHERE variable = ? AND subkey IS NULL AND access_url = 1',
                    [$p['title'], $p['comment'], $p['category'], $p['variable']]
                );
                $this->write(\sprintf('Updated setting metadata: %s', $p['variable']));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeStatement(
            "UPDATE settings
             SET title = 'Send all e-mails as originating from this (organizational) name',
                 comment = 'Sets the default display name used for sending platform emails. e.g. \"Support team\".'
             WHERE variable = 'mailer_from_email'
               AND subkey IS NULL AND access_url = 1"
        );

        $this->connection->executeStatement(
            "UPDATE settings
             SET title = 'Send all e-mails from this e-mail address',
                 comment = 'Sets the default email address used in the \"from\" field of emails.'
             WHERE variable = 'mailer_from_name'
               AND subkey IS NULL AND access_url = 1"
        );
    }
}
