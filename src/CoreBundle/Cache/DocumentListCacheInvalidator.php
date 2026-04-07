<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Clears the document-list cache pool.
 * Inject this service into any action that creates, deletes, moves, or changes
 * the visibility of a CDocument, then call invalidate() after the flush.
 */
final class DocumentListCacheInvalidator
{
    public function __construct(
        #[Autowire(service: 'chamilo.document_list')]
        private readonly CacheItemPoolInterface $pool,
    ) {}

    public function invalidate(): void
    {
        $this->pool->clear();
    }
}
