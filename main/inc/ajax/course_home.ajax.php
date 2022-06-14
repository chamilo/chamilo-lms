<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CTool;
use ChamiloSession as Session;

// @todo refactor this script, create a class that manage the jqgrid requests
/**
 * Responses to AJAX calls.
 */
$action = $_GET['a'];

switch ($action) {
    case 'set_visibility':
        require_once __DIR__.'/../global.inc.php';
        $course_id = api_get_course_int_id();
        $sessionId = api_get_session_id();
        // Allow tool visibility in sessions.
        $allowEditionInSession = api_get_configuration_value('allow_edit_tool_visibility_in_session');
        $em = Database::getManager();
        $repository = $em->getRepository('ChamiloCourseBundle:CTool');
        if (api_is_allowed_to_edit(null, true)) {
            $criteria = [
                'cId' => $course_id,
                //'sessionId' => 0,
                'iid' => (int) $_GET['id'],
            ];

            /** @var CTool $tool */
            $tool = $repository->findOneBy($criteria);
            $visibility = 0;
            if ($allowEditionInSession && !empty($sessionId)) {
                $newLink = str_replace('id_session=0', 'id_session='.$sessionId, $tool->getLink());
                $criteria = [
                    'cId' => $course_id,
                    'sessionId' => $sessionId,
                    //'iid' => (int) $_GET['id'],
                    'link' => $newLink,
                ];

                /** @var CTool $tool */
                $toolInSession = $repository->findOneBy($criteria);
                if ($toolInSession) {
                    // Use the session
                    $tool = $toolInSession;
                    $visibility = $toolInSession->getVisibility();
                } else {
                    // Creates new row in c_tool
                    $toolInSession = clone $tool;
                    $toolInSession->setLink($newLink);
                    $toolInSession->setIid(0);
                    $toolInSession->setId(0);
                    $toolInSession->setVisibility(0);
                    $toolInSession->setSessionId($sessionId);
                    $em->persist($toolInSession);
                    $em->flush();
                    // Update id with iid
                    $toolInSession->setId($toolInSession->getIid());
                    $em->persist($toolInSession);
                    $em->flush();
                    // $tool will be updated later
                    $tool = $toolInSession;
                }
            } else {
                $visibility = $tool->getVisibility();
            }

            $toolImage = $tool->getImage();
            $customIcon = $tool->getCustomIcon();

            if (api_get_setting('homepage_view') !== 'activity_big') {
                $toolImage = Display::return_icon(
                    $toolImage,
                    null,
                    null,
                    null,
                    null,
                    true
                );
                $inactiveImage = str_replace('.gif', '_na.gif', $toolImage);
            } else {
                // Display::return_icon() also checks in the app/Resources/public/css/themes/{theme}/icons folder
                $toolImage = (substr($toolImage, 0, strpos($toolImage, '.'))).'.png';
                $toolImage = Display::return_icon(
                    $toolImage,
                    get_lang(ucfirst($tool->getName())),
                    null,
                    ICON_SIZE_BIG,
                    null,
                    true
                );
                $inactiveImage = str_replace('.png', '_na.png', $toolImage);
            }

            if (isset($customIcon) && !empty($customIcon)) {
                $toolImage = CourseHome::getCustomWebIconPath().$customIcon;
                $inactiveImage = CourseHome::getCustomWebIconPath().CourseHome::getDisableIcon($customIcon);
            }

            $requested_image = $visibility == 0 ? $toolImage : $inactiveImage;
            $requested_class = $visibility == 0 ? '' : 'text-muted';
            $requested_message = $visibility == 0 ? 'is_active' : 'is_inactive';
            $requested_view = $visibility == 0 ? 'visible.png' : 'invisible.png';
            $requestedVisible = $visibility == 0 ? 1 : 0;
            $requested_view = $visibility == 0 ? 'visible.png' : 'invisible.png';
            $requestedVisible = $visibility == 0 ? 1 : 0;
            $requested_fa_class = $visibility == 0 ? 'fa fa-eye '.$requested_class : 'fa fa-eye-slash '.$requested_class;

            // HIDE AND REACTIVATE TOOL
            if ($_GET['id'] == strval(intval($_GET['id']))) {
                $tool->setVisibility($requestedVisible);
                $em->persist($tool);
                $em->flush();

                // Also hide the tool in all sessions
                if ($allowEditionInSession && empty($sessionId)) {
                    $criteria = [
                        'cId' => $course_id,
                        'name' => $tool->getName(),
                    ];

                    /** @var CTool $toolItem */
                    $tools = $repository->findBy($criteria);
                    foreach ($tools as $toolItem) {
                        $toolSessionId = $toolItem->getSessionId();
                        if (!empty($toolSessionId)) {
                            $toolItem->setVisibility($requestedVisible);
                            $em->persist($toolItem);
                        }
                    }
                    $em->flush();
                }
            }

            $response = [
                'image' => $requested_image,
                'tclass' => $requested_class,
                'message' => $requested_message,
                'view' => $requested_view,
                'fclass' => $requested_fa_class,
            ];
            echo json_encode($response);
        }
        break;
    case 'set_visibility_for_all':
        require_once __DIR__.'/../global.inc.php';
        $course_id = api_get_course_int_id();
        $sessionId = api_get_session_id();
        $allowEditionInSession = api_get_configuration_value('allow_edit_tool_visibility_in_session');
        $response = [];
        $tools_ids = json_decode($_GET['tools_ids']);
        $em = Database::getManager();
        $repository = $em->getRepository('ChamiloCourseBundle:CTool');
        // Allow tool visibility in sessions.
        if (api_is_allowed_to_edit(null, true)) {
            if (is_array($tools_ids) && count($tools_ids) != 0) {
                $total_tools = count($tools_ids);
                for ($i = 0; $i < $total_tools; $i++) {
                    $tool_id = (int) $tools_ids[$i];

                    $criteria = [
                        'cId' => $course_id,
                        'sessionId' => 0,
                        'iid' => $tool_id,
                    ];
                    /** @var CTool $tool */
                    $tool = $repository->findOneBy($criteria);
                    $visibility = $tool->getVisibility();

                    if ($allowEditionInSession && !empty($sessionId)) {
                        $criteria = [
                            'cId' => $course_id,
                            'sessionId' => $sessionId,
                            'name' => $tool->getName(),
                        ];

                        /** @var CTool $tool */
                        $toolInSession = $repository->findOneBy($criteria);
                        if ($toolInSession) {
                            // Use the session
                            $tool = $toolInSession;
                            $visibility = $toolInSession->getVisibility();
                        } else {
                            // Creates new row in c_tool
                            $toolInSession = clone $tool;
                            $toolInSession->setIid(0);
                            $toolInSession->setId(0);
                            $toolInSession->setVisibility(0);
                            $toolInSession->setSessionId($session_id);
                            $em->persist($toolInSession);
                            $em->flush();
                            // Update id with iid
                            $toolInSession->setId($toolInSession->getIid());
                            $em->persist($toolInSession);
                            $em->flush();
                            // $tool will be updated later
                            $tool = $toolInSession;
                        }
                    }

                    $toolImage = $tool->getImage();
                    $customIcon = $tool->getCustomIcon();

                    if (api_get_setting('homepage_view') != 'activity_big') {
                        $toolImage = Display::return_icon(
                            $toolImage,
                            null,
                            null,
                            null,
                            null,
                            true
                        );
                        $inactiveImage = str_replace('.gif', '_na.gif', $toolImage);
                    } else {
                        // Display::return_icon() also checks in the app/Resources/public/css/themes/{theme}/icons folder
                        $toolImage = (substr($toolImage, 0, strpos($toolImage, '.'))).'.png';
                        $toolImage = Display::return_icon(
                            $toolImage,
                            get_lang(ucfirst($tool->getName())),
                            null,
                            ICON_SIZE_BIG,
                            null,
                            true
                        );
                        $inactiveImage = str_replace('.png', '_na.png', $toolImage);
                    }

                    if (isset($customIcon) && !empty($customIcon)) {
                        $toolImage = CourseHome::getCustomWebIconPath().$customIcon;
                        $inactiveImage = CourseHome::getCustomWebIconPath().CourseHome::getDisableIcon($customIcon);
                    }

                    $requested_image = $visibility == 0 ? $toolImage : $inactiveImage;
                    $requested_class = $visibility == 0 ? '' : 'text-muted';
                    $requested_message = $visibility == 0 ? 'is_active' : 'is_inactive';
                    $requested_view = $visibility == 0 ? 'visible.png' : 'invisible.png';
                    $requestedVisible = $visibility == 0 ? 1 : 0;
                    $requested_view = $visibility == 0 ? 'visible.png' : 'invisible.png';
                    $requested_fa_class = $visibility == 0 ? 'fa fa-eye '.$requested_class : 'fa fa-eye-slash '.$requested_class;
                    $requestedVisible = $visibility == 0 ? 1 : 0;

                    // HIDE AND REACTIVATE TOOL
                    if ($tool_id == strval(intval($tool_id))) {
                        $tool->setVisibility($requestedVisible);
                        $em->persist($tool);
                        $em->flush();

                        // Also hide the tool in all sessions
                        if ($allowEditionInSession && empty($sessionId)) {
                            $criteria = [
                                'cId' => $course_id,
                                'name' => $tool->getName(),
                            ];

                            /** @var CTool $toolItem */
                            $tools = $repository->findBy($criteria);
                            foreach ($tools as $toolItem) {
                                $toolSessionId = $toolItem->getSessionId();
                                if (!empty($toolSessionId)) {
                                    $toolItem->setVisibility($requestedVisible);
                                    $em->persist($toolItem);
                                }
                            }
                            $em->flush();
                        }
                    }
                    $response[] = [
                        'image' => $requested_image,
                        'tclass' => $requested_class,
                        'message' => $requested_message,
                        'view' => $requested_view,
                        'fclass' => $requested_fa_class,
                        'id' => $tool_id,
                    ];
                }
            }
        }
        echo json_encode($response);
        break;
    case 'show_course_information':
        require_once __DIR__.'/../global.inc.php';

        // Get the name of the database course.
        $course_info = api_get_course_info($_GET['code']);
        $content = get_lang('NoDescription');
        if (!empty($course_info)) {
            if (api_get_setting('course_catalog_hide_private') === 'true' &&
                $course_info['visibility'] == COURSE_VISIBILITY_REGISTERED
            ) {
                echo get_lang('PrivateAccess');
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
        foreach ($course_list as $item) {
            $courseInfo = api_get_course_info($item['code']);
            $list = new LearnpathList(api_get_user_id(), $courseInfo, $session_id);
            $flat_list = $list->get_flat_list();
            $lps[$item['code']] = $flat_list;
            $course_url = api_get_path(WEB_COURSE_PATH).$item['directory'].'/?id_session='.$session_id;
            $item['title'] = Display::url($item['title'], $course_url, ['target' => SESSION_LINK_TARGET]);

            foreach ($flat_list as $lp_id => $lp_item) {
                $isAllowedToEdit = api_is_allowed_to_edit(null, true);

                if (!$isAllowedToEdit && 0 == $lp_item['lp_visibility']) {
                    continue;
                }

                $temp[$count]['id'] = $lp_id;

                $lp = new learnpath($item['code'], $lp_id, api_get_user_id());
                if ($lp->progress_db == 100) {
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

                $temp[$count]['cell'] = [
                    $date,
                    $item['title'],
                    Display::url($icons.' '.$lp_item['lp_name'], $lp_url, ['target' => SESSION_LINK_TARGET]),
                ];
                $temp[$count]['course'] = strip_tags($item['title']);
                $temp[$count]['lp'] = $lp_item['lp_name'];
                $temp[$count]['date'] = $lp_item['publicated_on'];
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
                'lp.publicatedOn DESC'
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
                $isAllowedToEdit = api_is_allowed_to_edit(null, true);

                if (!$isAllowedToEdit && 0 == $lp_item['lp_visibility']) {
                    continue;
                }

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
                $temp[$count]['cell'] = [
                    $date,
                    $item['title'],
                    Display::url($icons.' '.$lp_item['lp_name'], $lp_url, ['target' => SESSION_LINK_TARGET]),
                ];
                $temp[$count]['course'] = strip_tags($item['title']);
                $temp[$count]['lp'] = $lp_item['lp_name'];
                $temp[$count]['date'] = $lp_item['publicated_on'];
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
    case 'get_notification':
        $courseId = isset($_REQUEST['course_id']) ? (int) $_REQUEST['course_id'] : 0;
        $sessionId = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : 0;
        $status = isset($_REQUEST['status']) ? (int) $_REQUEST['status'] : 0;
        if (empty($courseId)) {
            break;
        }
        require_once __DIR__.'/../global.inc.php';

        $courseInfo = api_get_course_info_by_id($courseId);
        $courseInfo['id_session'] = $sessionId;
        $courseInfo['status'] = $status;
        $id = 'notification_'.$courseId.'_'.$sessionId.'_'.$status;

        $notificationId = Session::read($id);
        if ($notificationId) {
            echo Display::show_notification($courseInfo, false);
            Session::erase($notificationId);
        }

        break;
    default:
        echo '';
}
exit;
