<?php

/* For licensing terms, see /license.txt */

// @todo refactor this script, create a class that manage the jqgrid requests
/**
 * Responses to AJAX calls.
 */
$action = $_GET['a'];

switch ($action) {
    case 'show_course_information':
        require_once __DIR__.'/../global.inc.php';

        // Get the name of the database course.
        $course_info = api_get_course_info($_GET['code']);
        $content = get_lang('No description');
        if (!empty($course_info)) {
            if ('true' === api_get_setting('course_catalog_hide_private') &&
                COURSE_VISIBILITY_REGISTERED == $course_info['visibility']
            ) {
                echo get_lang('Private access');
                break;
            }
            $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
            $sql = "SELECT * FROM $table
                    WHERE c_id = ".$course_info['real_id']." AND session_id = 0
                    ORDER BY id";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                while ($description = Database::fetch_object($result)) {
                    $descriptions[$description->id] = $description;
                }
                // Function that displays the details of the course description in html.
                $content = CourseManager::get_details_course_description_html(
                    $descriptions,
                    api_get_system_encoding(),
                    false
                );
            }
        }
        echo $content;
        break;
    case 'session_courses_lp_default':
        /**
         * @todo this functions need to belong to a class or a special
         * wrapper to process the AJAX petitions from the jqgrid
         */
        require_once __DIR__.'/../global.inc.php';
        $now = time();
        $page = (int) $_REQUEST['page']; //page
        $limit = (int) $_REQUEST['rows']; // quantity of rows
        //index to filter
        $sidx = isset($_REQUEST['sidx']) && !empty($_REQUEST['sidx']) ? $_REQUEST['sidx'] : 'id';
        $sord = $_REQUEST['sord']; //asc or desc
        if (!in_array($sord, ['asc', 'desc'])) {
            $sord = 'desc';
        }
        $session_id = (int) $_REQUEST['session_id'];
        $course_id = (int) $_REQUEST['course_id'];

        //Filter users that does not belong to the session
        if (!api_is_platform_admin()) {
            $new_session_list = UserManager::get_personal_session_course_list(api_get_user_id());
            $my_session_list = [];
            foreach ($new_session_list as $item) {
                if (!empty($item['session_id'])) {
                    $my_session_list[] = $item['session_id'];
                }
            }
            if (!in_array($session_id, $my_session_list)) {
                break;
            }
        }

        $start = $limit * $page - $limit;
        $course_list = SessionManager::get_course_list_by_session_id($session_id);
        $count = 0;
        $temp = [];
        $userId = api_get_user_id();
        foreach ($course_list as $item) {
            $courseInfo = api_get_course_info($item['code']);
            $list = new LearnpathList($userId, $courseInfo, $session_id);
            $flat_list = $list->get_flat_list();
            $lps[$item['code']] = $flat_list;
            $course_url = api_get_path(WEB_COURSE_PATH).$item['directory'].'/?id_session='.$session_id;
            $item['title'] = Display::url($item['title'], $course_url, ['target' => SESSION_LINK_TARGET]);

            foreach ($flat_list as $lp_id => $lp_item) {
                $temp[$count]['id'] = $lp_id;

                $lp = new learnpath(api_get_lp_entity($lp_id), $courseInfo, $userId);
                if (100 == $lp->progress_db) {
                    continue;
                }

                $lp_url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?cidReq='.$item['code'].'&id_session='.$session_id.'&lp_id='.$lp_id.'&action=view';

                $last_date = Tracking::get_last_connection_date_on_the_course(
                    api_get_user_id(),
                    $item,
                    $session_id,
                    false
                );

                if (empty($lp_item['modified_on'])) {
                    $lp_date = api_get_local_time($lp_item['created_on']);
                    $image = 'new.gif';
                    $label = get_lang('Course added');
                } else {
                    $lp_date = api_get_local_time($lp_item['modified_on']);
                    $image = 'moderator_star.png';
                    $label = get_lang('Learning path updated');
                }

                $icons = '';
                if (strtotime($last_date) < strtotime($lp_date)) {
                    $icons = Display::return_icon($image, get_lang('Since your latest visit').': '.$label.' - '.$lp_date);
                }

                if (!empty($lp_item['published_on'])) {
                    $date = substr($lp_item['published_on'], 0, 10);
                } else {
                    $date = '-';
                }

                // Checking LP publicated and expired_on dates
                if (!empty($lp_item['published_on'])) {
                    if ($now < api_strtotime($lp_item['published_on'], 'UTC')) {
                        continue;
                    }
                }

                if (!empty($lp_item['expired_on'])) {
                    if ($now > api_strtotime($lp_item['expired_on'], 'UTC')) {
                        continue;
                    }
                }

                $temp[$count]['cell'] = [
                    $date,
                    $item['title'],
                    Display::url($icons.' '.$lp_item['lp_name'], $lp_url, ['target' => SESSION_LINK_TARGET]),
                ];
                $temp[$count]['course'] = strip_tags($item['title']);
                $temp[$count]['lp'] = $lp_item['lp_name'];
                $temp[$count]['date'] = $lp_item['published_on'];
                $count++;
            }
        }
        $temp = msort($temp, $sidx, $sord);

        $i = 0;
        $response = new stdClass();
        foreach ($temp as $key => $row) {
            $row = $row['cell'];
            if (!empty($row)) {
                if ($key >= $start && $key < ($start + $limit)) {
                    $response->rows[$i]['id'] = $key;
                    $response->rows[$i]['cell'] = [$row[0], $row[1], $row[2]];
                    $i++;
                }
            }
        }
        $total_pages = 0;
        if ($count > 0 && $limit > 0) {
            $total_pages = ceil($count / $limit);
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
    case 'session_courses_lp_by_week':
        require_once __DIR__.'/../global.inc.php';
        $now = time();
        $page = (int) $_REQUEST['page']; //page
        $limit = (int) $_REQUEST['rows']; // quantity of rows
        $sidx = isset($_REQUEST['sidx']) && !empty($_REQUEST['sidx']) ? $_REQUEST['sidx'] : 'course';
        $sidx = str_replace(['week desc,', ' '], '', $sidx);
        $sord = $_REQUEST['sord']; //asc or desc
        if (!in_array($sord, ['asc', 'desc'])) {
            $sord = 'desc';
        }

        $session_id = (int) $_REQUEST['session_id'];
        $course_id = (int) $_REQUEST['course_id'];

        //Filter users that does not belong to the session
        if (!api_is_platform_admin()) {
            $new_session_list = UserManager::get_personal_session_course_list(api_get_user_id());
            $my_session_list = [];
            foreach ($new_session_list as $item) {
                if (!empty($item['session_id'])) {
                    $my_session_list[] = $item['session_id'];
                }
            }
            if (!in_array($session_id, $my_session_list)) {
                break;
            }
        }

        $start = $limit * $page - $limit;
        $course_list = SessionManager::get_course_list_by_session_id($session_id);

        $count = 0;
        $temp = [];
        foreach ($course_list as $item) {
            if (isset($course_id) && !empty($course_id)) {
                if ($course_id != $item['id']) {
                    continue;
                }
            }

            $list = new LearnpathList(
                api_get_user_id(),
                api_get_course_info($item['code']),
                $session_id,
                'lp.publishedOn DESC'
            );
            $flat_list = $list->get_flat_list();
            $lps[$item['code']] = $flat_list;
            $item['title'] = Display::url(
                $item['title'],
                api_get_path(WEB_COURSE_PATH).$item['directory'].'/?id_session='.$session_id,
                ['target' => SESSION_LINK_TARGET]
            );

            foreach ($flat_list as $lp_id => $lp_item) {
                $temp[$count]['id'] = $lp_id;
                $lp_url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?cidReq='.$item['code'].'&id_session='.$session_id.'&lp_id='.$lp_id.'&action=view';

                $last_date = Tracking::get_last_connection_date_on_the_course(
                    api_get_user_id(),
                    $item,
                    $session_id,
                    false
                );

                if (empty($lp_item['modified_on'])) {
                    $lp_date = api_get_local_time($lp_item['created_on']);
                    $image = 'new.gif';
                    $label = get_lang('Course added');
                } else {
                    $lp_date = api_get_local_time($lp_item['modified_on']);
                    $image = 'moderator_star.png';
                    $label = get_lang('Learning path updated');
                }

                if (strtotime($last_date) < strtotime($lp_date)) {
                    $icons = Display::return_icon($image, get_lang('Since your latest visit').': '.$label.' - '.$lp_date);
                }

                if (!empty($lp_item['published_on'])) {
                    $date = substr($lp_item['published_on'], 0, 10);
                } else {
                    $date = '-';
                }

                // Checking LP publicated and expired_on dates
                if (!empty($lp_item['published_on'])) {
                    $week_data = date('Y', api_strtotime($lp_item['published_on'], 'UTC')).' - '.get_week_from_day($lp_item['published_on']);
                    if ($now < api_strtotime($lp_item['published_on'], 'UTC')) {
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

                $temp[$count]['cell'] = [
                    $week_data,
                    $date,
                    $item['title'],
                    Display::url($icons.' '.$lp_item['lp_name'], $lp_url, ['target' => SESSION_LINK_TARGET]),
                ];
                $temp[$count]['course'] = strip_tags($item['title']);
                $temp[$count]['lp'] = $lp_item['lp_name'];
                $count++;
            }
        }

        if (!empty($sidx)) {
            $temp = msort($temp, $sidx, $sord);
        }

        $response = new stdClass();
        $i = 0;
        foreach ($temp as $key => $row) {
            $row = $row['cell'];
            if (!empty($row)) {
                if ($key >= $start && $key < ($start + $limit)) {
                    $response->rows[$i]['id'] = $key;
                    $response->rows[$i]['cell'] = [$row[0], $row[1], $row[2], $row[3]];
                    $i++;
                }
            }
        }
        $total_pages = 0;
        if ($count > 0 && $limit > 0) {
            $total_pages = ceil($count / $limit);
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
        require_once __DIR__.'/../global.inc.php';
        $now = time();
        $page = (int) $_REQUEST['page']; //page
        $limit = (int) $_REQUEST['rows']; // quantity of rows
        $sidx = isset($_REQUEST['sidx']) && !empty($_REQUEST['sidx']) ? $_REQUEST['sidx'] : 'id';
        $sidx = str_replace(['course asc,', ' '], '', $sidx);

        $sord = $_REQUEST['sord']; //asc or desc
        if (!in_array($sord, ['asc', 'desc'])) {
            $sord = 'desc';
        }
        $session_id = (int) $_REQUEST['session_id'];
        $course_id = (int) $_REQUEST['course_id'];

        //Filter users that does not belong to the session
        if (!api_is_platform_admin()) {
            $new_session_list = UserManager::get_personal_session_course_list(api_get_user_id());
            $my_session_list = [];
            foreach ($new_session_list as $item) {
                if (!empty($item['session_id'])) {
                    $my_session_list[] = $item['session_id'];
                }
            }
            if (!in_array($session_id, $my_session_list)) {
                break;
            }
        }

        $start = $limit * $page - $limit;
        $course_list = SessionManager::get_course_list_by_session_id($session_id);
        $count = 0;
        $temp = [];

        foreach ($course_list as $item) {
            if (isset($course_id) && !empty($course_id)) {
                if ($course_id != $item['id']) {
                    continue;
                }
            }

            $list = new LearnpathList(
                api_get_user_id(),
                api_get_course_info($item['code']),
                $session_id
            );
            $flat_list = $list->get_flat_list();
            $lps[$item['code']] = $flat_list;
            $item['title'] = Display::url(
                $item['title'],
                api_get_path(WEB_COURSE_PATH).$item['directory'].'/?id_session='.$session_id,
                ['target' => SESSION_LINK_TARGET]
            );
            foreach ($flat_list as $lp_id => $lp_item) {
                $temp[$count]['id'] = $lp_id;
                $lp_url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?cidReq='.$item['code'].'&id_session='.$session_id.'&lp_id='.$lp_id.'&action=view';
                $last_date = Tracking::get_last_connection_date_on_the_course(
                    api_get_user_id(),
                    $item,
                    $session_id,
                    false
                );
                if (empty($lp_item['modified_on'])) {
                    $lp_date = api_get_local_time($lp_item['created_on']);
                    $image = 'new.gif';
                    $label = get_lang('Course added');
                } else {
                    $lp_date = api_get_local_time($lp_item['modified_on']);
                    $image = 'moderator_star.png';
                    $label = get_lang('Learning path updated');
                }
                $icons = '';
                if (strtotime($last_date) < strtotime($lp_date)) {
                    $icons = Display::return_icon($image, get_lang('Since your latest visit').': '.$label.' - '.$lp_date);
                }
                if (!empty($lp_item['published_on'])) {
                    $date = substr($lp_item['published_on'], 0, 10);
                } else {
                    $date = '-';
                }

                // Checking LP publicated and expired_on dates
                if (!empty($lp_item['published_on'])) {
                    if ($now < api_strtotime($lp_item['published_on'], 'UTC')) {
                        continue;
                    }
                }
                if (!empty($lp_item['expired_on'])) {
                    if ($now > api_strtotime($lp_item['expired_on'], 'UTC')) {
                        continue;
                    }
                }
                $temp[$count]['cell'] = [
                    $date,
                    $item['title'],
                    Display::url($icons.' '.$lp_item['lp_name'], $lp_url, ['target' => SESSION_LINK_TARGET]),
                ];
                $temp[$count]['course'] = strip_tags($item['title']);
                $temp[$count]['lp'] = $lp_item['lp_name'];
                $temp[$count]['date'] = $lp_item['published_on'];
                $count++;
            }
        }

        $temp = msort($temp, $sidx, $sord);
        $response = new stdClass();
        $i = 0;
        foreach ($temp as $key => $row) {
            $row = $row['cell'];
            if (!empty($row)) {
                if ($key >= $start && $key < ($start + $limit)) {
                    $response->rows[$i]['id'] = $key;
                    $response->rows[$i]['cell'] = [$row[0], $row[1], $row[2]];
                    $i++;
                }
            }
        }
        $total_pages = 0;
        if ($count > 0 && $limit > 0) {
            $total_pages = ceil($count / $limit);
        }
        $response->total = $total_pages;
        $response->page = $page;
        if ($page > $total_pages) {
            $response->page = $total_pages;
        }
        $response->records = $count;

        echo json_encode($response);
        break;
    default:
        echo '';
}
exit;
