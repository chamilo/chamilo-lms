<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Framework\PageController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserPortalController
 * author Julio Montoya <gugli100@gmail.com>.
 *
 * @Route("/userportal")
 *
 * @package Chamilo\CoreBundle\Controller
 */
class UserPortalController extends BaseController
{
    /**
     * @Route("/add_course", methods={"GET", "POST"}, name="add_course")
     *
     * @Security("has_role('ROLE_TEACHER')")
     *
     * @return Response
     */
    public function addCourseAction()
    {
        // "Course validation" feature. This value affects the way of a new course creation:
        // true  - the new course is requested only and it is created after approval;
        // false - the new course is created immediately, after filling this form.
        $courseValidation = false;
        if (api_get_setting('course.course_validation') === 'true' &&
            !api_is_platform_admin()
        ) {
            $courseValidation = true;
        }

        // Displaying the header.
        $tool_name = $courseValidation ? get_lang('CreateCourseRequest') : get_lang('CreateSite');

        if (api_get_setting('course.allow_users_to_create_courses') === 'false' &&
            !api_is_platform_admin()
        ) {
            api_not_allowed(true);
        }

        // Check access rights.
        if (!api_is_allowed_to_create_course()) {
            api_not_allowed(true);
        }

        $url = $this->generateUrl('add_course');

        // Build the form.
        $form = new \FormValidator('add_course', 'post', $url);

        // Form title
        $form->addHeader($tool_name);

        // Title
        $form->addElement(
            'text',
            'title',
            [
                get_lang('CourseName'),
                get_lang('Ex'),
            ],
            [
                'id' => 'title',
            ]
        );
        $form->applyFilter('title', 'html_filter');
        $form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');

        $form->addButtonAdvancedSettings('advanced_params');
        $form->addElement(
            'html',
            '<div id="advanced_params_options" style="display:none">'
        );

        // Category category.
        $url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_category';

        $form->addElement(
            'select_ajax',
            'category_code',
            get_lang('CourseFaculty'),
            null,
            ['url' => $url]
        );

        // Course code
        $form->addText(
            'wanted_code',
            [
                get_lang('Code'),
                get_lang('OnlyLettersAndNumbers'),
            ],
            '',
            [
                'maxlength' => \CourseManager::MAX_COURSE_LENGTH_CODE,
                'pattern' => '[a-zA-Z0-9]+',
                'title' => get_lang('OnlyLettersAndNumbers'),
            ]
        );
        $form->applyFilter('wanted_code', 'html_filter');
        $form->addRule(
            'wanted_code',
            get_lang('Max'),
            'maxlength',
            \CourseManager::MAX_COURSE_LENGTH_CODE
        );

        // The teacher
        if ($courseValidation) {
            // Description of the requested course.
            $form->addElement(
                'textarea',
                'description',
                get_lang('Description'),
                ['rows' => '3']
            );

            // Objectives of the requested course.
            $form->addElement(
                'textarea',
                'objetives',
                get_lang('Objectives'),
                ['rows' => '3']
            );

            // Target audience of the requested course.
            $form->addElement(
                'textarea',
                'target_audience',
                get_lang('TargetAudience'),
                ['rows' => '3']
            );
        }

        // Course language.
        $form->addElement(
            'select_language',
            'course_language',
            get_lang('Ln'),
            [],
            ['style' => 'width:150px']
        );
        $form->applyFilter('select_language', 'html_filter');

        // Exemplary content checkbox.
        $form->addElement(
            'checkbox',
            'exemplary_content',
            null,
            get_lang('FillWithExemplaryContent')
        );

        if ($courseValidation) {
            // A special URL to terms and conditions that is set
            // in the platform settings page.
            $terms_and_conditions_url = trim(
                api_get_setting('course_validation_terms_and_conditions_url')
            );

            // If the special setting is empty,
            // then we may get the URL from Chamilo's module "Terms and conditions",
            // if it is activated.
            if (empty($terms_and_conditions_url)) {
                if (api_get_setting('registration.allow_terms_conditions') === 'true') {
                    $terms_and_conditions_url = api_get_path(WEB_CODE_PATH);
                    $terms_and_conditions_url .= 'auth/inscription.php?legal';
                }
            }

            if (!empty($terms_and_conditions_url)) {
                // Terms and conditions to be accepted before sending a course request.
                $form->addElement(
                    'checkbox',
                    'legal',
                    null,
                    get_lang('IAcceptTermsAndConditions'),
                    1
                );
                $form->addRule(
                    'legal',
                    get_lang('YouHaveToAcceptTermsAndConditions'),
                    'required'
                );
                // Link to terms and conditions.
                $link_terms_and_conditions = '
                    <script>
                    function MM_openBrWindow(theURL, winName, features) { //v2.0
                        window.open(theURL,winName,features);
                    }
                    </script>
                ';
                $link_terms_and_conditions .= \Display::url(
                    get_lang('ReadTermsAndConditions'),
                    '#',
                    ['onclick' => "javascript:MM_openBrWindow('$terms_and_conditions_url', 'Conditions', 'scrollbars=yes, width=800');"]
                );
                $form->addElement('label', null, $link_terms_and_conditions);
            }
        }

        $obj = new \GradeModel();
        $obj->fill_grade_model_select_in_form($form);

        if (api_get_setting('course.teacher_can_select_course_template') === 'true') {
            $form->addElement(
                'select_ajax',
                'course_template',
                [
                    get_lang('CourseTemplate'),
                    get_lang('PickACourseAsATemplateForThisNewCourse'),
                ],
                null,
                ['url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course']
            );
        }

        $form->addElement('html', '</div>');

        // Submit button.
        $form->addButtonCreate(
            $courseValidation ? get_lang(
                'CreateThisCourseRequest'
            ) : get_lang('CreateCourseArea')
        );

        // Set default values.
        if (isset($_user['language']) && $_user['language'] != '') {
            $values['course_language'] = $_user['language'];
        } else {
            $values['course_language'] = api_get_setting('language.platform_language');
        }

        $form->setDefaults($values);
        $message = null;
        $content = null;

        // Validate the form.
        if ($form->validate()) {
            $course_values = $form->exportValues();
            $wanted_code = $course_values['wanted_code'];
            //$category_code = $course_values['category_code'];
            $category_code = '';
            $title = $course_values['title'];
            $course_language = $course_values['course_language'];
            $exemplary_content = !empty($course_values['exemplary_content']);

            if ($courseValidation) {
                $description = $course_values['description'];
                $objetives = $course_values['objetives'];
                $target_audience = $course_values['target_audience'];
            }

            if ($wanted_code == '') {
                $wanted_code = \CourseManager::generate_course_code(
                    api_substr(
                        $title,
                        0,
                        \CourseManager::MAX_COURSE_LENGTH_CODE
                    )
                );
            }

            // Check whether the requested course code has already been occupied.
            if (!$courseValidation) {
                $course_code_ok = !\CourseManager::course_code_exists(
                    $wanted_code
                );
            } else {
                $course_code_ok = !\CourseRequestManager::course_code_exists(
                    $wanted_code
                );
            }

            if ($course_code_ok) {
                if (!$courseValidation) {
                    $params = [];
                    $params['title'] = $title;
                    $params['exemplary_content'] = $exemplary_content;
                    $params['wanted_code'] = $wanted_code;
                    $params['course_category'] = $category_code;
                    $params['course_language'] = $course_language;
                    $params['gradebook_model_id'] = isset($course_values['gradebook_model_id']) ? $course_values['gradebook_model_id'] : null;

                    $course_info = \CourseManager::create_course($params);

                    if (!empty($course_info)) {
                        $url = api_get_path(WEB_CODE_PATH);
                        $url .= 'course_info/start.php?cidReq=';
                        $url .= $course_info['code'];
                        $url .= '&first=1';
                        header('Location: '.$url);
                        exit;
                    } else {
                        $this->addFlash(
                            'error',
                            $this->trans('CourseCreationFailed')
                        );
                        // Display the form.
                        $content = $form->returnForm();
                    }
                } else {
                    // Create a request for a new course.
                    $request_id = \CourseRequestManager::create_course_request(
                        $wanted_code,
                        $title,
                        $description,
                        $category_code,
                        $course_language,
                        $objetives,
                        $target_audience,
                        api_get_user_id(),
                        $exemplary_content
                    );

                    if ($request_id) {
                        $course_request_info = \CourseRequestManager::get_course_request_info(
                            $request_id
                        );
                        $message = (is_array(
                                $course_request_info
                            ) ? '<strong>'.$course_request_info['code'].'</strong> : ' : '').get_lang(
                                'CourseRequestCreated'
                            );
                        \Display::return_message(
                            $message,
                            'confirmation',
                            false
                        );
                        \Display::return_message(
                            'div',
                            \Display::url(
                                get_lang('Enter'),
                                api_get_path(WEB_PATH).'user_portal.php',
                                ['class' => 'btn btn-default']
                            ),
                            ['style' => 'float: left; margin:0px; padding: 0px;']
                        );
                    } else {
                        \Display::return_message(
                            get_lang('CourseRequestCreationFailed'),
                            'error',
                            false
                        );
                        // Display the form.
                        $content = $form->returnForm();
                    }
                }
            } else {
                \Display::return_message(
                    get_lang('CourseCodeAlreadyExists'),
                    'error',
                    false
                );
                // Display the form.
                $content = $form->returnForm();
            }
        } else {
            if (!$courseValidation) {
                $this->addFlash('warning', get_lang('Explanation'));
            }
            // Display the form.
            $content = $form->returnForm();
        }

        return $this->render(
            'ChamiloCoreBundle:Index:userportal.html.twig',
            [
                'content' => $content,
            ]
        );
    }

