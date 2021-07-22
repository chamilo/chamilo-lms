<?php
/* For licensing terms, see /license.txt */
/**
 * Controller for REST request
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @author Nosolored <info@nosolored.com>
 * @package chamilo.webservices
 */
/* Require libs and classes */
require_once __DIR__ . '/../../main/inc/global.inc.php';
require_once 'webservices/WSApp.class.php';
require_once 'webservices/AppWebService.class.php';
require_once 'app.lib.php';

use ChamiloSession as Session;
/* Manage actions */
$json = array();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$username = isset($_REQUEST['username']) ? Security::remove_XSS($_REQUEST['username']) : null;
$apiKey = isset($_REQUEST['api_key']) ? Security::remove_XSS($_REQUEST['api_key']) : null;

$userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : null;
$courseId = isset($_POST['c_id']) ? (int) $_POST['c_id'] : null;
$sessionId = isset($_POST['s_id']) ? (int) $_POST['s_id'] : 0;
$list = isset($_POST['list']) ? Security::remove_XSS($_POST['list']) : null;
$path = isset($_POST['path']) ? Security::remove_XSS($_POST['path']) : null;
$forumId = isset($_POST['f_id']) ? (int) $_POST['f_id'] : null;
$threadId = isset($_POST['t_id']) ? $_POST['t_id'] : null;
$parentId = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
$title = isset($_POST['title']) ? Security::remove_XSS($_POST['title']) : null;
$text = isset($_POST['text']) ? $_POST['text'] : null;
$notice = isset($_POST['notice']) ? Security::remove_XSS($_POST['notice']) : null;
$messageId = isset($_POST['messageId']) ? Security::remove_XSS($_POST['messageId']) : null;

