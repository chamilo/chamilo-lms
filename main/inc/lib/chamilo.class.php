<?php

/**
 *
 * @return ChamiloSession
 */
function session()
{
    return Chamilo::session();
}

/**
 * Description of chamilo
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Chamilo
{
    /**
     *
     * @return ChamiloSession
     */
    static function session()
    {
        return ChamiloSession::instance();
    }
}
