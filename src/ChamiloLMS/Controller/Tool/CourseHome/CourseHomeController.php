<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Tool\CourseHome;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use ChamiloLMS\Controller\CommonController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Entity\CTool;
use ChamiloLMS\Form\CourseHomeToolType;
use \Display;
use Doctrine\Common\Collections\Criteria;

/**
 * Class CourseHomeController
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class CourseHomeController extends CommonController
{
    /**
     * @Route("/courses/{cidReq}/{sessionId}")
     * @Method({"GET"})
     *
     * @param string $cidReq
     * @param int $id_session
     * @return Response
     */
    public function indexAction($cidReq, $id_session = null)
    {
        $courseCode = api_get_course_id();
        $sessionId = api_get_session_id();
        $userId = $this->getUser()->getUserId();

        $coursesAlreadyVisited = $this->getRequest()->getSession()->get('coursesAlreadyVisited');

        $result = $this->autolaunch();

        $showAutoLaunchLpWarning = $result['show_autolaunch_lp_warning'];
        $showAutoLaunchExerciseWarning = $result['show_autolaunch_exercise_warning'];

        if ($showAutoLaunchLpWarning) {
            $this->getTemplate()->assign(
                'lp_warning',
                Display::return_message(get_lang('TheLPAutoLaunchSettingIsONStudentsWillBeRedirectToAnSpecificLP'), 'warning')
            );
        }
        if ($showAutoLaunchExerciseWarning) {
            $this->getTemplate()->assign(
                'exercise_warning',
                Display::return_message(get_lang('TheExerciseAutoLaunchSettingIsONStudentsWillBeRedirectToAnSpecificExercise'), 'warning')
            );
        }
        if ($this->isCourseTeacher()) {
            $editIcons = Display::url(
                Display::return_icon('edit.png'),
                $this->generateUrl('course_home.controller:iconListAction', array('course' => api_get_course_id()))
            );

            $this->getTemplate()->assign('edit_icons', $editIcons);
        }

        if (!isset($coursesAlreadyVisited[$courseCode])) {
            event_access_course();
            $coursesAlreadyVisited[$courseCode] = 1;
            $this->getRequest()->getSession()->set('coursesAlreadyVisited', $coursesAlreadyVisited);
        }

        $this->getRequest()->getSession()->remove('toolgroup');
        $this->getRequest()->getSession()->remove('_gid');

        $isSpecialCourse = \CourseManager::is_special_course($courseCode);

        if ($isSpecialCourse) {
            $autoreg = $this->getRequest()->get('autoreg');
            if ($autoreg == 1) {
                \CourseManager::subscribe_user($userId, $courseCode, STUDENT);
            }
        }

        $script =  'activity.php';
        if (api_get_setting('homepage_view') == 'activity' || api_get_setting('homepage_view') == 'activity_big') {
            $script =  'activity.php';
        } elseif (api_get_setting('homepage_view') == '2column') {
            $script = '2column.php';
        } elseif (api_get_setting('homepage_view') == '3column') {
            $script = '3column.php';
        } elseif (api_get_setting('homepage_view') == 'vertical_activity') {
            $script = 'vertical_activity.php';
        }

        $result = require_once api_get_path(SYS_CODE_PATH).'course_home/'.$script;
        $toolList = $result['tool_list'];
        $this->getTemplate()->assign('icons', $result['content']);

        $introduction = Display::return_introduction_section(
            $this->get('url_generator'),
            TOOL_COURSE_HOMEPAGE,
            $toolList
        );

        $this->getTemplate()->assign('introduction_text', $introduction);

        if (api_get_setting('show_session_data') == 'true' && $sessionId) {
            $sessionInfo = \CourseHome::show_session_data($sessionId);
            $this->getTemplate()->assign('session_info', $sessionInfo);
        }

        $response = $this->get('template')->render_template($this->getTemplatePath().'index.tpl');

        return new Response($response, 200, array());
    }

    /**
     * @return array
     */
    private function autolaunch()
    {
        $showAutoLaunchExerciseWarning = false;

        // Exercise auto-launch
        $auto_launch = api_get_course_setting('enable_exercise_auto_launch');

        if (!empty($auto_launch)) {
            $session_id = api_get_session_id();
            //Exercise list
            if ($auto_launch == 2) {
                if (api_is_platform_admin() || api_is_allowed_to_edit()) {
                    $showAutoLaunchExerciseWarning = true;
                } else {
                    $session_key = 'exercise_autolunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
                    if (!isset($_SESSION[$session_key])) {
                        //redirecting to the Exercise
                        $url = api_get_path(WEB_CODE_PATH).'exercice/exercice.php?'.api_get_cidreq().'&id_session='.$session_id;
                        $_SESSION[$session_key] = true;
                        header("Location: $url");
                        exit;
                    }
                }
            } else {
                $table = Database::get_course_table(TABLE_QUIZ_TEST);
                $course_id = api_get_course_int_id();
                $condition = '';
                if (!empty($session_id)) {
                    $condition =  api_get_session_condition($session_id);
                    $sql = "SELECT iid FROM $table WHERE c_id = $course_id AND autolaunch = 1 $condition LIMIT 1";
                    $result = Database::query($sql);
                    //If we found nothing in the session we just called the session_id =  0 autolaunch
                    if (Database::num_rows($result) ==  0) {
                        $condition = '';
                    } else {
                        //great, there is an specific auto lunch for this session we leave the $condition
                    }
                }

                $sql = "SELECT iid FROM $table WHERE c_id = $course_id AND autolaunch = 1 $condition LIMIT 1";
                $result = Database::query($sql);
                if (Database::num_rows($result) >  0) {
                    $data = Database::fetch_array($result,'ASSOC');
                    if (!empty($data['iid'])) {
                        if (api_is_platform_admin() || api_is_allowed_to_edit()) {
                            $showAutoLaunchExerciseWarning = true;
                        } else {
                            $session_key = 'exercise_autolunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
                            if (!isset($_SESSION[$session_key])) {
                                //redirecting to the LP
                                $url = api_get_path(WEB_CODE_PATH).'exercice/overview.php?'.api_get_cidreq().'&exerciseId='.$data['iid'];

                                $_SESSION[$session_key] = true;
                                header("Location: $url");
                                exit;
                            }
                        }
                    }
                }
            }
        }

        /* Auto launch code */
        $showAutoLaunchLpWarning = false;
        $auto_launch = api_get_course_setting('enable_lp_auto_launch');
        if (!empty($auto_launch)) {
            $session_id = api_get_session_id();
            //LP list
            if ($auto_launch == 2) {
                if (api_is_platform_admin() || api_is_allowed_to_edit()) {
                    $showAutoLaunchLpWarning = true;
                } else {
                    $session_key = 'lp_autolunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
                    if (!isset($_SESSION[$session_key])) {
                        //redirecting to the LP
                        $url = api_get_path(WEB_CODE_PATH).'newscorm/lp_controller.php?'.api_get_cidreq().'&id_session='.$session_id;
                        $_SESSION[$session_key] = true;
                        header("Location: $url");
                        exit;
                    }
                }
            } else {
                $lp_table = Database::get_course_table(TABLE_LP_MAIN);
                $course_id = api_get_course_int_id();
                $condition = '';
                if (!empty($session_id)) {
                    $condition =  api_get_session_condition($session_id);
                    $sql = "SELECT id FROM $lp_table WHERE c_id = $course_id AND autolunch = 1 $condition LIMIT 1";
                    $result = Database::query($sql);
                    //If we found nothing in the session we just called the session_id =  0 autolunch
                    if (Database::num_rows($result) ==  0) {
                        $condition = '';
                    } else {
                        //great, there is an specific auto lunch for this session we leave the $condition
                    }
                }

                $sql = "SELECT id FROM $lp_table WHERE c_id = $course_id AND autolunch = 1 $condition LIMIT 1";
                $result = Database::query($sql);
                if (Database::num_rows($result) >  0) {
                    $lp_data = Database::fetch_array($result,'ASSOC');
                    if (!empty($lp_data['id'])) {
                        if (api_is_platform_admin() || api_is_allowed_to_edit()) {
                            $showAutoLaunchLpWarning = true;
                        } else {
                            $session_key = 'lp_autolunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
                            if (!isset($_SESSION[$session_key])) {
                                //redirecting to the LP
                                $url = api_get_path(WEB_CODE_PATH).'newscorm/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$lp_data['id'];

                                $_SESSION[$session_key] = true;
                                header("Location: $url");
                                exit;
                            }
                        }
                    }
                }
            }
        }

        return array(
            'show_autolaunch_exercise_warning' => $showAutoLaunchExerciseWarning,
            'show_autolaunch_lp_warning' => $showAutoLaunchLpWarning
        );
    }

    /**
     * @param string $courseCode
     * @param string $fileName
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function getFileAction($courseCode, $fileName)
    {
        $courseInfo = api_get_course_info($courseCode);
        $sessionId = $this->getRequest()->get('id_session');

        $docId = \DocumentManager::get_document_id($courseInfo, "/".$fileName);

        $filePath = null;

        if ($docId) {
            $isVisible = \DocumentManager::is_visible_by_id($docId, $courseInfo, $sessionId, api_get_user_id());
            $documentData = \DocumentManager::get_document_data_by_id($docId, $courseCode);
            $filePath = $documentData['absolute_path'];
            event_download($filePath);
        }

        if (!api_is_allowed_to_edit() && !$isVisible) {
            $this->abort(500);
        }
        return $this->sendFile($filePath);
    }

    /**
     * @Route("/show/{iconId}")
     * @Method({"GET"})
     * @param $iconId
     * @return null|string
     */
    public function showIconAction($iconId)
    {
        if (!$this->isCourseTeacher()) {
            return $this->abort(404);
        }
        $entityManager = $this->getManager();
        $criteria = array('cId' => api_get_course_int_id(), 'id' => $iconId);
        $tool = $this->getRepository('Entity\CTool')->findOneBy($criteria);
        if ($tool) {
            $tool->setVisibility(1);
        }
        $entityManager->persist($tool);
        //$entityManager->flush();
        return Display::return_message(get_lang('Visible'), 'confirmation');
    }

    /**
     * @Route("/hide/{iconId}")
     * @Method({"GET"})
     * @param $iconId
     * @return null|string
     */
    public function hideIconAction($iconId)
    {
        if (!$this->isCourseTeacher()) {
            return $this->abort(404);
        }

        $entityManager = $this->getManager();
        $criteria = array('cId' => api_get_course_int_id(), 'id' => $iconId);
        $tool = $this->getRepository('Entity\CTool')->findOneBy($criteria);
        if ($tool) {
            $tool->setVisibility(0);
        }
        $entityManager->persist($tool);
        //$entityManager->flush();
        return Display::return_message(get_lang('ToolIsNowHidden'), 'confirmation');
    }

    /**
     * @Route("/delete/{iconId}")
     * @Method({"GET"})
     * @param $iconId
     * @return null|string
     */
    public function deleteIcon($iconId)
    {
        if (!$this->isCourseTeacher()) {
            return $this->abort(404);
        }

        $entityManager = $this->getManager();
        $criteria = array('cId' => api_get_course_int_id(), 'id' => $iconId, 'added_tool' => 1);
        $tool = $this->getRepository('Entity\CTool')->findOneBy($criteria);
        $entityManager->remove($tool);
        //$entityManager->flush();
        return Display::return_message(get_lang('Deleted'), 'confirmation');
    }

    /**
     * @Route("/icon_list")
     * @Method({"GET"})
     */
    public function iconListAction()
    {
        /*$roles = $this->getUser()->getRoles();
        foreach ($roles as $role) {
            var_dump($role->getRole());
        }*/

        if (!$this->isCourseTeacher()) {
            return $this->abort(404);
        }
        $sessionId = intval($this->getRequest()->get('id_session'));
        $itemsFromSession = array();
        if (!empty($sessionId)) {

            $query = $this->getManager()->createQueryBuilder('a');
            $query->select('s');
            $query->from('Entity\CTool', 's');
            $query->where('s.cId  = :courseId AND s.sessionId = :sessionId')
                ->setParameters(
                    array(
                        'courseId' => $this->getCourse()->getId(),
                        'sessionId' => $sessionId
                    )
                );
            $itemsFromSession = $query->getQuery()->getResult();

            $itemNameList = array();
            foreach ($itemsFromSession as $item) {
                $itemNameList[] = $item->getName();
            }

            //$itemsFromSession = $this->getRepository()->findBy($criteria);
            $query = $this->getManager()->createQueryBuilder('a');
            $query->select('s');
            $query->from('Entity\CTool', 's');
            $query->where('s.cId  = :courseId AND s.sessionId = 0')
                ->setParameters(
                    array(
                        'courseId' => $this->getCourse()->getId()
                    )
                );
            if (!empty($itemNameList)) {
                $query->andWhere($query->expr()->notIn('s.name', $itemNameList));
            }
            $itemsFromCourse = $query->getQuery()->getResult();
        } else {
            $criteria = array('cId' => $this->getCourse()->getId(), 'sessionId' => 0);
            $itemsFromCourse = $this->getRepository()->findBy($criteria);
        }

        $this->getTemplate()->assign('items_from_course', $itemsFromCourse);
        $this->getTemplate()->assign('items_from_session', $itemsFromSession);
        $this->getTemplate()->assign('links', $this->generateLinks());
        return $this->get('template')->render_template($this->getTemplatePath().'tool/list.tpl');
    }

    /**
     *
     * @Route("/{itemName}/add")
     * @Method({"GET|POST"})
     * @param $itemName
     * @return mixed
     */
    public function addIconAction($itemName)
    {
        if (!$this->isCourseTeacher()) {
            return $this->abort(404);
        }

        $sessionId = intval($this->getRequest()->get('id_session'));

        if (empty($sessionId)) {
            return $this->abort(500);
        }

        $criteria = array('cId' => $this->getCourse()->getId(), 'sessionId' => 0, 'name' => $itemName);
        $itemFromDatabase = $this->getRepository()->findOneBy($criteria);

        if (!$itemFromDatabase) {
            $this->createNotFoundException();
        }
        /** @var CTool $item */
        $item = clone $itemFromDatabase;
        $item->setId(null);
        $item->setSessionId($sessionId);
        $form = $this->createForm($this->getFormType(), $item);

        $form->handleRequest($this->getRequest());

        if ($form->isValid()) {

            $query = $this->getManager()->createQueryBuilder('a');
            $query->select('MAX(s.id) as id');
            $query->from('Entity\CTool', 's');
            $query->where('s.cId  = :courseId')->setParameter('courseId', $this->getCourse()->getId());
            $result = $query->getQuery()->getArrayResult();
            $maxId = $result[0]['id'] + 1;
            $item->setId($maxId);

            $entityManager = $this->getManager();
            $entityManager->persist($item);
            $entityManager->flush();
            $customIcon = $item->getCustomIcon();
            if (!empty($customIcon)) {
                $item->createGrayIcon($this->get('imagine'));
            }

            $this->get('session')->getFlashBag()->add('success', "Added");
            $url = $this->generateUrl('course_home.controller:iconListAction', array('id_session' => $sessionId));
            return $this->redirect($url);
        }

        $this->getTemplate()->assign('item', $item);
        $this->getTemplate()->assign('form', $form->createView());
        $this->getTemplate()->assign('links', $this->generateLinks());
        return $this->get('template')->render_template($this->getTemplatePath().'tool/add.tpl');

    }

    /**
     * @Route("/{itemId}/edit")
     * @Method({"GET"})
     */
    public function editIconAction($itemId)
    {
        if (!$this->isCourseTeacher()) {
            return $this->abort(404);
        }

        $sessionId = intval($this->getRequest()->get('id_session'));

        $criteria = array('cId' => $this->getCourse()->getId(), 'id' => $itemId);
        /** @var CTool $item */
        $item = $this->getRepository()->findOneBy($criteria);

        $form = $this->createForm($this->getFormType(), $item);
        $form->handleRequest($this->getRequest());

        if ($form->isValid()) {
            $entityManager = $this->getManager();
            $entityManager->persist($item);
            $entityManager->flush();

            $customIcon = $item->getCustomIcon();
            if (!empty($customIcon)) {
                $item->createGrayIcon($this->get('imagine'));
            }

            $this->get('session')->getFlashBag()->add('success', "Updated");
            $url = $this->generateUrl('course_home.controller:iconListAction', array('id_session' => $sessionId));
            return $this->redirect($url);
        }

        $this->getTemplate()->assign('item', $item);
        $this->getTemplate()->assign('form', $form->createView());
        $this->getTemplate()->assign('links', $this->generateLinks());
        return $this->get('template')->render_template($this->getTemplatePath().'tool/edit.tpl');
    }

    /**
     * @Route("/{itemId}/delete")
     * @Method({"GET"})
     */
    public function deleteIconAction($itemId)
    {
        if (!$this->isCourseTeacher()) {
            return $this->abort(404);
        }

        $criteria = array('cId' => $this->getCourse()->getId(), 'id' => $itemId);

        /** @var CTool $item */
        $item = $this->getRepository()->findOneBy($criteria);
        $entityManager = $this->getManager();
        $sessionId = $item->getSessionId();
        if (!empty($sessionId)) {
            $entityManager->remove($item);
        } else {
            $item->setCustomIcon(null);
            $entityManager->persist($item);
        }
        $entityManager->flush();
        $this->get('session')->getFlashBag()->add('success', "Deleted");

        $this->getTemplate()->assign('links', $this->generateLinks());
        $url = $this->generateUrl('course_home.controller:iconListAction');
        return $this->redirect($url);
    }

    protected function getControllerAlias()
    {
        return 'course_home.controller';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplatePath()
    {
        return 'tool/course_home/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('Entity\CTool');
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
        return new CTool();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new CourseHomeToolType();
    }
}
