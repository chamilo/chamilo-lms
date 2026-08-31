<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * track_e_online is meant to hold at most one row per (login_user_id, access_url_id) — it tracks
 * current presence, not login history (that is track_e_login's job). A prior lack of a unique
 * constraint let concurrent heartbeats/logins create duplicate rows, which TrackEOnlineRepository
 * had to fetch-all-and-delete on every single heartbeat call. This deduplicates existing rows
 * (keeping the most recently active one per pair) and adds the constraint so duplicates become
 * impossible going forward.
 */
final class Version20260831150000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Deduplicate track_e_online and add a unique index on (login_user_id, access_url_id)';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('track_e_online')) {
            return;
        }

        $table = $schema->getTable('track_e_online');

        if ($table->hasIndex('uniq_track_e_online_user_url')) {
            return;
        }

        // Keep only the most recently active row per (login_user_id, access_url_id): delete any
        // row for which another row of the same pair has a later login_date (or, on a tie, a
        // higher login_id).
        $this->addSql(<<<'SQL'
DELETE t1 FROM track_e_online t1
INNER JOIN track_e_online t2
    ON t1.login_user_id = t2.login_user_id
   AND t1.access_url_id = t2.access_url_id
   AND (
       t1.login_date < t2.login_date
       OR (t1.login_date = t2.login_date AND t1.login_id < t2.login_id)
   )
SQL);

        $this->addSql('ALTER TABLE track_e_online ADD UNIQUE INDEX uniq_track_e_online_user_url (login_user_id, access_url_id)');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('track_e_online')) {
            return;
        }

        $table = $schema->getTable('track_e_online');

        if ($table->hasIndex('uniq_track_e_online_user_url')) {
            $this->addSql('DROP INDEX uniq_track_e_online_user_url ON track_e_online');
        }
    }
}
