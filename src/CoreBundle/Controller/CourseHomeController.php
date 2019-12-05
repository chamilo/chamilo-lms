<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\ToolChain;
use Chamilo\CourseBundle\Controller\ToolBaseController;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilosession as Session;
use CourseHome;
use CourseManager;
use Database;
use Display;
use Event;
use ExtraFieldValue;
use Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CourseHomeController.
 *
 * @author Julio Montoya <gugli100@gmail.com>
 *
 * @Route("/courses")
 */
class CourseHomeController extends ToolBaseController
{
    /**
     * @Route("/{cid}/home", name="chamilo_core_course_home")
     *
     * @Entity("course", expr="repository.find(cid)")
     */
    public function indexAction(Request $request, ToolChain $toolChain)
    {
        $course = $this->getCourse();
        $result = $this->autoLaunch();
        $js = '<script>'.api_get_language_translate_html().'</script>';
        $htmlHeadXtra[] = $js;

        $userId = $this->getUser()->getId();
        $courseCode = $course->getCode();
        $courseId = $course->getId();
        $sessionId = $this->getSessionId();
        $showMessage = '';

        if (api_is_invitee()) {
            $isInASession = $sessionId > 0;
            $isSubscribed = CourseManager::is_user_subscribed_in_course(
                $userId,
                $courseCode,
                $isInASession,
                $sessionId
            );

            if (!$isSubscribed) {
                api_not_allowed(true);
            }
        }

        // Deleting group session
        Session::erase('toolgroup');
        Session::erase('_gid');

        $isSpecialCourse = CourseManager::isSpecialCourse($courseId);

        if ($isSpecialCourse) {
            if (isset($_GET['autoreg']) && $_GET['autoreg'] == 1) {
                if (CourseManager::subscribeUser($userId, $courseCode, STUDENT)) {
                    Session::write('is_allowed_in_course', true);
                }
            }
        }

        $action = !empty($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';

        if ($action == 'subscribe') {
            if (Security::check_token('get')) {
                Security::clear_token();
                $result = CourseManager::autoSubscribeToCourse($courseCode);
                if ($result) {
                    if (CourseManager::is_user_subscribed_in_course($userId, $courseCode)) {
                        Session::write('is_allowed_in_course', true);
                    }
                }
                header('Location: '.api_get_self());
                exit;
            }
        }

        /*	Is the user allowed here? */
        api_protect_course_script(true);

        /*  STATISTICS */
        if (!isset($coursesAlreadyVisited[$courseCode])) {
            Event::accessCourse();
            $coursesAlreadyVisited[$courseCode] = 1;
            Session::write('coursesAlreadyVisited', $coursesAlreadyVisited);
        }

        $logInfo = [
            'tool' => 'course-main',
            'action' => $action,
        ];
        Event::registerLog($logInfo);

        /* Auto launch code */
        $autoLaunchWarning = '';
        $showAutoLaunchLpWarning = false;
        $course_id = api_get_course_int_id();
        $lpAutoLaunch = api_get_course_setting('enable_lp_auto_launch');
        $session_id = api_get_session_id();
        $allowAutoLaunchForCourseAdmins = api_is_platform_admin() || api_is_allowed_to_edit(true, true) || api_is_coach();

        if (!empty($lpAutoLaunch)) {
            if ($lpAutoLaunch == 2) {
                // LP list
                if ($allowAutoLaunchForCourseAdmins) {
                    $showAutoLaunchLpWarning = true;
                } else {
                    $session_key = 'lp_autolaunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
                    if (!isset($_SESSION[$session_key])) {
                        // Redirecting to the LP
                        $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq();
                        $_SESSION[$session_key] = true;
                        header("Location: $url");
                        exit;
                    }
                }
            } else {
                $lp_table = Database::get_course_table(TABLE_LP_MAIN);
                $condition = '';
                if (!empty($session_id)) {
                    $condition = api_get_session_condition($session_id);
                    $sql = "SELECT id FROM $lp_table
                            WHERE c_id = $course_id AND autolaunch = 1 $condition
                            LIMIT 1";
                    $result = Database::query($sql);
                    // If we found nothing in the session we just called the session_id =  0 autolaunch
                    if (Database::num_rows($result) == 0) {
                        $condition = '';
                    }
                }

                $sql = "SELECT id FROM $lp_table
                        WHERE c_id = $course_id AND autolaunch = 1 $condition
                        LIMIT 1";
                $result = Database::query($sql);
                if (Database::num_rows($result) > 0) {
                    $lp_data = Database::fetch_array($result, 'ASSOC');
                    if (!empty($lp_data['id'])) {
                        if ($allowAutoLaunchForCourseAdmins) {
                            $showAutoLaunchLpWarning = true;
                        } else {
                            $session_key = 'lp_autolaunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
                            if (!isset($_SESSION[$session_key])) {
                                // Redirecting to the LP
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

        if ($showAutoLaunchLpWarning) {
            $autoLaunchWarning = get_lang('The learning path auto-launch setting is ON. When learners enter this course, they will be automatically redirected to the learning path marked as auto-launch.');
        }

        $forumAutoLaunch = api_get_course_setting('enable_forum_auto_launch');
        if ($forumAutoLaunch == 1) {
            if ($allowAutoLaunchForCourseAdmins) {
                if (empty($autoLaunchWarning)) {
                    $autoLaunchWarning = get_lang('The forum\'s auto-launch setting is on. Students will be redirected to the forum tool when entering this course.');
                }
            } else {
                $url = api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq();
                header("Location: $url");
                exit;
            }
        }

        if (api_get_configuration_value('allow_exercise_auto_launch')) {
            $exerciseAutoLaunch = (int) api_get_course_setting('enable_exercise_auto_launch');
            if ($exerciseAutoLaunch == 2) {
                if ($allowAutoLaunchForCourseAdmins) {
                    if (empty($autoLaunchWarning)) {
                        $autoLaunchWarning = get_lang(
                            'TheExerciseAutoLaunchSettingIsONStudentsWillBeRedirectToTheExerciseList'
                        );
                    }
                } else {
                    // Redirecting to the document
                    $url = api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq();
                    header("Location: $url");
                    exit;
                }
            } elseif ($exerciseAutoLaunch == 1) {
                if ($allowAutoLaunchForCourseAdmins) {
                    if (empty($autoLaunchWarning)) {
                        $autoLaunchWarning = get_lang(
                            'TheExerciseAutoLaunchSettingIsONStudentsWillBeRedirectToAnSpecificExercise'
                        );
                    }
                } else {
                    // Redirecting to an exercise
                    $table = Database::get_course_table(TABLE_QUIZ_TEST);
                    $condition = '';
                    if (!empty($session_id)) {
                        $condition = api_get_session_condition($session_id);
                        $sql = "SELECT iid FROM $table
                        WHERE c_id = $course_id AND autolaunch = 1 $condition
                        LIMIT 1";
                        $result = Database::query($sql);
                        // If we found nothing in the session we just called the session_id = 0 autolaunch
                        if (Database::num_rows($result) == 0) {
                            $condition = '';
                        }
                    }

                    $sql = "SELECT iid FROM $table
                    WHERE c_id = $course_id AND autolaunch = 1 $condition
                    LIMIT 1";
                    $result = Database::query($sql);
                    if (Database::num_rows($result) > 0) {
                        $row = Database::fetch_array($result, 'ASSOC');
                        $exerciseId = $row['iid'];
                        $url = api_get_path(WEB_CODE_PATH).
                            'exercise/overview.php?exerciseId='.$exerciseId.'&'.api_get_cidreq();
                        header("Location: $url");
                        exit;
                    }
                }
            }
        }

        $documentAutoLaunch = api_get_course_setting('enable_document_auto_launch');
        if ($documentAutoLaunch == 1) {
            if ($allowAutoLaunchForCourseAdmins) {
                if (empty($autoLaunchWarning)) {
                    $autoLaunchWarning = get_lang('The document auto-launch feature configuration is enabled. Learners will be automatically redirected to document tool.');
                }
            } else {
                // Redirecting to the document
                $url = api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq();
                header("Location: $url");
                exit;
            }
        }

        // Used in different pages
        $tool_table = Database::get_course_table(TABLE_TOOL_LIST);

        /*	Introduction section (editable by course admins) */
        $content = Display::return_introduction_section(
            TOOL_COURSE_HOMEPAGE,
            [
                'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/',
                'CreateDocumentDir' => 'document/',
                'BaseHref' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/',
            ]
        );

        /*	SWITCH TO A DIFFERENT HOMEPAGE VIEW
            the setting homepage_view is adjustable through
            the platform administration section */
        if (!empty($autoLaunchWarning)) {
            $showMessage .= Display::return_message(
                $autoLaunchWarning,
                'warning'
            );
        }

        // Activity start
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();

        // Work with data post askable by admin of course
        if (api_is_platform_admin()) {
            // Show message to confirm that a tool it to be hidden from available tools
            // visibility 0,1->2
            if (!empty($_GET['askDelete'])) {
                $content .= '<div id="toolhide">'.get_lang('Do you really want to delete this link?').'<br />&nbsp;&nbsp;&nbsp;
                    <a href="'.api_get_self().'">'.get_lang('No').'</a>&nbsp;|&nbsp;
                    <a href="'.api_get_self().'?delete=yes&id='.$id.'">'.get_lang('Yes').'</a>
                </div>';
            } elseif (isset($_GET['delete']) && $_GET['delete']) {
                /*
                * Process hiding a tools from available tools.
                */
                Database::query("DELETE FROM $tool_table WHERE c_id = $course_id AND id='$id' AND added_tool=1");
            }
        }

        // Course legal
        $enabled = api_get_plugin_setting('courselegal', 'tool_enable');
        $pluginExtra = null;
        if ($enabled === 'true') {
            require_once api_get_path(SYS_PLUGIN_PATH).'courselegal/config.php';
            $plugin = \CourseLegalPlugin::create();
            $pluginExtra = $plugin->getTeacherLink();
        }

        // Start of tools for CourseAdmins (teachers/tutors)
        if ($session_id === 0 && api_is_course_admin() && api_is_allowed_to_edit(null, true)) {
            $content .= '<div class="alert alert-success" style="border:0px; margin-top: 0px;padding:0px;">
		<div class="normal-message" id="id_normal_message" style="display:none">';
            $content .= '<img src="'.api_get_path(WEB_PATH).'main/inc/lib/javascript/indicator.gif"/>&nbsp;&nbsp;';
            $content .= get_lang('Please stand by...');
            $content .= '</div>
		<div class="alert alert-success" id="id_confirmation_message" style="display:none"></div>
	</div>';
            $content .= $pluginExtra;
        } elseif (api_is_coach()) {
            $content .= $pluginExtra;
            if (api_get_setting('show_session_data') === 'true' && $session_id > 0) {
                $content .= '<div class="row">
            <div class="col-xs-12 col-md-12">
			<span class="viewcaption">'.get_lang('Session\'s data').'</span>
			<table class="course_activity_home">';
                $content .= CourseHome::show_session_data($session_id);
                $content .= '</table></div></div>';
            }
        }

        $blocks = CourseHome::getUserBlocks($toolChain);

        // Get session-career diagram
        $diagram = '';
        $allow = api_get_configuration_value('allow_career_diagram');
        if ($allow === true) {
            $htmlHeadXtra[] = api_get_js('jsplumb2.js');
            $extra = new ExtraFieldValue('session');
            $value = $extra->get_values_by_handler_and_field_variable(
                api_get_session_id(),
                'external_career_id'
            );

            if (!empty($value) && isset($value['value'])) {
                $careerId = $value['value'];
                $extraFieldValue = new ExtraFieldValue('career');
                $item = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
                    'external_career_id',
                    $careerId,
                    false,
                    false,
                    false
                );

                if (!empty($item) && isset($item['item_id'])) {
                    $careerId = $item['item_id'];
                    $career = new Career();
                    $careerInfo = $career->get($careerId);
                    if (!empty($careerInfo)) {
                        $extraFieldValue = new ExtraFieldValue('career');
                        $item = $extraFieldValue->get_values_by_handler_and_field_variable(
                            $careerId,
                            'career_diagram',
                            false,
                            false,
                            false
                        );

                        if (!empty($item) && isset($item['value']) && !empty($item['value'])) {
                            /** @var Graph $graph */
                            $graph = UnserializeApi::unserialize(
                                'career',
                                $item['value']
                            );
                            $diagram = Career::renderDiagram($careerInfo, $graph);
                        }
                    }
                }
            }
        }

        $content = '<div id="course_tools">'.$diagram.$content.'</div>';

        // Deleting the objects
        Session::erase('_gid');
        Session::erase('oLP');
        Session::erase('lpobject');
        api_remove_in_gradebook();
        \Exercise::cleanSessionVariables();
        \DocumentManager::removeGeneratedAudioTempFile();

        return $this->render(
            '@ChamiloTheme/Course/home.html.twig',
            [
                'course' => $course,
                'diagram' => $diagram,
               // 'session_info' => $sessionInfo,
                'icons' => $result['content'],
                'blocks' => $blocks,
                //'edit_icons' => $editIcons,
                //'introduction_text' => $introduction,
                'exercise_warning' => null,
                'lp_warning' => null,
            ]
        );
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
        return Display::return_message(get_lang('The tool is now invisible.'), 'confirmation');
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
     */
    public function iconListAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $this->getDoctrine()->getRepository('ChamiloCourseBundle:CTool');

        $sessionId = (int) $request->get('id_session');
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

        if ($form->isSubmitted() && $form->isValid()) {
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
                        $url = api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq();
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
                        $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq();
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
