<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

#[AsEntityListener(event: Events::postUpdate, entity: ResourceNode::class)]
final class ResourceNodeDocumentSearchEntityListener
{
    public function __construct(
        private readonly DocumentXapianIndexer $indexer,
        private readonly RequestStack $requestStack,
    ) {}

    public function postUpdate(ResourceNode $node, LifecycleEventArgs $args): void
    {
        if (!$this->shouldIndexFromRequest()) {
            error_log('[Xapian] ResourceNodeDocumentSearchEntityListener postUpdate: indexing disabled by indexDocumentContent flag');

            return;
        }

        $om = $args->getObjectManager();
        if (!$om instanceof EntityManagerInterface) {
            return;
        }

        // Only reindex when meaningful fields changed
        $changeset = $om->getUnitOfWork()->getEntityChangeSet($node);
        $watched = ['content' => true, 'parent' => true, 'updatedAt' => true];

        $shouldReindex = false;
        foreach ($changeset as $field => $_) {
            if (isset($watched[$field])) {
                $shouldReindex = true;

                break;
            }
        }

        if (!$shouldReindex) {
            return;
        }

        /** @var CDocument|null $document */
        $document = $om->getRepository(CDocument::class)->findOneBy(['resourceNode' => $node]);
        if (!$document instanceof CDocument) {
            return; // Not a document node
        }

        try {
            $this->indexer->indexDocument($document);
        } catch (Throwable $e) {
            error_log(
                '[Xapian] ResourceNodeDocumentSearchEntityListener postUpdate: indexing failed: '.
                $e->getMessage().' in '.$e->getFile().':'.$e->getLine()
            );
        }
    }

    private function shouldIndexFromRequest(): bool
    {
        $req = $this->requestStack->getCurrentRequest();

        // CLI/tests => index
        if (null === $req) {
            return true;
        }

        $raw = $req->get('indexDocumentContent');

        // Not present => default index
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
            if (\in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
            if (\in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
        }

        return (bool) $raw;
    }
}