    /**
     * Userportal main page.
     *
     * @Route("/{type}/{filter}", methods={"GET"}, name="userportal")
     *
     * @Security("has_role('ROLE_USER')")
     *
     * @param string  $type       courses|sessions|mycoursecategories
     * @param string  $filter     history|current for the userportal courses page
     * @param int     $coursePage
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(
        $type = 'courses',
        $filter = 'current',
        $coursePage = 1,
        Request $request
    ) {
        //$settingsManager = $this->get('chamilo.settings.manager');
        //$setting = $settingsManager->getSetting('platform.institution');

        /*$settingsManagerCourse = $this->get('chamilo_course.settings.manager');
        $course = $this->getDoctrine()->getRepository('ChamiloCoreBundle:Course')->find(1);
        if ($course) {
            $settingsManagerCourse->setCourse($course);
            $agenda = $settingsManagerCourse->getSetting(
                'calendar_event.enabled'
            );
        }*/

        $user = $this->getUser();
        $pageController = new PageController();
        $items = null;
        $page = $coursePage;

        if (!empty($user)) {
            $userId = $user->getId();

            // Main courses and session list
            $type = str_replace('/', '', $type);
            switch ($type) {
                case 'sessions':
                    $items = $pageController->returnSessions(
                        $userId,
                        $filter,
                        $page
                    );
                    break;
                case 'sessioncategories':
                    $items = $pageController->returnSessionsCategories(
                        $userId,
                        $filter,
                        $page
                    );
                    break;
                case 'courses':
                    $items = $pageController->returnCourses(
                        $userId,
                        $filter,
                        $page
                    );
                    break;
                case 'mycoursecategories':
                    $items = $pageController->returnMyCourseCategories(
                        $userId,
                        $filter,
                        $page
                    );
                    break;
                case 'specialcourses':
                    $items = $pageController->returnSpecialCourses(
                        $userId,
                        $filter,
                        $page
                    );
                    break;
            }
        }

        /** @var \Chamilo\SettingsBundle\Manager\SettingsManager $settingManager */
        $settingManager = $this->get('chamilo.settings.manager');
        /*var_dump($settingManager->getSetting('platform.institution'));
        $settings = $settingManager->loadSettings('platform');
        var_dump($settings->get('institution'));
        var_dump(api_get_setting('platform.institution'));*/

        $pageController->returnSkillsLinks();

        // Deleting the session_id.
        $request->getSession()->remove('session_id');

        $countCourses = \CourseManager::count_courses();

        return $this->render(
            'ChamiloCoreBundle:Index:userportal.html.twig',
            [
                'content' => $items,
                'count_courses' => $countCourses,
            ]
        );
    }
}
