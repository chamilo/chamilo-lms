<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Career;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\ToolChain;
use Chamilo\CourseBundle\Controller\ToolBaseController;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Manager\SettingsFormFactory;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Chamilo\CourseBundle\Repository\CToolRepository;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use CourseManager;
use Database;
use Display;
use Event;
use Exercise;
use ExtraFieldValue;
use Fhaculty\Graph\Graph;
use Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\ValidatorException;
use UnserializeApi;

/**
 * Class CourseHomeController.
 *
 * @author Julio Montoya <gugli100@gmail.com>
 *
 * @Route("/course")
 */
class CourseHomeController extends ToolBaseController
{
    /**
     * @Route("/{cid}/home.json", name="chamilo_core_course_home_json")
     *
     * @Entity("course", expr="repository.find(cid)")
     */
    public function indexJsonAction(Request $request, CToolRepository $toolRepository, CShortcutRepository $shortcutRepository, ToolChain $toolChain)
    {
        $course = $this->getCourse();
        if (null === $course) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);

        $session = $request->getSession();

        $userId = 0;
        $user = $this->getUser();
        if (null !== $user) {
            $userId = $user->getId();
        }

        $courseCode = $course->getCode();
        $courseId = $course->getId();
        $sessionId = $this->getSessionId();

        if ($user && INVITEE === $user->getStatus()) {
            $isInASession = $sessionId > 0;
            $isSubscribed = CourseManager::is_user_subscribed_in_course(
                $userId,
                $courseCode,
                $isInASession,
                $sessionId
            );

            if (!$isSubscribed) {
                throw $this->createAccessDeniedException();
            }
        }

        $isSpecialCourse = CourseManager::isSpecialCourse($courseId);

        if ($user && $isSpecialCourse && (isset($_GET['autoreg']) && 1 === (int) $_GET['autoreg']) &&
            CourseManager::subscribeUser($userId, $courseId, STUDENT)
        ) {
            $session->set('is_allowed_in_course', true);
        }

        /*$action = empty($_GET['action']) ? '' : Security::remove_XSS($_GET['action']);
        if ('subscribe' === $action && Security::check_token('get')) {
            Security::clear_token();
            $result = CourseManager::autoSubscribeToCourse($courseCode);
            if ($result && CourseManager::is_user_subscribed_in_course($userId, $courseCode)) {
                $session->set('is_allowed_in_course', true);
            }
            header('Location: '.api_get_self());
            exit;
        }

        $logInfo = [
            'tool' => 'course-main',
            'action' => $action,
        ];
        Event::registerLog($logInfo);*/
        $logInfo = [
            'tool' => 'course-main',
        ];
        Event::registerLog($logInfo);

        $qb = $toolRepository->getResourcesByCourse($course, $this->getSession());

        $qb->addSelect('tool');
        $qb->innerJoin('resource.tool', 'tool');

        $result = $qb->getQuery()->getResult();
        $tools = [];
        $isCourseTeacher = $this->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        /** @var CTool $item */
        foreach ($result as $item) {
            if ('course_tool' === $item->getName()) {
                continue;
            }
            $toolModel = $toolChain->getToolFromName($item->getTool()->getName());

            if (!$isCourseTeacher && 'admin' === $toolModel->getCategory()) {
                continue;
            }
            $tools[$toolModel->getCategory()][] = [
                'ctool' => $item,
                'tool' => $toolModel,
            ];
        }

        // Get session-career diagram
        $diagram = '';
        /*$allow = api_get_configuration_value('allow_career_diagram');
        if (true === $allow) {
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
                            0
                        );

                        if (!empty($item) && isset($item['value']) && !empty($item['value'])) {
                            // @var Graph $graph
                            $graph = UnserializeApi::unserialize('career', $item['value']);
                            $diagram = Career::renderDiagram($careerInfo, $graph);
                        }
                    }
                }
            }
        }*/

        // Deleting the objects
        $session->remove('toolgroup');
        $session->remove('_gid');
        $session->remove('oLP');
        $session->remove('lpobject');

        api_remove_in_gradebook();
        Exercise::cleanSessionVariables();

        $shortcuts = [];
        if (null !== $user) {
            $shortcutQuery = $shortcutRepository->getResources($course->getResourceNode());
            $shortcuts = $shortcutQuery->getQuery()->getResult();
        }
        $responseData = [
            'course' => $course,
            'shortcuts' => $shortcuts,
            'diagram' => $diagram,
            'tools' => $tools,
        ];

        $json = $this->get('serializer')->serialize(
            $responseData,
            'json',
            [
                'groups' => ['course:read', 'ctool:read', 'tool:read', 'cshortcut:read'],
            ]
        );

