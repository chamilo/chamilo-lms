<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250926142500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return "Fix mis-labeled 'Português do Brasil': change isocode from pt_PT to pt_BR only when pt_PT appears twice and pt_BR doesn't exist.";
    }

    public function up(Schema $schema): void
    {
        // Convert the Brazilian row from pt_PT -> pt_BR
        // Only do it when there are at least 2 rows with isocode 'pt_PT' and there's no existing 'pt_BR'
        $this->addSql("
            UPDATE language l
            JOIN (
                SELECT id
                FROM language
                WHERE isocode = 'pt_PT'
                  AND (
                        original_name = 'Português do Brasil'
                        OR english_name LIKE '%Brazil%'
                        OR english_name LIKE '%brazilian%'
                      )
                LIMIT 1
            ) b ON b.id = l.id
            SET l.isocode = 'pt_BR'
            WHERE
                (SELECT COUNT(*) FROM language WHERE isocode = 'pt_PT') > 1
                AND (SELECT COUNT(*) FROM language WHERE isocode = 'pt_BR') = 0
        ");

        // Normalize english_name for Brazilian row (optional, harmless if already set)
        $this->addSql("
            UPDATE language
            SET english_name = 'brazilian portuguese'
            WHERE isocode = 'pt_BR'
              AND (english_name IS NULL OR english_name = '' OR english_name LIKE '%brazil%')
        ");
    }

    public function down(Schema $schema): void
    {
        // Revert only if it looks like we performed the change (avoid clobbering a valid dataset).
        $this->addSql("
            UPDATE language l
            JOIN (
                SELECT id
                FROM language
                WHERE isocode = 'pt_BR'
                  AND (
                        original_name = 'Português do Brasil'
                        OR english_name LIKE '%brazil%'
                      )
                LIMIT 1
            ) b ON b.id = l.id
            SET l.isocode = 'pt_PT'
            WHERE (SELECT COUNT(*) FROM language WHERE isocode = 'pt_PT') = 1
        ");
    }
}
