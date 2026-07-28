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
        description: 'List the documents in a base course managed by the authenticated teacher (title, modification date, size, language and internal ID) so the MCP client can locate one document within the list before referencing it precisely in another tool call. Not meant to return document content.',
    )]
    public function listDocuments(int $courseId): array
    {
        try {
            return $this->doListDocuments($courseId);
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
     *     documents: array<int, array{
     *         document_id: int,
     *         title: string,
     *         modified_at: string|null,
     *         size: int,
     *         language: string|null
     *     }>
     * }
     */
    private function doListDocuments(int $courseId): array
    {
        $context = $this->courseContext->resolve($courseId);
        $course = $context['course'];

        /** @var CDocument[] $documents */
        $documents = $this->entityManager->createQueryBuilder()
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
            ->getQuery()
            ->getResult()
        ;

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
            'total' => \count($items),
            'documents' => $items,
        ];
    }
}
