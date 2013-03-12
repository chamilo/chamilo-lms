<?php
namespace ChamiloLMS\Controller;

class Controller {
    public $language_files = array();

    public function __construct() {/*
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
}