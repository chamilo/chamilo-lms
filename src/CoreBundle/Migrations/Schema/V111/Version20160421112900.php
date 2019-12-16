<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V111;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version_a
 * Remove enable_nanogong and enable_wami_record settings and create enable_record_audio.
 */
class Version20160421112900 extends AbstractMigrationChamilo
{
    /**
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

        if ('false' === $enableNanogong->getSelectedValue() && 'false' === $enableWami->getSelectedValue()) {
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
                ['value' => 'true', 'text' => 'Yes'],
            ]
        );

        $em->remove($enableNanogong);
        $em->remove($enableWami);
        $em->flush();
    }

    public function down(Schema $schema)
    {
    }
}
