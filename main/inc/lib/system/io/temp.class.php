<?php

/**
 * Temporary file/folder. The file/folder is automatically deleted at
 * the end of the script/during garbage collection.
 * 
 * The object implements __toString so it can be used as string variable.
 * 
 * Usage
 * 
 *      $path = Temp::file();
 *      file_puts_content($path, $content);
 * 
 * or
 * 
 *      $path = Temp::dir();
 *      ...
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class Temp
{

    /**
     * Recursively delete files and/or folders.
     * 
     * @param string $path
     * @return boolean 
     */
    public static function delete($path)
    {
        if (!file_exists($path)) {
            return false;
        }

        if (is_file($path)) {
            unlink($path);
            return true;
        }
        $files = scandir($path);
        $files = array_diff($files, array('.', '..'));
        foreach ($files as $file) {
            self::delete($file);
        }
        rmdir($path);
    }

    private static $temp_root = '';

    /**
     * Set the temp root directory. Temporary files are by default created in this directory.
     * Defaults to sys_get_temp_dir().
     * 
     * @param string $value 
     */
    public static function set_temp_root($value)
    {
        self::$temp_root = $value;
    }

    public static function get_temp_root()
    {
        if (empty(self::$temp_root)) {
            self::$temp_root = sys_get_temp_dir();
        }

        return self::$temp_root;
    }

    /**
     * Returns a path to a non-existing temporary file located under temp_dir.
     * 
     * @return string 
     */
    public static function get_temporary_name()
    {
        $result = self::get_temp_root() . '/' . md5(uniqid('tmp', true));
        while (file_exists($result)) {
            $result = self::get_temp_root() . '/' . md5(uniqid('tmp', true));
        }
        return $result;
    }

    /**
     *
     * @param string $path
     * @return Temp 
     */
    public static function file($path = '')
    {
        $path = $path ? $path : self::get_temporary_name();
        return new self($path);
    }

    /**
     *
     * @param string $path
     * @return Temp 
     */
    public static function dir($path = '')
    {
        $path = $path ? $path : self::get_temporary_name();
        if (!file_exists($path)) {
            mkdir($path, 0777, $true);
        }
        return new self($path);
    }

    /**
     *
     * @param string $path
     * @return Temp 
     */
    public static function create($path = '')
    {
        $path = $path ? $path : self::get_temporary_name();
        return new self($path);
    }

    protected $path = '';

    function __construct($path = '')
    {
        $this->path = $path;
    }

    function get_path()
    {
        return $this->path;
    }

    function __toString()
    {
        return $this->path;
    }

    function __destruct()
    {
        $path = $this->path;
        self::delete($path);
    }

}