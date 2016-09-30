<?php
/* For licensing terms, see /license.txt */
// @todo refactor this script, create a class that manage the jqgrid requests
/**
 * Responses to AJAX calls
*/
$action = $_GET['a'];

switch ($action) {
    case 'set_visibility':
        require_once '../global.inc.php';
        $course_id = api_get_course_int_id();
        if (api_is_allowed_to_edit(null, true)) {
            $tool_table = Database::get_course_table(TABLE_TOOL_LIST);
            $tool_info = api_get_tool_information($_GET['id']);
            $tool_visibility = $tool_info['visibility'];
            $tool_image = $tool_info['image'];

            if (api_get_setting('homepage_view') != 'activity_big') {
                $tool_image = Display::return_icon($tool_image, null, null, null, null, true);
                $na_image = str_replace('.gif', '_na.gif', $tool_image);
            } else {
                // Display::return_icon() also checks in the app/Resources/public/css/themes/{theme}/icons folder
                $tool_image = (substr($tool_image, 0, strpos($tool_image, '.'))).'.png';
                $tool_image = Display::return_icon(
                    $tool_image,
                    get_lang(ucfirst($tool_info['name'])),
                    null,
                    ICON_SIZE_BIG,
                    null,
                    true
                );
                $na_image   = str_replace('.png', '_na.png', $tool_image);
            }

            if (isset($tool_info['custom_icon']) && !empty($tool_info['custom_icon'])) {
                $tool_image = CourseHome::getCustomWebIconPath().$tool_info['custom_icon'];
                $na_image = CourseHome::getCustomWebIconPath().CourseHome::getDisableIcon($tool_info['custom_icon']);
            }

            $requested_image = ($tool_visibility == 0 ) ? $tool_image : $na_image;
            $requested_class = ($tool_visibility == 0 ) ? '' : 'text-muted';
            $requested_message = ($tool_visibility == 0 ) ? 'is_active' : 'is_inactive';
            $requested_view = ($tool_visibility == 0 ) ? 'visible.png' : 'invisible.png';
            $requested_visible = ($tool_visibility == 0 ) ? 1 : 0;

            $requested_view = ($tool_visibility == 0 ) ? 'visible.png' : 'invisible.png';
            $requested_visible = ($tool_visibility == 0 ) ? 1 : 0;

            // HIDE AND REACTIVATE TOOL
            if ($_GET["id"] == strval(intval($_GET["id"]))) {
                $sql = "UPDATE $tool_table SET
                        visibility = $requested_visible
                        WHERE c_id = $course_id AND id='" . intval($_GET['id']) . "'";
                Database::query($sql);
            }
            $response_data = array(
                'image' => $requested_image,
                'tclass' => $requested_class,
                'message' => $requested_message,
                'view' => $requested_view
            );
            echo json_encode($response_data);
        }
        break;
    case 'show_course_information' :
        require_once '../global.inc.php';

        // Get the name of the database course.
        $tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
        $course_info = api_get_course_info($_GET['code']);

        if (
            api_get_setting('course_catalog_hide_private') === 'true' &&
            $course_info['visibility'] == COURSE_VISIBILITY_REGISTERED
        ) {
            echo get_lang('PrivateAccess');
            break;
        }

        $sql = "SELECT * FROM $tbl_course_description
                WHERE c_id = ".$course_info['real_id']." AND session_id = 0
                ORDER BY id";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0 ) {
            while ($description = Database::fetch_object($result)) {
                $descriptions[$description->id] = $description;
            }
            // Function that displays the details of the course description in html.
            echo CourseManager::get_details_course_description_html(
                $descriptions,
                api_get_system_encoding(),
                false
            );
        } else {
            echo get_lang('NoDescription');
        }
        break;
    case 'session_courses_lp_default':
        /**
         * @todo this functions need to belong to a class or a special
         * wrapper to process the AJAX petitions from the jqgrid
         */

        require_once '../global.inc.php';
        $now = time();
        $page  = intval($_REQUEST['page']);     //page
        $limit = intval($_REQUEST['rows']);     // quantity of rows
        //index to filter
        $sidx  = isset($_REQUEST['sidx']) && !empty($_REQUEST['sidx']) ? $_REQUEST['sidx'] : 'id';
        $sord  = $_REQUEST['sord'];    //asc or desc
        if (!in_array($sord, array('asc','desc'))) {
            $sord = 'desc';
        }
        $session_id  = intval($_REQUEST['session_id']);
        $course_id   = intval($_REQUEST['course_id']);

        //Filter users that does not belong to the session
        if (!api_is_platform_admin()) {
            $new_session_list = UserManager::get_personal_session_course_list(api_get_user_id());
            $my_session_list  = array();
            foreach($new_session_list as $item) {
                if (!empty($item['id_session']))
                    $my_session_list[] = $item['id_session'];
            }
            if (!in_array($session_id, $my_session_list)) {
                break;
            }
        }

        $start = $limit*$page - $limit;
        $course_list = SessionManager::get_course_list_by_session_id($session_id);
        $count = 0;
        $temp = array();
        foreach ($course_list as $item) {
            $list = new LearnpathList(api_get_user_id(), $item['code'], $session_id);
            $flat_list = $list->get_flat_list();
            $lps[$item['code']] = $flat_list;
            $course_url = api_get_path(WEB_COURSE_PATH).$item['directory'].'/?id_session='.$session_id;
            $item['title'] = Display::url($item['title'], $course_url, array('target' => SESSION_LINK_TARGET));

            foreach ($flat_list as $lp_id => $lp_item) {
                $temp[$count]['id']= $lp_id;

                $lp = new learnpath($item['code'], $lp_id, api_get_user_id());
                if ($lp->progress_db == 100) {
                    continue;
                }

                $lp_url = api_get_path(WEB_CODE_PATH) . 'lp/lp_controller.php?cidReq='.$item['code'].'&id_session='.$session_id.'&lp_id='.$lp_id.'&action=view';

                $last_date = Tracking::get_last_connection_date_on_the_course(
                    api_get_user_id(),
                    $item,
                    $session_id,
                    false
                );

                if (empty($lp_item['modified_on'])) {
                    $lp_date = api_get_local_time($lp_item['created_on']);
                    $image = 'new.gif';
                    $label = get_lang('LearnpathAdded');
                } else {
                    $lp_date = api_get_local_time($lp_item['modified_on']);
                    $image = 'moderator_star.png';
                    $label = get_lang('LearnpathUpdated');
                }

                $icons = '';
                if (strtotime($last_date) < strtotime($lp_date)) {
                    $icons = Display::return_icon($image, get_lang('TitleNotification').': '.$label.' - '.$lp_date);
                }

                if (!empty($lp_item['publicated_on'])) {
                    $date = substr($lp_item['publicated_on'], 0, 10);
                } else {
                    $date = '-';
                }

                // Checking LP publicated and expired_on dates
                if (!empty($lp_item['publicated_on'])) {
                    if ($now < api_strtotime($lp_item['publicated_on'], 'UTC')) {
                        continue;
                    }
                }

                if (!empty($lp_item['expired_on'])) {
                    if ($now > api_strtotime($lp_item['expired_on'], 'UTC')) {
                        continue;
                    }
                }

                $temp[$count]['cell'] = array(
                    $date,
                    $item['title'],
                    Display::url($icons.' '.$lp_item['lp_name'], $lp_url, array('target'=>SESSION_LINK_TARGET))
                );
                $temp[$count]['course'] = strip_tags($item['title']);
                $temp[$count]['lp'] = $lp_item['lp_name'];
                $temp[$count]['date'] = $lp_item['publicated_on'];
                $count++;
            }
        }
        $temp = msort($temp, $sidx, $sord);

        $i =0;
        $response = new stdClass();
        foreach($temp as $key=>$row) {
            $row = $row['cell'];
            if (!empty($row)) {
                if ($key >= $start  && $key < ($start + $limit)) {
                    $response->rows[$i]['id']= $key;
                    $response->rows[$i]['cell']=array($row[0], $row[1], $row[2]);
                    $i++;
                }
            }
        }

        if($count > 0 && $limit > 0) {
            $total_pages = ceil($count/$limit);
        } else {
            $total_pages = 0;
        }
        $response->total    = $total_pages;
        if ($page > $total_pages) {
            $response->page= $total_pages;
        } else {
            $response->page = $page;
        }
        $response->records = $count;
        echo json_encode($response);
        break;
    case 'session_courses_lp_by_week':
        require_once '../global.inc.php';
        $now = time();

        $page  = intval($_REQUEST['page']);     //page
        $limit = intval($_REQUEST['rows']);     // quantity of rows
        $sidx  = isset($_REQUEST['sidx']) && !empty($_REQUEST['sidx']) ? $_REQUEST['sidx'] : 'course';
        $sidx = str_replace(array('week desc,', ' '), '', $sidx);

        $sord  = $_REQUEST['sord'];    //asc or desc
        if (!in_array($sord, array('asc','desc'))) {
            $sord = 'desc';
        }

        $session_id  = intval($_REQUEST['session_id']);
        $course_id   = intval($_REQUEST['course_id']);

        //Filter users that does not belong to the session
        if (!api_is_platform_admin()) {
            $new_session_list = UserManager::get_personal_session_course_list(api_get_user_id());
            $my_session_list  = array();
            foreach($new_session_list as $item) {
                if (!empty($item['id_session']))
                    $my_session_list[] = $item['id_session'];
            }
            if (!in_array($session_id, $my_session_list)) {
                break;
            }
        }

        $start = $limit*$page - $limit;
        $course_list = SessionManager::get_course_list_by_session_id($session_id);

        $count = 0;
        $temp = array();
        foreach ($course_list as $item) {
            if (isset($course_id) && !empty($course_id)) {
                if ($course_id != $item['id']) {
                    continue;
                }
            }

            $list = new LearnpathList(
                api_get_user_id(),
                $item['code'],
                $session_id,
                'lp.publicatedOn DESC'
            );
            $flat_list = $list->get_flat_list();
            $lps[$item['code']] = $flat_list;
            $item['title'] = Display::url(
                $item['title'],
                api_get_path(WEB_COURSE_PATH).$item['directory'].'/?id_session='.$session_id,
                array('target' => SESSION_LINK_TARGET)
            );

            foreach ($flat_list as $lp_id => $lp_item) {
                $temp[$count]['id']= $lp_id;
                $lp_url = api_get_path(WEB_CODE_PATH) . 'lp/lp_controller.php?cidReq='.$item['code'].'&id_session='.$session_id.'&lp_id='.$lp_id.'&action=view';

                $last_date = Tracking::get_last_connection_date_on_the_course(
                    api_get_user_id(),
                    $item,
                    $session_id,
                    false
                );

                if (empty($lp_item['modified_on'])) {
                    $lp_date = api_get_local_time($lp_item['created_on']);
                    $image = 'new.gif';
                    $label = get_lang('LearnpathAdded');
                } else {
                    $lp_date = api_get_local_time($lp_item['modified_on']);
                    $image = 'moderator_star.png';
                    $label = get_lang('LearnpathUpdated');
                }

                if (strtotime($last_date) < strtotime($lp_date)) {
                    $icons = Display::return_icon($image, get_lang('TitleNotification').': '.$label.' - '.$lp_date);
                }

                if (!empty($lp_item['publicated_on'])) {
                    $date = substr($lp_item['publicated_on'], 0, 10);
                } else {
                    $date = '-';
                }

                // Checking LP publicated and expired_on dates
                if (!empty($lp_item['publicated_on'])) {
                    $week_data = date('Y', api_strtotime($lp_item['publicated_on'], 'UTC')).' - '.get_week_from_day($lp_item['publicated_on']);
                    if ($now < api_strtotime($lp_item['publicated_on'], 'UTC')) {
                        continue;
                    }
                } else {
                    $week_data = '';
                }

                if (!empty($lp_item['expired_on'])) {
                    if ($now > api_strtotime($lp_item['expired_on'], 'UTC')) {
                        continue;
                    }
                }

                $temp[$count]['cell'] = array(
                    $week_data,
                    $date,
                    $item['title'],
                    Display::url($icons.' '.$lp_item['lp_name'], $lp_url, array('target' => SESSION_LINK_TARGET)),
                );
                $temp[$count]['course'] = strip_tags($item['title']);
                $temp[$count]['lp'] = $lp_item['lp_name'];
                $count++;
            }
        }

        if (!empty($sidx)) {
            $temp = msort($temp, $sidx, $sord);
        }

        $response = new stdClass();
        $i =0;
        foreach($temp as $key=>$row) {
            $row = $row['cell'];
            if (!empty($row)) {
                if ($key >= $start  && $key < ($start + $limit)) {
                    $response->rows[$i]['id']= $key;
                    $response->rows[$i]['cell']=array($row[0], $row[1], $row[2],$row[3]);
                    $i++;
                }
            }
        }

        if ($count > 0 && $limit > 0) {
            $total_pages = ceil($count/$limit);
        } else {
            $total_pages = 0;
        }
        $response->total = $total_pages;
        if ($page > $total_pages) {
            $response->page = $total_pages;
        } else {
            $response->page = $page;
        }
        $response->records = $count;
        echo json_encode($response);
        break;
    case 'session_courses_lp_by_course':
        require_once '../global.inc.php';
        $now = time();
        $page  = intval($_REQUEST['page']);     //page
        $limit = intval($_REQUEST['rows']);     // quantity of rows
        $sidx = isset($_REQUEST['sidx']) && !empty($_REQUEST['sidx']) ? $_REQUEST['sidx'] : 'id';
        $sidx = str_replace(array('course asc,', ' '), '', $sidx);

        $sord = $_REQUEST['sord'];    //asc or desc
        if (!in_array($sord, array('asc', 'desc'))) {
            $sord = 'desc';
        }
        $session_id  = intval($_REQUEST['session_id']);
        $course_id   = intval($_REQUEST['course_id']);

        //Filter users that does not belong to the session
        if (!api_is_platform_admin()) {
            $new_session_list = UserManager::get_personal_session_course_list(api_get_user_id());
            $my_session_list  = array();
            foreach($new_session_list as $item) {
                if (!empty($item['id_session']))
                    $my_session_list[] = $item['id_session'];
            }
            if (!in_array($session_id, $my_session_list)) {
                break;
            }
        }

        $start = $limit*$page - $limit;
        $course_list = SessionManager::get_course_list_by_session_id($session_id);
        $count = 0;

        foreach ($course_list as $item) {
            if (isset($course_id) && !empty($course_id)) {
                if ($course_id != $item['id']) {
                    continue;
                }
            }

            $list = new LearnpathList(api_get_user_id(),$item['code'],$session_id);
            $flat_list = $list->get_flat_list();
            $lps[$item['code']] = $flat_list;
            $item['title'] = Display::url(
                $item['title'],
                api_get_path(WEB_COURSE_PATH).$item['directory'].'/?id_session='.$session_id, array('target'=>SESSION_LINK_TARGET)
            );
            foreach($flat_list as $lp_id => $lp_item) {
                $temp[$count]['id']= $lp_id;
                $lp_url = api_get_path(WEB_CODE_PATH) . 'lp/lp_controller.php?cidReq='.$item['code'].'&id_session='.$session_id.'&lp_id='.$lp_id.'&action=view';
                $last_date = Tracking::get_last_connection_date_on_the_course(
                    api_get_user_id(),
                    $item,
                    $session_id,
                    false
                );
                if (empty($lp_item['modified_on'])) {
                    $lp_date = api_get_local_time($lp_item['created_on']);
                    $image = 'new.gif';
                    $label = get_lang('LearnpathAdded');
                } else {
                    $lp_date = api_get_local_time($lp_item['modified_on']);
                    $image = 'moderator_star.png';
                    $label = get_lang('LearnpathUpdated');
                }
                $icons = '';
                if (strtotime($last_date) < strtotime($lp_date)) {
                    $icons = Display::return_icon($image, get_lang('TitleNotification').': '.$label.' - '.$lp_date);
                }
                if (!empty($lp_item['publicated_on'])) {
                    $date = substr($lp_item['publicated_on'], 0, 10);
                } else {
                    $date = '-';
                }

                 //Checking LP publicated and expired_on dates
                if (!empty($lp_item['publicated_on'])) {
                    if ($now < api_strtotime($lp_item['publicated_on'], 'UTC')) {
                        continue;
                    }
                }
                if (!empty($lp_item['expired_on'])) {
                    if ($now > api_strtotime($lp_item['expired_on'], 'UTC')) {
                        continue;
                    }
                }
                $temp[$count]['cell'] = array(
                    $date,
                    $item['title'],
                    Display::url($icons.' '.$lp_item['lp_name'], $lp_url, array('target'=>SESSION_LINK_TARGET))
                );
                $temp[$count]['course'] = strip_tags($item['title']);
                $temp[$count]['lp'] = $lp_item['lp_name'];
                $temp[$count]['date'] = $lp_item['publicated_on'];

                $count++;
            }
        }

        $temp = msort($temp, $sidx, $sord);

        $response = new stdClass();
        $i =0;
        foreach ($temp as $key => $row) {
            $row = $row['cell'];
            if (!empty($row)) {
                if ($key >= $start  && $key < ($start + $limit)) {
                    $response->rows[$i]['id']= $key;
                    $response->rows[$i]['cell']=array($row[0], $row[1], $row[2]);
                    $i++;
                }
            }
        }

        if ($count > 0 && $limit > 0) {
            $total_pages = ceil($count / $limit);
        } else {
            $total_pages = 0;
        }
        $response->total = $total_pages;
        if ($page > $total_pages) {
            $response->page = $total_pages;
        } else {
            $response->page = $page;
        }
        $response->records = $count;

        echo json_encode($response);
        break;
    default:
        echo '';
}
exit;
