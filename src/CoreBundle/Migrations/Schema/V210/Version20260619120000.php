<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260619120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Ensure disclose_ai_assistance is enabled when the value is unset.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('settings')) {
            $this->enableDisclosureWhenUnset('settings');
        }

        if ($schema->hasTable('settings_current')) {
            $this->enableDisclosureWhenUnset('settings_current');
        }
    }

    public function down(Schema $schema): void
    {
        // No rollback: do not disable an AI disclosure setting automatically.
    }

    private function enableDisclosureWhenUnset(string $table): void
    {
        $this->addSql(
            \sprintf(
                "UPDATE %s
                 SET selected_value = 'true'
                 WHERE variable = 'disclose_ai_assistance'
                   AND subkey IS NULL
                   AND access_url = 1
                   AND (selected_value IS NULL OR selected_value = '')",
                $table
            )
        );

        $this->write(\sprintf('Enabled disclose_ai_assistance in %s when it was unset.', $table));
    }
}
