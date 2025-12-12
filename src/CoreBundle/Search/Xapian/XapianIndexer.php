<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use RuntimeException;

/**
 * Base Xapian indexer for Chamilo 2.
 *
 * This is a modernized version of the legacy XapianIndexer from Chamilo 1,
 * adapted to namespaces and DI.
 */
abstract class XapianIndexer
{
    /** @var \XapianTermGenerator|null */
    protected ?\XapianTermGenerator $indexer = null;

    /** @var \XapianStem|null */
    protected ?\XapianStem $stemmer = null;

    /** @var \XapianWritableDatabase|null */
    protected ?\XapianWritableDatabase $db = null;

    /** @var array<int,object> */
    protected array $chunks = [];

    public function __construct(
        private readonly SearchIndexPathResolver $indexPathResolver,
    ) {
        // Defer DB opening until first use.
    }

    public function __destruct()
    {
        unset($this->db, $this->stemmer, $this->indexer);
    }

    /**
     * Returns the list of languages supported by Xapian.
     *
     * @return array<string,string> Language codes -> Xapian languages
     */
    final public function getSupportedLanguages(): array
    {
        return [
            'none'   => 'none',
            'da'     => 'danish',
            'nl'     => 'dutch',
            'en'     => 'english',
            'lovins' => 'english_lovins',
            'porter' => 'english_porter',
            'fi'     => 'finnish',
            'fr'     => 'french',
            'de'     => 'german',
            'it'     => 'italian',
            'no'     => 'norwegian',
            'pt'     => 'portuguese',
            'ru'     => 'russian',
            'es'     => 'spanish',
            'sv'     => 'swedish',
        ];
    }

    /**
     * Connect to the Xapian writable database, creating it when needed.
     *
     * @throws RuntimeException When the DB cannot be created or opened.
     */
    public function connectDb(?string $path = null, ?int $dbMode = null, string $lang = 'english'): \XapianWritableDatabase
    {
        require_once 'xapian.php';

        if ($this->db instanceof \XapianWritableDatabase) {
            return $this->db;
        }

        if ($dbMode === null) {
            $dbMode = \Xapian::DB_CREATE_OR_OPEN;
        }

        if ($path === null) {
            $path = $this->indexPathResolver->getIndexDir();
        }

        $this->indexPathResolver->ensureIndexDirectoryExists();

        try {
            $this->db = new \XapianWritableDatabase($path, $dbMode);
            $this->indexer = new \XapianTermGenerator();

            $supported = $this->getSupportedLanguages();
            if (!\in_array($lang, $supported, true)) {
                $lang = 'english';
            }

            $this->stemmer = new \XapianStem($lang);
            $this->indexer->set_stemmer($this->stemmer);

            return $this->db;
        } catch (\Exception $e) {
            throw new RuntimeException(
                \sprintf('Unable to create or open Xapian index at "%s": %s', $path, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Simple getter for the writable database.
     */
    public function getDb(): ?\XapianWritableDatabase
    {
        return $this->db;
    }

    /**
     * Add a chunk of indexable data to the batch.
     *
     * @param object $chunk Generic DTO with: terms[], data[], xapian_data
     */
    public function addChunk(object $chunk): void
    {
        $this->chunks[] = $chunk;
    }

    /**
     * Index the current batch of chunks.
     *
     * @return int|null New Xapian document ID, or null when nothing was indexed
     */
    public function index(): ?int
    {
        if (empty($this->chunks)) {
            return null;
        }

        $this->connectDb();

        try {
            foreach ($this->chunks as $chunk) {
                $doc = new \XapianDocument();
                $this->indexer?->set_document($doc);

                if (!empty($chunk->terms)) {
                    foreach ($chunk->terms as $term) {
                        $doc->add_term($term['flag'] . $term['name'], 1);
                    }
                }

                if (!empty($chunk->data)) {
                    foreach ($chunk->data as $value) {
                        $this->indexer?->index_text((string) $value, 1);
                    }
                }

                $doc->set_data($chunk->xapian_data, 1);

                $did = $this->db?->add_document($doc);
                $this->db?->flush();

                return $did ?? null;
            }
        } catch (\Exception $e) {
            throw new RuntimeException(
                sprintf('Failed to index chunk in Xapian: %s', $e->getMessage()),
                0,
                $e
            );
        }

        return null;
    }

    /**
     * Fetch a document by its Xapian docid.
     */
    public function getDocument(int $did): ?\XapianDocument
    {
        if ($this->db === null) {
            $this->connectDb();
        }

        try {
            return $this->db?->get_document($did) ?: null;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Update all terms of a document in the index.
     *
     * @param int    $did    Xapian docid
     * @param string $prefix Prefix used to categorize the terms
     * @param array  $terms  New terms (strings)
     */
    public function updateTerms(int $did, array $terms, string $prefix): bool
    {
        $doc = $this->getDocument($did);
        if ($doc === null) {
            return false;
        }

        $doc->clear_terms();

        foreach ($terms as $term) {
            $doc->add_term($prefix . $term, 1);
        }

        $this->db?->replace_document($did, $doc);
        $this->db?->flush();

        return true;
    }

    /**
     * Remove a document from the index.
     */
    public function removeDocument(int $did): void
    {
        if ($this->db === null) {
            $this->connectDb();
        }

        if ($did <= 0) {
            return;
        }

        $doc = $this->getDocument($did);
        if ($doc === null) {
            return;
        }

        $this->db?->delete_document($did);
        $this->db?->flush();
    }

    /**
     * Replace a document in the index.
     */
    public function replaceDocument(\XapianDocument $doc, int $did): void
    {
        if ($this->db === null) {
            $this->connectDb();
        }

        $this->db?->replace_document($did, $doc);
        $this->db?->flush();
    }
}
