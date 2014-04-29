<?php

namespace Portfolio;

/**
 * An artefact is any object the user can display in its portfolio. 
 * 
 * The artefact point either to a local file or to a url from which the object's content
 * can be fetched.
 * 
 * Usage
 * 
 *      $artefact = new artefact();
 *      $artefact->set_path('...');
 * 
 * or 
 * 
 * 
 *      $artefact = new artefact();
 *      $artefact->set_url('...');
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class Artefact
{

    protected $id = '';
    protected $mime_type = '';
    protected $name = '';
    protected $description = '';
    protected $path = '';
    protected $url = '';
    protected $creation_date = '';
    protected $modification_date = '';
    protected $metadata = null;

    /**
     *
     * @param string $file Either url or file path
     */
    public function __construct($file = '')
    {
        if ($file) {
            if (strpos($file, 'http') !== false) {
                $this->url = $file;
            } else {
                $this->path = $file;
            }
        }
        $this->id = uniqid('', true);
        $this->mime_type = '';
        $time = time();
        $this->creation_date = $time;
        $this->modification_date = $time;
    }

    public function get_id()
    {
        return $this->id;
    }

    public function set_id($value)
    {
        $this->id = $value;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function set_name($value)
    {
        $this->name = $value;
    }

    public function get_mime_type()
    {
        return $this->mime_type;
    }

    public function set_mime_type($value)
    {
        $this->mime_type = $value;
    }

    public function get_description()
    {
        return $this->description;
    }

    public function set_description($value)
    {
        $this->description = $value;
    }

    public function get_path()
    {
        return $this->path;
    }

    public function set_path($value)
    {
        $this->path = $value;
    }

    public function get_url()
    {
        return $this->url;
    }

    public function set_url($value)
    {
        $this->url = $value;
    }

    public function get_creation_date()
    {
        return $this->creation_date;
    }

    public function set_creation_date($value)
    {
        $this->creation_date = $value;
    }

    public function get_modification_date()
    {
        return $this->modification_date;
    }

    public function set_modification_date($value)
    {
        $this->modification_date = $value;
    }

    public function get_metadata()
    {
        return $this->metadata;
    }

    public function set_metadata($value)
    {
        $this->metadata = $value;
    }

}