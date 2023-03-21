<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\TrackEAttemptQualify;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20230321164019 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate track_e_attempt_recording';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();

        $sql = 'SELECT * FROM track_e_attempt_recording';
        $connection = $this->getEntityManager()->getConnection();
        $result = $connection->executeQuery($sql);
        $items = $result->fetchAllAssociative();

        foreach ($items as $item) {
            /** @var TrackEAttemptQualify $trackQualify */
            $trackQualify = new TrackEAttemptQualify();
            $trackQualify
                ->setExeId($item['exe_id'])
                ->setQuestionId($item['question_id'])
                ->setAnswer($item['answer'])
                ->setMarks((int) $item['marks'])
                ->setAuthor($item['author'])
                ->setTeacherComment($item['teacher_comment'])
                ->setSessionId($item['session_id'])
            ;
            $em->persist($trackQualify);
            $em->flush();
        }
    }

    public function down(Schema $schema): void
    {
    }
}
