<?php
/* For licensing terms, see /license.txt */

// @todo add setting to add xapian.php
// require_once 'xapian.php';

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Search\Xapian\SearchIndexPathResolver;

/**
 * Abstract helper class.
 */
abstract class XapianIndexer
{
    /** @var XapianTermGenerator */
    public $indexer;

    /** @var XapianStem */
    public $stemmer;

    /** @var XapianWritableDatabase */
    protected $db;

    /** @var IndexableChunk[] */
    protected $chunks = [];

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->db = null;
        $this->stemmer = null;
    }

    /**
     * Class destructor.
     */
    public function __destruct()
    {
        unset($this->db);
        unset($this->stemmer);
    }

    /**
     * Generates a list of languages Xapian manages.
     *
     * This method enables the definition of more matches between
     * Chamilo languages and Xapian languages (through hardcoding)
     *
     * @return array Array of languages codes -> Xapian languages
     */
    final public function xapian_languages()
    {
        /* http://xapian.org/docs/apidoc/html/classXapian_1_1Stem.html */
        return [
            'none' => 'none', // do not stem terms
            'da' => 'danish',
            'nl' => 'dutch',
            /* Martin Porter's 2002 revision of his stemmer */
            'en' => 'english',
            /* Lovin's stemmer */
            'lovins' => 'english_lovins',
            /* Porter's stemmer as described in his 1980 paper */
            'porter' => 'english_porter',
            'fi' => 'finnish',
            'fr' => 'french',
            'de' => 'german',
            'it' => 'italian',
            'no' => 'norwegian',
            'pt' => 'portuguese',
            'ru' => 'russian',
            'es' => 'spanish',
            'sv' => 'swedish',
        ];
    }

    /**
     * Connect to the database, and create it if it does not exist.
     *
     * In Chamilo 2, this will prefer the Symfony SearchIndexPathResolver
     * (var/search) and fall back to the legacy upload path when needed.
     */
    public function connectDb($path = null, $dbMode = null, $lang = 'english')
    {
        if ($this->db !== null) {
            return $this->db;
        }

        if ($dbMode === null) {
            $dbMode = Xapian::DB_CREATE_OR_OPEN;
        }

        if ($path === null) {
            // Legacy default path (Chamilo 1)
            $path = api_get_path(SYS_UPLOAD_PATH) . 'plugins/xapian/searchdb/';

            // If running under Chamilo 2, prefer the Symfony resolver and var/search
            if (class_exists(Container::class)) {
                try {
                    /** @var SearchIndexPathResolver $resolver */
                    $resolver = Container::getSearchIndexPathResolver();
                    $resolver->ensureIndexDirectoryExists();
                    $path = $resolver->getIndexDir();
                } catch (\Throwable $e) {
                    // Fallback to legacy path if resolver or container are not available.
                    // This keeps backward compatibility and avoids hard failures.
                }
            }
        }

        try {
            $this->db = new XapianWritableDatabase($path, $dbMode);
            $this->indexer = new XapianTermGenerator();

            if (!in_array($lang, $this->xapian_languages(), true)) {
                $lang = 'english';
            }

            $this->stemmer = new XapianStem($lang);
            $this->indexer->set_stemmer($this->stemmer);

            return $this->db;
        } catch (Exception $e) {
            echo Display::return_message($e->getMessage(), 'error');

            return 1;
        }
    }

    /**
     * Simple getter for the db attribute.
     *
     * @return object|null The db attribute
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Add this chunk to the chunk array attribute.
     *
     * @param mixed $chunk Chunk of text (IndexableChunk instance)
     */
    public function addChunk($chunk): void
    {
        $this->chunks[] = $chunk;
    }

    /**
     * Actually index the current data.
     *
     * @return int|null New Xapian document ID or null upon failure
     */
    public function index()
    {
        try {
            if (!empty($this->chunks)) {
                foreach ($this->chunks as $chunk) {
                    $doc = new XapianDocument();
                    $this->indexer->set_document($doc);

                    if (!empty($chunk->terms)) {
                        foreach ($chunk->terms as $term) {
                            // @todo consider using a proper weight value instead of 1
                            $doc->add_term($term['flag'] . $term['name'], 1);
                        }
                    }

                    // Free-form index all data array (title, content, etc.)
                    if (!empty($chunk->data)) {
                        foreach ($chunk->data as $key => $value) {
                            $this->indexer->index_text($value, 1);
                        }
                    }

                    $doc->set_data($chunk->xapian_data, 1);
                    $did = $this->db->add_document($doc);

                    // Make sure changes are flushed to disk
                    $this->db->flush();

                    return $did;
                }
            }
        } catch (Exception $e) {
            echo Display::return_message($e->getMessage(), 'error');
            exit(1);
        }

        return null;
    }

    /**
     * Get a specific document from Xapian db.
     *
     * @param int $did Xapian::docid
     *
     * @return XapianDocument|false XapianDocument, or false on error
     */
    public function get_document($did)
    {
        if ($this->db === null) {
            $this->connectDb();
        }

        try {
            $docid = $this->db->get_document($did);
        } catch (Exception $e) {
            // Intentionally silent here: caller will handle false result.
            return false;
        }

        return $docid;
    }

    /**
     * Get document data on a Xapian document.
     *
     * @param XapianDocument $doc Xapian document to read from
     *
     * @return mixed Xapian document data or false if error
     */
    public function get_document_data($doc)
    {
        if ($this->db === null) {
            $this->connectDb();
        }

        try {
            if (!is_a($doc, 'XapianDocument')) {
                return false;
            }

            $doc_data = $doc->get_data();

            return $doc_data;
        } catch (Exception $e) {
            // Intentionally silent here: caller will handle false result.
            return false;
        }
    }

    /**
     * Replace all terms of a document in Xapian db.
     *
     * @param int    $did    Xapian::docid
     * @param array  $terms  New terms of the document
     * @param string $prefix Prefix used to categorize the doc
     *                       (usually 'T' for title, 'A' for author)
     *
     * @return bool false on error
     */
    public function update_terms($did, $terms, $prefix): bool
    {
        $doc = $this->get_document($did);
        if ($doc === false) {
            return false;
        }

        $doc->clear_terms();

        foreach ($terms as $term) {
            // Add directly with given prefix
            $doc->add_term($prefix . $term, 1);
        }

        $this->db->replace_document($did, $doc);
        $this->db->flush();

        return true;
    }

    /**
     * Remove a document from Xapian db.
     *
     * @param int $did Xapian::docid
     */
    public function remove_document($did): void
    {
        if ($this->db === null) {
            $this->connectDb();
        }

        $did = (int) $did;

        if ($did > 0) {
            $doc = $this->get_document($did);
            if ($doc !== false) {
                $this->db->delete_document($did);
                $this->db->flush();
            }
        }
    }

    /**
     * Adds a term to the document specified.
     *
     * @param string         $term The term to add
     * @param XapianDocument $doc  The Xapian document where to add the term
     *
     * @return XapianDocument|false XapianDocument, or false on error
     */
    public function add_term_to_doc($term, $doc)
    {
        if (!is_a($doc, 'XapianDocument')) {
            return false;
        }

        try {
            $doc->add_term($term);
        } catch (Exception $e) {
            echo Display::return_message($e->getMessage(), 'error');

            return 1;
        }

        return $doc;
    }

    /**
     * Remove a term from the document specified.
     *
     * @param string         $term The term to remove
     * @param XapianDocument $doc  The Xapian document where to remove the term
     *
     * @return XapianDocument|false XapianDocument, or false on error
     */
    public function remove_term_from_doc($term, $doc)
    {
        if (!is_a($doc, 'XapianDocument')) {
            return false;
        }

        try {
            $doc->remove_term($term);
        } catch (Exception $e) {
            echo Display::return_message($e->getMessage(), 'error');

            return 1;
        }

        return $doc;
    }

    /**
     * Replace a document in the actual db.
     *
     * @param XapianDocument $doc Xapian document to push into the db
     * @param int            $did Xapian document id of the document to replace
     *
     * @return mixed
     */
    public function replace_document($doc, $did)
    {
        if (!is_a($doc, 'XapianDocument')) {
            return false;
        }

        if ($this->db === null) {
            $this->connectDb();
        }

        try {
            $this->getDb()->replace_document((int) $did, $doc);
            $this->getDb()->flush();
        } catch (Exception $e) {
            echo Display::return_message($e->getMessage(), 'error');

            return 1;
        }

        return $doc;
    }
}
