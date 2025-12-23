<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\SearchEngineRef;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

/**
 * Handles Xapian indexing for CDocument entities.
 */
final class DocumentXapianIndexer
{
    public function __construct(
        private readonly XapianIndexService $xapianIndexService,
        private readonly EntityManagerInterface $em,
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * Index a CDocument into Xapian.
     *
     * @return int|null Xapian document id or null when indexing is skipped
     */
    public function indexDocument(CDocument $document): ?int
    {
        $resourceNode = $document->getResourceNode();

        error_log(
            '[Xapian] indexDocument: start for iid='.(string) $document->getIid()
            .', resource_node_id='.($resourceNode ? $resourceNode->getId() : 'null')
            .', filetype='.$document->getFiletype()
        );

        // 1) Check if search is globally enabled
        $enabled = (string) $this->settingsManager->getSetting('search.search_enabled', true);
        error_log('[Xapian] indexDocument: search.search_enabled='.var_export($enabled, true));

        if ('true' !== $enabled) {
            error_log('[Xapian] indexDocument: search is disabled, skipping indexing');

            return null;
        }

        if (!$resourceNode instanceof ResourceNode) {
            error_log('[Xapian] indexDocument: missing ResourceNode, skipping');

            return null;
        }

        // Do not index folders
        if ('folder' === $document->getFiletype()) {
            error_log(
                '[Xapian] indexDocument: skipping folder document, resource_node_id='
                .$resourceNode->getId()
            );

            return null;
        }

        // 2) Resolve course, session and course root node ids
        [$courseId, $sessionId, $courseRootNodeId] = $this->resolveCourseSessionAndRootNode($resourceNode);

        error_log(
            '[Xapian] indexDocument: courseId='.var_export($courseId, true)
            .', sessionId='.var_export($sessionId, true)
            .', courseRootNodeId='.var_export($courseRootNodeId, true)
        );

        // 3) Get textual content if any
        $content = (string) ($resourceNode->getContent() ?? '');
        error_log('[Xapian] indexDocument: content_length='.\strlen($content));

        // 4) Build fields payload
        $fields = [
            'title' => (string) $document->getTitle(),
            'description' => (string) ($document->getComment() ?? ''),
            'content' => $content,
            'filetype' => (string) $document->getFiletype(),
            'resource_node_id' => (string) $resourceNode->getId(),
            'course_id' => null !== $courseId ? (string) $courseId : '',
            'session_id' => null !== $sessionId ? (string) $sessionId : '',
            'course_root_node_id' => null !== $courseRootNodeId ? (string) $courseRootNodeId : '',
            'full_path' => $document->getFullPath(),
        ];

        // 5) Base terms
        $terms = ['Tdocument'];

        if (null !== $courseId) {
            $terms[] = 'C'.$courseId;
        }
        if (null !== $sessionId) {
            $terms[] = 'S'.$sessionId;
        }

        // 6) Extra prefilter terms from config
        $this->applyPrefilterConfigToTerms($terms, $courseId, $sessionId, $document);

        error_log('[Xapian] indexDocument: terms='.json_encode($terms));

        $resourceNodeRef = $this->em->getReference(ResourceNode::class, (int) $resourceNode->getId());

        /** @var SearchEngineRef|null $existingRef */
        $existingRef = $this->em
            ->getRepository(SearchEngineRef::class)
            ->findOneBy(['resourceNode' => $resourceNodeRef])
        ;

        $existingDocId = $existingRef?->getSearchDid();
        error_log(
            '[Xapian] indexDocument: existing SearchEngineRef id='
            .($existingRef?->getId() ?? 'null')
            .', existing_did='.var_export($existingDocId, true)
        );

        // 7.1) If we already had a doc in Xapian, try to delete it first
        if (null !== $existingDocId) {
            try {
                $this->xapianIndexService->deleteDocument($existingDocId);
                error_log(
                    '[Xapian] indexDocument: previous docId deleted='
                    .var_export($existingDocId, true)
                );
            } catch (Throwable $e) {
                error_log(
                    '[Xapian] indexDocument: failed to delete previous docId='
                    .var_export($existingDocId, true)
                    .' error='.$e->getMessage()
                );
            }
        }

        // 8) Call Xapian (create new document)
        try {
            $docId = $this->xapianIndexService->indexDocument(
                $fields,
                $terms
            );
        } catch (Throwable $e) {
            error_log('[Xapian] indexDocument: indexDocument() failed: '.$e->getMessage());

            return null;
        }

        error_log(
            '[Xapian] indexDocument: XapianIndexService->indexDocument returned docId='
            .var_export($docId, true)
        );

        if ($existingRef instanceof SearchEngineRef) {
            $existingRef->setSearchDid($docId);
            error_log('[Xapian] indexDocument: updating existing SearchEngineRef id='.$existingRef->getId());
        } else {
            $existingRef = new SearchEngineRef();
            $existingRef->setResourceNode($resourceNodeRef);
            $existingRef->setSearchDid($docId);
            $this->em->persist($existingRef);
            error_log(
                '[Xapian] indexDocument: creating new SearchEngineRef for resource_node_id='
                .$resourceNode->getId()
            );
        }

        $this->em->flush();

        error_log('[Xapian] indexDocument: SearchEngineRef saved with id='.$existingRef->getId());

        return $docId;
    }

    /**
     * Remove a document from Xapian using the resource node id.
     */
    public function deleteForResourceNodeId(int $resourceNodeId): void
    {
        $enabled = (string) $this->settingsManager->getSetting('search.search_enabled', true);
        if ('true' !== $enabled) {
            error_log('[Xapian] deleteForResourceNodeId: search is disabled, skipping');

            return;
        }

        $resourceNodeRef = $this->em->getReference(ResourceNode::class, $resourceNodeId);

        /** @var SearchEngineRef|null $ref */
        $ref = $this->em
            ->getRepository(SearchEngineRef::class)
            ->findOneBy(['resourceNode' => $resourceNodeRef])
        ;

        if (!$ref instanceof SearchEngineRef) {
            error_log('[Xapian] deleteForResourceNodeId: no SearchEngineRef found, nothing to delete');

            return;
        }

        $docId = $ref->getSearchDid();
        if (null !== $docId) {
            try {
                $this->xapianIndexService->deleteDocument($docId);
                error_log(
                    '[Xapian] deleteForResourceNodeId: deleteDocument called for did='
                    .var_export($docId, true)
                );
            } catch (Throwable $e) {
                error_log(
                    '[Xapian] deleteForResourceNodeId: deleteDocument failed for did='
                    .var_export($docId, true)
                    .' error='.$e->getMessage()
                );
            }
        }

        $this->em->remove($ref);
        $this->em->flush();

        error_log(
            '[Xapian] deleteForResourceNodeId: SearchEngineRef removed for resource_node_id='
            .$resourceNodeId
        );
    }

    /**
     * Resolve course id, session id and course root node id from resource links.
     *
     * @return array{0: int|null, 1: int|null, 2: int|null}
     */
    private function resolveCourseSessionAndRootNode(ResourceNode $resourceNode): array
    {
        $courseId = null;
        $sessionId = null;
        $courseRootNodeId = null;

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink) {
                continue;
            }

            if (null === $courseId && $link->getCourse()) {
                $course = $link->getCourse();
                $courseId = $course->getId();

                $courseRootNode = $course->getResourceNode();
                if ($courseRootNode instanceof ResourceNode) {
                    $courseRootNodeId = $courseRootNode->getId();
                }
            }

            if (null === $sessionId && $link->getSession()) {
                $sessionId = $link->getSession()->getId();
            }

            if (null !== $courseId && null !== $sessionId && null !== $courseRootNodeId) {
                break;
            }
        }

        return [$courseId, $sessionId, $courseRootNodeId];
    }

    /**
     * Apply configured prefilter prefixes to Xapian terms.
     */
    private function applyPrefilterConfigToTerms(
        array &$terms,
        ?int $courseId,
        ?int $sessionId,
        CDocument $document
    ): void {
        $raw = (string) $this->settingsManager->getSetting('search.search_prefilter_prefix', true);
        if ('' === $raw) {
            return;
        }

        $config = json_decode($raw, true);
        if (!\is_array($config)) {
            return;
        }

        foreach ($config as $key => $item) {
            if (!\is_array($item)) {
                continue;
            }

            $prefix = (string) ($item['prefix'] ?? '');
            if ('' === $prefix) {
                $prefix = strtoupper((string) $key);
            }

            switch ($key) {
                case 'course':
                    if (null !== $courseId) {
                        $terms[] = $prefix.(string) $courseId;
                    }

                    break;

                case 'session':
                    if (null !== $sessionId) {
                        $terms[] = $prefix.(string) $sessionId;
                    }

                    break;

                case 'filetype':
                    $terms[] = $prefix.$document->getFiletype();

                    break;

                default:
                    // Unknown key: ignore for now
                    break;
            }
        }
    }
}
