<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\App\ModelAjax;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ModelAjaxController should replace the model.ajax.php file
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class ModelAjaxController
{
     public $ops = array(
        'eq' => '=',        //equal
        'ne' => '<>',       //not equal
        'lt' => '<',        //less than
        'le' => '<=',       //less than or equal
        'gt' => '>',        //greater than
        'ge' => '>=',       //greater than or equal
        'bw' => 'LIKE',     //begins with
        'bn' => 'NOT LIKE', //doesn't begin with
        'in' => 'LIKE',     //is in
        'ni' => 'NOT LIKE', //is not in
        'ew' => 'LIKE',     //ends with
        'en' => 'NOT LIKE', //doesn't end with
        'cn' => 'LIKE',     //contains
        'nc' => 'NOT LIKE'  //doesn't contain
    );

    //@todo move this in the display_class or somewhere else
    /**
     * @param $col
     * @param $oper
     * @param $val
     * @return string
     */
    private function getWhereClause($col, $oper, $val)
    {

        if (empty($col)) {
            return '';
        }
        if ($oper == 'bw' || $oper == 'bn') {
            $val .= '%';
        }
        if ($oper == 'ew' || $oper == 'en') {
            $val = '%'.$val;
        }
        if ($oper == 'cn' || $oper == 'nc' || $oper == 'in' || $oper == 'ni') {
            $val = '%'.$val.'%';
        }
        $val = \Database::escape_string($val);

        return " $col {$this->ops[$oper]} '$val' ";
    }

    /**
     * @param Application $app
     * @return string
     */
    public function indexAction(Application $app)
    {
        $request = $app['request'];

        $language_file = array('admin', 'exercice', 'gradebook', 'tracking');

        // 1. Setting variables needed by jqgrid

        $action = $request->get('a');
        $page   = $request->get('page'); //page
        $limit  = $request->get('rows'); //quantity of rows
        $sidx   = $request->get('sidx');         //index (field) to filter
        $sord   = $request->get('sord');         //asc or desc

        if (strpos(strtolower($sidx), 'asc') !== false) {
            $sidx = str_replace(array('asc', ','), '', $sidx);
            $sord = 'asc';
        }

        if (strpos(strtolower($sidx), 'desc') !== false) {
            $sidx = str_replace(array('desc', ','), '', $sidx);
            $sord = 'desc';
        }

        if (!in_array($sord, array('asc','desc'))) {
            $sord = 'desc';
        }

        if (!in_array($action,
            array(
                'get_exercise_results',
                'get_hotpotatoes_exercise_results',
                'get_work_user_list',
                'get_timelines',
                'get_user_skill_ranking',
                'get_usergroups_teacher',
                'get_question_list',
                'get_user_list_plugin_widescale'
            )
        ))
        {
            api_protect_admin_script(true);
        }

        if ($action == 'get_user_list_plugin_widescale') {
            $allowed = api_is_drh() || api_is_platform_admin();
            if (!$allowed) {
                api_not_allowed();
            }
        }

        // Search features.

        // If there is no search request sent by jqgrid, $where should be empty.
        $where_condition = "";
        $operation       = $request->get('oper');
        $export_format   = $request->get('export_format');
        $search_field    = $request->get('searchField');
        $search_oper     = $request->get('searchOper');
        $search_string   = $request->get('searchString');
        $isSearch        = $request->get('_search');
        $filters         = $request->get('filters');
        $type            = $request->get('type');

        $extra_fields = array();
        $questionFields = array();

        if ($isSearch  == 'true') {
            $where_condition = ' 1 = 1 ';
            $where_condition_in_form = $this->getWhereClause($search_field, $search_oper, $search_string);

            if (!empty($where_condition_in_form)) {
                $where_condition .= ' AND '.$where_condition_in_form;
            }
            $filters = isset($filters) ? json_decode($filters) : false;

            // for now
            if (!empty($filters)) {
                switch($action) {
                    case 'get_questions':
                        $extraFieldtype = 'question';
                        break;
                    case 'get_sessions':
                        $extraFieldtype = 'session';
                        break;
                }

                // Extra field.

                $extraField = new \ExtraField($extraFieldtype);
                $result = $extraField->getExtraFieldRules($filters, 'extra_');

                $extra_fields = $result['extra_fields'];
                $condition_array = $result['condition_array'];

                if (!empty($condition_array)) {
                    $where_condition .= ' AND ( ';
                    $where_condition .= implode($filters->groupOp, $condition_array);
                    $where_condition .= ' ) ';
                }

                // Question field.

                $resultQuestion = $extraField->getExtraFieldRules($filters, 'question_');
                $questionFields = $resultQuestion['extra_fields'];

                $condition_array = $resultQuestion['condition_array'];

                if (!empty($condition_array)) {
                    $where_condition .= ' AND ( ';
                    $where_condition .= implode($filters->groupOp, $condition_array);
                    $where_condition .= ' ) ';
                }
            }
        }

        // get index row - i.e. user click to sort $sord = $_GET['sord'];
        // get the direction
        if (!$sidx) {
            $sidx = 1;
        }

        //2. Selecting the count FIRST
        //@todo rework this

        switch ($action) {
            case 'get_questions':
                $categoryId = $request->get('categoryId');
                $exerciseId = $request->get('exerciseId');
                //$courseId = null; //$request->get('courseId');
                $courseId = $request->get('courseId');

                // Question manager can view all questions
                if (api_is_question_manager()) {
                    $courseId  = null;
                }

                $count = \Question::getQuestions(
                    $app,
                    $categoryId,
                    $exerciseId,
                    $courseId,
                    array(
                        'where'=> $where_condition,
                        'extra' => $extra_fields,
                        'question' => $questionFields
                    ),
                    true
                );
                break;
            case 'get_user_list_plugin_widescale':
                $count = \UserManager::get_user_data(null, null, null, null, true);
                break;
            case 'get_question_list':
                require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php';
                $exerciseId = $request->get('exerciseId');
                $exercise = new \Exercise(api_get_course_int_id());
                $exercise->read($exerciseId);
                $count = $exercise->selectNbrQuestions();
                break;
            case 'get_group_reporting':
                $course_id = $request->get('course_id');
                $group_id = $request->get('gidReq');
                $count = \Tracking::get_group_reporting($course_id, $group_id, 'count');
                break;
            case 'get_user_course_report_resumed':
                $count = \CourseManager::get_count_user_list_from_course_code(true, 'ruc');
                break;
            case 'get_user_course_report':
                $count = \CourseManager::get_count_user_list_from_course_code(false);
                break;
            case 'get_course_exercise_medias':
                $course_id = api_get_course_int_id();
                $count = \Question::get_count_course_medias($course_id);
                break;
            case 'get_user_skill_ranking':
                $skill = new \Skill();
                $count = $skill->get_user_list_skill_ranking_count();
                break;
            case 'get_work_user_list':
                require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
                $work_id = $request->get('work_id'); //$_REQUEST['work_id'];
                $count = get_count_work($work_id);
                break;
            case 'get_exercise_results':
                $exercise_id = $request->get('exerciseId');//$_REQUEST['exerciseId'];
                $filter_by_user = $request->get('filter_by_user');
                if (isset($filter_by_user) && !empty($filter_by_user)) {
                    $filter_user = intval($filter_by_user);
                    if ($where_condition == "") {
                        $where_condition .= " te.exe_user_id  = '$filter_user'" ;
                    } else {
                        $where_condition .= " AND te.exe_user_id  = '$filter_user'";
                    }
                }
                $count = \ExerciseLib::get_count_exam_results($exercise_id, $where_condition);
                break;
            case 'get_hotpotatoes_exercise_results':
                $hotpot_path = $request->get('path'); //$_REQUEST['path'];
                $count = \ExerciseLib::get_count_exam_hotpotatoes_results($hotpot_path);
                break;
            case 'get_sessions':
                $list_type = $request->get('list_type');
                if ($list_type == 'simple' || empty($list_type)) {
                    $count = \SessionManager::get_sessions_admin(array('where'=> $where_condition, 'extra' => $extra_fields), true);
                } else {
                    $count = \SessionManager::get_count_admin_complete(array('where'=> $where_condition, 'extra' => $extra_fields));
                }
                break;
            case 'get_extra_fields':
                $obj = new \ExtraField($type);
                $count = $obj->get_count();
                break;
            case 'get_extra_field_options':
                $field_id = $request->get('field_id');
                $obj = new \ExtraFieldOption($type);
                $count = $obj->get_count_by_field_id($field_id);
                break;
            case 'get_timelines':
                $obj        = new \Timeline();
                $count      = $obj->get_count();
                break;
            case 'get_gradebooks':
                $obj        = new \Gradebook();
                $count      = $obj->get_count();
                break;
            case 'get_event_email_template':
                $obj        = new \EventEmailTemplate();
                $count      = $obj->get_count();
                break;
            case 'get_careers':
                $obj        = new \Career();
                $count      = $obj->get_count();
                break;
            case 'get_promotions':
                $obj        = new \Promotion();
                $count      = $obj->get_count();
                break;
            case 'get_grade_models':
                $obj        = new \GradeModel();
                $count      = $obj->get_count();
                break;
            case 'get_usergroups':
                $obj        = new \UserGroup();
                $count      = $obj->get_count();
                break;
            case 'get_usergroups_teacher':
                $obj        = new \UserGroup();
                $course_id = api_get_course_int_id();
                if ($type == 'registered') {
                    $count = $obj->get_usergroup_by_course_with_data_count($course_id);
                } else {
                    $count = $obj->get_count();
                }
                break;
            default:
                exit;
        }

        //3. Calculating first, end, etc
        $total_pages = 0;

        if ((int)$count > 0) {
            if (!empty($limit)) {
                $total_pages = ceil($count/$limit);
            }
        }
        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $start = $limit * $page - $limit;
        if ($start < 0) {
            $start = 0;
        }

        //4. Deleting an element if the user wants to
        if ($operation == 'del') {
            $obj->delete($request->get('id'));
        }

        $is_allowedToEdit = api_is_allowed_to_edit(null, true) || api_is_allowed_to_edit(true) || api_is_drh();

        //5. Querying the DB for the elements
        $columns = array();

        switch ($action) {
            case 'get_questions':
                $columns = \Question::getQuestionColumns(api_get_course_id(), $extra_fields, $questionFields, true);
                $columns = $columns['simple_column_name'];
                $result = \Question::getQuestions(
                    $app,
                    $categoryId,
                    $exerciseId,
                    $courseId,
                    array(
                        'where'=> $where_condition,
                        'order'=>"$sidx $sord",
                        'extra' => $extra_fields,
                        'question' => $questionFields,
                        'limit'=> "$start , $limit"
                    )
                );
                //var_dump($result);

                break;
            case 'get_user_list_plugin_widescale':
                $columns = array('username', 'firstname', 'lastname', 'exam_password');
                $column_names = array(get_lang('Username'), get_lang('Firstname'), get_lang('Lastname'), get_lang('Password'));
                $result = \UserManager::get_user_data($start, $limit, $sidx, $sord);
                break;
            case 'get_question_list':
                if (isset($exercise) && !empty($exercise)) {
                    $columns = array('question', 'type', 'category', 'level', 'score', 'actions');
                    $result = $exercise->getQuestionListPagination($start, $limit, $sidx, $sord, $where_condition);
                }
                break;
            case 'get_group_reporting':
                $columns = array('name', 'time', 'progress', 'score', 'works', 'messages', 'actions');
                $result = \Tracking::get_group_reporting($course_id, $group_id, 'all', $start, $limit, $sidx, $sord, $where_condition);
                break;
            case 'get_course_exercise_medias':
                $columns = array('question');
                $result = \Question::get_course_medias($course_id, $start, $limit, $sidx, $sord, $where_condition);
                if (!empty($result)) {
                    foreach ($result as &$media) {
                        $media['id'] = $media['iid'];
                    }
                }
                break;
            case 'get_user_course_report_resumed':
                $columns = array('extra_ruc', 'training_hours', 'count_users', 'count_users_registered', 'average_hours_per_user', 'count_certificates');
                $column_names = array(get_lang('Company'), get_lang('TrainingHoursAccumulated'), get_lang('CountOfSubscriptions'), get_lang('CountOfUsers'), get_lang('AverageHoursPerStudent'), get_lang('CountCertificates'));
                $result = \CourseManager::get_user_list_from_course_code(null, null, "LIMIT $start, $limit", " $sidx $sord", null, null, true, true, 'ruc');
                $new_result = array();
                if (!empty($result)) {
                    foreach ($result as $row) {
                        $row['training_hours'] = api_time_to_hms($row['training_hours']);
                        $row['average_hours_per_user'] = api_time_to_hms($row['average_hours_per_user']);
                        $new_result[] = $row;
                    }
                    $result = $new_result;
                }
                break;
            case 'get_user_course_report':
                $columns = array('course', 'user', 'time', 'certificate', 'progress_100', 'progress');
                $column_names = array(get_lang('Course'), get_lang('User'), get_lang('ManHours'), get_lang('CertificateGenerated'), get_lang('Approved'), get_lang('CourseAdvance'));
                $extra_fields = \UserManager::get_extra_fields(0, 100, null, null, true, true);
                if (!empty($extra_fields)) {
                    foreach($extra_fields as $extra) {
                        $columns[] = $extra['1'];
                        $column_names[] = $extra['3'];
                    }
                }
                $result = \CourseManager::get_user_list_from_course_code(null, null, "LIMIT $start, $limit", " $sidx $sord", null, null, true);
                break;
            case 'get_user_skill_ranking':
                $columns = array('photo', 'firstname', 'lastname', 'skills_acquired', 'currently_learning', 'rank');
                $result = $skill->get_user_list_skill_ranking($start, $limit, $sidx, $sord, $where_condition);
                $result = \ArrayClass::msort($result, 'skills_acquired', 'asc');

                $skills_in_course = array();
                if (!empty($result)) {
                    //$counter = 1;
                    foreach ($result as &$item) {
                        $user_info = api_get_user_info($item['user_id']);
                        $personal_course_list = \UserManager::get_personal_session_course_list($item['user_id']);
                        $count_skill_by_course = array();
                        foreach ($personal_course_list  as $course_item) {
                            if (!isset($skills_in_course[$course_item['code']])) {
                                $count_skill_by_course[$course_item['code']] = $skill->get_count_skills_by_course($course_item['code']);
                                $skills_in_course[$course_item['code']] = $count_skill_by_course[$course_item['code']];
                            } else {
                                $count_skill_by_course[$course_item['code']] = $skills_in_course[$course_item['code']];
                            }
                        }
                        $item['photo'] =\ Display::img($user_info['avatar_small']);
                        $item['currently_learning'] = !empty($count_skill_by_course) ? array_sum($count_skill_by_course) : 0;
                    }
                }
                break;
            case 'get_work_user_list':
                if (isset($type) && $type == 'simple') {
                    $columns = array('type', 'firstname', 'lastname',  'username', 'title', 'qualification', 'sent_date', 'qualificator_id', 'actions');
                } else {
                    $columns = array('type', 'firstname', 'lastname',  'username', 'title', 'sent_date', 'actions');
                }
                $result = get_work_user_list($start, $limit, $sidx, $sord, $work_id, $where_condition);
                break;
            case 'get_exercise_results':
                $course                     = api_get_course_info();
                //used inside get_exam_results_data()
                $documentPath				= api_get_path(SYS_COURSE_PATH) . $course['path'] . "/document";
                if ($is_allowedToEdit) {
                    $columns = array('firstname', 'lastname', 'username', 'group_name', 'exe_duration', 'start_date', 'exe_date', 'score', 'status', 'lp', 'actions');
                } else {
                    //$columns = array('exe_duration', 'start_date', 'exe_date', 'score', 'status', 'actions');
                }
                $result = \ExerciseLib::get_exam_results_data($start, $limit, $sidx, $sord, $exercise_id, $where_condition);
                break;
            case 'get_hotpotatoes_exercise_results':
                $course = api_get_course_info();
                //used inside get_exam_results_data()
                $documentPath = api_get_path(SYS_COURSE_PATH) . $course['path'] . "/document";
                $columns = array('firstname', 'lastname', 'username', 'group_name', 'exe_date',  'score', 'actions');
                $result = ExerciseLib::get_exam_results_hotpotatoes_data($start, $limit, $sidx, $sord, $hotpot_path, $where_condition); //get_exam_results_data($start, $limit, $sidx, $sord, $exercise_id, $where_condition);
                break;
            case 'get_sessions':
                $session_columns = \SessionManager::get_session_columns($list_type);
                $columns = $session_columns['simple_column_name'];

                if ($list_type == 'simple') {
                    $result = SessionManager::get_sessions_admin(array('where'=> $where_condition, 'order'=>"$sidx $sord", 'extra' => $extra_fields, 'limit'=> "$start , $limit"), false);
                } else {
                    $result = SessionManager::get_sessions_admin_complete(array('where'=> $where_condition, 'order'=>"$sidx $sord", 'extra' => $extra_fields, 'limit'=> "$start , $limit"));
                }
                break;
             case 'get_timelines':
                $columns = array('headline', 'actions');
                //$columns = array('headline', 'type', 'start_date', 'end_date', 'text', 'media', 'media_credit', 'media_caption', 'title_slide', 'parent_id');

                if(!in_array($sidx, $columns)) {
                    $sidx = 'headline';
                }
                $course_id = api_get_course_int_id();
                $result     = Database::select('*', $obj->table, array('where' => array('parent_id = ? AND c_id = ?' => array('0', $course_id)), 'order'=>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
                $new_result = array();
                foreach ($result as $item) {
                    if (!$item['status']) {
                        $item['name'] = '<font style="color:#AAA">'.$item['name'].'</font>';
                    }
                    $item['headline'] = Display::url($item['headline'], api_get_path(WEB_CODE_PATH).'timeline/view.php?id='.$item['id']);
                    $item['actions'] = Display::url(Display::return_icon('add.png', get_lang('AddItems')), api_get_path(WEB_CODE_PATH).'timeline/?action=add_item&parent_id='.$item['id']);
                    $item['actions'] .= Display::url(Display::return_icon('edit.png', get_lang('Edit')), api_get_path(WEB_CODE_PATH).'timeline/?action=edit&id='.$item['id']);
                    $item['actions'] .= Display::url(Display::return_icon('delete.png', get_lang('Delete')), api_get_path(WEB_CODE_PATH).'timeline/?action=delete&id='.$item['id']);

                    $new_result[] = $item;
                }
                $result = $new_result;
                break;
            case 'get_gradebooks':
                $columns = array('name', 'certificates','skills', 'actions', 'has_certificates');
                if (!in_array($sidx, $columns)) {
                    $sidx = 'name';
                }
                $result     = Database::select('*', $obj->table, array('order'=>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
                $new_result = array();
                foreach($result as $item) {
                    if ($item['parent_id'] != 0) {
                        continue;
                    }
                    $skills = $obj->get_skills_by_gradebook($item['id']);

                    //Fixes bug when gradebook doesn't have names
                    if (empty($item['name'])) {
                        $item['name'] = $item['course_code'];
                    } else {
                        //$item['name'] =  $item['name'].' ['.$item['course_code'].']';
                    }

                    $item['name'] = Display::url($item['name'], api_get_path(WEB_CODE_PATH).'gradebook/index.php?id_session=0&cidReq='.$item['course_code']);

                    if (!empty($item['certif_min_score']) && !empty($item['document_id'])) {
                        $item['certificates'] = Display::return_icon('accept.png', get_lang('WithCertificate'), array(), ICON_SIZE_SMALL);
                         $item['has_certificates'] = '1';
                    } else {
                        $item['certificates'] = Display::return_icon('warning.png', get_lang('NoCertificate'), array(), ICON_SIZE_SMALL);
                        $item['has_certificates'] = '0';
                    }
                    if (!empty($skills)) {
                        foreach($skills as $skill) {
                            $item['skills'] .= Display::span($skill['name'], array('class' => 'label_tag skill'));
                        }
                    }
                    $new_result[] = $item;
                }
                $result = $new_result;
                break;
            case 'get_event_email_template':
                $columns = array('subject', 'event_type_name', 'language_id', 'activated', 'actions');
                if (!in_array($sidx, $columns)) {
                    $sidx = 'subject';
                }
                $result = Database::select('*', $obj->table, array('order'=>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
                $new_result = array();
                foreach ($result as $item) {
                    $language_info = api_get_language_info($item['language_id']);
                    $item['language_id'] = $language_info['english_name'];
                    $item['actions'] = Display::url(Display::return_icon('edit.png', get_lang('Edit')), api_get_path(WEB_CODE_PATH).'admin/event_type.php?action=edit&event_type_name='.$item['event_type_name']);
                    $item['actions'] .= Display::url(Display::return_icon('delete.png', get_lang('Delete')), api_get_path(WEB_CODE_PATH).'admin/event_controller.php?action=delete&id='.$item['id']);

                    /*if (!$item['status']) {
                        $item['name'] = '<font style="color:#AAA">'.$item['subject'].'</font>';
                    }*/
                    $new_result[] = $item;
                }
                $result = $new_result;
                break;
            case 'get_careers':
                $columns = array('name', 'description', 'actions');
                if(!in_array($sidx, $columns)) {
                    $sidx = 'name';
                }
                $result     = Database::select('*', $obj->table, array('order'=>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
                $new_result = array();
                foreach ($result as $item) {
                    if (!$item['status']) {
                        $item['name'] = '<font style="color:#AAA">'.$item['name'].'</font>';
                    }
                    $new_result[] = $item;
                }
                $result = $new_result;
                break;
            case 'get_promotions':
                $columns = array('name', 'career', 'description', 'actions');
                if(!in_array($sidx, $columns)) {
                    $sidx = 'name';
                }
                $result     = Database::select('p.id,p.name, p.description, c.name as career, p.status', "$obj->table p LEFT JOIN ".Database::get_main_table(TABLE_CAREER)." c  ON c.id = p.career_id ", array('order' =>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
                $new_result = array();
                foreach($result as $item) {
                    if (!$item['status']) {
                        $item['name'] = '<font style="color:#AAA">'.$item['name'].'</font>';
                    }
                    $new_result[] = $item;
                }
                $result = $new_result;
                break;
            case 'get_grade_models':
                $columns = array('name', 'description', 'actions');
                if (!in_array($sidx, $columns)) {
                    $sidx = 'name';
                }
                $result     = Database::select('*', "$obj->table ", array('order' =>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
                $new_result = array();
                foreach($result as $item) {
                    $new_result[] = $item;
                }
                $result = $new_result;
                break;
            case 'get_usergroups':
                $columns = array('name', 'users', 'courses', 'sessions', 'group_type', 'actions');
                $result     = Database::select('*', $obj->table, array('order'=>"name $sord", 'LIMIT'=> "$start , $limit"));
                $new_result = array();
                if (!empty($result)) {
                    foreach ($result as $group) {
                        $group['sessions']   = count($obj->get_sessions_by_usergroup($group['id']));
                        $group['courses']    = count($obj->get_courses_by_usergroup($group['id']));
                        $group['users']      = count($obj->get_users_by_usergroup($group['id']));
                        switch ($group['group_type']) {
                            case '0':
                                $group['group_type'] = Display::label(get_lang('Class'), 'info');
                                break;
                            case '1' :
                                $group['group_type'] = Display::label(get_lang('Social'), 'success');
                                break;
                        }
                        $new_result[] = $group;
                    }
                    $result = $new_result;
                }
                $columns = array('name', 'users', 'courses','sessions', 'group_type');
                if(!in_array($sidx, $columns)) {
                    $sidx = 'name';
                }
                //Multidimensional sort
                ArrayClass::msort($result, $sidx);
                break;
            case 'get_extra_fields':
                $obj = new \ExtraField($type);
                $columns = array('field_display_text', 'field_variable', 'field_type', 'field_changeable', 'field_visible', 'field_filter', 'field_order');
                $result  = \Database::select('*', $obj->table, array('order'=>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
                $new_result = array();
                if (!empty($result)) {
                    foreach ($result as $item) {
                        $item['field_type']         = $obj->get_field_type_by_id($item['field_type']);
                        $item['field_changeable']   = $item['field_changeable'] ? Display::return_icon('right.gif') : Display::return_icon('wrong.gif');
                        $item['field_visible']      = $item['field_visible'] ? Display::return_icon('right.gif') : Display::return_icon('wrong.gif');
                        $item['field_filter']       = $item['field_filter'] ? Display::return_icon('right.gif') : Display::return_icon('wrong.gif');
                        $new_result[]        = $item;
                    }
                    $result = $new_result;
                }
                break;
            case 'get_extra_field_options':
                $obj = new \ExtraFieldOption($type);
                $columns = array('option_display_text', 'option_value', 'option_order');
                $result  = \Database::select('*', $obj->table, array('where' => array("field_id = ? " => $field_id),'order'=>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
                /*$new_result = array();
                if (!empty($result)) {
                    foreach ($result as $item) {
                        $item['field_type']         = $obj->get_field_type_by_id($item['field_type']);
                        $item['field_changeable']   = $item['field_changeable'] ? Display::return_icon('right.gif') : Display::return_icon('wrong.gif');
                        $item['field_visible']      = $item['field_visible'] ? Display::return_icon('right.gif') : Display::return_icon('wrong.gif');
                        $item['field_filter']       = $item['field_filter'] ? Display::return_icon('right.gif') : Display::return_icon('wrong.gif');
                        $new_result[]        = $item;
                    }
                    $result = $new_result;
                }*/
                break;
            case 'get_usergroups_teacher':
                $columns = array('name', 'users', 'actions');
                $options = array('order'=>"name $sord", 'LIMIT'=> "$start , $limit");
                $options['course_id'] = $course_id;
                switch ($type) {
                    case 'not_registered':
                        $options['where'] = array(" (course_id IS NULL OR course_id != ?) " => $course_id);
                        $result = $obj->get_usergroup_not_in_course($options);
                        break;
                    case 'registered':
                        $options['where'] = array(" usergroup.course_id = ? " =>  $course_id);
                        $result = $obj->get_usergroup_in_course($options);
                        break;
                }
                $new_result = array();

                if (!empty($result)) {
                    foreach ($result as $group) {
                        $group['users']      = count($obj->get_users_by_usergroup($group['id']));

                        if ($obj->usergroup_was_added_in_course($group['id'], $course_id)) {
                            $url  = 'class.php?action=remove_class_from_course&id='.$group['id'];
                            $icon = Display::return_icon('delete.png', get_lang('Remove'));
                        } else {
                            $url  = 'class.php?action=add_class_to_course&id='.$group['id'];
                            $icon = Display::return_icon('add.png', get_lang('Add'));
                        }
                        $group['actions']    = Display::url($icon, $url);

                        $new_result[]        = $group;
                    }
                    $result = $new_result;
                }
                if (!in_array($sidx, $columns)) {
                    $sidx = 'name';
                }
                //Multidimensional sort
                \ArrayClass::msort($result, $sidx);
                break;
            default:
                exit;
        }

        $allowed_actions = array(
            'get_careers',
            'get_promotions',
            'get_usergroups',
            'get_usergroups_teacher',
            'get_gradebooks',
            'get_sessions',
            'get_exercise_results',
            'get_hotpotatoes_exercise_results',
            'get_work_user_list',
            'get_timelines',
            'get_grade_models',
            'get_event_email_template',
            'get_user_skill_ranking',
            'get_extra_fields',
            'get_extra_field_options',
            'get_course_exercise_medias',
            'get_user_course_report',
            'get_user_course_report_resumed',
            'get_group_reporting',
            'get_question_list',
            'get_user_list_plugin_widescale',
            'get_questions'
        );

        //5. Creating an obj to return a json
        if (in_array($action, $allowed_actions)) {
            $response           = new \stdClass();
            $response->page     = $page;
            $response->total    = $total_pages;
            $response->records  = $count;

            if ($operation && $operation == 'excel') {
                $j = 1;

                $array = array();
                if (empty($column_names)) {
                    $column_names = $columns;
                }

                //Headers
                foreach ($column_names as $col) {
                    $array[0][] = $col;
                }
                foreach ($result as $row) {
                    foreach ($columns as $col) {
                        $array[$j][] = strip_tags($row[$col]);
                    }
                    $j++;
                }
                switch ($export_format) {
                    case 'xls':
                        Export::export_table_xls($array, 'company_report');
                        break;
                    case 'csv':
                    default:
                        Export::export_table_csv($array, 'company_report');
                        break;
                }
                exit;
            }
            $i = 0;
            if (!empty($result)) {
                foreach ($result as $row) {
                    //print_r($row);
                    // if results tab give not id, set id to $i otherwise id="null" for all <tr> of the jqgrid - ref #4235
                    if (!isset($row['id']) || isset($row['id']) && $row['id'] == "") {
                        $response->rows[$i]['id']=$i;
                    } else {
                        $response->rows[$i]['id']=$row['id'];
                    }
                    $array = array();
                    foreach ($columns as $col) {
                        $array[] = isset($row[$col]) ? $row[$col] : null;
                    }
                    $response->rows[$i]['cell']=$array;
                    $i++;
                }
            }
            return json_encode($response);
        }
    }
}
