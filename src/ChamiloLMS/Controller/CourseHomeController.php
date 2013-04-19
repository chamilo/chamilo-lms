<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class LearnpathController
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class CourseHomeController
{
    public $language_files = array('course_home','courses');

    public function indexAction(Application $app, $courseCode, $sessionId = null)
    {
        //Needed because of this script:
        $course_code = $courseCode;

        $result = require_once api_get_path(SYS_CODE_PATH).'course_home/course_home.php';

        $app['template']->assign('content', $result['content']);
        $app['template']->assign('message', $result['message']);

        $response = $app['template']->render_layout('layout_1_col.tpl');
        return new Response($response, 200, array());
    }

    /**
     * @param Application $app
     * @param $courseCode
     * @param $fileName
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function getFileAction(Application $app, $courseCode, $fileName)
    {
        api_protect_course_script();

        $courseInfo = api_get_course_info($courseCode);
        $sessionId = $app['request']->get('id_session');
        //$groupId = $app['request']->get('gidReq');

        $docId = \DocumentManager::get_document_id($courseInfo, "/".$fileName);

        $filePath = null;

        if ($docId) {
            $isVisible = \DocumentManager::is_visible_by_id($docId, $courseInfo, $sessionId, api_get_user_id());
            $documentData = \DocumentManager::get_document_data_by_id($docId, $courseCode);
            $filePath = $documentData['absolute_path'];
            event_download($filePath);
        }

        if (!api_is_allowed_to_edit() && !$isVisible) {
            $app->abort(500);
        }
        //DocumentManager::file_send_for_download($full_file_name);
        return $app->sendFile($filePath);
    }
}