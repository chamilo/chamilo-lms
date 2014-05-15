<?php

namespace Portfolio;

/**
 * Portfolio are used to display and share content. The porfolio class represents
 * one (external) portfolio application and allows to share content with an it. 
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class Portfolio
{

    public static function none()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self('empty', null);
        }
        return $result;
    }

    public static function all()
    {
        
    }

    protected $name;
    protected $title = '';
    protected $description = '';
    protected $channel;

    function __construct($name, $channel = null)
    {
        $this->name = $name;
        $this->title = $name;
        $this->channel = $channel;
    }

    /**
     * The name of the portfolio - i.e. the unique id.
     * @return type 
     */
    function get_name()
    {
        return $this->name;
    }
    
    /**
     * Title for the end user.
     * 
     * @return type 
     */
    function get_title()
    {
        return $this->title;
    }
    
    function set_title($value)
    {
        $this->title = $value;
    }
    
    /**
     * Description for the end user.
     * 
     * @return type 
     */
    function get_description()
    {
        return $this->description;
    }
    
    function set_description($value)
    {
        $this->description = $value;
    }

    /**
     *
     * @return HttpChannel
     */
    function channel()
    {
        return $this->channel;
    }

    function __toString()
    {
        return $this->name;
    }

    /**
     *
     * @param User $user
     * @param Artefact $artefact
     * @return bool
     */
    function send($user, $artefact)
    {
        return false;
    }

}