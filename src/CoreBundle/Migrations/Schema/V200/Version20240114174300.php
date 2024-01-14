<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240114174300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Replace "name" with "title" fields in tables (part 2)';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('skill_level')) {
            $this->addSql(
                'ALTER TABLE skill_level CHANGE short_name short_title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('c_chat_conversation')) {
            $this->addSql(
                'ALTER TABLE c_chat_conversation CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('c_wiki_category')) {
            $this->addSql(
                'ALTER TABLE c_wiki_category CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('c_shortcut')) {
            $this->addSql(
                'ALTER TABLE c_shortcut CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('illustration')) {
            $this->addSql(
                'ALTER TABLE illustration CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('resource_type')) {
            $this->addSql(
                'ALTER TABLE resource_type CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('resource_format')) {
            $this->addSql(
                'ALTER TABLE resource_format CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('tool')) {
            $table = $schema->getTable('tool');
            if ($table->hasIndex('UNIQ_20F33ED15E237E06')) {
                $this->addSql(
                    'DROP INDEX UNIQ_20F33ED15E237E06 on tool'
                );
            }
            $this->addSql(
                'ALTER TABLE tool CHANGE name title VARCHAR(255) NOT NULL'
            );
            $this->addSql(
                'CREATE UNIQUE INDEX UNIQ_20F33ED12B36786B ON tool (title)'
            );
        }

        if ($schema->hasTable('resource_tag')) {
            $this->addSql(
                'ALTER TABLE resource_tag CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('track_e_hotpotatoes')) {
            $this->addSql(
                'ALTER TABLE track_e_hotpotatoes CHANGE exe_name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('lti_external_tool')) {
            $this->addSql(
                'ALTER TABLE lti_external_tool CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

    }

    public function down(Schema $schema): void
    {

        $table = $schema->getTable('lti_external_tool');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE lti_external_tool CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('track_e_hotpotatoes');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE track_e_hotpotatoes CHANGE title exe_name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('resource_tag');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE resource_tag CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('tool');
        if ($table->hasIndex('UNIQ_20F33ED12B36786B')) {
            $this->addSql(
                'DROP INDEX UNIQ_20F33ED12B36786B on tool'
            );
        }
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE tool CHANGE title name VARCHAR(255) NOT NULL');
        }
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_20F33ED15E237E06 ON tool (title)'
        );

        $table = $schema->getTable('resource_format');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE resource_format CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('resource_type');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE resource_type CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('illustration');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE illustration CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('c_shortcut');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_shortcut CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('c_wiki_category');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_wiki_category CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('c_chat_conversation');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_chat_conversation CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('skill_level');
        if ($table->hasColumn('short_title')) {
            $this->addSql('ALTER TABLE skill_level CHANGE short_title short_name VARCHAR(255) NOT NULL');
        }
    }
}
