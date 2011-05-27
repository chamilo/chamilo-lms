<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';
api_protect_course_script(true);
$action = $_REQUEST['a'];

switch ($action) {    
    case 'update_question_order':
        if (api_is_allowed_to_edit(null, true)) {    
            $new_question_list     = $_POST['question_id_list'];
            $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);        
            $counter = 1;
            foreach ($new_question_list as $new_order_id) {            
                Database::update($TBL_QUESTIONS, array('question_order'=>$counter), array('question_id = ? '=>intval($new_order_id)));
                $counter++;
            }
            Display::display_confirmation_message(get_lang('Saved'));
        }
        break;
    default:
        echo '';
}
exit;