<?php

/* For licensing terms, see /license.txt */

use Skill as SkillManager;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_webservices();

ini_set('memory_limit', -1);

/*
ini_set('upload_max_filesize', '4000M');
ini_set('post_max_size', '4000M');
ini_set('max_execution_time', '80000');
ini_set('max_input_time', '80000');
*/

$debug = true;

define('WS_ERROR_SECRET_KEY', 1);

function return_error($code)
{
    $fault = null;
    switch ($code) {
        case WS_ERROR_SECRET_KEY:
            $fault = new soap_fault('Server', '', 'Secret key is not correct or params are not correctly set');
            break;
    }

    return $fault;
}

function WSHelperVerifyKey($params)
{
    global $_configuration, $debug;
    if (is_array($params)) {
        $secret_key = $params['secret_key'];
    } else {
        $secret_key = $params;
    }
    //error_log(print_r($params,1));
    $check_ip = false;
    $ip_matches = false;
    $ip = trim($_SERVER['REMOTE_ADDR']);
    // if we are behind a reverse proxy, assume it will send the
    // HTTP_X_FORWARDED_FOR header and use this IP instead
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        list($ip1) = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ip1);
    }
    if ($debug) {
        error_log("ip: $ip");
    }
    // Check if a file that limits access from webservices exists and contains
    // the restraining check
    if (is_file('webservice-auth-ip.conf.php')) {
        include 'webservice-auth-ip.conf.php';
        if ($debug) {
            error_log("webservice-auth-ip.conf.php file included");
        }
        if (!empty($ws_auth_ip)) {
            $check_ip = true;
            $ip_matches = api_check_ip_in_range($ip, $ws_auth_ip);
            if ($debug) {
                error_log("ip_matches: $ip_matches");
            }
        }
    }

    if ($debug) {
        error_log("checkip ".intval($check_ip));
    }

    if ($check_ip) {
        $security_key = $_configuration['security_key'];
    } else {
        $security_key = $ip.$_configuration['security_key'];
        //error_log($secret_key.'-'.$security_key);
    }
    $result = api_is_valid_secret_key($secret_key, $security_key);
    //error_log($secret_key.'-'.$security_key);
    if ($debug) {
        error_log('WSHelperVerifyKey result: '.intval($result));
    }

    return $result;
}

// Create the server instance
$server = new soap_server();
//$server->soap_defencoding = 'UTF-8';

// Initialize WSDL support
$server->configureWSDL('WSGradebook', 'urn:WSGradebook');

$server->wsdl->addComplexType(
    'WSGradebookScoreParams',
    'complexType',
    'struct',
    'all',
    '',
    [
        'item_id' => [
            'name' => 'item_id',
            'type' => 'xsd:string',
        ],
        'item_type' => [
            'name' => 'item_type',
            'type' => 'xsd:string',
        ],
        'email' => [
            'name' => 'email',
            'type' => 'xsd:string',
        ],
        'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
    ]
);

$server->wsdl->addComplexType(
    'returnItemScore',
    'complexType',
    'struct',
    'sequence',
    '',
    [
        'score' => ['name' => 'score', 'type' => 'xsd:string'],
        'date' => ['name' => 'date', 'type' => 'xsd:string'],
        'counter' => ['name' => 'counter', 'type' => 'xsd:string'],
    ]
);

// Register the method to expose
$server->register(
    'WSGetGradebookUserItemScore', // method name
    ['params' => 'tns:WSGradebookScoreParams'], // input parameters
    ['return' => 'tns:returnItemScore'], // output parameters
    'urn:WSGradebook', // namespace
    'urn:WSGradebook#WSGetGradebookUserItemScore', // soapaction
    'rpc', // style
    'encoded', // use
    'get gradebook item user result'
);

/**
 * @param array $params
 *
 * @return int|string
 */
