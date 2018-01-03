<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Controller;

use Chamilo\CoreBundle\Controller\BaseController;
use Knp\Menu\FactoryInterface as MenuFactoryInterface;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Knp\Menu\Renderer\ListRenderer;
use Symfony\Component\HttpFoundation\Request;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Controller\ToolInterface;

/**
 * Each entity controller must extends this class.
 *
 * @abstract
 */
abstract class ToolBaseController extends BaseController implements ToolInterface
{
    protected $course;
    protected $session;

    /**
     * @inheritdoc
     */
    public function setCourse(Course $course)
    {
        $this->course = $course;
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     *
     * @inheritdoc
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

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
     * Converts string 'Chamilo\CourseBundle\Controller\CourseHome\CourseHomeController' into
     * 'tool/course_home'
     */
    public function getTemplatePath()
    {
        $parts = $this->getClassParts();

        $newPath = array();
        foreach ($parts as $part) {
            if (in_array($part, array('chamilo', 'controller')) ||
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
     *
     * @param Request $request
     */
    public function before(Request $request)
    {
        $cidReset = $this->get('cidReset');
        $cidReq = $request->get('cidReq');
        $sessionHandler = $request->getSession();

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

        $sessionHandler->set('courseReset', $courseReset);

        $groupReset = false;
        if ($tempGroupId != $groupId || empty($tempGroupId)) {
            $groupReset = true;
        }

        $sessionReset = false;
        if ($tempSessionId != $sessionId || empty($tempSessionId)) {
            $sessionReset = true;
        }

        if ($courseReset) {

            if (!empty($cidReq) && $cidReq != -1) {
                $courseInfo = api_get_course_info($cidReq, true, true);

                if (!empty($courseInfo)) {
                    $courseCode = $courseInfo['code'];
                    $courseId   = $courseInfo['real_id'];

                    $sessionHandler->set('_real_cid', $courseId);
                    $sessionHandler->set('_cid', $courseCode);
                    $sessionHandler->set('_course', $courseInfo);

                } else {
                    $this->abort(404, $this->trans('Course not available'));
                }
            } else {
                $sessionHandler->remove('_real_cid');
                $sessionHandler->remove('_cid');
                $sessionHandler->remove('_course');
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
            $sessionHandler->remove('session_name');
            $sessionHandler->remove('id_session');

            if (!empty($sessionId)) {
                $sessionInfo = api_get_session_info($sessionId);
                if (empty($sessionInfo)) {
                    $this->abort(404, $this->trans('Session not available'));
                } else {
                    $sessionHandler->set('id_session', $sessionId);
                }
            }
        }

        if ($groupReset) {
            $sessionHandler->remove('_gid');
            if (!empty($groupId)) {
                $sessionHandler->set('_gid', $groupId);
            }
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectCourseHome()
    {
        $url = $this->generateUrl(
            'course_home',
            ['course' => $this->getCourse()->getCode()]
        );

        return $this->redirect($url);
    }

}
