<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

use const PHP_SAPI;

final class Version20260928090000 extends AbstractMigrationChamilo
{
    private const array DEMO_LEARNING_PATHS = [
        'AIACT' => 'AI Act Awareness for European Organizations',
        'USINGCHAMILO' => 'Getting Started with Chamilo 3.0',
    ];

    private const array DEMO_DOCUMENTS_TO_PUBLISH = [
        'AIACT' => ['actors.jpg'],
    ];

    private const array DEMO_DOCUMENT_MIME_TYPES = [
        'AIACT' => ['actors.jpg' => 'image/jpeg'],
    ];

    public function getDescription(): string
    {
        return 'Repair bundled demo course LP prerequisites and embedded image metadata.';
    }

    public function up(Schema $schema): void
    {
        if ($this->isSimulationMode()) {
            $this->write('Skipping bundled demo course content repair during migration simulation.');

            return;
        }

        foreach (['course', 'c_lp', 'c_lp_item', 'c_document', 'resource_node', 'resource_link'] as $table) {
            if (!$schema->hasTable($table)) {
                return;
            }
        }

        $entityManager = $this->entityManager;
        if (null === $entityManager) {
            throw new RuntimeException('The EntityManager is required to repair bundled demo courses.');
        }

        $prerequisitesRepaired = 0;
        $documentsPublished = 0;
        $documentMimeTypesRepaired = 0;

        foreach (self::DEMO_LEARNING_PATHS as $courseCode => $learningPathTitle) {
            $course = $entityManager->getRepository(Course::class)->findOneBy(['code' => $courseCode]);
            if (!$course instanceof Course) {
                continue;
            }

            $prerequisitesRepaired += $this->repairLearningPathPrerequisites($course, $learningPathTitle);
            $documentsPublished += $this->publishDemoDocuments(
                $course,
                self::DEMO_DOCUMENTS_TO_PUBLISH[$courseCode] ?? [],
            );
            $documentMimeTypesRepaired += $this->repairDemoDocumentMimeTypes(
                $course,
                self::DEMO_DOCUMENT_MIME_TYPES[$courseCode] ?? [],
            );
        }

        $entityManager->flush();

        $this->write(\sprintf(
            'Bundled demo course content repaired: %d prerequisite references updated, %d documents published, %d document MIME types repaired.',
            $prerequisitesRepaired,
            $documentsPublished,
            $documentMimeTypesRepaired
        ));
    }

    public function down(Schema $schema): void
    {
        // Do not restore broken prerequisite references or hide a learner-facing image.
        $this->write('Bundled demo course content repair is intentionally not reverted.');
    }

    private function repairLearningPathPrerequisites(Course $course, string $learningPathTitle): int
    {
        $entityManager = $this->entityManager;
        if (null === $entityManager) {
            return 0;
        }

        $learningPath = $entityManager->createQueryBuilder()
            ->select('lp')
            ->from(CLp::class, 'lp')
            ->innerJoin('lp.resourceNode', 'resourceNode')
            ->innerJoin('resourceNode.resourceLinks', 'resourceLink')
            ->andWhere('resourceLink.course = :course')
            ->andWhere('resourceLink.session IS NULL')
            ->andWhere('resourceLink.group IS NULL')
            ->andWhere('lp.title = :title')
            ->setParameter('course', $course)
            ->setParameter('title', $learningPathTitle)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$learningPath instanceof CLp) {
            return 0;
        }

        $items = $entityManager->getRepository(CLpItem::class)
            ->createQueryBuilder('item')
            ->andWhere('item.lp = :learningPath')
            ->andWhere('item.itemType <> :rootType')
            ->setParameter('learningPath', $learningPath)
            ->setParameter('rootType', 'root')
            ->orderBy('item.displayOrder', 'ASC')
            ->addOrderBy('item.iid', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!\is_array($items) || [] === $items) {
            return 0;
        }

        $itemIds = [];
        foreach ($items as $item) {
            if ($item instanceof CLpItem && null !== $item->getIid()) {
                $itemIds[(int) $item->getIid()] = true;
            }
        }

        $updated = 0;
        $previous = null;

