<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\Schema\Schema;
use Exception;

final class Version20251024173200 extends AbstractMigrationChamilo
{
    /**
     * Enable this to log changes without writing them to the database.
     */
    private const DRY_RUN = false;

    public function getDescription(): string
    {
        return 'Rewrite legacy /main/img/* by /img/* in HTML documents (filetype=certificate).';
    }

    public function up(Schema $schema): void
    {
        $this->entityManager->clear();

        /** @var CDocumentRepository $documentRepo */
        $documentRepo = $this->container->get(CDocumentRepository::class);

        /** @var ResourceNodeRepository $resourceNodeRepo */
        $resourceNodeRepo = $this->container->get(ResourceNodeRepository::class);

        // Course certificates saved as documents
        $certSql = "SELECT iid FROM c_document WHERE filetype = 'certificate'";
        $certItems = $this->connection->executeQuery($certSql)->fetchAllAssociative();
        $this->processHtmlDocuments($certItems, $documentRepo, $resourceNodeRepo, 'certificate');
    }

    /**
     * Iterate over candidate documents and update only those that are text/html,
     * replacing legacy asset paths in their HTML content.
     */
    private function processHtmlDocuments(
        array $items,
        CDocumentRepository $documentRepo,
        ResourceNodeRepository $resourceNodeRepo,
        string $context
    ): void {
        $total = \count($items);
        $processed = 0;
        $changed = 0;

        foreach ($items as $item) {
            $processed++;
            $iid = (int) $item['iid'];

            try {
                $document = $documentRepo->find($iid);
                if (!$document) {
                    continue;
                }

                $resourceNode = $document->getResourceNode();
                if (!$resourceNode || !$resourceNode->hasResourceFile()) {
                    continue;
                }

                $resourceFile = $resourceNode->getResourceFiles()->first();
                if (!$resourceFile) {
                    continue;
                }

                // Update HTML only (skip binaries and non-HTML text)
                $mime = $resourceFile->getMimeType();
                if (!\is_string($mime) || false === stripos($mime, 'text/html')) {
                    continue;
                }

                $content = $resourceNodeRepo->getResourceNodeFileContent($resourceNode);
                if (!\is_string($content) || '' === trim($content)) {
                    continue;
                }

                $updatedContent = $this->normalizeLegacyImgPaths($content);

                if ($updatedContent !== $content) {
                    $changed++;
                    if (!self::DRY_RUN) {
                        $documentRepo->updateResourceFileContent($document, $updatedContent);
                        $documentRepo->update($document);
                    } else {
                        error_log("[MIGRATION][DRY_RUN][{$context}] Would update HTML for document iid={$iid}");
                    }
                }
            } catch (Exception $e) {
                error_log("[MIGRATION][{$context}] Error processing iid={$iid}: ".$e->getMessage());
                // Continue with the next item
            }
        }

        error_log(\sprintf('[MIGRATION][%s] Processed=%d, Updated=%d (Total candidates=%d)', $context, $processed, $changed, $total));
    }

    /**
     * Normalize legacy C1 asset paths to C2:
     *  - /main/img/...  -> /img/...
     * Covers HTML attributes (src, href) and CSS url() references.
     */
    private function normalizeLegacyImgPaths(string $html): string
    {
        // Targeted replacements first, to cover common attribute and CSS forms
        $map = [
            'src="/main/img/' => 'src="/img/',
            "src='/main/img/" => "src='/img/",
            'href="/main/img/' => 'href="/img/',
            "href='/main/img/" => "href='/img/",
            'url(/main/img/' => 'url(/img/',
            'url("/main/img/' => 'url("/img/',
            "url('/main/img/" => "url('/img/",
        ];

        $updated = strtr($html, $map);

        // Fallback replacement to catch any remaining occurrences (absolute local paths only)
        return str_replace('/main/img/', '/img/', $updated);
    }

    public function down(Schema $schema): void {}
}
