<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240114212100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Replace "name" with "title" fields in tables (part 3)';
    }

    public function up(Schema $schema): void
    {

        if ($schema->hasTable('contact_form_contact_category')) {
            $this->addSql(
                'ALTER TABLE contact_form_contact_category CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('fos_group')) {
            $table = $schema->getTable('tool');
            if ($table->hasIndex('UNIQ_4B019DDB5E237E06')) {
                $this->addSql(
                    'DROP INDEX UNIQ_4B019DDB5E237E06 on fos_group'
                );
            }
            $this->addSql(
                'ALTER TABLE fos_group CHANGE name title VARCHAR(255) NOT NULL'
            );
            $this->addSql(
                'CREATE UNIQUE INDEX UNIQ_4B019DDB2B36786B ON fos_group (title)'
            );
        }

        if ($schema->hasTable('resource_file')) {
            $this->addSql(
                'ALTER TABLE fos_group CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

    }

    public function down(Schema $schema): void
    {

        $table = $schema->getTable('resource_file');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE resource_file CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('fos_group');
        if ($table->hasIndex('UNIQ_4B019DDB2B36786B')) {
            $this->addSql(
                'DROP INDEX UNIQ_4B019DDB2B36786B on fos_group'
            );
        }
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE fos_group CHANGE title name VARCHAR(255) NOT NULL');
        }
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_4B019DDB5E237E06 ON fos_group (title)'
        );

        $table = $schema->getTable('contact_form_contact_category');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE contact_form_contact_category CHANGE title name VARCHAR(255) NOT NULL');
        }

    }
}
