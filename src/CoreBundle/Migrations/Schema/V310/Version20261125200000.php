<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Version20261125100000 can only attribute the old connections of a platform with one access URL.
 * With several URLs, a user registered in exactly one of them can only have connected through
 * that one, so their old connections get it too. The connections of users shared between URLs
 * stay NULL: they cannot be attributed.
 */
final class Version20261125200000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Give the old connections of users registered in a single access URL that URL';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('track_e_login') || !$schema->getTable('track_e_login')->hasColumn('access_url_id')) {
            return;
        }

        $this->addSql(self::attributeSql());
    }

    public function down(Schema $schema): void
    {
        // The NULL values cannot be told apart from the attributed ones afterwards.
    }

    public static function attributeSql(): string
    {
        return 'UPDATE track_e_login
            SET access_url_id = (
                SELECT MIN(url_user.access_url_id)
                FROM access_url_rel_user url_user
                WHERE url_user.user_id = track_e_login.login_user_id
            )
            WHERE access_url_id IS NULL
              AND login_user_id IN (
                  SELECT user_id
                  FROM access_url_rel_user
                  GROUP BY user_id
                  HAVING COUNT(DISTINCT access_url_id) = 1
              )';
    }
}
