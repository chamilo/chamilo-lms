<?php

/* For license terms, see /license.txt */
/**
 * Return HTML list for AJAX request.
 */
require_once __DIR__.'/../../../../main/inc/global.inc.php';

if (api_is_anonymous()) {
    header('Location: '.api_get_path(WEB_PATH).'index.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$urlId = api_get_current_access_url_id();
$UrlWhere = "";
if (api_get_multiple_access_url()) {
    $UrlWhere = " WHERE url_id = $urlId ";
}

$sql = "SELECT id,title,node_type FROM plugin_h5p $UrlWhere ORDER BY title";
$resultSet = Database::query($sql);
$html = '';

while ($row = Database::fetch_array($resultSet)) {
    $id = $row['id'];
    $title = $row['title'];
    $nodeType = $row['node_type'];
    $html .= '<div class="bloch5pLine bloch5pLine'.$id.'" onClick="selectH5Pbase(\''.$id.'\',\''.$nodeType.'\');" >'.$title.'</div>';
}

echo $html;
