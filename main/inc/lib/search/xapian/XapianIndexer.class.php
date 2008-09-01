<?php
require 'xapian.php';

/**
 * Abstract helper class
 */
abstract class XapianIndexer {
    /* XapianWritableDatabase */
    protected $db;
    protected $parents;
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
	 * Dokeos languages and Xapian languages (through hardcoding)
	 * @return	array	Array of languages codes -> Xapian languages
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
        }
        catch (Exception $e) {
          Display::display_error_message($e->getMessage());
          return 1;
        }
    }
    
    /**
     * Simple getter for the db attribute
     * @return	object	The db attribute
     */
    function getDb() {
        return $this->db;
    }

	/**
	 * Add this chunk to the chunk array attribute
	 * @param	string	Chunk of text
	 * @return  void
	 */
    function addChunk($chunk) {
        /*
        if ($chunk->parent) {
            $this->parents[] = $chunk;
        } else {
            $this->chunks[] = $chunk;
        }
        */
        $this->chunks[] = $chunk;
    }
    
    /**
     * Actually index the current data
     * 
     * @return integer	New Xapian document ID or NULL upon failure 
     */
    function index() {
      try {
        foreach ($this->chunks as $chunk) {
            $doc = new XapianDocument();
            $this->indexer->set_document($doc);
            
            foreach ($chunk->terms as $term) {
                /* FIXME: think of getting weight */
                $doc->add_term($term['flag'] . $term['name'], 1);
            }

            /* free-form ignoring ids, indexes title and content */
            foreach ($chunk->data as $key => $value) {
                //if text is empty, we don't index (because it triggers a Xapian error)
                if ($key != 'ids' && !empty($value))
                    $this->indexer->index_text($value, 1);
            }
            
            /* Hard-coded approach */
            /*
            if (array_key_exists ('title', $chunk->data))
                $this->indexer->index_text($chunk->data['title'], 1);
            */

            $doc->set_data($chunk->data['ids'], 1);
            $id = $chunk->getId();
            if ($id < 0)
              return NULL;

            $did = $this->db->replace_document($id, $doc);
            
            //write to disk
            $this->db->flush();

            return $did;
        }
      }
      catch (Exception $e) {
        Display::display_error_message($e->getMessage());
        exit(1);
      }

    }

    /**
     * Get a specific document from xapian db
     *
     * @param   int     did     Xapian::docid
     * @return  XapianDocument
     */
    function get_document($did) {
      if ($path == NULL) {
        $this->connectDb();
      }
      return $this->db->get_document($did);
    }

    /**
     * Replace all terms of a document in xapian db
     *
     * @param   int     did     Xapian::docid
     * @param   array   terms   New terms of the document
     */
    function update_terms($did, $terms, $prefix='T') {
      $doc = $this->get_document($did);
      $doc->clear_terms();
      foreach ($terms as $term) {
        //add directly
        $doc->add_term($prefix.$term, 1);
      }
      $this->db->replace_document($did, $doc);
      $this->db->flush();
    }

    /**
     * Remove a document from xapian db
     *
     * @param int   did     Xapian::docid
     */
    function remove_document($did) {
      if ($path == NULL) {
        $this->connectDb();
      }
      $this->db->delete_document($did);
      $this->db->flush();
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
?>
