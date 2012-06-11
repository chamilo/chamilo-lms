<?php

/**
 * Wrapper around pclzip. Makes a bit easier to use compression.
 * 
 * Usage:
 * 
 *      $zip = Zip::create('...');
 *      $zip->add($file_path, $local_path);
 * 
 * Note
 * 
 * Pclzip do not accept method callbacks. It only accepts pure function callbacks.
 * As a result the implementation is a bit more complicated than it should be.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Zip
{

    protected static $pool = array();

    public static function pool($hash = '')
    {
        if (empty($hash)) {
            return self::$pool;
        } else {
            return self::$pool[$hash];
        }
    }
    
    
    /**
     *
     * @param string $path
     * @return Zip 
     */
    public static function create($path)
    {
        return new self($path);
    }

    protected $path = '';
    protected $archive = null;
    protected $entries = array();

    public function __construct($path = '')
    {
        $this->path = $path;
        self::$pool[$this->get_hash()] = $this;
    }

    public function get_path()
    {
        return $this->path;
    }

    public function get_hash()
    {
        return md5($this->path);
    }

    public function add($file_path, $archive_path = '', $comment = '')
    {
        /**
         * Remove c: when working on windows. 
         */
        if (substr($file_path, 1, 1) == ':') {
            $file_path = substr($file_path, 2);
        }
        
        $entry = array(
            'file_path' => $file_path,
            'archive_path' => $archive_path,
            'comment' => $comment
        );
        $this->entries[$file_path] = $entry;

        $callback_name = 'zipcallback_' . $this->get_hash();
        if (!function_exists($callback_name)) {
            $callback = '';
            $callback .= 'function ' . $callback_name . '($event, &$header){';
            $callback .= '$parts = explode(\'_\', __FUNCTION__);';
            $callback .= '$hash = end($parts);';
            $callback .= 'return Zip::pool($hash)->callback($event, $header);';
            $callback .= '};';
            eval($callback);
        }

        $archive = $this->archive();
        $archive->add($file_path, PCLZIP_CB_PRE_ADD, $callback_name);
    }
    
    /**
     *
     * @return PclZip
     */
    protected function archive()
    {
        if ($this->archive) {
            return $this->archive;
        }
        if (empty($this->path)) {
            return null;
        }
        return $this->archive = new PclZip($this->path);
    }

    public function callback($event, &$header)
    {
        if ($event != PCLZIP_CB_PRE_ADD) {
            return 0;
        }

        $path = $header['filename'];
        if (!isset($this->entries[$path])) {
            return 1;
        }

        $entry = $this->entries[$path];
        $archive_path = $entry['archive_path'];
        if (!empty($archive_path)) {
            $header['stored_filename'] = $archive_path;
        }
        return 1;
    }

}