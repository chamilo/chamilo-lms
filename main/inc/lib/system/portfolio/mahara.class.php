<?php

namespace Portfolio;

use Header;

/**
 * Interface with a Mahara portfolio.
 * 
 * This class requires that the connect mahara plugin is installed and enabled.
 *
 * @see https://mahara.org/
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class Mahara extends Portfolio
{

    protected $url = '';

    /**
     *
     * @param string $url       The root url 
     */
    function __construct($url)
    {
        parent::__construct('Mahara', null);
        $this->url = $url;
    }

    function get_url()
    {
        return $this->url;
    }

    /**
     *
     * @param User $user
     * @param Artefact $artefact
     * @return bool
     */
    function send($user, $artefact)
    {
        $root = $this->get_url();
        rtrim($root, '/');
        $url = $artefact->get_url();
        $url = $root . '/artefact/connect/upload.php?url=' . urlencode($url) . '&extract=true';
        Header::location($url);
    }

}