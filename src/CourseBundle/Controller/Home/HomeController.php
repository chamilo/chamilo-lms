<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Controller\Home;

use Chamilo\CourseBundle\Controller\ToolBaseController;
use Chamilo\CourseBundle\Entity\CTool;
use CourseHome;
use Display;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HomeController.
 *
 * @package Chamilo\CourseBundle\Controller\Home
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @Route("/")
 */
class HomeController extends ToolBaseController
{
    /**
     * @Route("/", name="course_home")
     * @Route("/index.php", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $sessionId = api_get_session_id();
        $course = $this->getCourse();
        $courseCode = $course->getId();
        $result = $this->autoLaunch();

        $showAutoLaunchLpWarning = $result['show_autolaunch_lp_warning'];
        $showAutoLaunchExerciseWarning = $result['show_autolaunch_exercise_warning'];

        if ($showAutoLaunchLpWarning) {
            $this->addFlash(
                'warning',
                $this->trans('TheLPAutoLaunchSettingIsONStudentsWillBeRedirectToAnSpecificLP')
            );
        }

        if ($showAutoLaunchExerciseWarning) {
            $this->addFlash(
                'warning',
                $this->trans('TheExerciseAutoLaunchSettingIsONStudentsWillBeRedirectToAnSpecificExercise')
            );
        }

        if (true) {
            $editIcons = Display::url(
                Display::return_icon('edit.png'),
                $this->generateUrl(
                    'chamilo_course_home_home_iconlist',
                    [
                        'course' => api_get_course_id(),
                    ]
                )
            );
        }

        $isSpecialCourse = \CourseManager::isSpecialCourse($courseCode);

        if ($isSpecialCourse) {
            $user = $this->getUser();
            if (!empty($user)) {
                $userId = $this->getUser()->getId();
                $autoreg = $request->get('autoreg');
                if ($autoreg == 1) {
                    \CourseManager::subscribe_user(
                        $userId,
                        $courseCode,
                        STUDENT
                    );
                }
            }
        }

        $homeView = api_get_setting('course.homepage_view');

        if ($homeView == 'activity' || $homeView == 'activity_big') {
            $blocks = $this->renderActivityView();
        } elseif ($homeView == '2column') {
            $result = $this->render2ColumnView();
        } elseif ($homeView == '3column') {
            $result = $this->render3ColumnView();
        } elseif ($homeView == 'vertical_activity') {
            $result = $this->renderVerticalActivityView();
        }

        $toolList = $result['tool_list'];

        $introduction = Display::return_introduction_section(
            TOOL_COURSE_HOMEPAGE,
            $toolList
        );

        $sessionInfo = null;
        if (api_get_setting('session.show_session_data') == 'true' && $sessionId) {
            $sessionInfo = CourseHome::show_session_data($sessionId);
        }

        return $this->render(
            'ChamiloCourseBundle:Home:index.html.twig',
            [
                'course' => $course,
                'session_info' => $sessionInfo,
                'icons' => $result['content'],
                'blocks' => $blocks,
                'edit_icons' => $editIcons,
                'introduction_text' => $introduction,
                'exercise_warning' => null,
                'lp_warning' => null,
            ]
        );
    }

    /**
     * @param string $courseCode
     * @param string $fileName
     *
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
     * @Route("/show/{iconId}", methods={"GET"})
     *
     * @param $iconId
     *
     * @return string|null
     */
    public function showIconAction($iconId)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $criteria = ['cId' => api_get_course_int_id(), 'id' => $iconId];
        $tool = $this->getRepository(
            'Chamilo\CourseBundle\Entity\CTool'
        )->findOneBy($criteria);
        if ($tool) {
            $tool->setVisibility(1);
        }
        $entityManager->persist($tool);
        //$entityManager->flush();
        return Display::return_message(get_lang('Visible'), 'confirmation');
    }

    /**
     * @Route("/hide/{iconId}", methods={"GET"})
     *
     * @param $iconId
     *
     * @return string|null
     */
    public function hideIconAction($iconId)
    {
        if (!$this->isCourseTeacher()) {
            return $this->abort(404);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $criteria = ['cId' => api_get_course_int_id(), 'id' => $iconId];
        $tool = $this->getRepository(
            'Chamilo\CourseBundle\Entity\CTool'
        )->findOneBy($criteria);
        if ($tool) {
            $tool->setVisibility(0);
        }
        $entityManager->persist($tool);
        //$entityManager->flush();
        return Display::return_message(get_lang('ToolIsNowHidden'), 'confirmation');
    }

    /**
     * @Route("/delete/{iconId}", methods={"GET"})
     *
     * @param $iconId
     *
     * @return string|null
     */
    public function deleteIcon($iconId)
    {
        if (!$this->isCourseTeacher()) {
            return $this->abort(404);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $criteria = ['cId' => api_get_course_int_id(), 'id' => $iconId, 'added_tool' => 1];
        $tool = $this->getRepository(
            'Chamilo\CourseBundle\Entity\CTool'
        )->findOneBy($criteria);
        $entityManager->remove($tool);
        //$entityManager->flush();
        return Display::return_message(get_lang('Deleted'), 'confirmation');
    }

    /**
     * @Route("/icon_list", methods={"GET"})
     *
     * @param Request $request
     */
    public function iconListAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $this->getDoctrine()->getRepository('ChamiloCourseBundle:CTool');

        $sessionId = intval($request->get('id_session'));
        $itemsFromSession = [];
        if (!empty($sessionId)) {
            $query = $repo->createQueryBuilder('a');
            $query->select('s');
            $query->from('Chamilo\CourseBundle\Entity\CTool', 's');
            $query->where('s.cId  = :courseId AND s.sessionId = :sessionId')
                ->setParameters(
                    [
                        'course' => $this->getCourse()->getId(),
                        'sessionId' => $sessionId,
                    ]
                );
            $itemsFromSession = $query->getQuery()->getResult();

            $itemNameList = [];
            foreach ($itemsFromSession as $item) {
                $itemNameList[] = $item->getName();
            }

            //$itemsFromSession = $this->getRepository()->findBy($criteria);
            $query = $repo->createQueryBuilder('a');
            $query->select('s');
            $query->from('Chamilo\CourseBundle\Entity\CTool', 's');
            $query->where('s.cId  = :courseId AND s.sessionId = 0')
                ->setParameters(
                    [
                        'courseId' => $this->getCourse()->getId(),
                    ]
                );
            if (!empty($itemNameList)) {
                $query->andWhere($query->expr()->notIn('s.name', $itemNameList));
            }
            $itemsFromCourse = $query->getQuery()->getResult();
        } else {
            $criteria = ['cId' => $this->getCourse()->getId(), 'sessionId' => 0];
            $itemsFromCourse = $repo->findBy($criteria);
        }

        return $this->render(
            '@ChamiloCourse/Home/list.html.twig',
            [
                'items_from_course' => $itemsFromCourse,
                'items_from_session' => $itemsFromSession,
                'links' => '',
            ]
        );
    }

    /**
     * @Route("/{itemName}/add", methods={"GET", "POST"})
     *
     * @param $itemName
     *
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

        $criteria = ['cId' => $this->getCourse()->getId(), 'sessionId' => 0, 'name' => $itemName];
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
            $query = $this->getDoctrine()->getManager()->createQueryBuilder('a');
            $query->select('MAX(s.id) as id');
            $query->from('Chamilo\CourseBundle\Entity\CTool', 's');
            $query->where('s.cId  = :courseId')->setParameter('courseId', $this->getCourse()->getId());
            $result = $query->getQuery()->getArrayResult();
            $maxId = $result[0]['id'] + 1;
            $item->setId($maxId);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($item);
            $entityManager->flush();
            $customIcon = $item->getCustomIcon();
            if (!empty($customIcon)) {
                $item->createGrayIcon($this->get('imagine'));
            }

            $this->get('session')->getFlashBag()->add('success', "Added");
            $url = $this->generateUrl('course_home.controller:iconListAction', ['id_session' => $sessionId]);

            return $this->redirect($url);
        }

        $this->getTemplate()->assign('item', $item);
        $this->getTemplate()->assign('form', $form->createView());
        $this->getTemplate()->assign('links', $this->generateLinks());

        return $this->render('@ChamiloCourse/Home/add.html.twig');
    }

    /**
     * @Route("/{itemId}/edit", methods={"GET"})
     */
    public function editIconAction($itemId)
    {
        if (!$this->isCourseTeacher()) {
            return $this->abort(404);
        }

        $sessionId = intval($this->getRequest()->get('id_session'));

        $criteria = ['cId' => $this->getCourse()->getId(), 'id' => $itemId];
        /** @var CTool $item */
        $item = $this->getRepository()->findOneBy($criteria);

        $form = $this->createForm($this->getFormType(), $item);
        $form->handleRequest($this->getRequest());

        if ($form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($item);
            $entityManager->flush();

            $customIcon = $item->getCustomIcon();
            if (!empty($customIcon)) {
                $item->createGrayIcon($this->get('imagine'));
            }

            $this->get('session')->getFlashBag()->add('success', "Updated");
            $url = $this->generateUrl('course_home.controller:iconListAction', ['id_session' => $sessionId]);

            return $this->redirect($url);
        }

        $this->getTemplate()->assign('item', $item);
        $this->getTemplate()->assign('form', $form->createView());
        $this->getTemplate()->assign('links', $this->generateLinks());

        return $this->render('@ChamiloCourse/Home/edit.html.twig');
    }

    /**
     * @Route("/{itemId}/delete", methods={"GET"})
     */
    public function deleteIconAction($itemId)
    {
        if (!$this->isCourseTeacher()) {
            return $this->abort(404);
        }

        $criteria = ['cId' => $this->getCourse()->getId(), 'id' => $itemId];

        /** @var CTool $item */
        $item = $this->getRepository()->findOneBy($criteria);
        $entityManager = $this->getDoctrine()->getManager();
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

    /**
     * @param string $title
     * @param string $content
     * @param string $class
     *
     * @return string
     */
    private function return_block($title, $content, $class = null)
    {
        $html = '<div class="row">
                <div class="col-xs-12 col-md-12">
                    <div class="title-tools">'.$title.'</div>
                </div>
            </div>
            <div class="row '.$class.'">'.$content.'</div>';

        return $html;
    }

    /**
     * @return array
     */
    private function renderActivityView()
    {
        $session_id = api_get_session_id();
        $urlGenerator = $this->get('router');
        $content = '';

        $enabled = api_get_plugin_setting('courselegal', 'tool_enable');
        $pluginExtra = null;
        if ($enabled === 'true') {
            /*require_once api_get_path(SYS_PLUGIN_PATH).'courselegal/config.php';
            $plugin = CourseLegalPlugin::create();
            $pluginExtra = $plugin->getTeacherLink();*/
        }

        // Start of tools for CourseAdmins (teachers/tutors)
        $totalList = [];

        // Start of tools for CourseAdmins (teachers/tutors)
        if ($session_id === 0 && api_is_course_admin() && api_is_allowed_to_edit(null, true)) {
            $content .= '<div class="alert alert-success" style="border:0px; margin-top: 0px;padding:0px;">
                <div class="normal-message" id="id_normal_message" style="display:none">';
            $content .= '<img src="'.api_get_path(WEB_PATH).'main/inc/lib/javascript/indicator.gif"/>&nbsp;&nbsp;';
            $content .= get_lang('PleaseStandBy');
            $content .= '</div>
                <div class="alert alert-success" id="id_confirmation_message" style="display:none"></div>
            </div>';

            $content .= $pluginExtra;

            if (api_get_setting('show_session_data') == 'true' && $session_id > 0) {
                $content .= '
                <div class="row">
                    <div class="col-xs-12 col-md-12">
                        <span class="viewcaption">'.get_lang('SessionData').'</span>
                        <table class="course_activity_home">'.
                            CourseHome::show_session_data($session_id).'
                        </table>
                    </div>
                </div>';
            }

            $my_list = CourseHome::get_tools_category(TOOL_AUTHORING);

            $blocks[] = [
                'title' => get_lang('Authoring'),
                'class' => 'course-tools-author',
                'content' => CourseHome::show_tools_category($my_list),
            ];

            $list1 = CourseHome::get_tools_category(TOOL_INTERACTION);
            $list2 = CourseHome::get_tools_category(TOOL_COURSE_PLUGIN);
            $my_list = array_merge($list1, $list2);

            $blocks[] = [
                'title' => get_lang('Interaction'),
                'class' => 'course-tools-interaction',
                'content' => CourseHome::show_tools_category($my_list),
            ];

            $my_list = CourseHome::get_tools_category(TOOL_ADMIN_PLATFORM);

            $blocks[] = [
                'title' => get_lang('Administration'),
                'class' => 'course-tools-administration',
                'content' => CourseHome::show_tools_category($my_list),
            ];
        } elseif (api_is_coach()) {
            $content .= $pluginExtra;
            if (api_get_setting('show_session_data') === 'true' && $session_id > 0) {
                $content .= '<div class="row">
                    <div class="col-xs-12 col-md-12">
                    <span class="viewcaption">'.get_lang('SessionData').'</span>
                    <table class="course_activity_home">';
                $content .= CourseHome::show_session_data($session_id);
                $content .= '</table></div></div>';
            }

            $my_list = CourseHome::get_tools_category(TOOL_STUDENT_VIEW);

            $blocks[] = [
                'content' => CourseHome::show_tools_category($my_list),
            ];

            $sessionsCopy = api_get_setting('allow_session_course_copy_for_teachers');
            if ($sessionsCopy === 'true') {
                // Adding only maintenance for coaches.
                $myList = CourseHome::get_tools_category(TOOL_ADMIN_PLATFORM);
                $onlyMaintenanceList = [];

                foreach ($myList as $item) {
                    if ($item['name'] === 'course_maintenance') {
                        $item['link'] = 'course_info/maintenance_coach.php';

                        $onlyMaintenanceList[] = $item;
                    }
                }

                $blocks[] = [
                    'title' => get_lang('Administration'),
                    'content' => CourseHome::show_tools_category($onlyMaintenanceList),
                ];
            }
        } else {
            $tools = CourseHome::get_tools_category(TOOL_STUDENT_VIEW);

            $isDrhOfCourse = \CourseManager::isUserSubscribedInCourseAsDrh(
                api_get_user_id(),
                api_get_course_info()
            );

            // Force user icon for DRH
            if ($isDrhOfCourse) {
                $addUserTool = true;
                foreach ($tools as $tool) {
                    if ($tool['name'] === 'user') {
                        $addUserTool = false;
                        break;
                    }
                }

                if ($addUserTool) {
                    $tools[] = [
                        'c_id' => api_get_course_int_id(),
                        'name' => 'user',
                        'link' => 'user/user.php',
                        'image' => 'members.gif',
                        'visibility' => '1',
                        'admin' => '0',
                        'address' => 'squaregrey.gif',
                        'added_tool' => '0',
                        'target' => '_self',
                        'category' => 'interaction',
                        'session_id' => api_get_session_id(),
                    ];
                }
            }

            if (count($tools) > 0) {
                $blocks[] = ['content' => CourseHome::show_tools_category($tools)];
            }

            if ($isDrhOfCourse) {
                $drhTool = CourseHome::get_tools_category(TOOL_DRH);
                $blocks[] = ['content' => CourseHome::show_tools_category($drhTool)];
            }
        }

        return $blocks;
    }

    private function render2ColumnView()
    {
    }

    private function render3ColumnView()
    {
    }

    private function renderVerticalActivityView()
    {
    }

    /**
     * @return array
     */
    private function autoLaunch()
    {
        return;
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
                    $sessionData = Session::read($session_key);
                    if (!isset($sessionData)) {
                        //redirecting to the Exercise
                        $url = api_get_path(WEB_CODE_PATH).'exercise/exercice.php?'.api_get_cidreq().'&id_session='.$session_id;
                        $_SESSION[$session_key] = true;

                        header("Location: $url");
                        exit;
                    }
                }
            } else {
                $table = \Database::get_course_table(TABLE_QUIZ_TEST);
                $course_id = api_get_course_int_id();
                $condition = '';
                if (!empty($session_id)) {
                    $condition = api_get_session_condition($session_id);
                    $sql = "SELECT iid FROM $table
                            WHERE c_id = $course_id AND autolaunch = 1 $condition
                            LIMIT 1";
                    $result = \Database::query($sql);
                    //If we found nothing in the session we just called the session_id =  0 autolaunch
                    if (\Database::num_rows($result) == 0) {
                        $condition = '';
                    } else {
                        //great, there is an specific auto lunch for this session we leave the $condition
                    }
                }

                $sql = "SELECT iid FROM $table
                        WHERE c_id = $course_id AND autolaunch = 1 $condition
                        LIMIT 1";
                $result = \Database::query($sql);
                if (\Database::num_rows($result) > 0) {
                    $data = \Database::fetch_array($result, 'ASSOC');
                    if (!empty($data['iid'])) {
                        if (api_is_platform_admin() || api_is_allowed_to_edit()) {
                            $showAutoLaunchExerciseWarning = true;
                        } else {
                            $session_key = 'exercise_autolunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
                            if (!isset($_SESSION[$session_key])) {
                                //redirecting to the LP
                                $url = api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.api_get_cidreq().'&exerciseId='.$data['iid'];

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
                        $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&id_session='.$session_id;
                        $_SESSION[$session_key] = true;
                        header("Location: $url");
                        exit;
                    }
                }
            } else {
                $lp_table = \Database::get_course_table(TABLE_LP_MAIN);
                $course_id = api_get_course_int_id();
                $condition = '';
                if (!empty($session_id)) {
                    $condition = api_get_session_condition($session_id);
                    $sql = "SELECT id FROM $lp_table WHERE c_id = $course_id AND autolunch = 1 $condition LIMIT 1";
                    $result = \Database::query($sql);
                    //If we found nothing in the session we just called the session_id =  0 autolunch
                    if (\Database::num_rows($result) == 0) {
                        $condition = '';
                    } else {
                        //great, there is an specific auto lunch for this session we leave the $condition
                    }
                }

                $sql = "SELECT id FROM $lp_table
                        WHERE c_id = $course_id AND autolunch = 1 $condition
                        LIMIT 1";
                $result = \Database::query($sql);
                if (\Database::num_rows($result) > 0) {
                    $lp_data = \Database::fetch_array($result, 'ASSOC');
                    if (!empty($lp_data['id'])) {
                        if (api_is_platform_admin() || api_is_allowed_to_edit()) {
                            $showAutoLaunchLpWarning = true;
                        } else {
                            $session_key = 'lp_autolunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
                            if (!isset($_SESSION[$session_key])) {
                                //redirecting to the LP
                                $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$lp_data['id'];

                                $_SESSION[$session_key] = true;
                                header("Location: $url");
                                exit;
                            }
                        }
                    }
                }
            }
        }

        return [
            'show_autolaunch_exercise_warning' => $showAutoLaunchExerciseWarning,
            'show_autolaunch_lp_warning' => $showAutoLaunchLpWarning,
        ];
    }
}