        foreach ($items as $item) {
            if (!$item instanceof CLpItem) {
                continue;
            }

            if (!$previous instanceof CLpItem) {
                $previous = $item;

                continue;
            }

            $currentPrerequisite = trim((string) $item->getPrerequisite());
            $hasValidPrerequisite = '' !== $currentPrerequisite
                && (!ctype_digit($currentPrerequisite) || isset($itemIds[(int) $currentPrerequisite]));

            if (!$hasValidPrerequisite) {
                $previousId = (int) ($previous->getIid() ?? 0);
                if ($previousId > 0) {
                    $item->setPrerequisite((string) $previousId);
                    $entityManager->persist($item);
                    ++$updated;
                }
            }

            $previous = $item;
        }

        return $updated;
    }

    /**
     * @param string[] $documentTitles
     */
    private function publishDemoDocuments(Course $course, array $documentTitles): int
    {
        if ([] === $documentTitles) {
            return 0;
        }

        $entityManager = $this->entityManager;
        if (null === $entityManager) {
            return 0;
        }

        $documents = $entityManager->createQueryBuilder()
            ->select('document')
            ->from(CDocument::class, 'document')
            ->innerJoin('document.resourceNode', 'resourceNode')
            ->innerJoin('resourceNode.resourceLinks', 'resourceLink')
            ->andWhere('resourceLink.course = :course')
            ->andWhere('resourceLink.session IS NULL')
            ->andWhere('resourceLink.group IS NULL')
            ->andWhere('document.title IN (:titles)')
            ->setParameter('course', $course)
            ->setParameter('titles', $documentTitles)
            ->getQuery()
            ->getResult()
        ;

        if (!\is_array($documents)) {
            return 0;
        }

        $updated = 0;

        foreach ($documents as $document) {
            if (!$document instanceof CDocument) {
                continue;
            }

            $resourceLink = $document->getResourceNode()?->getResourceLinkByContext($course);
            if (!$resourceLink instanceof ResourceLink) {
                continue;
            }

            if (ResourceLink::VISIBILITY_PUBLISHED === $resourceLink->getVisibility()) {
                continue;
            }

            $resourceLink->setVisibility(ResourceLink::VISIBILITY_PUBLISHED);
            $entityManager->persist($resourceLink);
            ++$updated;
        }

        return $updated;
    }

    /**
     * @param array<string, string> $mimeTypesByDocumentTitle
     */
    private function repairDemoDocumentMimeTypes(Course $course, array $mimeTypesByDocumentTitle): int
    {
        if ([] === $mimeTypesByDocumentTitle) {
            return 0;
        }

        $entityManager = $this->entityManager;
        if (null === $entityManager) {
            return 0;
        }

        $documents = $entityManager->createQueryBuilder()
            ->select('document')
            ->from(CDocument::class, 'document')
            ->innerJoin('document.resourceNode', 'resourceNode')
            ->innerJoin('resourceNode.resourceLinks', 'resourceLink')
            ->andWhere('resourceLink.course = :course')
            ->andWhere('resourceLink.session IS NULL')
            ->andWhere('resourceLink.group IS NULL')
            ->andWhere('document.title IN (:titles)')
            ->setParameter('course', $course)
            ->setParameter('titles', array_keys($mimeTypesByDocumentTitle))
            ->getQuery()
            ->getResult()
        ;

        if (!\is_array($documents)) {
            return 0;
        }

        $updated = 0;

        foreach ($documents as $document) {
            if (!$document instanceof CDocument) {
                continue;
            }

            $expectedMimeType = $mimeTypesByDocumentTitle[$document->getTitle()] ?? null;
            if (null === $expectedMimeType) {
                continue;
            }

            $resourceFile = $document->getResourceNode()?->getFirstResourceFile();
            if (!$resourceFile instanceof ResourceFile || $expectedMimeType === $resourceFile->getMimeType()) {
                continue;
            }

            $resourceFile->setMimeType($expectedMimeType);
            $entityManager->persist($resourceFile);
            ++$updated;
        }

        return $updated;
    }

    private function isSimulationMode(): bool
    {
        if ('cli' !== PHP_SAPI) {
            return false;
        }

        foreach ($_SERVER['argv'] ?? [] as $argument) {
            $argument = (string) $argument;

            if ('--dry-run' === $argument || str_starts_with($argument, '--write-sql')) {
                return true;
            }
        }

        return false;
    }
}