function WSGetGradebookUserItemScore($params)
{
    if (!WSHelperVerifyKey($params)) {
        return return_error(WS_ERROR_SECRET_KEY);
    }

    $itemId = $params['item_id'];
    $itemType = $params['item_type'];
    $email = $params['email'];
    $userInfo = api_get_user_info_from_email($email);

    if (empty($userInfo)) {
        return new soap_fault('Server', '', 'User not found');
    }

    $em = Database::getManager();

    $score = [];
    switch ($itemType) {
        case 'link':
            /** @var \Chamilo\CoreBundle\Entity\GradebookLink $link */
            $link = $em->getRepository('ChamiloCoreBundle:GradebookLink')->find($itemId);
            if (empty($link)) {
                return new soap_fault('Server', '', 'gradebook link not found');
            }

            $links = AbstractLink::load($link->getId());
            switch ($link->getType()) {
                case LINK_EXERCISE:
                    /** @var ExerciseLink $link */
                    foreach ($links as $link) {
                        $link->set_session_id($link->getCategory()->get_session_id());
                        $score = $link->calc_score($userInfo['user_id']);
                        break;
                    }

                    if (empty($score)) {
                        // If no score found then try exercises from base course.
                        /** @var ExerciseLink $link */
                        foreach ($links as $link) {
                            $link->checkBaseExercises = true;
                            $link->set_session_id($link->getCategory()->get_session_id());
                            $score = $link->calc_score($userInfo['user_id']);
                            break;
                        }
                    }
                    break;
                case LINK_STUDENTPUBLICATION:
                    /** @var StudentPublicationLink $link */
                    foreach ($links as $link) {
                        $link->set_session_id($link->getCategory()->get_session_id());
                        $score = $link->calc_score($userInfo['user_id']);
                        break;
                    }
                    break;
            }
            break;
        case 'evaluation':
            //$evaluation = $em->getRepository('ChamiloCoreBundle:GradebookEvaluation')->find($itemId);
            break;
    }

    if (!empty($score)) {
        $result = ExerciseLib::show_score($score[0], $score[1], false);
        $result = strip_tags($result);

        return ['score' => $result, 'date' => $score[2], 'counter' => $score[3]];
    }

    return new soap_fault('Server', '', 'Score not found');
}

$server->wsdl->addComplexType(
    'WSGradebookCategoryScoreParams',
    'complexType',
    'struct',
    'all',
    '',
    [
        'course_code' => [
            'name' => 'course_code',
            'type' => 'xsd:string',
        ],
        'session_id' => [
            'name' => 'session_id',
            'type' => 'xsd:string',
        ],
        'email' => [
            'name' => 'email',
            'type' => 'xsd:string',
        ],
        'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
    ]
);

// Register the method to expose
$server->register(
    'WSGetGradebookCategoryUserScore', // method name
    ['params' => 'tns:WSGradebookCategoryScoreParams'], // input parameters
    ['return' => 'xsd:string'], // output parameters
    'urn:WSGradebook', // namespace
    'urn:WSGradebook#WSGetGradebookCategoryUserScore', // soapaction
    'rpc', // style
    'encoded'
);

/**
 * @param array $params
 *
 * @return int|string
 */
function WSGetGradebookCategoryUserScore($params)
{
    if (!WSHelperVerifyKey($params)) {
        return return_error(WS_ERROR_SECRET_KEY);
    }
    $courseCode = $params['course_code'];
    $sessionId = (int) $params['session_id'];
    if (!empty($sessionId)) {
        $sessionInfo = api_get_session_info($sessionId);
        if (empty($sessionInfo)) {
            return new soap_fault('Server', '', 'Session not found');
        }
    }

    $email = $params['email'];
    $userInfo = api_get_user_info_from_email($email);

    if (empty($userInfo)) {
        return new soap_fault('Server', '', 'User not found');
    }
    $userId = $userInfo['user_id'];
    $courseInfo = api_get_course_info($courseCode);
    if (empty($courseInfo)) {
        return new soap_fault('Server', '', 'Course not found');
    }

    $cats = Category::load(null,
        null,
        $courseCode,
        null,
        null,
        $sessionId
    );

    /** @var Category $category */
    $category = isset($cats[0]) ? $cats[0] : null;
    $scorecourse_display = null;

    if (!empty($category)) {
        $categoryCourse = Category::load($category->get_id());
        $category = isset($categoryCourse[0]) ? $categoryCourse[0] : null;
        $allevals = $category->get_evaluations($userId, true);
        $alllinks = $category->get_links($userId, true);

        $allEvalsLinks = array_merge($allevals, $alllinks);
        $main_weight = $category->get_weight();
        $scoredisplay = ScoreDisplay::instance();
        $item_value_total = 0;
        /** @var AbstractLink $item */
        foreach ($allEvalsLinks as $item) {
            $item->set_session_id($sessionId);
            $item->set_course_code($courseCode);
            $score = $item->calc_score($userId);
            if (!empty($score)) {
                $divide = $score[1] == 0 ? 1 : $score[1];
                $item_value = $score[0] / $divide * $item->get_weight();
                $item_value_total += $item_value;
            }
        }

        $item_total = $main_weight;
        $total_score = [$item_value_total, $item_total];
        $score = $scoredisplay->display_score($total_score, SCORE_DIV_PERCENT);
        $score = strip_tags($score);

        return $score;
    }

    if (empty($category)) {
        return new soap_fault('Server', '', 'Gradebook category not found');
    }

    return new soap_fault('Server', '', 'Score not found');
}

