<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be.inc.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';

api_protect_course_script(true);

$action = $_REQUEST['a'];

switch ($action) {    
    case 'get_gradebook_weight':
        if (api_is_allowed_to_edit(null, true)) {
            $cat_id = $_GET['cat_id'];
            $cat = Category :: load($cat_id);            
            if ($cat && isset($cat[0])) {
                echo $cat[0]->get_weight();
            } else {
                echo 0;
            }
        }
        break;
    default:
        echo '';
}
exit;