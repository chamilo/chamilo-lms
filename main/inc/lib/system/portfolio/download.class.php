<?php

namespace Portfolio;

use Header;

/**
 * Download file to your desktop
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class Download extends Portfolio
{

    function __construct()
    {
        parent::__construct('download', null);
    }

    /**
     *
     * @param User $user
     * @param Artefact $artefact
     * @return bool
     */
    function send($user, $artefact)
    {
        if ($artefact->get_url()) {
            Header::location($artefact->get_url());
        }
    }

}