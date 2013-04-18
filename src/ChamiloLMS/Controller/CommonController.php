<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Controller;

use \ChamiloSession as Session;

/**
 * @package ChamiloLMS.CommonController
 * @author Julio Montoya <gugli100@gmail.com>
 */
class CommonController
{

    public $languageFiles = array();
    /**
     *
    */
    public function __construct()
    {
    /*
        if (!empty($this->language_files)) {
            $language_interface = api_get_language_interface();
            $langPath = api_get_path(SYS_LANG_PATH);

            $language_files =  $this->language_files;
            foreach ($language_files as $index => $language_file) {
                // include English
                include $langPath.'english/'.$language_file.'.inc.php';
                // prepare string for current language
                $langFile = $langPath.$language_interface.'/'.$language_file.'.inc.php';
                if (file_exists($langFile)) {
                    include $langFile;
                }
            }
        }*/
    }

    /**
     *
     */
    public function cidReset()
    {
        Session::erase('_cid');
        Session::erase('_real_cid');
        Session::erase('_course');

        if (!empty($_SESSION)) {
            foreach ($_SESSION as $key => $item) {
                if (strpos($key,'lp_autolunch_') === false) {
                    continue;
                } else {
                    if (isset($_SESSION[$key])) {
                        Session::erase($key);
                    }
                }
            }
        }
        //Deleting session info
        if (api_get_session_id()) {
            Session::erase('id_session');
            Session::erase('session_name');
        }
        if (api_get_group_id()) {
            Session::erase('_gid');
        }
    }
}