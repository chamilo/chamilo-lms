<?php

/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

$action = $_REQUEST['a'];

switch ($action) {
    case 'get_user_sessions':
        if (api_is_platform_admin() || api_is_session_admin()) {
            $user_id = (int) $_POST['user_id'];
            $list_sessions = SessionManager::get_sessions_by_user($user_id, true);
            if (!empty($list_sessions)) {
                foreach ($list_sessions as $session_item) {
                    echo $session_item['session_name'].'<br />';
                }
            } else {
                echo get_lang('NoSessionsForThisUser');
            }
            unset($list_sessions);
        }
        break;
    case 'order':
        api_protect_admin_script();
        $allowOrder = api_get_configuration_value('session_list_order');
        if ($allowOrder) {
            $order = isset($_GET['order']) ? $_GET['order'] : [];
            $order = json_decode($order);
            if (!empty($order)) {
                $table = Database::get_main_table(TABLE_MAIN_SESSION);
                foreach ($order as $data) {
                    if (isset($data->order) && isset($data->id)) {
                        $orderId = (int) $data->order;
                        $sessionId = (int) $data->id;
                        $sql = "UPDATE $table SET position = $orderId WHERE id = $sessionId ";
                        Database::query($sql);
                    }
                }
            }
        }
        break;
    case 'search_session':
        if (api_is_platform_admin()) {
            $sessions = SessionManager::get_sessions_list(
                [
                    's.name' => [
                        'operator' => 'LIKE',
                        'value' => "%".$_REQUEST['q']."%",
                    ],
                ]
            );

            $list = [
                'items' => [],
            ];

            if (empty($sessions)) {
                echo json_encode([]);
                break;
            }

            foreach ($sessions as $session) {
                $list['items'][] = [
                    'id' => $session['id'],
                    'text' => $session['name'],
                ];
            }

            echo json_encode($list);
        }
        break;
    case 'search_session_all':
        if (api_is_platform_admin()) {
            $results = SessionManager::get_sessions_list(
                [
                    's.name' => ['operator' => 'like', 'value' => "%".$_REQUEST['q']."%"],
                    'c.id' => ['operator' => '=', 'value' => $_REQUEST['course_id']],
                ]
            );
            $results2 = [];
            if (!empty($results)) {
                foreach ($results as $item) {
                    $item2 = [];
                    foreach ($item as $id => $internal) {
                        if ($id == 'id') {
                            $item2[$id] = $internal;
                        }
                        if ($id == 'name') {
                            $item2['text'] = $internal;
                        }
                    }
                    $results2[] = $item2;
                }
                $results2[] = ['T', 'text' => 'TODOS', 'id' => 'T'];
                echo json_encode($results2);
            } else {
                echo json_encode([['T', 'text' => 'TODOS', 'id' => 'T']]);
            }
        }
        break;
    case 'search_session_by_course':
        if (api_is_platform_admin()) {
            $results = SessionManager::get_sessions_list(
                [
                    's.name' => ['operator' => 'like', 'value' => "%".$_REQUEST['q']."%"],
                    'c.id' => ['operator' => '=', 'value' => $_REQUEST['course_id']],
                ]
            );
            $json = [
                'items' => [
                    ['id' => 'T', 'text' => get_lang('All')],
                ],
            ];
            if (!empty($results)) {
                foreach ($results as $item) {
                    $item2 = [];
                    foreach ($item as $id => $internal) {
                        if ($id == 'id') {
                            $item2[$id] = $internal;
                        }
                        if ($id == 'name') {
                            $item2['text'] = $internal;
                        }
                    }
                    $json['items'][] = $item2;
                }
            }

            echo json_encode($json);
        }
        break;
    case 'session_info':
        $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : '';
        $sessionInfo = api_get_session_info($sessionId);

        $extraFieldValues = new ExtraFieldValue('session');
        $extraField = new ExtraField('session');
        $values = $extraFieldValues->getAllValuesByItem($sessionId);
        $load = isset($_GET['load_empty_extra_fields']) ? true : false;

        if ($load) {
            $allExtraFields = $extraField->get_all();
            $valueList = array_column($values, 'id');
            foreach ($allExtraFields as $extra) {
                if (!in_array($extra['id'], $valueList)) {
                    $values[] = [
                        'id' => $extra['id'],
                        'variable' => $extra['variable'],
                        'value' => '',
                        'field_type' => $extra['field_type'],
                    ];
                }
            }
        }

        $sessionInfo['extra_fields'] = $values;

        if (!empty($sessionInfo)) {
            echo json_encode($sessionInfo);
        }
        break;
    case 'get_description':
        if (isset($_GET['session'])) {
            $sessionInfo = api_get_session_info($_GET['session']);
            echo '<h2>'.$sessionInfo['name'].'</h2>';
            echo '<div class="home-course-intro"><div class="page-course"><div class="page-course-intro">';
            echo $sessionInfo['show_description'] == 1 ? $sessionInfo['description'] : get_lang('None');
            echo '</div></div></div>';
        }
        break;
    case 'search_general_coach':
        SessionManager::protectSession(null, false);
        api_protect_limit_for_session_admin();

        if (api_is_anonymous()) {
            echo '';
            break;
        }

        $list = [
            'items' => [],
        ];

        $usersRepo = UserManager::getRepository();
        $users = $usersRepo->searchUsersByStatus($_GET['q'], COURSEMANAGER, api_get_current_access_url_id());
        /** @var User $user */
        foreach ($users as $user) {
            $list['items'][] = [
                'id' => $user->getId(),
                'text' => UserManager::formatUserFullName($user),
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($list);
        break;
    case 'get_courses_inside_session':
        $userId = api_get_user_id();
        $isAdmin = api_is_platform_admin();
        if ($isAdmin) {
            $sessionList = SessionManager::get_sessions_list();
            $sessionIdList = array_column($sessionList, 'id');
        } else {
            $sessionList = SessionManager::get_sessions_by_user($userId);
            $sessionIdList = array_column($sessionList, 'session_id');
        }

        $sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
        $courseList = [];
        if (empty($sessionId)) {
            $preCourseList = CourseManager::get_courses_list_by_user_id(
                $userId,
                false,
                true
            );
            $courseList = array_column($preCourseList, 'real_id');
        } else {
            if ($isAdmin) {
                $courseList = SessionManager::getCoursesInSession($sessionId);
            } else {
                if (in_array($sessionId, $sessionIdList)) {
                    $courseList = SessionManager::getCoursesInSession($sessionId);
                }
            }
        }

        $courseListToSelect = [];
        if (!empty($courseList)) {
            // Course List
            foreach ($courseList as $courseId) {
                $courseInfo = api_get_course_info_by_id($courseId);
                $courseListToSelect[] = [
                    'id' => $courseInfo['real_id'],
                    'name' => $courseInfo['title'],
                ];
            }
        }

        echo json_encode($courseListToSelect);
        break;
    case 'get_basic_course_documents_list':
    case 'get_basic_course_documents_form':
        $courseId = isset($_GET['course']) ? (int) $_GET['course'] : 0;
        $sessionId = isset($_GET['session']) ? (int) $_GET['session'] : 0;
        $currentUserId = api_get_user_id();

        $em = Database::getManager();

        $course = $em->find('ChamiloCoreBundle:Course', $courseId);
        $session = $em->find('ChamiloCoreBundle:Session', $sessionId);

        if (!$course || !$session) {
            break;
        }

        if (!api_is_platform_admin(true) || $session->getSessionAdminId() != $currentUserId) {
            break;
        }

        $folderName = '/basic-course-documents__'.$session->getId().'__0';

        if ('get_basic_course_documents_list' === $action) {
            $courseInfo = api_get_course_info_by_id($course->getId());
            $exists = DocumentManager::folderExists('/basic-course-documents', $courseInfo, $session->getId(), 0);
            if (!$exists) {
                $courseDir = $courseInfo['directory'].'/document';
                $baseWorkDir = api_get_path(SYS_COURSE_PATH).$courseDir;

                $newFolderData = create_unexisting_directory(
                    $courseInfo,
                    $currentUserId,
                    $session->getId(),
                    0,
                    0,
                    $baseWorkDir,
                    '/basic-course-documents',
                    get_lang('BasicCourseDocuments'),
                    1
                );

                $id = (int) $newFolderData['iid'];
            } else {
                $id = DocumentManager::get_document_id($courseInfo, $folderName, $session->getId());
            }
            $http_www = api_get_path(WEB_COURSE_PATH).$courseInfo['directory'].'/document';

            $documentAndFolders = DocumentManager::getAllDocumentData(
                $courseInfo,
                $folderName,
                0,
                0,
                false,
                false,
                $session->getId()
            );

            $documentAndFolders = array_filter(
                $documentAndFolders,
                function (array $documentData) {
                    return $documentData['filetype'] != 'folder';
                }
            );
            $documentAndFolders = array_map(
                function (array $documentData) use ($course, $session, $folderName) {
                    $downloadUrl = api_get_path(WEB_CODE_PATH).'document/document.php?'
                        .api_get_cidreq_params($course->getCode(), $session->getId()).'&'
                        .http_build_query(['action' => 'download', 'id' => $documentData['id']]);
                    $deleteUrl = api_get_path(WEB_AJAX_PATH).'session.ajax.php?'
                        .http_build_query(
                            [
                                'a' => 'delete_basic_course_documents',
                                'deleteid' => $documentData['id'],
                                'curdirpath' => $folderName,
                                'course' => $course->getId(),
                                'session' => $session->getId(),
                            ]
                        );

                    $row = [];
                    $row[] = DocumentManager::build_document_icon_tag($documentData['filetype'], $documentData['path']);
                    $row[] = Display::url($documentData['title'], $downloadUrl);
                    $row[] = format_file_size($documentData['size']);
                    $row[] = date_to_str_ago($documentData['lastedit_date']).PHP_EOL
                        .'<div class="muted"><small>'
                        .api_get_local_time($documentData['lastedit_date'])
                        ."</small></div>";

                    $row[] = Display::url(
                            Display::return_icon('save.png', get_lang('Download')),
                            $downloadUrl
                        )
                        .PHP_EOL
                        .Display::url(
                            Display::return_icon('delete.png', get_lang('Delete')),
                            $deleteUrl,
                            [
                                'class' => 'delete_document',
                                'data-course' => $course->getId(),
                                'data-session' => $session->getId(),
                            ]
                        );

                    return $row;
                },
                $documentAndFolders
            );

            $table = new SortableTableFromArray($documentAndFolders, 1, 20, $folderName);
            $table->set_header(0, get_lang('Type'), false, [], ['class' => 'text-center', 'width' => '60px']);
            $table->set_header(1, get_lang('Name'), false);
            $table->set_header(2, get_lang('Size'), false, [], ['class' => 'text-right', 'style' => 'width: 80px;']);
            $table->set_header(3, get_lang('Date'), false, [], ['class' => 'text-center', 'style' => 'width: 200px;']);
            $table->set_header(4, get_lang('Actions'), false, [], ['class' => 'text-center']);
            $table->display();
        }

        if ('get_basic_course_documents_form' === $action) {
            $form = new FormValidator('get_basic_course_documents_form_'.$session->getId());
            $form->addMultipleUpload(
                api_get_path(WEB_AJAX_PATH).'document.ajax.php?'
                    .api_get_cidreq_params($course->getCode(), $session->getId())
                    .'&a=upload_file&curdirpath='.$folderName,
                ''
            );

            $form->display();
        }
        break;
    case 'delete_basic_course_documents':
        $curdirpath = isset($_GET['curdirpath']) ? Security::remove_XSS($_GET['curdirpath']) : null;
        $docId = isset($_GET['deleteid']) ? (int) $_GET['deleteid'] : 0;
        $courseId = isset($_GET['course']) ? (int) $_GET['course'] : 0;
        $sessionId = isset($_GET['session']) ? (int) $_GET['session'] : 0;

        if (empty($curdirpath) || empty($docId) || empty($courseId) || empty($sessionId)) {
            break;
        }

        $em = Database::getManager();

        $courseInfo = api_get_course_info_by_id($courseId);
        $session = $em->find('ChamiloCoreBundle:Session', $sessionId);
        $currentUserId = api_get_user_id();

        if (empty($courseInfo) || !$session) {
            break;
        }

        if (!api_is_platform_admin(true) || $session->getSessionAdminId() != $currentUserId) {
            break;
        }

        $sysCoursePath = api_get_path(SYS_COURSE_PATH);
        $courseDir = $courseInfo['directory'].'/document';
        $baseWorkDir = $sysCoursePath.$courseDir;

        $documentInfo = DocumentManager::get_document_data_by_id(
            $docId,
            $courseInfo['code'],
            false,
            $session->getId()
        );

        if (empty($documentInfo)) {
            break;
        }

        if ($documentInfo['filetype'] != 'link') {
            $deletedDocument = DocumentManager::delete_document(
                $courseInfo,
                null,
                $baseWorkDir,
                $session->getId(),
                $docId
            );
        } else {
            $deletedDocument = DocumentManager::deleteCloudLink(
                $courseInfo,
                $docId
            );
        }

        if (!$deletedDocument) {
            break;
        }

        echo true;
        break;
    case 'search_template_session':
        SessionManager::protectSession(null, false);

        api_protect_limit_for_session_admin();

        if (empty($_GET['q'])) {
            break;
        }

        $q = strtolower(trim($_GET['q']));

        $list = array_map(
            function ($session) {
                return [
                    'id' => $session['id'],
                    'text' => strip_tags($session['name']),
                ];
            },
            SessionManager::formatSessionsAdminForGrid()
        );

        $list = array_filter(
            $list,
            function ($session) use ($q) {
                $name = strtolower($session['text']);

                return strpos($name, $q) !== false;
            }
        );

        header('Content-Type: application/json');
        echo json_encode(['items' => array_values($list)]);
        break;
    default:
        echo '';
}
exit;
