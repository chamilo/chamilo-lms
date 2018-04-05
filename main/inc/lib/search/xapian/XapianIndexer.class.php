<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.include.search
 */
require_once 'xapian.php';

/**
 * Abstract helper class.
 *
 * @package chamilo.include.search
 */
abstract class XapianIndexer
{
    /* XapianTermGenerator */
    public $indexer;
    /* XapianStem */
    public $stemmer;
    /* XapianWritableDatabase */
    protected $db;
    /* IndexableChunk[] */
    protected $chunks;

    /**
     * Class contructor.
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
            'none' => 'none', //don't stem terms
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
     * Connect to the database, and create it if it doesn't exist.
     */
    public function connectDb($path = null, $dbMode = null, $lang = 'english')
    {
        if ($this->db != null) {
            return $this->db;
        }
        if ($dbMode == null) {
            $dbMode = Xapian::DB_CREATE_OR_OPEN;
        }

        if ($path == null) {
            $path = api_get_path(SYS_UPLOAD_PATH).'plugins/xapian/searchdb/';
        }

        try {
            $this->db = new XapianWritableDatabase($path, $dbMode);
            $this->indexer = new XapianTermGenerator();

            if (!in_array($lang, $this->xapian_languages())) {
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
     * @return object The db attribute
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Add this chunk to the chunk array attribute.
     *
     * @param  string  Chunk of text
     */
    public function addChunk($chunk)
    {
        $this->chunks[] = $chunk;
    }

    /**
     * Actually index the current data.
     *
     * @return int New Xapian document ID or null upon failure
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
                            /* FIXME: think of getting weight */
                            $doc->add_term($term['flag'].$term['name'], 1);
                        }
                    }

                    // free-form index all data array (title, content, etc)
                    if (!empty($chunk->data)) {
                        foreach ($chunk->data as $key => $value) {
                            $this->indexer->index_text($value, 1);
                        }
                    }
                    $doc->set_data($chunk->xapian_data, 1);
                    $did = $this->db->add_document($doc);

                    //write to disk
                    $this->db->flush();

                    return $did;
                }
            }
        } catch (Exception $e) {
            echo Display::return_message($e->getMessage(), 'error');
            exit(1);
        }
    }

    /**
     * Get a specific document from xapian db.
     *
     * @param   int     did     Xapian::docid
     *
     * @return mixed XapianDocument, or false on error
     */
    public function get_document($did)
    {
        if ($this->db == null) {
            $this->connectDb();
        }
        try {
            $docid = $this->db->get_document($did);
        } catch (Exception $e) {
            //echo Display::return_message($e->getMessage(), 'error');
            return false;
        }

        return $docid;
    }

    /**
     * Get document data on a xapian document.
     *
     * @param XapianDocument $doc xapian document to push into the db
     *
     * @return mixed xapian document data or FALSE if error
     */
    public function get_document_data($doc)
    {
        if ($this->db == null) {
            $this->connectDb();
        }
        try {
            if (!is_a($doc, 'XapianDocument')) {
                return false;
            }
            $doc_data = $doc->get_data();

            return $doc_data;
        } catch (Exception $e) {
            //echo Display::return_message($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Replace all terms of a document in xapian db.
     *
     * @param int    $did    Xapian::docid
     * @param array  $terms  New terms of the document
     * @param string $prefix Prefix used to categorize the doc
     *                       (usually 'T' for title, 'A' for author)
     *
     * @return bool false on error
     */
    public function update_terms($did, $terms, $prefix)
    {
        $doc = $this->get_document($did);
        if ($doc === false) {
            return false;
        }
        $doc->clear_terms();
        foreach ($terms as $term) {
            //add directly
            $doc->add_term($prefix.$term, 1);
        }
        $this->db->replace_document($did, $doc);
        $this->db->flush();

        return true;
    }

    /**
     * Remove a document from xapian db.
     *
     * @param int   did     Xapian::docid
     */
    public function remove_document($did)
    {
        if ($this->db == null) {
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
     * @param XapianDocument $doc  The xapian document where to add the term
     *
     * @return mixed XapianDocument, or false on error
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
    }

    /**
     * Remove a term from the document specified.
     *
     * @param string         $term The term to add
     * @param XapianDocument $doc  The xapian document where to add the term
     *
     * @return mixed XapianDocument, or false on error
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
    }

    /**
     * Replace a document in the actual db.
     *
     * @param XapianDocument $doc xapian document to push into the db
     * @param int            $did xapian document id of the document to replace
     *
     * @return mixed
     */
    public function replace_document($doc, $did)
    {
        if (!is_a($doc, 'XapianDocument')) {
            return false;
        }
        if ($this->db == null) {
            $this->connectDb();
        }
        try {
            $this->getDb()->replace_document((int) $did, $doc);
            $this->getDb()->flush();
        } catch (Exception $e) {
            echo Display::return_message($e->getMessage(), 'error');

            return 1;
        }
    }
}
