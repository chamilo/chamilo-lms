<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\DataFixtures\SkillLevelFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261010223000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add skill level descriptions and acquirable skills, and seed standard skill level profiles.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (
            $schemaManager->tablesExist(['skill_level'])
            && !$schemaManager->introspectTable('skill_level')->hasColumn('description')
        ) {
            $this->connection->executeStatement('ALTER TABLE skill_level ADD description LONGTEXT DEFAULT NULL');
            $this->connection->executeStatement("UPDATE skill_level SET description = '' WHERE description IS NULL");
            $this->connection->executeStatement('ALTER TABLE skill_level MODIFY description LONGTEXT NOT NULL');
        }

        if (
            $schemaManager->tablesExist(['skill'])
            && !$schemaManager->introspectTable('skill')->hasColumn('acquirable')
        ) {
            $this->connection->executeStatement('ALTER TABLE skill ADD acquirable TINYINT(1) DEFAULT 1 NOT NULL');
        }

        if (!$schemaManager->tablesExist(['skill_level_profile', 'skill_level'])) {
            $this->write('Skipped standard skill level seeding because the required tables do not exist.');

            return;
        }

        foreach (SkillLevelFixtures::getProfiles() as $profileData) {
            $profileId = $this->connection->fetchOne(
                'SELECT id FROM skill_level_profile WHERE title = ? ORDER BY id ASC LIMIT 1',
                [$profileData['title']],
            );

            if (!$profileId) {
                $this->connection->insert('skill_level_profile', ['title' => $profileData['title']]);
                $profileId = (int) $this->connection->lastInsertId();
            } else {
                $profileId = (int) $profileId;
            }

            foreach ($profileData['levels'] as $levelData) {
                $levelId = $this->connection->fetchOne(
                    'SELECT id FROM skill_level WHERE profile_id = ? AND short_title = ? ORDER BY id ASC LIMIT 1',
                    [$profileId, $levelData['shortTitle']],
                );

                $values = [
                    'profile_id' => $profileId,
                    'title' => $levelData['title'],
                    'position' => $levelData['position'],
                    'short_title' => $levelData['shortTitle'],
                    'description' => $levelData['description'],
                ];

                if ($levelId) {
                    $this->connection->update('skill_level', $values, ['id' => (int) $levelId]);
                } else {
                    $this->connection->insert('skill_level', $values);
                }
            }
        }

        if ($schemaManager->tablesExist(['skill'])) {
            $this->connection->executeStatement(
                'UPDATE skill SET acquirable = 0 WHERE short_code = ?',
                ['root'],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (
            $schemaManager->tablesExist(['skill'])
            && $schemaManager->introspectTable('skill')->hasColumn('acquirable')
        ) {
            $this->connection->executeStatement('ALTER TABLE skill DROP acquirable');
        }

        if (
            $schemaManager->tablesExist(['skill_level'])
            && $schemaManager->introspectTable('skill_level')->hasColumn('description')
        ) {
            $this->connection->executeStatement('ALTER TABLE skill_level DROP description');
        }

        $this->write('Seeded standard skill level profiles and levels are intentionally kept to avoid deleting user-referenced data.');
    }
}
