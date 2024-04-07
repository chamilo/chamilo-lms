<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\TrackEAttemptQualify;
use Chamilo\CoreBundle\Entity\TrackEExercise;
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
        $em = $this->getEntityManager();

        $sql = 'SELECT * FROM track_e_attempt_recording';
        $connection = $this->getEntityManager()->getConnection();
        $result = $connection->executeQuery($sql);
        $items = $result->fetchAllAssociative();

        foreach ($items as $item) {
            $attemptQualify = new TrackEAttemptQualify();
            $attemptQualify
                ->setQuestionId($item['question_id'])
                ->setAnswer($item['answer'])
                ->setMarks($item['marks'])
                ->setAuthor($item['author'])
                ->setTeacherComment($item['teacher_comment'])
                ->setSessionId($item['session_id'])
            ;

            $trackEExercise = $em->getRepository(TrackEExercise::class)->find($item['exe_id']);
            if ($trackEExercise) {
                $attemptQualify->setTrackExercise($trackEExercise);
                $em->persist($attemptQualify);
            }
        }

        $em->flush();
    }

    public function down(Schema $schema): void {}
}
