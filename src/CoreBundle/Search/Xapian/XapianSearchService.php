<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use RuntimeException;

/**
 * High-level Xapian search service for Chamilo 2.
 *
 * Demo version: free-text search over documents indexed by XapianIndexService.
 */
final class XapianSearchService
{
    public function __construct(
        private readonly SearchIndexPathResolver $indexPathResolver,
    ) {
    }

    /**
     * Execute a simple search query against the Xapian index.
     *
     * @param string $queryString Free text query string
     * @param int    $offset      First result offset (0-based)
     * @param int    $length      Maximum number of results
     *
     * @return array{
     *     count:int,
     *     results:array<int,array<string,mixed>>
     * }
     */
    public function search(
        string $queryString,
        int $offset = 0,
        int $length = 10,
        array $extra = [],    // Kept for signature compatibility but unused in the demo
        int $countType = 0,   // Kept for signature compatibility but unused in the demo
    ): array {
        if (!\class_exists(\XapianDatabase::class)) {
            throw new RuntimeException('Xapian PHP extension is not loaded.');
        }

        $indexDir = $this->indexPathResolver->getIndexDir();
        $this->indexPathResolver->ensureIndexDirectoryExists();

        try {
            $db = new \XapianDatabase($indexDir);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                \sprintf('Unable to open Xapian database at "%s": %s', $indexDir, $e->getMessage()),
                0,
                $e
            );
        }

        $enquire = new \XapianEnquire($db);

        if ($queryString !== '') {
            // Free-text query with stemming
            $queryParser = new \XapianQueryParser();
            $stemmer     = new \XapianStem('english'); // TODO: pick language from user/platform

            $queryParser->set_stemmer($stemmer);
            $queryParser->set_database($db);
            $queryParser->set_stemming_strategy(\XapianQueryParser::STEM_SOME);

            $parsedQuery = $queryParser->parse_query($queryString);
            $query       = $parsedQuery;
        } else {
            // Empty query = match everything
            $query = new \XapianQuery('');
        }

        $enquire->set_query($query);

        $matches = $enquire->get_mset($offset, $length);

        $results = [];

        for ($m = $matches->begin(); !$m->equals($matches->end()); $m->next()) {
            $document = $m->get_document();

            if (!$document instanceof \XapianDocument) {
                continue;
            }

            $rawData = $document->get_data();
            $data    = $rawData !== '' ? @\unserialize($rawData) : null;

            $results[] = [
                'doc_id' => $m->get_docid(),
                'score'  => $m->get_percent(),
                'data'   => $data,
            ];
        }

        $count = $matches->get_matches_estimated();

        return [
            'count'   => $count,
            'results' => $results,
        ];
    }
}
