<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Class Version_a
 * Remove enable_nanogong and enable_wami_record settings and create enable_record_audio
 * @package Application\Migrations\Schema\V111
 */
class Version20160421112900 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $em = $this->getEntityManager();

        $enableNanogong = $em
            ->getRepository('ChamiloCoreBundle:SettingsCurrent')
            ->findOneBy(['variable' => 'enable_nanogong']);

        $enableWami = $em
            ->getRepository('ChamiloCoreBundle:SettingsCurrent')
            ->findOneBy(['variable' => 'enable_wami_record']);

        $enableRecordAudioValue = 'true';

        if ($enableNanogong->getSelectedValue() === 'false' && $enableWami->getSelectedValue() === 'false') {
            $enableRecordAudioValue = 'false';
        }

        $this->addSettingCurrent(
            'enable_record_audio',
            null,
            'radio',
            'Course',
            $enableRecordAudioValue,
            'EnableRecordAudioTitle',
            'EnableRecordAudioComment',
            null,
            '',
            1,
            true,
            false,
            [
                ['value' => 'false', 'text' => 'No'],
                ['value' => 'true', 'text' => 'Yes']
            ]
        );

        $em->remove($enableNanogong);
        $em->remove($enableWami);
        $em->flush();
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}