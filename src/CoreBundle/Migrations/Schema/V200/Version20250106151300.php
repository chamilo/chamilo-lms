<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\Schema\Schema;
use Exception;

final class Version20250106151300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Fix incorrect resource links in HTML files already migrated.';
    }

    public function up(Schema $schema): void
    {
        $this->entityManager->clear();
        $this->processHtmlFiles();
    }

    private function processHtmlFiles(): void
    {
        // Select only HTML files that are linked to resource links
        $sql = "
            SELECT rl.resource_node_id, rl.c_id
            FROM resource_link rl
            JOIN c_document cd ON rl.resource_node_id = cd.resource_node_id
            WHERE cd.filetype = 'file'
        ";
        $result = $this->connection->executeQuery($sql);
        $items = $result->fetchAllAssociative();

        $documentRepo = $this->container->get(CDocumentRepository::class);
        $resourceNodeRepo = $this->container->get(ResourceNodeRepository::class);

        foreach ($items as $item) {
            try {
                // Find the document by resource node ID
                $document = $documentRepo->findOneBy(['resourceNode' => $item['resource_node_id']]);
                if (!$document) {
                    error_log("Document not found for resource node ID {$item['resource_node_id']}");
                    continue;
                }

                // Retrieve the resource node
                $resourceNode = $document->getResourceNode();
                if (!$resourceNode || !$resourceNode->hasResourceFile()) {
                    error_log("Resource node or file not found for document ID {$document->getIid()}");
                    continue;
                }

                // Get the first resource file and validate MIME type
                $resourceFile = $resourceNode->getResourceFiles()->first();
                if (!$resourceFile || 'text/html' !== $resourceFile->getMimeType()) {
                    error_log("Invalid or missing HTML file for document ID {$document->getIid()}");
                    continue;
                }

                // Fetch content of the HTML file
                $content = $resourceNodeRepo->getResourceNodeFileContent($resourceNode);
                if (!is_string($content) || '' === trim($content)) {
                    error_log("Empty or invalid content for resource node ID {$item['resource_node_id']}");
                    continue;
                }

                // Update the content with fixed links
                $updatedContent = $this->fixHtmlLinks($content, $document->getIid(), $item['resource_node_id'], $item['c_id']);
                if ($content !== $updatedContent) {
                    // Save the updated content back
                    $documentRepo->updateResourceFileContent($document, $updatedContent);
                    $documentRepo->update($document);
                    error_log("Updated content for document ID {$document->getIid()}");
                } else {
                    error_log("No changes required for document ID {$document->getIid()}");
                }
            } catch (Exception $e) {
                // Log errors for debugging purposes
                error_log("Error processing document with resource node ID {$item['resource_node_id']}: " . $e->getMessage());
            }
        }
    }

    private function fixHtmlLinks(string $content, int $documentId, int $resourceNodeId, int $currentCourseId): string
    {
        // Define the pattern to find resource links
        $pattern = '/\/r\/document\/files\/([a-f0-9\-]+)\/view/';

        return preg_replace_callback($pattern, function ($matches) use ($documentId, $resourceNodeId, $currentCourseId) {
            $uuid = $matches[1];

            // Normalize UUID by removing dashes and converting to binary
            $cleanUuid = strtoupper(str_replace('-', '', $uuid));
            $uuidBinary = pack('H*', $cleanUuid);

            // Check the resource link for the given UUID and fetch title
                $query = '
                SELECT rl.resource_node_id, rl.c_id, rn.title
                FROM resource_link rl
                JOIN resource_node rn ON rl.resource_node_id = rn.id
                WHERE rn.uuid = UNHEX(:uuid)
            ';
            $resourceData = $this->connection->fetchAssociative($query, ['uuid' => $cleanUuid]);

            if ($resourceData && $resourceData['c_id'] === $currentCourseId) {
                // Log debugging information
                error_log("Document ID $documentId, Resource Node $resourceNodeId: Valid resource found for UUID $uuid in the correct course $currentCourseId");

                // Return the corrected link with original UUID format
                return "/r/document/files/$uuid/view";
            } elseif ($resourceData) {
                // If the course ID doesn't match, find the correct file in the current course
                error_log("Document ID $documentId, Resource Node $resourceNodeId: UUID $uuid does not belong to course $currentCourseId. Searching for the correct file...");

                $documentRepo = $this->container->get(CDocumentRepository::class);
                $courseRepo = $this->container->get(CourseRepository::class);
                $title = $resourceData['title'];

                // Load the Course object
                $course = $courseRepo->find($currentCourseId);
                if (!$course) {
                    error_log("Document ID $documentId, Resource Node $resourceNodeId: Course with ID $currentCourseId not found.");
                    return $matches[0]; // Return original link if course not found
                }

                // Search for a document in the current course by title
                $correctDocument = $documentRepo->findResourceByTitleInCourse($title, $course);

                if ($correctDocument) {
                    $newUrl = $documentRepo->getResourceFileUrl($correctDocument);
                    error_log("Document ID $documentId: Correct URL found: $newUrl");

                    return $newUrl;
                } else {
                    error_log("Document ID $documentId, Resource Node $resourceNodeId: No document found for title $title in course $currentCourseId.");
                }
            }

            // Log missing resource data
            error_log("Document ID $documentId, Resource Node $resourceNodeId: Resource link for UUID $uuid not found.");
            return $matches[0]; // Return original link if no match found
        }, $content);
    }


    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'This migration cannot be rolled back.');
    }
}
