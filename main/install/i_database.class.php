<?php

/**
 * Install database. With logging.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class iDatabase extends Database
{

    static function select_db($database_name, $connection = null)
    {
        Log::notice(__FUNCTION__ . ' ' . $database_name, Log::frame(1));
        parent::select_db($database_name, $connection);
    }

    static function query($query, $connection = null, $file = null, $line = null)
    {
        Log::notice(__FUNCTION__ . ' ' . $database_name, Log::frame(1));

        parent::query($query, $connection, $file, $line);
    }

}