$server->wsdl->addComplexType(
    'WSLpProgressParams',
    'complexType',
    'struct',
    'all',
    '',
    [
        'course_code' => [
            'name' => 'course_code',
            'type' => 'xsd:string',
        ],
        'session_id' => [
            'name' => 'session_id',
            'type' => 'xsd:string',
        ],
        'lp_id' => [
            'name' => 'lp_id',
            'type' => 'xsd:string',
        ],
        'email' => [
            'name' => 'email',
            'type' => 'xsd:string',
        ],
        'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
    ]
);

// Register the method to expose
$server->register(
    'WSGetLpProgress', // method name
    ['params' => 'tns:WSLpProgressParams'], // input parameters
    ['return' => 'xsd:string'], // output parameters
    'urn:WSGradebook', // namespace
    'urn:WSGradebook#WSGetLpProgress', // soapaction
    'rpc', // style
    'encoded'
);

/**
 * @param array $params
 *
 * @return int|string
 */
function WSGetLpProgress($params)
{
    if (!WSHelperVerifyKey($params)) {
        return return_error(WS_ERROR_SECRET_KEY);
    }

    $courseCode = $params['course_code'];
    $courseInfo = api_get_course_info($courseCode);
    if (empty($courseInfo)) {
        return new soap_fault('Server', '', 'Course not found');
    }

    $sessionId = (int) $params['session_id'];
    if (!empty($sessionId)) {
        $sessionInfo = api_get_session_info($sessionId);
        if (empty($sessionInfo)) {
            return new soap_fault('Server', '', 'Session not found');
        }
    }

    $email = $params['email'];
    $userInfo = api_get_user_info_from_email($email);
    $userId = $userInfo['user_id'];

    if (empty($userInfo)) {
        return new soap_fault('Server', '', 'User not found');
    }

    $lpId = $params['lp_id'];
    $lp = new learnpath($courseCode, $lpId, $userId);

    if (empty($lp)) {
        return new soap_fault('Server', '', 'LP not found');
    }

    return $lp->progress_db;
}

$server->wsdl->addComplexType(
    'WSAssignSkillParams',
    'complexType',
    'struct',
    'all',
    '',
    [
        'skill_id' => [
            'name' => 'skill_id',
            'type' => 'xsd:string',
        ],
        'level' => [
            'name' => 'level',
            'type' => 'xsd:string',
        ],
        'justification' => [
            'name' => 'justification',
            'type' => 'xsd:string',
        ],
        'email' => [
            'name' => 'email',
            'type' => 'xsd:string',
        ],
        'author_email' => [
            'name' => 'author_email',
            'type' => 'xsd:string',
        ],
        'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
    ]
);

// Register the method to expose
$server->register(
    'WSAssignSkill', // method name
    ['params' => 'tns:WSAssignSkillParams'], // input parameters
    ['return' => 'xsd:string'], // output parameters
    'urn:WSGradebook', // namespace
    'urn:WSGradebook:WSAssignSkill', // soapaction
    'rpc', // style
    'encoded'
);

/**
 * @param array $params
 *
 * @return int|string
 */
function WSAssignSkill($params)
{
    if (!WSHelperVerifyKey($params)) {
        return return_error(WS_ERROR_SECRET_KEY);
    }

    $em = Database::getManager();
    $skillManager = new SkillManager();

    $skillId = isset($params['skill_id']) ? $params['skill_id'] : 0;
    $skillRepo = $em->getRepository('ChamiloCoreBundle:Skill');
    $skill = $skillRepo->find($skillId);

    if (empty($skill)) {
        return new soap_fault('Server', '', 'Skill not found');
    }

    $justification = $params['justification'];

    if (strlen($justification) < 10) {
        return new soap_fault('Server', '', 'Justification smaller than 10 chars');
    }

    $level = (int) $params['level'];

    $email = $params['email'];
    $userInfo = api_get_user_info_from_email($email);

    if (empty($userInfo)) {
        return new soap_fault('Server', '', 'User not found');
    }

    $email = $params['author_email'];
    $authorInfo = api_get_user_info_from_email($email);

    if (empty($authorInfo)) {
        return new soap_fault('Server', '', 'Author not found');
    }

    $userId = $userInfo['user_id'];
    $user = api_get_user_entity($userId);
    $skillUser = $skillManager->addSkillToUserBadge(
        $user,
        $skill,
        $level,
        $justification,
        $authorInfo['id']
    );

    if (!empty($skillUser)) {
        return 1;
    }

    return 0;
}

// Use the request to (try to) invoke the service
$GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents('php://input');
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';

// If you send your data in utf8 then this value must be false.
$decodeUTF8 = api_get_setting('registration.soap.php.decode_utf8');
if ($decodeUTF8 === 'true') {
    $server->decode_utf8 = true;
} else {
    $server->decode_utf8 = false;
}
$server->service($HTTP_RAW_POST_DATA);
