<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20241025120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Ensure resource node and ResourceFile creation for CQuizQuestion with picture associations.';
    }

    public function up(Schema $schema): void
    {
        $quizQuestionRepo = $this->container->get(CQuizQuestionRepository::class);
        $documentRepo = $this->container->get(CDocumentRepository::class);

        $questions = $quizQuestionRepo->findAll();

        foreach ($questions as $question) {
            $courseAdmin = $this->getAdmin();
            $pictureId = $question->getPicture();
            $course = null;

            if (!$question->hasResourceNode()) {
                if ($pictureId) {
                    $document = $this->findDocumentByPictureId($pictureId, $documentRepo);
                    if ($document && $document->hasResourceNode() && $document->getResourceNode()->getResourceLinks()->first()) {
                        $course = $document->getResourceNode()->getResourceLinks()->first()->getCourse();
                        error_log('Creating resource node for question IID ' . $question->getIid());

                        $resourceNode = $quizQuestionRepo->addResourceNode($question, $courseAdmin, $course);
                        $this->entityManager->persist($resourceNode);

                        // Flush here to ensure the resource node is saved
                        $this->entityManager->flush();
                    } else {
                        error_log('No course association for question IID ' . $question->getIid() . ' with document ' . $pictureId);
                        continue;
                    }
                }
            }

            if ($question->hasResourceNode() && $pictureId && !$question->getResourceNode()->hasResourceFile()) {
                error_log('Existing resource node found for question IID ' . $question->getIid() . ' but no ResourceFile.');

                $document = $this->findDocumentByPictureId($pictureId, $documentRepo);
                if ($document) {
                    error_log('Document found for picture ID ' . $pictureId . ' and question IID ' . $question->getIid());

                    if ($document->hasResourceNode() && !$document->getResourceNode()->hasResourceFile()) {
                        $course = $document->getResourceNode()->getResourceLinks()->first()->getCourse();
                        $filePath = $this->getUpdateRootPath() . '/app/courses/' . $course->getDirectory() . '/document/images/' . $document->getTitle();
                        if (file_exists($filePath)) {
                            $this->addLegacyFileToResource($filePath, $documentRepo, $document, $document->getIid());
                            $this->entityManager->persist($document);
                            $this->entityManager->flush();
                            error_log('ResourceFile created and flushed for document with IID ' . $document->getIid());
                        } else {
                            continue;
                        }
                    }

                    if ($document->getResourceNode()->hasResourceFile()) {
                        $resourceFile = $document->getResourceNode()->getResourceFiles()->first();
                        error_log('Resource file ready for question IID ' . $question->getIid() . ': ' . $resourceFile->getOriginalName());

                        $contents = $documentRepo->getResourceFileContent($document);
                        $quizQuestionRepo->addFileFromString(
                            $question,
                            $resourceFile->getOriginalName(),
                            $resourceFile->getMimeType(),
                            $contents
                        );
                        $this->entityManager->persist($question);
                    }
                }
            }
        }

        $this->entityManager->flush();
    }

    private function findDocumentByPictureId(string $pictureId, $documentRepo): ?CDocument
    {
        if (str_starts_with($pictureId, 'quiz-')) {
            return $documentRepo->findOneBy(['title' => $pictureId]) ?: null;
        }

        return $documentRepo->find($pictureId);
    }
}
