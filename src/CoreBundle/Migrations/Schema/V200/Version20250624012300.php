<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20250624012300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Decode HTML entities in language.original_name to UTF-8';
    }

    public function up(Schema $schema): void
    {
        // Retrieve all language entries where original_name contains HTML entities
        $languages = $this->connection->fetchAllAssociative(
            "SELECT id, original_name FROM language WHERE original_name LIKE '%&%;%'"
        );

        foreach ($languages as $lang) {
            $decoded = html_entity_decode((string) $lang['original_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Only update if the decoded value is different
            if ($decoded !== $lang['original_name']) {
                $this->addSql(
                    "UPDATE language SET original_name = :decoded WHERE id = :id",
                    [
                        'decoded' => $decoded,
                        'id' => $lang['id'],
                    ]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        // This migration is not reversible, as the original entity-encoded values are lost
    }
}
