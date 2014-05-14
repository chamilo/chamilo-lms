<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use ChamiloLMS\Controller\BaseController;
use Knp\Menu\FactoryInterface as MenuFactoryInterface;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Knp\Menu\Renderer\ListRenderer;
use Symfony\Component\HttpFoundation\Request;

/**
 * Each entity controller must extends this class.
 *
 * @abstract
 */
abstract class ToolBaseController extends BaseController
{
    /**
     * @param $action
     * @param MenuItemInterface $menu
     * @return MenuItemInterface
     */
    public function buildBreadcrumbs($action, MenuItemInterface $menu = null)
    {
        if (!$menu) {
            $menu = $this->getHomeBreadCrumb();
        }

        // Tool home
        $menu->addChild(
            $this->trans($this->getClassnameLabel()),
            array(
                'uri' => $this->generateControllerUrl(
                    'indexAction',
                    array(
                        'courseCode' => $this->getCourse()->getCode()
                    )
                )
            )
        );

        $action = str_replace(
            array($this->getControllerAlias().':', 'Action'),
            '',
            $action
        );

        switch ($action) {
            case 'add':
            case 'edit':
                $menu->addChild(
                    $this->trans($this->getClassnameLabel().ucfirst($action))
                    //array('uri' => $this->generateControllerUrl($action.'Action'))
                );
                break;
        }

        return $menu;
    }

    /**
     * Converts string 'ChamiloLMS\Controller\Tool\CourseHome\CourseHomeController' into
     * 'tool/course_home'
     */
    public function getTemplatePath()
    {
        $parts = $this->getClassParts();

        $newPath = array();
        foreach ($parts as $part) {
            if (in_array($part, array('chamilo_lms', 'controller')) ||
                strpos($part, '_controller') > 0
            ) {
                continue;
            }
            $newPath[] = $part;
        }

        $template = implode('/', $newPath);
        return str_replace('_controller', '', $template);
    }

    /**
     * Before middleware for the ToolBaseController
     */
    public function before(Request $request)
    {
        $cidReset = $this->get('cidReset');

        $cidReq = $request->get('cidReq');

        if (empty($cidReq)) {
            $cidReq = $request->get('courseCode');
        }

        $sessionId = $request->get('id_session');
        $groupId   = $request->get('gidReq');

        $tempCourseId  = api_get_course_id();
        $tempGroupId   = api_get_group_id();
        $tempSessionId = api_get_session_id();

        $courseReset = false;
        if ((!empty($cidReq) && $tempCourseId != $cidReq) || empty($tempCourseId) || empty($tempCourseId) == -1) {
            $courseReset = true;
        }

        if (isset($cidReset) && $cidReset == 1) {
            $courseReset = true;
        }

        $this->getSessionHandler()->set('courseReset', $courseReset);

        $groupReset = false;
        if ($tempGroupId != $groupId || empty($tempGroupId)) {
            $groupReset = true;
        }

        $sessionReset = false;
        if ($tempSessionId != $sessionId || empty($tempSessionId)) {
            $sessionReset = true;
        }
        /*
            $app['monolog']->addDebug('Start');
            $app['monolog']->addDebug($courseReset);
            $app['monolog']->addDebug($cidReq);
            $app['monolog']->addDebug($tempCourseId);
            $app['monolog']->addDebug('End');
        */

        if ($courseReset) {

            if (!empty($cidReq) && $cidReq != -1) {
                $courseInfo = api_get_course_info($cidReq, true, true);

                if (!empty($courseInfo)) {
                    $courseCode = $courseInfo['code'];
                    $courseId   = $courseInfo['real_id'];

                    $this->getSessionHandler()->set('_real_cid', $courseId);
                    $this->getSessionHandler()->set('_cid', $courseCode);
                    $this->getSessionHandler()->set('_course', $courseInfo);

                } else {
                    $this->abort(404, $this->trans('Course not available'));
                }
            } else {
                $this->getSessionHandler()->remove('_real_cid');
                $this->getSessionHandler()->remove('_cid');
                $this->getSessionHandler()->remove('_course');
            }
        }

        $courseCode = api_get_course_id();

        if (!empty($courseCode) && $courseCode != -1) {
            //$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
            $time = api_get_utc_datetime();
            $sql = "UPDATE course SET last_visit= '$time' WHERE code='$courseCode'";
            $this->getDatabase()->query($sql);
        }

        if ($sessionReset) {
            $this->getSessionHandler()->remove('session_name');
            $this->getSessionHandler()->remove('id_session');

            if (!empty($sessionId)) {
                $sessionInfo = api_get_session_info($sessionId);
                if (empty($sessionInfo)) {
                    $this->abort(404, $this->trans('Session not available'));
                } else {
                    $this->getSessionHandler()->set('id_session', $sessionId);
                }
            }
        }

        if ($groupReset) {
            $this->getSessionHandler()->remove('_gid');
            if (!empty($groupId)) {
                $this->getSessionHandler()->set('_gid', $groupId);
            }
        }
    }
}
