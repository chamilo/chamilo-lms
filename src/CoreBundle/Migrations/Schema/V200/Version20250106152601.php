<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Filesystem\Filesystem;

final class Version20250106152601 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Set new ISO code for sub-languages.';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $kernel = $this->container->get('kernel');
        $baseTranslationPath = $kernel->getProjectDir().'/var/translations/messages.';

        $fs = new Filesystem();

        $subLanguages = $this->connection
            ->executeQuery("SELECT id, isocode, parent_id FROM language WHERE parent_id IS NOT NULL")
            ->fetchAllAssociative()
        ;

        /** @var array $subLanguage */
        foreach ($subLanguages as $subLanguage) {

            $parentIsoCode = $this->connection
                ->executeQuery('SELECT isocode FROM language WHERE id = ?', [$subLanguage['parent_id']])
                ->fetchOne()
            ;

            $newIsoCode = sprintf(
                '%s_%d',
                explode('_', $parentIsoCode)[0],
                $subLanguage['id']
            );

            $params = [
                'new_iso' => $newIsoCode,
                'old_iso' => $subLanguage['isocode'],
            ];

            if ($params['new_iso'] === $params['old_iso']) {
                continue;
            }

            $this->addSql(
                'UPDATE language SET isocode = :new_iso WHERE id = :id',
                [
                    'new_iso' => $newIsoCode,
                    'id' => $subLanguage['id'],
                ]
            );

            $this->addSql('UPDATE user SET locale = :new_iso WHERE locale = :old_iso', $params);
            $this->addSql('UPDATE course SET course_language = :new_iso WHERE course_language = :old_iso', $params);
            $this->addSql("UPDATE settings SET selected_value = :new_iso WHERE variable = 'platform_language' AND selected_value = :old_iso", $params);

            $oldPoFile = $baseTranslationPath.$params['old_iso'].'.po';
            $newPoFile = $baseTranslationPath.$params['new_iso'].'.po';

            if ($fs->exists($oldPoFile)) {
                $fs->rename($oldPoFile, $newPoFile);
            }
        }
    }
}
