<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20241025120000 extends AbstractMigrationChamilo
{
    private const FLUSH_BATCH_SIZE = 25;

    public function getDescription(): string
    {
        return 'Ensure resource nodes and files for quiz question pictures using streamed questions and bounded flushes.';
    }

    public function up(Schema $schema): void
    {
        /** @var CQuizQuestionRepository $quizQuestionRepo */
        $quizQuestionRepo = $this->container->get(CQuizQuestionRepository::class);
        /** @var CDocumentRepository $documentRepo */
        $documentRepo = $this->container->get(CDocumentRepository::class);

        $query = $this->entityManager->createQuery(
            'SELECT question
             FROM Chamilo\\CourseBundle\\Entity\\CQuizQuestion question
             WHERE question.picture IS NOT NULL AND question.picture <> :empty
             ORDER BY question.iid'
        )->setParameter('empty', '');

        $courseAdmin = $this->getAdmin();
        $seen = 0;
        $createdNodes = 0;
        $migratedFiles = 0;
        $missingDocuments = 0;
        $missingFiles = 0;
        $pendingFlush = 0;

        foreach ($query->toIterable() as $question) {
            if (!$question instanceof CQuizQuestion) {
                continue;
            }

            ++$seen;
            $pictureId = trim((string) $question->getPicture());
            if ('' === $pictureId) {
                $this->entityManager->detach($question);
                continue;
            }

            $document = null;

            if (!$question->hasResourceNode()) {
                $document = $this->findDocumentByPictureId($pictureId, $documentRepo);
                $resourceLink = $document?->getResourceNode()?->getResourceLinks()->first();
                $course = $resourceLink ?: null;
                $course = $course ? $course->getCourse() : null;

                if (!$document instanceof CDocument || null === $course) {
                    ++$missingDocuments;
                    $this->entityManager->detach($question);
                    continue;
                }

                // A flush is required here because the following file phase needs
                // the newly-created resource node and its identifier.
                $resourceNode = $quizQuestionRepo->addResourceNode($question, $courseAdmin, $course);
                $this->entityManager->persist($resourceNode);
                $this->entityManager->persist($question);
                $this->entityManager->flush();
                ++$createdNodes;
            }

            if (!$question->hasResourceNode() || $question->getResourceNode()->hasResourceFile()) {
                $this->entityManager->detach($question);
                continue;
            }

            $document ??= $this->findDocumentByPictureId($pictureId, $documentRepo);
            if (!$document instanceof CDocument || !$document->hasResourceNode()) {
                ++$missingDocuments;
                $this->entityManager->detach($question);
                continue;
            }

            if (!$document->getResourceNode()->hasResourceFile()) {
                $resourceLink = $document->getResourceNode()->getResourceLinks()->first();
                $course = $resourceLink ? $resourceLink->getCourse() : null;
                if (null === $course) {
                    ++$missingDocuments;
                    $this->entityManager->detach($question);
                    continue;
                }

                $filePath = $this->getUpdateRootPath().'/app/courses/'
                    .$course->getDirectory().'/document/images/'.$document->getTitle();

                if (!$this->fileExists($filePath)) {
                    ++$missingFiles;
                    $this->entityManager->detach($question);
                    continue;
                }

                $this->addLegacyFileToResource($filePath, $documentRepo, $document, $document->getIid());
                $this->entityManager->persist($document);
                $this->entityManager->flush();
            }

            if (!$document->getResourceNode()->hasResourceFile()) {
                ++$missingFiles;
                $this->entityManager->detach($question);
                continue;
            }

            $resourceFile = $document->getResourceNode()->getResourceFiles()->first();
            if (!$resourceFile) {
                ++$missingFiles;
                $this->entityManager->detach($question);
                continue;
            }

            $contents = $documentRepo->getResourceFileContent($document);
            $quizQuestionRepo->addFileFromString(
                $question,
                $resourceFile->getOriginalName(),
                $resourceFile->getMimeType(),
                $contents
            );
            $this->entityManager->persist($question);
            ++$migratedFiles;
            ++$pendingFlush;

            if ($pendingFlush >= self::FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $courseAdmin = $this->getAdmin();
                $pendingFlush = 0;

                $this->getLogger()->info('Quiz question picture migration progress.', [
                    'seen' => $seen,
                    'created_nodes' => $createdNodes,
                    'migrated_files' => $migratedFiles,
                    'missing_documents' => $missingDocuments,
                    'missing_files' => $missingFiles,
                ]);
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Quiz question picture migration completed.', [
            'seen' => $seen,
            'created_nodes' => $createdNodes,
            'migrated_files' => $migratedFiles,
            'missing_documents' => $missingDocuments,
            'missing_files' => $missingFiles,
        ]);
    }

    private function findDocumentByPictureId(string $pictureId, CDocumentRepository $documentRepo): ?CDocument
    {
        if (str_starts_with($pictureId, 'quiz-')) {
            $document = $documentRepo->findOneBy(['title' => $pictureId]);

            return $document instanceof CDocument ? $document : null;
        }

        $document = $documentRepo->find($pictureId);

        return $document instanceof CDocument ? $document : null;
    }
}
