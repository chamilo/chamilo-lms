<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Filesystem\Filesystem;

class Version20250328161000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate ext_translations.locale to new ISO code format for sublanguages';
    }

    public function up(Schema $schema): void
    {
        $sublanguages = $this->connection
            ->executeQuery(
                "SELECT * FROM language
                WHERE parent_id IS NOT NULL AND isocode NOT IN('".implode("', '", Version20::ALLOWED_SUBLANGUAGES)."')"
            )
            ->fetchAllAssociative()
        ;

        $filesystem = new Filesystem();
        $projectDir = $this->container->get('kernel')->getProjectDir();

        /** @var array $sublanguage */
        foreach ($sublanguages as $sublanguage) {
            $newIsoCode = $this->getNewIsoCode($sublanguage);

            // Update the isocode in the language table
            $this->connection->executeStatement(
                'UPDATE ext_translations SET locale = ? WHERE locale = ?',
                [$newIsoCode, $sublanguage['isocode']]
            );
        }
    }

    private function getNewIsoCode(array $sublanguage)
    {
        $parentId = $sublanguage['parent_id'];

        // Query to obtain the isocode of the parent language
        $parentIsoCode = $this->connection
            ->executeQuery('SELECT isocode FROM language WHERE id = ?', [$parentId])
            ->fetchOne()
        ;

        // Get the prefix of the parent language's isocode
        $firstIso = explode('_', $parentIsoCode)[0];

        return $firstIso.'_'.$sublanguage['id'];
    }
}
