<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.include.search
 */
/**
 * Code
 */
require_once 'xapian.php';
require_once dirname(__FILE__) . '/../IndexableChunk.class.php';

/**
 * Abstract helper class
 * @package chamilo.include.search
 */
abstract class XapianIndexer {
    /* XapianWritableDatabase */
    protected $db;
    /* IndexableChunk[] */
    protected $chunks;
    /* XapianTermGenerator */
    public $indexer;
    /* XapianStem */
    public $stemmer;

  /**
   * Generates a list of languages Xapian manages
   *
   * This method enables the definition of more matches between
   * Chamilo languages and Xapian languages (through hardcoding)
   * @return  array  Array of languages codes -> Xapian languages
   */
    public final function xapian_languages() {
      /* http://xapian.org/docs/apidoc/html/classXapian_1_1Stem.html */
      return array(
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
      );
    }

  /**
   * Connect to the database, and create it if it doesn't exist
   */
    function connectDb($path=NULL, $dbMode=NULL, $lang='english') {
    	if ($this->db != NULL)
    		return $this->db;
        if ($dbMode == NULL)
            $dbMode = Xapian::DB_CREATE_OR_OPEN;

        if ($path == NULL)
            $path = api_get_path(SYS_PATH).'searchdb/';

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
            Display::display_error_message($e->getMessage());
            return 1;
        }
    }

    /**
     * Simple getter for the db attribute
     * @return  object  The db attribute
     */
    function getDb() {
        return $this->db;
    }

  /**
   * Add this chunk to the chunk array attribute
   * @param  string  Chunk of text
   * @return  void
   */
    function addChunk($chunk) {
        $this->chunks[] = $chunk;
    }

    /**
     * Actually index the current data
     *
     * @return integer  New Xapian document ID or NULL upon failure
     */
    function index() {
        try {
            if (!empty($this->chunks)) {                
                foreach ($this->chunks as $chunk) {
                    $doc = new XapianDocument();
                    $this->indexer->set_document($doc);
                    if (!empty($chunk->terms)) {
                        foreach ($chunk->terms as $term) {
                            /* FIXME: think of getting weight */
                            $doc->add_term($term['flag'] . $term['name'], 1);
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
            Display::display_error_message($e->getMessage());
            exit(1);
        }
    }

    /**
     * Get a specific document from xapian db
     *
     * @param   int     did     Xapian::docid
     * @return  mixed   XapianDocument, or false on error
     */
    function get_document($did) {
      if ($this->db == NULL) {
        $this->connectDb();
      }
      try {
        $docid = $this->db->get_document($did);
      }
      catch (Exception $e) {
        //Display::display_error_message($e->getMessage());
        return false;
      }
      return $docid;
    }

    /**
     * Get document data on a xapian document
     *
     * @param XapianDocument $doc xapian document to push into the db
     * @return mixed xapian document data or FALSE if error
     */
    function get_document_data($doc) {
      if ($this->db == NULL) {
        $this->connectDb();
      }
      try {
        if (!is_a($doc, 'XapianDocument')) {
        	return FALSE;
        }
        $doc_data = $doc->get_data();
        return $doc_data;
      }
      catch (Exception $e) {
        //Display::display_error_message($e->getMessage());
        return false;
      }
    }

    /**
     * Replace all terms of a document in xapian db
     *
     * @param   int     did     Xapian::docid
     * @param   array   terms   New terms of the document
     * @return  boolean false on error
     */
    function update_terms($did, $terms, $prefix) {
      $doc = $this->get_document($did);
      if($doc===false){return false;}
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
     * Remove a document from xapian db
     *
     * @param int   did     Xapian::docid
     */
    function remove_document($did) {
        if ($this->db == NULL) {
            $this->connectDb();
        }
        if (is_numeric($did) && $did>0) {
            $doc = $this->get_document($did);
            if ($doc !== FALSE) {
                $this->db->delete_document($did);
                $this->db->flush();
            }
        }
    }

    /**
     * Adds a term to the document specified
     *
     * @param string $term The term to add
     * @param XapianDocument $doc The xapian document where to add the term
     * @return  mixed   XapianDocument, or false on error
     */
    function add_term_to_doc($term, $doc) {
        if (!is_a($doc,'XapianDocument')) {
            return FALSE;
        }
        try {
            $doc->add_term($term);
        }
        catch (Exception $e) {
          Display::display_error_message($e->getMessage());
          return 1;
        }
    }

    /**
     * Remove a term from the document specified
     *
     * @param string $term The term to add
     * @param XapianDocument $doc The xapian document where to add the term
     * @return  mixed   XapianDocument, or false on error
     */
    function remove_term_from_doc($term, $doc) {
        if (!is_a($doc,'XapianDocument')) {
            return FALSE;
        }
        try {
            $doc->remove_term($term);
        }
        catch (Exception $e) {
          Display::display_error_message($e->getMessage());
          return 1;
        }
    }

    /**
     * Replace a document in the actual db
     *
     * @param XapianDocument $doc xapian document to push into the db
     * @param Xapian::docid $did xapian document id of the document to replace
     */
    function replace_document($doc, $did) {
        if (!is_a($doc,'XapianDocument')) {
            return FALSE;
        }
        if ($this->db == NULL) {
            $this->connectDb();
        }
        try {
            $this->getDb()->replace_document((int)$did, $doc);
			$this->getDb()->flush();
        }
        catch (Exception $e) {
          Display::display_error_message($e->getMessage());
          return 1;
        }
    }


  /**
   * Class contructor
   */
    function __construct() {
        $this->db = NULL;
        $this->stemmer = NULL;
    }
    /**
     * Class destructor
     */
    function __destruct() {
        unset($this->db);
        unset($this->stemmer);
    }
}
