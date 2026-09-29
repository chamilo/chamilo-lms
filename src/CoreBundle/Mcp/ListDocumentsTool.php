<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

use const DATE_ATOM;

final readonly class ListDocumentsTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array{
     *     course_id: int,
     *     total: int,
     *     title_filter: string|null,
     *     limit: int,
     *     documents: array<int, array{
     *         document_id: int,
     *         title: string,
     *         modified_at: string|null,
     *         size: int,
     *         language: string|null
     *     }>
     * }
     */
    #[McpTool(
        name: 'list_documents',
        description: 'List or filter documents in a base course managed by the authenticated teacher. Returns title, modification date, size, language and internal ID, but not document content. Use titleContains to narrow the result before referencing a document in another tool call.',
    )]
    public function listDocuments(int $courseId, ?string $titleContains = null, int $limit = 50): array
    {
        try {
            return $this->doListDocuments($courseId, $titleContains, $limit);
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The documents could not be listed because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{
     *     course_id: int,
     *     total: int,
     *     title_filter: string|null,
     *     limit: int,
     *     documents: array<int, array{
     *         document_id: int,
     *         title: string,
     *         modified_at: string|null,
     *         size: int,
     *         language: string|null
     *     }>
     * }
     */
    private function doListDocuments(int $courseId, ?string $titleContains, int $limit): array
    {
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('The document result limit must be between 1 and 100.');
        }

        $titleContains = null !== $titleContains ? trim($titleContains) : '';
        if (mb_strlen($titleContains) > 250) {
            throw new InvalidArgumentException('The document title filter cannot be longer than 250 characters.');
        }

        $context = $this->courseContext->resolve($courseId);
        $course = $context['course'];

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('document')
            ->from(CDocument::class, 'document')
            ->innerJoin('document.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'resourceLink')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->andWhere('resourceLink.session IS NULL')
            ->andWhere('resourceLink.group IS NULL')
            ->andWhere('resourceLink.userGroup IS NULL')
            ->andWhere('resourceLink.user IS NULL')
            ->andWhere('document.filetype != :folderType')
            ->andWhere('document.template = :isTemplate')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('folderType', 'folder')
            ->setParameter('isTemplate', false)
            ->orderBy('document.title', 'ASC')
            ->addOrderBy('document.iid', 'ASC')
            ->setMaxResults($limit)
        ;

        if ('' !== $titleContains) {
            $queryBuilder
                ->andWhere('LOWER(document.title) LIKE :titleContains')
                ->setParameter('titleContains', '%'.mb_strtolower($titleContains).'%', Types::STRING)
            ;
        }

        /** @var CDocument[] $documents */
        $documents = $queryBuilder->getQuery()->getResult();

        $items = [];
        foreach ($documents as $document) {
            $resourceFile = $document->getResourceNode()?->getFirstResourceFile();

            $items[] = [
                'document_id' => (int) $document->getIid(),
                'title' => $document->getTitle(),
                'modified_at' => $resourceFile?->getUpdatedAt()?->format(DATE_ATOM),
                'size' => (int) ($resourceFile?->getSize() ?? 0),
                'language' => $resourceFile?->getLanguage()?->getIsocode(),
            ];
        }

        return [
            'course_id' => (int) $course->getId(),
            'title_filter' => '' !== $titleContains ? $titleContains : null,
            'limit' => $limit,
            'total' => \count($items),
            'documents' => $items,
        ];
    }
}