        return new Response(
            $json,
            Response::HTTP_OK,
            [
                'Content-type' => 'application/json',
            ]
        );
        /*return $this->render(
            '@ChamiloCore/Course/home.html.twig',
            [
                'course' => $course,
                'shortcuts' => $shortcuts,
                'diagram' => $diagram,
                'tools' => $tools,
            ]
        );*/
    }

    /**
     * Redirects the page to a tool, following the tools.yml settings.
     *
     * @Route("/{cid}/tool/{toolName}", name="chamilo_core_course_redirect_tool")
     */
    public function redirectTool(string $toolName, CToolRepository $repo, ToolChain $toolChain)
    {
        /** @var CTool|null $tool */
        $tool = $repo->findOneBy([
            'name' => $toolName,
        ]);

        if (null === $tool) {
            throw new NotFoundHttpException($this->trans('Tool not found'));
        }

        $tool = $toolChain->getToolFromName($tool->getTool()->getName());
        $link = $tool->getLink();

        if (strpos($link, 'nodeId')) {
            $nodeId = (string) $this->getCourse()->getResourceNode()->getId();
            $link = str_replace(':nodeId', $nodeId, $link);
        }

        $url = $link.'?'.$this->getCourseUrlQuery();

        return $this->redirect($url);
    }

    public function redirectToShortCut(string $toolName, CToolRepository $repo, ToolChain $toolChain)
    {
        /** @var CTool|null $tool */
        $tool = $repo->findOneBy([
            'name' => $toolName,
        ]);

        if (null === $tool) {
            throw new NotFoundHttpException($this->trans('Tool not found'));
        }

        $tool = $toolChain->getToolFromName($tool->getTool()->getName());
        $link = $tool->getLink();

        if (strpos($link, 'nodeId')) {
            $nodeId = (string) $this->getCourse()->getResourceNode()->getId();
            $link = str_replace(':nodeId', $nodeId, $link);
        }

        $url = $link.'?'.$this->getCourseUrlQuery();

        return $this->redirect($url);
    }

    /**
     * Edit configuration with given namespace.
     *
     * @Route("/{cid}/settings/{namespace}", name="chamilo_core_course_settings")
     *
     * @Entity("course", expr="repository.find(cid)")
     *
     * @return Response
     */
    public function updateSettingsAction(Request $request, Course $course, string $namespace, SettingsCourseManager $manager, SettingsFormFactory $formFactory)
    {
        $schemaAlias = $manager->convertNameSpaceToService($namespace);
        $settings = $manager->load($namespace);

        $form = $formFactory->create($schemaAlias);

        $form->setData($settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messageType = 'success';

            try {
                $manager->setCourse($course);
                $manager->save($form->getData());
                $message = $this->trans('Update');
            } catch (ValidatorException $validatorException) {
                $message = $this->trans($validatorException->getMessage());
                $messageType = 'error';
            }
            $this->addFlash($messageType, $message);

            if ($request->headers->has('referer')) {
                return $this->redirect($request->headers->get('referer'));
            }
        }

        $schemas = $manager->getSchemas();

        return $this->render(
            '@ChamiloCore/Course/settings.html.twig',
            [
                'course' => $course,
                'schemas' => $schemas,
                'settings' => $settings,
                'form' => $form->createView(),
            ]
        );
    }

    private function autoLaunch(): void
    {
        $autoLaunchWarning = '';
        $showAutoLaunchLpWarning = false;
        $course_id = api_get_course_int_id();
        $lpAutoLaunch = api_get_course_setting('enable_lp_auto_launch');
        $session_id = api_get_session_id();
        $allowAutoLaunchForCourseAdmins =
            api_is_platform_admin() ||
            api_is_allowed_to_edit(true, true) ||
            api_is_coach();

        if (!empty($lpAutoLaunch)) {
            if (2 === $lpAutoLaunch) {
                // LP list
                if ($allowAutoLaunchForCourseAdmins) {
                    $showAutoLaunchLpWarning = true;
                } else {
                    $session_key = 'lp_autolaunch_'.$session_id.'_'.$course_id.'_'.api_get_user_id();
                    if (!isset($_SESSION[$session_key])) {
                        // Redirecting to the LP
                        $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq();
                        $_SESSION[$session_key] = true;
                        header(sprintf('Location: %s', $url));
                        exit;
                    }
                }
            } else {
                $lp_table = Database::get_course_table(TABLE_LP_MAIN);
                $condition = '';
                if (!empty($session_id)) {
                    $condition = api_get_session_condition($session_id);
                    $sql = "SELECT id FROM {$lp_table}
                            WHERE c_id = {$course_id} AND autolaunch = 1 {$condition}
                            LIMIT 1";
                    $result = Database::query($sql);
                    // If we found nothing in the session we just called the session_id =  0 autolaunch
                    if (0 === Database::num_rows($result)) {
                        $condition = '';
                    }
                }

                $sql = "SELECT iid FROM {$lp_table}
                        WHERE c_id = {$course_id} AND autolaunch = 1 {$condition}
                        LIMIT 1";
                $result = Database::query($sql);
                if (Database::num_rows($result) > 0) {
                    $lp_data = Database::fetch_array($result, 'ASSOC');
                    if (!empty($lp_data['iid'])) {
                        if ($allowAutoLaunchForCourseAdmins) {
                            $showAutoLaunchLpWarning = true;
                        } else {
                            $session_key = 'lp_autolaunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
                            if (!isset($_SESSION[$session_key])) {
                                // Redirecting to the LP
                                $url = api_get_path(WEB_CODE_PATH).
                                    'lp/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$lp_data['iid'];

                                $_SESSION[$session_key] = true;
                                header(sprintf('Location: %s', $url));
                                exit;
                            }
                        }
                    }
                }
            }
        }

        if ($showAutoLaunchLpWarning) {
            $autoLaunchWarning = get_lang(
                'The learning path auto-launch setting is ON. When learners enter this course, they will be automatically redirected to the learning path marked as auto-launch.'
            );
        }

        $forumAutoLaunch = (int) api_get_course_setting('enable_forum_auto_launch');
        if (1 === $forumAutoLaunch) {
            if ($allowAutoLaunchForCourseAdmins) {
                if (empty($autoLaunchWarning)) {
                    $autoLaunchWarning = get_lang(
                        "The forum's auto-launch setting is on. Students will be redirected to the forum tool when entering this course."
                    );
                }
            } else {
                $url = api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq();
                header(sprintf('Location: %s', $url));
                exit;
            }
        }

        if (api_get_configuration_value('allow_exercise_auto_launch')) {
            $exerciseAutoLaunch = (int) api_get_course_setting('enable_exercise_auto_launch');
            if (2 === $exerciseAutoLaunch) {
                if ($allowAutoLaunchForCourseAdmins) {
                    if (empty($autoLaunchWarning)) {
                        $autoLaunchWarning = get_lang(
                            'TheExerciseAutoLaunchSettingIsONStudentsWillBeRedirectToTheExerciseList'
                        );
                    }
                } else {
                    // Redirecting to the document
                    $url = api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq();
                    header(sprintf('Location: %s', $url));
                    exit;
                }
            } elseif (1 === $exerciseAutoLaunch) {
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
                        $sql = "SELECT iid FROM {$table}
                                WHERE c_id = {$course_id} AND autolaunch = 1 {$condition}
                                LIMIT 1";
                        $result = Database::query($sql);
                        // If we found nothing in the session we just called the session_id = 0 autolaunch
                        if (0 === Database::num_rows($result)) {
                            $condition = '';
                        }
                    }

                    $sql = "SELECT iid FROM {$table}
                            WHERE c_id = {$course_id} AND autolaunch = 1 {$condition}
                            LIMIT 1";
                    $result = Database::query($sql);
                    if (Database::num_rows($result) > 0) {
                        $row = Database::fetch_array($result, 'ASSOC');
                        $exerciseId = $row['iid'];
                        $url = api_get_path(WEB_CODE_PATH).
                            'exercise/overview.php?exerciseId='.$exerciseId.'&'.api_get_cidreq();
                        header(sprintf('Location: %s', $url));
                        exit;
                    }
                }
            }
        }

        $documentAutoLaunch = (int) api_get_course_setting('enable_document_auto_launch');
        if (1 === $documentAutoLaunch) {
            if ($allowAutoLaunchForCourseAdmins) {
                if (empty($autoLaunchWarning)) {
                    $autoLaunchWarning = get_lang(
                        'The document auto-launch feature configuration is enabled. Learners will be automatically redirected to document tool.'
                    );
                }
            } else {
                // Redirecting to the document
                $url = api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq();
                header("Location: $url");
                exit;
            }
        }

        /*	SWITCH TO A DIFFERENT HOMEPAGE VIEW
         the setting homepage_view is adjustable through
         the platform administration section */
        if (!empty($autoLaunchWarning)) {
            $this->addFlash(
                'warning',
                Display::return_message(
                    $autoLaunchWarning,
                    'warning'
                )
            );
        }
    }
}
