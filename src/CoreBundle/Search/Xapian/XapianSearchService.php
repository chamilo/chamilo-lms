<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Throwable;
use XapianDatabase;
use XapianDocument;
use XapianEnquire;
use XapianQuery;
use XapianQueryParser;
use XapianStem;

/**
 * High-level Xapian search service for Chamilo 2.
 */
final class XapianSearchService
{
    public function __construct(
        private readonly SearchIndexPathResolver $indexPathResolver,
        private readonly Connection $conn,
    ) {}

    /**
     * Execute a simple search query against the Xapian index.
     *
     * Supports field queries like:
     *  - t:"some title"
     *  - d:lorem
     *  - k:peru
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
        array $extra = [],
        int $countType = 0,
    ): array {
        if (!class_exists(XapianDatabase::class)) {
            throw new RuntimeException('Xapian PHP extension is not loaded.');
        }

        $indexDir = $this->indexPathResolver->getIndexDir();
        $this->indexPathResolver->ensureIndexDirectoryExists();

        try {
            $db = new XapianDatabase($indexDir);
        } catch (Throwable $e) {
            throw new RuntimeException(\sprintf('Unable to open Xapian database at "%s": %s', $indexDir, $e->getMessage()), 0, $e);
        }

        $enquire = new XapianEnquire($db);

        if ('' !== trim($queryString)) {
            $queryParser = new XapianQueryParser();

            // Resolve language for stemming. Caller can pass:
            // - $extra['language'] or $extra['language_iso'] (e.g. "fr_61", "fr_FR", "fr", "french")
            // - $extra['locale'] (e.g. "es_PE")
            $languageRaw = null;
            foreach (['language', 'language_iso', 'locale'] as $k) {
                if (isset($extra[$k]) && \is_string($extra[$k]) && '' !== trim($extra[$k])) {
                    $languageRaw = trim((string) $extra[$k]);

                    break;
                }
            }

            // Normalize ISO/code into a Xapian stemmer language string (fallback to english).
            $xapianLanguage = $this->mapLanguageToXapianStemmer($languageRaw);

            $usedLanguage = $xapianLanguage;

            try {
                $stemmer = new XapianStem($xapianLanguage);
            } catch (Throwable $e) {
                $usedLanguage = 'english';
                $stemmer = new XapianStem($usedLanguage);
            }

            $queryParser->set_stemmer($stemmer);
            $queryParser->set_database($db);
            $queryParser->set_stemming_strategy(XapianQueryParser::STEM_SOME);

            // Dynamic prefixes (t:, d:, k:, etc)
            $this->configureDynamicFieldPrefixes($queryParser);

            // IMPORTANT: make parsing consistent (phrases, boolean ops, etc)
            $flags = $this->buildQueryParserFlags();

            try {
                $query = $queryParser->parse_query($queryString, $flags);
            } catch (Throwable $e) {
                // Safe fallback: do not crash search endpoint on malformed queries.
                error_log('[Xapian] XapianSearchService::search: parse_query failed: '.$e->getMessage());
                $query = new XapianQuery('');
            }
        } else {
            $query = new XapianQuery('');
        }

        $enquire->set_query($query);

        $matches = $enquire->get_mset($offset, $length);

        $results = [];
        for ($m = $matches->begin(); !$m->equals($matches->end()); $m->next()) {
            $document = $m->get_document();
            if (!$document instanceof XapianDocument) {
                continue;
            }

            $rawData = $document->get_data();
            $data = '' !== $rawData ? @unserialize($rawData) : null;

            $results[] = [
                'doc_id' => $m->get_docid(),
                'score' => $m->get_percent(),
                'data' => $data,
            ];
        }

        $count = $matches->get_matches_estimated();

        return [
            'count' => $count,
            'results' => $results,
        ];
    }

    /**
     * Map ISO codes or language names into Xapian stemmer language.
     * Keeps behavior stable by falling back to english.
     */
    private function mapLanguageToXapianStemmer(?string $language): string
    {
        if (null === $language) {
            return 'english';
        }

        $raw = strtolower(trim($language));
        if ('' === $raw) {
            return 'english';
        }

        // If caller already provides a Xapian language name, accept it.
        $known = [
            'english', 'spanish', 'french', 'portuguese', 'italian', 'german', 'dutch',
            'swedish', 'norwegian', 'danish', 'finnish', 'russian', 'arabic', 'greek',
            'turkish', 'romanian', 'hungarian', 'indonesian',
        ];

        if (\in_array($raw, $known, true)) {
            return $raw;
        }

        // Normalize ISO variants: es_ES, pt-BR, fr_61, en_US -> es, pt, fr, en
        $iso = $raw;
        if (str_contains($iso, '_')) {
            $iso = explode('_', $iso, 2)[0];
        }
        if (str_contains($iso, '-')) {
            $iso = explode('-', $iso, 2)[0];
        }
        $iso = strtolower(trim($iso));

        $map = [
            'en' => 'english',
            'es' => 'spanish',
            'fr' => 'french',
            'pt' => 'portuguese',
            'it' => 'italian',
            'de' => 'german',
            'nl' => 'dutch',
            'sv' => 'swedish',
            'no' => 'norwegian',
            'da' => 'danish',
            'fi' => 'finnish',
            'ru' => 'russian',
            'ar' => 'arabic',
            'el' => 'greek',
            'tr' => 'turkish',
            'ro' => 'romanian',
            'hu' => 'hungarian',
            'id' => 'indonesian',
        ];

        return $map[$iso] ?? 'english';
    }

    private function configureDynamicFieldPrefixes(XapianQueryParser $qp): void
    {
        try {
            $rows = $this->conn->fetchAllAssociative('SELECT code FROM search_engine_field');
        } catch (Throwable $e) {
            error_log('[Xapian] XapianSearchService: failed to read search_engine_field: '.$e->getMessage());

            // Safe fallback
            $qp->add_prefix('t', 'FT');
            $qp->add_prefix('d', 'FD');
            $qp->add_prefix('k', 'FK');
            $qp->add_prefix('c', 'FC');

            return;
        }

        $loaded = [];

        foreach ($rows as $row) {
            $code = strtolower(trim((string) ($row['code'] ?? '')));
            if ('' === $code) {
                continue;
            }

            // Must match indexing convention: 'F' + strtoupper(code)
            $prefix = 'F'.strtoupper($code);
            $qp->add_prefix($code, $prefix);

            $loaded[] = $code.':'.$prefix;
        }
    }

    private function buildQueryParserFlags(): int
    {
        // Start with default if available, otherwise 0
        $flags = 0;

        $defaultConst = XapianQueryParser::class.'::FLAG_DEFAULT';
        if (\defined($defaultConst)) {
            $flags = \constant($defaultConst);
        }

        // Add common useful flags if present in the binding
        $flagNames = [
            'FLAG_PHRASE',
            'FLAG_BOOLEAN',
            'FLAG_LOVEHATE',
            'FLAG_WILDCARD',
            'FLAG_PURE_NOT',
            'FLAG_SPELLING_CORRECTION',
            'FLAG_PARTIAL',
        ];

        foreach ($flagNames as $name) {
            $const = XapianQueryParser::class.'::'.$name;
            if (\defined($const)) {
                $flags |= \constant($const);
            }
        }

        return $flags;
    }
}
