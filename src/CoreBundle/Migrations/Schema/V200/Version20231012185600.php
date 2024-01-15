<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20231012185600 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate missing c_quiz items as resource nodes';
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
        $userRepo = $container->get(UserRepository::class);

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);
            $courseRelUserList = $course->getTeachersSubscriptions();
            $courseAdmin = null;
            if (!empty($courseRelUserList)) {
                foreach ($courseRelUserList as $courseRelUser) {
                    $courseAdmin = $courseRelUser->getUser();

                    break;
                }
            }

            if (null === $courseAdmin) {
                $courseAdmin = $this->getAdmin();
            }

            // Quiz
            $sql = "SELECT * FROM c_quiz WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];

                /** @var CQuiz $quiz */
                $quiz = $quizRepo->find($id);
                if ($quiz->hasResourceNode()) {
                    continue;
                }

                error_log('Version20231012185600 checking quiz '.$id.' as resource node ');
                $courseAdmin = $userRepo->find($courseAdmin->getId());
                $quiz->setParent($course);
                $resourceNode = $quizRepo->addResourceNode($quiz, $courseAdmin, $course);
                $quiz->addCourseLink($course);
                $em->persist($resourceNode);
                $em->persist($quiz);
                $em->flush();
            }
        }
    }
}