try {
    /** @var Rest $restApi */
    $restApi = $apiKey ? AppWebService::validate($username, $apiKey) : null;

    if ($restApi) {
        $restApi->setCourse($courseId);
        $restApi->setSession($sessionId);
    }

    switch ($action) {
        case 'loginNewMessages':
            AppWebService::init();
            
            $password = isset($_POST['password']) ? $_POST['password'] : null;
            $isValid = AppWebService::isValidUser($username, $password);
            if (!$isValid) {
                $json = array(
                        'status' => false
                );
                exit;
            }
            $apiKey = AppWebService::findUserApiKey($username, AppWebService::SERVICE_NAME);
            $userInfo = api_get_user_info_from_username($username);
            $userInfo['apiKey'] = $apiKey;
            
            Event::eventLogin($userInfo['user_id']);
            LoginCheck($userInfo['user_id']);
            
            $json = [
                'status' => true,
                'userInfo' => $userInfo,
                'gcmSenderId' => api_get_setting('messaging_gdc_project_number'),
            ];
            break;
        case 'gcm_id':
            $gcmId = isset($_POST['registration_id']) ? Security::remove_XSS($_POST['registration_id']) : null;
            $restApi->setGcmId($gcmId);
            $json = ['status' => true];
            break;
        case 'check_conditions':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);

                $checkCondition = $webService->checkCondition($userId);

                if ($checkCondition) {
                    $json = [
                        'status' => true,
                        'check_condition' => true,
                    ];
                } else {
                    $json = [
                        'status' => true,
                        'check_condition' => false,
                    ];
                }
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
        case 'logoutPlatform':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);

                logoutPlatform($userId, $courseId, $sessionId);

                $json = [
                    'status' => true,
                ];
            } else {
                $json = array(
                        'status' => false
                );
            }
            break;
        case 'getConditions':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $getCondition= $webService->getCondition($userId);
                
                if (!empty($getCondition)) {
                    $json = [
                        'status' => true,
                        'language_id' => $getCondition['language_id'],
                        'date' => $getCondition['date'],
                        'content' => $getCondition['content'],
                        'type' => $getCondition['type'],
                        'changes' => $getCondition['changes'],
                        'version' => $getCondition['version'],
                        'id' => $getCondition['id'],
                    ];
                } else {
                    $json = [
                        'status' => false,
                        'text_condition' => '',
                    ];
                }
            } else {
                $json = ['status' => false];
            }
            break;
        case 'setAcceptCondition':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);

                $legalAcceptType = isset($_POST['legal_accept_type']) ? $_POST['legal_accept_type'] : null;
                $setCondition = $webService->setConditions($userId, $legalAcceptType);
                
                if (!empty($setCondition)) {
                    $json = [
                        'status' => true,
                    ];
                } else {
                    $json = [
                        'status' => false,
                    ];
                }
            } else {
                $json = ['status' => false];
            }
            break;
        case 'getCatalog':
            $code = isset($_POST['code']) ? Security::remove_XSS($_POST['code']) : 'ALL';
            $info = $restApi->getCatalog($code);
            $json = [
                'status' => true,
                'code' => $info['code'],
                'user_id' => $info['user_id'],
                'courses' => $info['courses_in_category'],
                'sessions' => $info['sessions_in_category'],
                'user_coursecodes' => $info['user_coursecodes'],
                'catalog_show_courses_sessions' => $info['catalogShowCoursesSessions'],
                'categories_select' => $info['categories_select'],
            ];
            break;
        case 'subscribeCourse':
            $code = isset($_POST['code']) ? Security::remove_XSS($_POST['code']) : null;
            $result = $restApi->subscribeCourse($code);
            $json = array(
                    'status' => true,
                    'id' => $result['id'],
                    'message' => $result['message'],
                    'password' => $result['password']
            );
            break;
        case 'subscribeCoursePassword':
            $code = isset($_POST['code']) ? Security::remove_XSS($_POST['code']) : null;
            $password = isset($_POST['password']) ? Security::remove_XSS($_POST['password']) : null;
            $result = $restApi->subscribeCourse($code, $password);
            $json = array(
                    'status' => true,
                    'id' => $result['id'],
                    'message' => $result['message']
            );
            break;
        case 'countNewMessages':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $lastId = isset($_POST['last']) ? $_POST['last'] : 0;
    
                $count = $webService->countNewMessages($username, $lastId);
                
                $json = [
                    'status' => true,
                    'count' => $count,
                ];
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
        case 'getAllMessages':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $messages = $webService->getAllMessages($username);
    
                $json = [
                    'status' => true,
                    'messages' => $messages,
                ];
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
        case 'setReadMessage':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $message = $webService->setReadMessage($messageId);
    
                $json = ['status' => true];
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
        case 'getNumMessages':
             if (AppWebService::isValidApiKey($username, $apiKey)) {
                 $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $num = $webService->getNumMessages($userId);
                $allowStudentsToBrowseCourse = api_get_setting('allow_students_to_browse_courses');

                $json = [
                    'status' => true,
                    'num_messages' => $num,
                    'allow_students_to_browse_courses' => $allowStudentsToBrowseCourse,
                ];
            } else {
                $json = array(
                    'status' => false,
                    'num_messages' => 0
                );
            }
            break;
        case 'getNewMessages':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $lastId = isset($_POST['last']) ? $_POST['last'] : 0;
    
                $messages = $webService->getNewMessages($username, $lastId);
                $removeMessages = $webService->getRemoveMessages($list, $username);
    
                $json = [
                    'status' => true,
                    'messages' => $messages,
                    'remove_messages' => $removeMessages,
                ];
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
        case 'getOutMessages':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                 
                $lastId = isset($_POST['last']) ? $_POST['last'] : 0;
                 
                $messages = $webService->getOutMessages($username, $lastId);
                $removeMessages = $webService->getRemoveOutMessages($list, $username);
                 
                $json = [
                    'status' => true,
                    'messages' => $messages,
                    'remove_messages' => $removeMessages,
                ];
            } else {
                $json = array(
                        'status' => false
                );
            }
            break;
             
        case 'getAllOutMessages':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
        
                $messages = $webService->getAllOutMessages($username);
                $json = [
                    'status' => true,
                    'messages' => $messages,
                ];
            } else {
                $json = array(
                        'status' => false
                );
            }
            break;
        case 'getUsersMessage':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $user_search = isset($_POST['user_search']) ? $_POST['user_search'] : '';
                if ($user_search == '') {
                    $users = '';
                } else {
                    $users = $webService->getUsersMessage($userId, $user_search);
                }
                
                $json = [
                    'status' => true,
                    'users' => $users,
                ];
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
        
        case 'formNewMessage':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $toUserid = isset($_POST['to_userid']) ? $_POST['to_userid'] : '';
                $result = $webService->sendNewEmail($toUserid, $title, $text, $userId);
                
                $json = ['status' => $result];
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
            
            
        case 'formReplyMessage':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $messageId = isset($_POST['message_id']) ? (int) $_POST['message_id'] : '0';
                $checkQuote = isset($_POST['check_quote']) ? (int) $_POST['check_quote'] : '0';
                
                $result = $webService->sendReplyEmail($messageId, $title, $text, $checkQuote, $userId);
                
                $json = ['status' => $result];
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
            
        case 'getCoursesList':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $courses = $webService->getCoursesList($userId);
                $sessions = $webService->getSessionsList($userId);
    
                $json = [
                    'status' => true,
                    'user_id' => $userId,
                    'courses' => $courses,
                    'sessions' => $sessions,
                ];
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
            
        case 'getProfile':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $profile = $webService->getProfile($userId);
    
                $json = [
                    'status' => true,
                    'profile' => $profile,
                ];
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
        
        case 'getInfoCourse':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $info = $webService->registerAccessCourse($courseId, $userId, $sessionId);
    
                $json = [
                    'status' => true,
                    'info' => $info
                ];
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;    
        
        case 'getDescription':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $descriptions = $webService->getDescription($courseId, $username, $sessionId);
    
                $json = [
                    'status' => true,
                    'descriptions' => $descriptions,
                ];
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
            
        case 'getLearnpath':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $learnpaths = $webService->getLearnpaths($courseId, $userId, $sessionId);
    
                $json = [
                    'status' => true,
                    'learnpaths' => $learnpaths,
                ];
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
        
        case 'getLink':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $links = $webService->getLink($courseId, $username, $sessionId);
                $json = [
                    'status' => true,
                    'links' => $links
                ];
            } else {
                $json = array(
                        'status' => false
                );
            }
            break;
            
        case 'getNotebook':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $notebooks = $webService->getNotebook($courseId, $username, $sessionId);
                $json = [
                    'status' => true,
                    'notebooks' => $notebooks,
                ];
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
            
        case 'formNewNotebook':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $notebook = $webService->createNotebook($courseId, $title, $text, $userId, $sessionId);
                if ($notebook !== false) {
                    $json = [
                        'status' => true
                    ];
                } else {
                    $json = array(
                        'status' => false
                    );
                }
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
            
        case 'getDocuments':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $documents = $webService->getDocuments($courseId, $path, $username, $sessionId);
    
                $json = [
                    'status' => true,
                    'documents' => $documents,
                ];
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
            
        case 'getAnnouncementsList':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $announcements = $webService->getAnnouncements($courseId, $userId, $sessionId);
                if ($announcements !== false) {
                    $json = [
                        'status' => true,
                        'announcements' => $announcements,
                    ];
                } else {
                    $json = array(
                        'status' => false
                    );
                }
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
            
        case 'getAgenda':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $events = $webService->getCourseEvents($courseId, $userId, $sessionId);
                if ($events !== false) {
                    $json = [
                        'status' => true,
                        'events' => $events,
                    ];
                } else {
                    $json = array(
                        'status' => false
                    );
                }
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
            
        case 'getWorksTeacher':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                $works = $webService->getWorks($courseId, $userId, $sessionId, true);
                if ($works !== false) {
                    $json = [
                            'status' => true,
                            'works' => $works,
                    ];
                } else {
                    $json = array(
                            'status' => false
                    );
                }
            } else {
                $json = array(
                        'status' => false
                );
            }
            break;
            
        case 'getWorksStudent':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $works = $webService->getWorks($courseId, $userId, $sessionId, false);
                if ($works !== false) {
                    $json = [
                        'status' => true,
                        'works' => $works,
                    ];
                } else {
                    $json = array(
                        'status' => false
                    );
                }
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
        
        case 'getWorkListTeacher':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $workId = isset($_POST['w_id']) ? (int) $_POST['w_id'] : null;
                if (!empty($workId)) {
                    $worksList = $webService->getWorksList($courseId, $workId, $userId, $sessionId, true);
                    if ($worksList !== false) {
                        $json = [
                                'status' => true,
                                'works' => $worksList,
                        ];
                    } else {
                        $json = ['status' => false];
                    }
                } else {
                    throw new Exception('Empty work Id');
                }
            } else {
                $json = ['status' => false];
            }
            break;
            
        case 'getWorkList':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $workId = isset($_POST['w_id']) ? (int) $_POST['w_id'] : null;
                if (!empty($workId)) {
                    $worksList = $webService->getWorksList($courseId, $workId, $userId, $sessionId);
                    if ($worksList !== false) {
                        $json = [
                            'status' => true,
                            'works' => $worksList,
                        ];
                    } else {
                        $json = ['status' => false];
                    }
                } else {
                    throw new Exception('Empty work Id');
                }
            } else {
                $json = ['status' => false];
            }
            break;
            
        case 'formNewWork': 
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $workId = isset($_POST['w_id']) ? (int) $_POST['w_id'] : null;
                $values = [
                    'title' => Database::escape_string($_POST['title']),
                    'extension' => isset($_POST['extension']) ? Database::escape_string($_POST['extension']) : null,
                    'description' => Database::escape_string($_POST['description']),
                    'contains_file' => (int) $_POST['contains_file'],
                ];
                
                if (!empty($workId)) {
                    $workResponse = $webService->getWorksUpload(
                        $courseId,
                        $workId,
                        $values,
                        $userId,
                        $_FILES,
                        $sessionId
                    );
                    
                    if ($workResponse === true) {
                        $json = [
                            'status' => true,
                        ];
                    } else {
                        $json = ['status' => false, 'message' => $workResponse];
                    }
                } else {
                    throw new Exception('Empty work Id');
                }
            } else {
                $json = ['status' => false];
            }
            break;
            
        case 'formNewCommentWork':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $workId = isset($_POST['w_id']) ? (int) $_POST['w_id'] : null;
                $values = [
                    'comment' => Database::escape_string($_POST['comment']),
                    'send_email' => isset($_POST['send_email']) ? $_POST['send_email'] : null,
                    'check_correction' => isset($_POST['check_correction']) ? $_POST['check_correction'] : null,
                    'qualification' => isset($_POST['qualification']) ? $_POST['qualification'] : 0,
                    'allow_edit' => isset($_POST['allow_edit']) ? (int) $_POST['allow_edit'] : 0,
                ];

                if (isset($_FILES['attachment'])) {
                    $values['attachment'] = $_FILES['attachment'];
                }

                if (!empty($workId)) {
                    $workResponse = $webService->newCommentWorksUpload(
                        $courseId,
                        $workId,
                        $values,
                        $userId,
                        $sessionId
                    );

                    if ($workResponse === true) {
                        $json = [
                                'status' => true,
                        ];
                    } else {
                        $json = ['status' => false, 'message' => $workResponse];
                    }
                } else {
                    throw new Exception('Empty work Id');
                }
            } else {
                $json = ['status' => false];
            }
            break;

        case 'formNewCommentWorkIOS':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
            } else {
                $json = ['status' => false];
            }
            break;
            
        case 'getParamsFormWork':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                $workId = isset($_POST['w_id']) ? (int) $_POST['w_id'] : null;
                
                $options = $webService->getCategoryGradebookWork($courseId, $userId, $sessionId);
                
                $timeNextWeek = time() + 86400 * 7;
                $nextWeek = substr(api_get_local_time($timeNextWeek), 0, 10);
                $date = substr($nextWeek, 0, 10);
                $expires_on = $date.'T23:59';
                $nextDay = substr(api_get_local_time($timeNextWeek + 86400), 0, 10);
                $date = substr($nextDay, 0, 10);
                $ends_on = $date.'T23:59';
                
                $params =  [];
                if (!empty($workId)) {
                    $params = $webService->getParamsFormWork($courseId, $userId, $sessionId, $workId);
                }
                
                if ($options !== false) {
                    $json = [
                        'status' => true,
                        'options' => $options,
                        'expires_on' => $expires_on,
                        'ends_on' => $ends_on,
                        'params' => $params,
                    ];
                } else {
                    $json = array(
                        'status' => false
                    );
                }
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
            
        case 'formNewWorkMain':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $expiresOn = $endsOn = '';
                if (!empty($_POST['expires_on'])) {
                    $expiresOn = date("Y-m-d H:i:s", strtotime(Database::escape_string($_POST['expires_on'])));
                }
                
                if (!empty($_POST['ends_on'])) {
                    $endsOn = date("Y-m-d H:i:s", strtotime(Database::escape_string($_POST['ends_on'])));
                }

                $values = [
                    'new_dir' => Database::escape_string($_POST['new_dir']),
                    'description' => isset($_POST['description']) ? Database::escape_string(nl2br($_POST['description'])) : '',
                    'qualification' => (float) $_POST['qualification'],
                    'make_calification' => isset($_POST['make_calification_id']) ? (int) $_POST['make_calification_id'] : 0,
                    'category_id' => isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0,
                    'weight' => isset($_POST['weight']) ? (int) $_POST['weight'] : 0,
                    'enableExpiryDate' => (int) $_POST['expiry_date'] > 0 ? (int) $_POST['expiry_date'] : null,
                    'expires_on' => $expiresOn,
                    'enableEndDate' => (int) $_POST['end_date'] > 0 ? (int) $_POST['end_date'] : null,
                    'ends_on' => $endsOn,
                    'add_to_calendar' => isset($_POST['add_to_calendar']) ? (int) $_POST['add_to_calendar'] : 0,
                    'allow_text_assignment' => isset($_POST['allow_text_assignment']) ? (int) $_POST['allow_text_assignment'] : 0,
                    'action' => isset($_POST['action_work']) ? Database::escape_string($_POST['action_work']) : '',
                    'item_id' => isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0,
                ];

                $workResponse = $webService->addWorksMain($courseId, $userId, $values, $sessionId);

                if ($workResponse !== false) {
                    $json = [
                        'status' => true,
                    ];
                } else {
                    $json = ['status' => false, 'message' => get_lang('CannotCreateDir')];
                }
            } else {
                $json = ['status' => false];
            }
            break;
            
        case 'formEditWorkMain':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);

                $expiresOn = $endsOn = '';
                if (!empty($_POST['expires_on'])) {
                    $expiresOn = date("Y-m-d H:i:s", strtotime(Database::escape_string($_POST['expires_on'])));
                }

                if (!empty($_POST['ends_on'])) {
                    $endsOn = date("Y-m-d H:i:s", strtotime(Database::escape_string($_POST['ends_on'])));
                }
                
                $values = [
                    'new_dir' => Database::escape_string($_POST['new_dir']),
                    'description' => isset($_POST['description']) ? $_POST['description'] : '',
                    'qualification' => (float) $_POST['qualification'],
                    'make_calification' => isset($_POST['make_calification']) ? (int) $_POST['make_calification'] : 0,
                    'category_id' => isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0,
                    'weight' => isset($_POST['weight']) ? (int) $_POST['weight'] : 0,
                    'enableExpiryDate' => isset($_POST['expiry_date']) ? (int) $_POST['expiry_date'] : 0,
                    'expires_on' => $expiresOn,
                    'enableEndDate' => isset($_POST['end_date']) ? (int) $_POST['end_date'] : 0,
                    'ends_on' => $endsOn,
                    'add_to_calendar' => isset($_POST['add_to_calendar']) ? (int) $_POST['add_to_calendar'] : 0,
                    'allow_text_assignment' => isset($_POST['allow_text_assignment']) ? (int) $_POST['allow_text_assignment'] : 0,
                    'action' => isset($_POST['action_work']) ? Database::escape_string($_POST['action_work']) : '',
                    'item_id' => isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0,
                    'work_id' => isset($_POST['w_id']) ? (int) $_POST['w_id'] : 0,
                ];
                $workResponse = $webService->editWorksMain($courseId, $userId, $values, $sessionId);
                
                if ($workResponse !== false) {
                    $json = ['status' => true, 'message' => $workResponse];
                }
            } else {
                $json = ['status' => false];
            }
            break;
            
        case 'formWorkEditItem':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $values = [
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'work_id' => isset($_POST['w_id']) ? (int) $_POST['w_id'] : 0,
                    'send_email' => isset($_POST['send_mail']) ? (int) $_POST['send_mail'] : 0,
                    'send_to_drh_users' => isset($_POST['send_to_drh_users']) ? (int) $_POST['send_to_drh_users'] : 0,
                ];
                $workResponse = $webService->formWorkEditItem($courseId, $userId, $values, $sessionId);
                
                if ($workResponse !== false) {
                    $json = ['status' => true, 'message' => $workResponse];
                }
            } else {
                $json = ['status' => false];
            }
            break;
            
        case 'getWorkStudentList':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $workId = null;
                $studentList = $webService->getWorkStudentList($courseId, $userId, $sessionId, $workId);
                
                if ($studentList!== false) {
                    $json = [
                            'status' => true, 'student_list' => $studentList
                    ];
                } else {
                    $json = ['status' => false];
                }
            } else {
                $json = ['status' => false];
            }
            
            break;
            
        case 'getUserWork': 
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);

                $workId = isset($_POST['w_id']) ? (int) $_POST['w_id'] : null;
                $userList = $webService->getUserWork($courseId, $userId, $workId, $sessionId);
                
                if ($userList !== false) {
                    $json = [
                        'status' => true,
                        'users_added' => $userList['users_added'],
                        'users_to_add' => $userList['users_to_add'],
                    ];
                } else {
                    $json = ['status' => false];
                }
            } else {
                $json = ['status' => false];
            }
            
            break;
        
        case 'formDeleteWorkUser':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $workId = isset($_POST['w_id']) ? (int) $_POST['w_id'] : null;
                $studentId = isset($_POST['student_id']) ? substr($_POST['student_id'], 5) : null;
                
                require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
                if (!empty($workId) && !empty($studentId)) {
                    deleteUserToWork($studentId, $workId, $courseId);
                    $json = ['status' => true];
                } else {
                    $json = ['status' => false];
                }
            } else {
                $json = ['status' => false];
            }
            break;
            
        case 'formAddWorkUser':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $workId = isset($_POST['w_id']) ? (int) $_POST['w_id'] : null;
                $studentId = isset($_POST['student_id']) ? substr($_POST['student_id'], 5) : null;
                
                require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
                $data = getUserToWork($studentId, $workId, $courseId);
                if (empty($data)) {
                    addUserToWork($studentId, $workId, $courseId);
                    $json = ['status' => true];
                } else {
                    $json = ['status' => false];
                }
            } else {
                $json = ['status' => false];
            }
            break;
            
        case 'getUserWithoutPublication':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $workId = isset($_POST['w_id']) ? (int) $_POST['w_id'] : null;
                $userList = $webService->getUserWithoutPublication($courseId, $userId, $workId, $sessionId);
                
                if ($userList !== false) {
                    $json = [
                        'status' => true,
                        'users_list' => $userList,
                    ];
                } else {
                    $json = ['status' => false];
                }
            } else {
                $json = ['status' => false];
            }
            break;

        case 'sendMailMissing':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $workId = isset($_POST['w_id']) ? (int) $_POST['w_id'] : null;
                $sendEmail = $webService->sendMailMissing($courseId, $userId, $workId, $sessionId);
                
                if ($sendEmail !== false) {
                    $json = ['status' => true, 'content' => $sendEmail];
                } else {
                    $json = ['status' => false];
                }
            } else {
                $json = ['status' => false];
            }
            break;
            
        case 'deleteWorkCorrection':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $workId = isset($_POST['w_id']) ? (int) $_POST['w_id'] : null;
                $result = $webService->deleteWorkCorrection($courseId, $userId, $workId, $sessionId);
                
                if ($result !== false) {
                    $json = [
                        'status' => true,
                        'message' => $result,
                    ];
                } else {
                    $json = ['status' => false];
                }
            } else {
                $json = ['status' => false];
            }
            break;
        case 'deleteWorkItem':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $workId = isset($_POST['w_id']) ? (int) substr($_POST['w_id'], 6) : null;
                $result = $webService->deleteWorkItem($courseId, $userId, $workId, $sessionId);
                
                if ($result !== false) {
                    $json = [
                            'status' => true,
                            'message' => $result,
                    ];
                } else {
                    $json = ['status' => false];
                }
            } else {
                $json = ['status' => false];
            }
            break;
            
        case 'setInvisibleWorkItem':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $workId = isset($_POST['w_id']) ? (int) substr($_POST['w_id'], 5) : null;
                $result = $webService->setInvisibleWorkItem($courseId, $userId, $workId, $sessionId);
                
                if ($result !== false) {
                    $json = [
                            'status' => true,
                            'message' => get_lang('FileInvisible')
                    ];
                } else {
                    $json = ['status' => false];
                }
            } else {
                $json = ['status' => false];
            }
            break;
            
        case 'setVisibleWorkItem':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $workId = isset($_POST['w_id']) ? (int) substr($_POST['w_id'], 5) : null;
                $result = $webService->setVisibleWorkItem($courseId, $userId, $workId, $sessionId);
                if ($result !== false) {
                    $json = [
                            'status' => true,
                            'message' => get_lang('FileVisible'),
                    ];
                } else {
                    $json = ['status' => false];
                }
            } else {
                $json = ['status' => false];
            }
            break;
            
        case 'getForumsList':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $forums = $webService->getForums($courseId, $userId, $sessionId);
                if ($forums !== false) {
                    $json = [
                        'status' => true,
                        'forums' => $forums,
                    ];
                } else {
                    $json = array(
                        'status' => false
                    );
                }
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
        
        case 'getThreadsList':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $threads = $webService->getThreads($courseId, $forumId, $userId);
                if ($threads !== false) {
                    $json = [
                        'status' => true,
                        'data' => $threads
                    ];
                } else {
                    $json = array(
                        'status' => false
                    );
                }
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
            
        case 'getPostsList':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
    
                $posts = $webService->getPosts($courseId, $forumId, $threadId);
                if ($posts !== false) {
                    $json = [
                        'status' => true,
                        'data' => $posts,
                    ];
                } else {
                    $json = array(
                        'status' => false
                    );
                }
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
        
        case 'formNewThread':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);
                
                $posts = $webService->createThread($courseId, $forumId, $title, $text, $notice, $userId, $sessionId);
                if ($posts !== false) {
                    $json = ['status' => true];
                } else {
                    $json = array(
                        'status' => false
                    );
                }
            } else {
                $json = array(
                    'status' => false
                );
            }
            break;
        
        case 'setNotify':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);

                $notify = $webService->setNotifyThread($courseId, substr($threadId, 6));
                if ($notify !== false) {
                    $json = [
                        'status' => true,
                        'message' => $notify,
                        'id' => $threadId,
                    ];
                } else {
                    $json = ['status' => false];
                }
            } else {
                $json = ['status' => false];
            }
            break;
            
        case 'formNewPost':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);

                $posts = $webService->createPost(
                    $courseId,
                    $forumId,
                    $threadId,
                    $title,
                    $text,
                    $notice,
                    $userId,
                    $parentId
                );

                if ($posts !== false) {
                    $json = [
                        'status' => true,
                        'statusFile' => false,
                        'post_id' => $posts,
                    ];
                } else {
                    $json = ['status' => false];
                }
            } else {
                $json = ['status' => false];
            }
            break;

        case 'getRanking':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);

                $ranking = $webService->getRanking($courseId, $sessionId);

                $json = [
                    'status' => true,
                    'ranking' => $ranking,
                ];
            } else {
                $json = ['status' => false];
            }
            break;

        case 'getDetailsRanking':
            if (AppWebService::isValidApiKey($username, $apiKey)) {
                $webService = new AppWebService($username, $apiKey);
                $webService->setApiKey($apiKey);

                $details_ranking = $webService->getDetailsRanking($courseId, $userId, $sessionId);

                $json = [
                    'status' => true,
                    'info' => $details_ranking,
                ];
            } else {
                $json = ['status' => false];
            }
            break;

        default:
    }
} catch (Exception $exeption) {
    /*
    $restResponse->setErrorMessage(
        $exeption->getMessage()
    );
    */
    error_log($exeption->getMessage());
    $json = ['status' => false];
}

/* View */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode($json);
