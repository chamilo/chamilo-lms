<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

/**
 * Doctrine entity listener for CDocument to trigger Xapian indexing.
 */
#[AsEntityListener(event: Events::postPersist, entity: CDocument::class)]
#[AsEntityListener(event: Events::postUpdate, entity: CDocument::class)]
#[AsEntityListener(event: Events::postRemove, entity: CDocument::class)]
final class DocumentSearchEntityListener
{
    public function __construct(
        private readonly DocumentXapianIndexer $indexer,
        private readonly RequestStack $requestStack,
    ) {}

    public function postPersist(CDocument $document, LifecycleEventArgs $args): void
    {
        if (!$this->shouldIndexDocumentFromRequest()) {
            error_log('[Xapian] DocumentSearchEntityListener postPersist: indexing disabled by indexDocumentContent flag');

            return;
        }

        try {
            $this->indexer->indexDocument($document);
        } catch (Throwable $e) {
            error_log(
                '[Xapian] DocumentSearchEntityListener postPersist: indexing failed: '.
                $e->getMessage().' in '.$e->getFile().':'.$e->getLine()
            );
        }
    }

    public function postUpdate(CDocument $document, LifecycleEventArgs $args): void
    {
        if (!$this->shouldIndexDocumentFromRequest()) {
            error_log('[Xapian] DocumentSearchEntityListener postUpdate: indexing disabled by indexDocumentContent flag');

            return;
        }

        try {
            $this->indexer->indexDocument($document);
        } catch (Throwable $e) {
            error_log(
                '[Xapian] DocumentSearchEntityListener postUpdate: indexing failed: '.
                $e->getMessage().' in '.$e->getFile().':'.$e->getLine()
            );
        }
    }

    public function postRemove(CDocument $document, LifecycleEventArgs $args): void
    {
        $resourceNode = $document->getResourceNode();
        if (null === $resourceNode) {
            return;
        }

        try {
            $this->indexer->deleteForResourceNodeId((int) $resourceNode->getId());
        } catch (Throwable $e) {
            error_log(
                '[Xapian] DocumentSearchEntityListener postRemove: delete failed: '.
                $e->getMessage().' in '.$e->getFile().':'.$e->getLine()
            );
        }
    }

    private function shouldIndexDocumentFromRequest(): bool
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        // No HTTP request (CLI, tests, etc.) => keep legacy behavior: index
        if (null === $currentRequest) {
            return true;
        }

        // Only care about document endpoints
        $path = $currentRequest->getPathInfo();
        if (!\is_string($path) || !str_contains($path, '/documents')) {
            return true;
        }

        // indexDocumentContent may come from form-data, query or JSON body
        $raw = $currentRequest->get('indexDocumentContent');

        // If not present, keep legacy behavior (index)
        if (null === $raw) {
            return true;
        }

        if (\is_bool($raw)) {
            return $raw;
        }

        if (is_numeric($raw)) {
            return ((int) $raw) !== 0;
        }

        if (\is_string($raw)) {
            $normalized = strtolower(trim($raw));
            $falseValues = ['0', 'false', 'no', 'off', ''];
            $trueValues = ['1', 'true', 'yes', 'on'];

            if (\in_array($normalized, $falseValues, true)) {
                return false;
            }

            if (\in_array($normalized, $trueValues, true)) {
                return true;
            }
        }

        // Fallback: cast to boolean
        return (bool) $raw;
    }
}
