<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20201215142610 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_quiz, c_quiz_question_category, c_quiz_question';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $quizRepo = $container->get(CQuizRepository::class);
        $quizQuestionRepo = $container->get(CQuizQuestionRepository::class);
        $quizQuestionCategoryRepo = $container->get(CQuizQuestionCategoryRepository::class);

        $documentRepo = $container->get(CDocumentRepository::class);
        $courseRepo = $container->get(CourseRepository::class);
        $sessionRepo = $container->get(SessionRepository::class);
        $groupRepo = $container->get(CGroupRepository::class);
        $userRepo = $container->get(UserRepository::class);

        $admin = $this->getAdmin();

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);
            $courseRelUserList = $course->getTeachers();
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
                /** @var CQuiz $resource */
                $resource = $quizRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }
                $result = $this->fixItemProperty(
                    'quiz',
                    $quizRepo,
                    $course,
                    $courseAdmin,
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

            $em->flush();
            $em->clear();

            // Question categories.
            $sql = "SELECT * FROM c_quiz_question_category WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();

            $course = $courseRepo->find($courseId);
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                /** @var CQuizQuestionCategory $resource */
                $resource = $quizQuestionCategoryRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }
                $result = $this->fixItemProperty(
                    'test_category',
                    $quizQuestionCategoryRepo,
                    $course,
                    $courseAdmin,
                    $resource,
                    $course
                );
                if (false === $result) {
                    continue;
                }

                $em->persist($resource);
                $em->flush();
            }

            $em->flush();
            $em->clear();

            //$courseAdmin = $userRepo->find($courseAdmin->getId());

            $sql = "SELECT * FROM c_quiz_question WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                $course = $courseRepo->find($courseId);
                /** @var CQuizQuestion $question */
                $question = $quizQuestionRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }

                $question->setParent($course);
                //$resourceNode = $quizQuestionRepo->addResourceNode($resource, $courseAdmin, $course);
                $question->addCourseLink($course);
                //$em->persist($resourceNode);
                $em->persist($question);
                $em->flush();

                $pictureId = $question->getPicture();
                if (!empty($pictureId)) {
                    /** @var CDocument $document */
                    $document = $documentRepo->find($pictureId);
                    if ($document && $document->hasResourceNode() && $document->getResourceNode()->hasResourceFile()) {
                        $resourceFile = $document->getResourceNode()->getResourceFile();
                        $question->getResourceNode()->setResourceFile($resourceFile);
                        //$em->persist($resourceNode);
                    }
                }

                $em->persist($question);
                $em->flush();
            }
            $em->flush();
            $em->clear();
        }
    }
}
