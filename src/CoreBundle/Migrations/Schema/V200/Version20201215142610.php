<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20201215142610 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_quiz, c_quiz_question_category, c_quiz_question';
    }

    public function up(Schema $schema): void
    {
        $quizRepo = $this->container->get(CQuizRepository::class);
        $quizQuestionRepo = $this->container->get(CQuizQuestionRepository::class);
        $quizQuestionCategoryRepo = $this->container->get(CQuizQuestionCategoryRepository::class);
        $documentRepo = $this->container->get(CDocumentRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

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
            $result = $this->connection->executeQuery($sql);
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

                $this->entityManager->persist($resource);
                $this->entityManager->flush();

                /*$sql = "SELECT q.* FROM c_quiz_question q
                        INNER JOIN c_quiz_rel_question cq
                        ON (q.iid = cq.exercice_id and q.c_id = cq.c_id)
                        WHERE q.c_id = $courseId AND exercice_id = $id
                        ORDER BY iid";
                $result = $this->connection->executeQuery($sql);
                $questions = $result->fetchAllAssociative();
                foreach ($questions as $questionData) {
                    $questionData[''];
                }
                $sql = "SELECT * FROM c_quiz_question WHERE c_id = $courseId
                        ORDER BY iid";
                $result = $this->connection->executeQuery($sql);
                $items = $result->fetchAllAssociative();
                foreach ($items as $itemData) {
                }*/
            }

            $this->entityManager->flush();

            // Question categories.
            $sql = "SELECT * FROM c_quiz_question_category WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
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

                $this->entityManager->persist($resource);
                $this->entityManager->flush();
            }

            $this->entityManager->flush();

            $sql = "SELECT * FROM c_quiz_question WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                $course = $courseRepo->find($courseId);

                /** @var CQuizQuestion $question */
                $question = $quizQuestionRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }

                $courseAdmin = $userRepo->find($courseAdmin->getId());
                $question->setParent($course);
                $resourceNode = $quizQuestionRepo->addResourceNode($question, $courseAdmin, $course);
                $question->addCourseLink($course);
                $this->entityManager->persist($resourceNode);
                $this->entityManager->persist($question);
                $this->entityManager->flush();

                /** @var CQuizQuestion $question */
                $question = $quizQuestionRepo->find($id);
                $pictureId = $question->getPicture();
                if (!empty($pictureId)) {
                    /** @var CDocument $document */
                    $document = $documentRepo->find($pictureId);
                    if ($document && $document->hasResourceNode() && $document->getResourceNode()->hasResourceFile()) {
                        $resourceFile = $document->getResourceNode()->getResourceFiles()->first();
                        $contents = $documentRepo->getResourceFileContent($document);
                        $quizQuestionRepo->addFileFromString($question, $resourceFile->getOriginalName(), $resourceFile->getMimeType(), $contents);
                    }
                }

                $this->entityManager->persist($question);
                $this->entityManager->flush();
            }
            $this->entityManager->flush();
        }
    }
}
