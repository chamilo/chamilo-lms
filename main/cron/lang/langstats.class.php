<?php 
/* For licensing terms, see /license.txt */
/**
 * This class takes the creation and querying of an SQLite DB in charge. The
 * goal of this DB is to get stats on the usage of language vars for a common
 * user.
 * @package chamilo.cron.lang
 */
/**
 * This class takes the creation and querying of an SQLite DB in charge. The
 * goal of this DB is to get stats on the usage of language vars for a common
 * user. This class requires the SQLite extension of PHP to be installed. The
 * check for the availability of sqlite_open() should be made before calling
 * the constructor (preferrably)
 */
class langstats {

  public $db; //database connector
  public $error; //errores almacenados
  
  public function __construct($file='') {
    if (!class_exists('SQLite3')) {
      $this->error = 'SQLiteNotAvailable';
      return false; //cannot use if sqlite not installed
    }
    if (empty($file)) {
      $file = api_get_path(SYS_ARCHIVE_PATH).'/langstasdb';
    }
    if (is_file($file) && is_writeable($file)) {
      $this->db = new SQLite3($file,SQLITE3_OPEN_READWRITE);
    } else {
      try {
        $this->db = new SQLite3($file);
      } catch (Exception $e) {
        $this->error = 'DatabaseCreateError';
        error_log('Exception: '. $e->getMessage());
        return false;
      }
      $err = $this->db->exec('CREATE TABLE lang_freq ('
        .' id integer PRIMARY KEY AUTOINCREMENT, '
        .' term_name text, term_file text, term_count integer default 0)');
      if ($err === false) { $this->error = 'CouldNotCreateTable'; return false;}
      $err = $this->db->exec('CREATE INDEX lang_freq_terms_idx ON lang_freq(term_name, term_file)');
      if ($err === false) { $this->error = 'CouldNotCreateIndex'; return false; }
      // Table and index created, move on.
    }
    return $this->db;
  }

  /**
   * Add a count for a specific term
   * @param string The language term used
   * @param string The file from which the language term came from
   * @return boolean true
   */
  public function add_use($term, $term_file='') {
    $term = $this->db->escapeString($term);
    $term_file = $this->db->escapeString($term_file);
    $sql = "SELECT id, term_name, term_file, term_count FROM lang_freq WHERE term_name='$term' and term_file='$term_file'";
    $ress = $this->db->query($sql);
    if ($ress === false) {
      $this->error = 'CouldNotQueryTermFromTable';
      return false;
    }
    $i = 0;
    while ($row = $ress->fetchArray(SQLITE3_BOTH)) {
      $num = $row[3];
      $num++;
      $res = $this->db->query('UPDATE lang_freq SET term_count = '.$num.' WHERE id = '.$row[0]);
      if ($res === false) {
        $this->error = 'CouldNotUpdateTerm';
        return false;
      } else {
        return $row[0];
      }
      $i++;
    }
    if ($i == 0) {
      //No term found in the table, register as new term
      $resi = $this->db->query("INSERT INTO lang_freq(term_name, term_file, term_count) VALUES ('$term', '$term_file', 1)");
      if ($resi === false) {
        $this->error = 'CouldNotInsertRow';
        return false;
      } else {
        return $this->db->lastInsertRowID();
      }
    } 
  }

  /**
   * Function to get a list of the X most-requested terms
   * @param	integer	Limit of terms to show
   * @return	array	List of most requested terms
   */
  public function get_popular_terms($num=1000) {
    $res = $this->db->query('SELECT * FROM lang_freq ORDER BY term_count DESC LIMIT '.$num);
    $list = array();
    while ($row = $res->fetchArray()) {
      $list[] = $row;
    }
    return $list;
  }
  /**
   * Clear all records in lang_freq
   * @return boolean true
   */
  public function clear_all() {
    $res = sqlite_query($this->db, 'DELETE FROM lang_freq WHERE 1=1');
    return $list;
  }
} 
