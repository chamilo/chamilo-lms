<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

/**
 * Service responsible for indexing documents into the Xapian database.
 */
final class XapianIndexService
{
    private const DEFAULT_LANGUAGE = 'english';

    public function __construct(
        private readonly SearchIndexPathResolver $indexPathResolver,
    ) {
    }

    /**
     * Indexes a simple demo document so we can verify that search works end-to-end.
     *
     * @return int The Xapian internal document id
     *
     * @throws \RuntimeException When indexing fails
     */
    public function indexDemoDocument(): int
    {
        $now = new \DateTimeImmutable('now');

        $fields = [
            'title'      => 'Demo test document',
            'content'    => 'This is a test document indexed from XapianIndexService in Chamilo 2.',
            'created_at' => $now->format(\DATE_ATOM),
        ];

        $terms = [
            'XTdemo',
            'XTchamilo',
        ];

        return $this->indexDocument($fields, $terms);
    }

    /**
     * Indexes a generic document.
     *
     * @param array<string,mixed> $fields Arbitrary data to store and index as free-text
     * @param string[]            $terms  Optional list of additional terms to add to the document
     * @param string|null         $language Language used for stemming (defaults to english)
     *
     * @return int The Xapian internal document id
     *
     * @throws \RuntimeException When Xapian fails during indexing
     */
    public function indexDocument(
        array $fields,
        array $terms = [],
        ?string $language = null,
    ): int {
        if (!\class_exists(\XapianWritableDatabase::class)) {
            throw new \RuntimeException('Xapian PHP extension is not loaded.');
        }

        $db = $this->openWritableDatabase();

        $doc     = new \XapianDocument();
        $termGen = new \XapianTermGenerator();

        $lang    = $language ?: self::DEFAULT_LANGUAGE;
        $stemmer = new \XapianStem($lang);

        $termGen->set_stemmer($stemmer);
        $termGen->set_document($doc);

        // Index all field values as free-text (title, content, etc.)
        foreach ($fields as $value) {
            if ($value === null) {
                continue;
            }

            if (!\is_string($value)) {
                $value = (string) $value;
            }

            $termGen->index_text($value, 1);
        }

        // Add explicit terms if provided
        foreach ($terms as $term) {
            $term = (string) $term;
            if ($term === '') {
                continue;
            }

            $doc->add_term($term, 1);
        }

        // Store fields as serialized payload (compatible with the search service decode)
        $doc->set_data(\serialize($fields));

        try {
            $docId = $db->add_document($doc);
            $db->flush();

            error_log('[Xapian] XapianIndexService::indexDocument: document added with docId='
                .var_export($docId, true)
            );

            return $docId;
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                \sprintf('Failed to index document in Xapian: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Deletes a document from the Xapian index using its internal document id.
     *
     * @throws \RuntimeException When Xapian fails during deletion
     */
    public function deleteDocument(int $docId): void
    {
        if (!\class_exists(\XapianWritableDatabase::class)) {
            throw new \RuntimeException('Xapian PHP extension is not loaded.');
        }

        $db = $this->openWritableDatabase();

        try {
            error_log('[Xapian] XapianIndexService::deleteDocument: deleting docId='
                .var_export($docId, true)
            );

            $db->delete_document($docId);
            $db->flush();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                \sprintf('Failed to delete document in Xapian: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Opens the writable Xapian database using DB_CREATE_OR_OPEN.
     */
    private function openWritableDatabase(): \XapianWritableDatabase
    {
        $indexDir = $this->indexPathResolver->getIndexDir();

        return new \XapianWritableDatabase($indexDir, \Xapian::DB_CREATE_OR_OPEN);
    }
}
