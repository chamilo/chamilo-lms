<?php

/**
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class FileStore
{

    /**
     *
     * @param int $c_id
     * @param string $sub_path 

     * @return FileStore 
     */
    static function course($c_id, $sub_path = '') 
    { 
        
        $sys_path = api_get_path(SYS_COURSE_PATH);
        $course = api_get_course_info_by_id($c_id);
        $course_path = $course['path'];
        $path = $sys_path . $course_path . $sub_path;
        if (!is_dir($path)) {
            $mode = api_get_permissions_for_new_directories();
            $success = mkdir($path, $mode, true);
            if (!$success) {
                return false;
            }
        }
        return new self($path);
    }

    protected $root = '';

    public function __construct($root)
    {
        $root = ltrim($root, '/');
        $root .= '/';
        $this->root = $root;
    }

    public function root()
    {
        return $this->root;
    }

    function accept($filename)
    {
        return (bool) filter_extension($filename);
    }

    function add($path)
    {
        $root = $this->root();
        $id = $this->new_id();

        $new_path = "$root/$id";
        $success = @move_uploaded_file($path, $new_path);
        return $success ? $id : false;
    }
    
    function remove($path){
        
        $root = $this->root();
        $full_path = "$root/$path";
        if(is_file($full_path)){
            $result = unlink($full_path);
            return $result;
        }
        return  false;
    }

    function get($id)
    {
        $root = $this->root();
        $result = "$root/$id";
        return $result;
    }

    function new_id()
    {
        $root = $this->root();
        $id = uniqid('');
        $path = "$root/$id";
        while (file_exists($path)) {
            $id = uniqid('');
            $path = "$root/$id";
        }
        return $id;
    }

}