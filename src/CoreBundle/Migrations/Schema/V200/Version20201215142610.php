<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20201215142610 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_quiz';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $quizRepo = $container->get(CQuizRepository::class);
        $courseRepo = $container->get(CourseRepository::class);
        $sessionRepo = $container->get(SessionRepository::class);
        $groupRepo = $container->get(CGroupRepository::class);
        $userRepo = $container->get(UserRepository::class);

        $admin = $this->getAdmin();

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            $sql = "SELECT * FROM c_quiz WHERE c_id = $courseId
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                /** @var CQuiz $resource */
                $resource = $quizRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }
                $result = $this->fixItemProperty(
                    'quiz',
                    $quizRepo,
                    $course,
                    $admin,
                    $resource,
                    $course
                );
                if (false === $result) {
                    continue;
                }

                $em->persist($resource);
                $em->flush();

                /*$sql = "SELECT q.* FROM c_quiz_question q
                        INNER JOIN c_quiz_rel_question cq
                        ON (q.iid = cq.exercice_id and q.c_id = cq.c_id)
                        WHERE q.c_id = $courseId AND exercice_id = $id
                        ORDER BY iid";
                $result = $connection->executeQuery($sql);
                $questions = $result->fetchAllAssociative();
                foreach ($questions as $questionData) {
                    $questionData[''];
                }
                $sql = "SELECT * FROM c_quiz_question WHERE c_id = $courseId
                        ORDER BY iid";
                $result = $connection->executeQuery($sql);
                $items = $result->fetchAllAssociative();
                foreach ($items as $itemData) {
                }*/
            }
        }
    }
}
