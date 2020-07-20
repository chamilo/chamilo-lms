<?php

    require_once __DIR__.'/../../main/inc/global.inc.php';

    ini_set('display_errors',1);
    ini_set('display_startup_errors',1);
    error_reporting(E_ALL);

    $user = api_get_user_info();
    $userId = $user['id'];
    $idurl = api_get_current_access_url_id();
    $UrlWhere = "";

	if ((api_is_platform_admin() || api_is_session_admin()) && api_get_multiple_access_url()) {
        $UrlWhere = " AND url_id = $idurl ";
    }

    if(isset($_GET['id'])){

        $id = (int) $_GET['id'];
        $table = 'plugin_h5p';
        $sql = "SELECT * FROM $table  WHERE id = $id AND user_id = $userId $UrlWhere";
        $result = Database::query($sql);

        while($Part=Database::fetch_array($result)){

            $pageFinale = __DIR__.'/cache_page/page-show-'.$id.'.html';

            echo $pageFinale;

            if(file_exists($pageFinale)){

                $base_html = file_get_contents($pageFinale);
                $base_html = str_replace( ' src="img/' , ' src="'.api_get_path(WEB_PLUGIN_PATH).'h5p/editor/img/', $base_html );
                $pageCustom = __DIR__.'/../../custompages/landchami-'.$id.'.html';

                $fp = fopen($pageCustom,'w');
		        fwrite($fp,$base_html);
                fclose($fp);

                if(file_exists($pageCustom)){
                    echo '<script>';
                    echo 'location.href="'.api_get_path(WEB_PATH).'custompages/landchami-'.$id.'.html";';
                    echo '</script>';
                }else{
                    echo ' the page ('.api_get_path(WEB_PATH).'custompages/landchami-'.$id.'.html) no exist !"';
                }


            }
        }

	}else{
        echo ' No ID !"';
    }
