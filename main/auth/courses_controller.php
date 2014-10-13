<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains class used like controller, it should be included inside a dispatcher file (e.g: index.php)
 * @author Christian Fasanando <christian1827@gmail.com> - BeezNest
 * @package chamilo.auth
 */
/**
 * Code
 * @package chamilo.auth
 */
class CoursesController { // extends Controller {

    private $toolname;
    private $view;
    private $model;

    /**
     * Constructor
     */
    public function __construct() {
        $this->toolname = 'auth';
        $actived_theme_path = api_get_template();
        $this->view = new View($this->toolname, $actived_theme_path);
        $this->model = new Auth();
    }

    /**
     * It's used for listing courses,
     * render to courses_list view
     * @param string   	action
     * @param string    confirmation message(optional)
     */
    public function courses_list($action, $message = '') {
        $data = array();
        $user_id = api_get_user_id();

        $data['user_courses']             = $this->model->get_courses_of_user($user_id);
        $data['user_course_categories']   = $this->model->get_user_course_categories();
        $data['courses_in_category']      = $this->model->get_courses_in_category();
        $data['all_user_categories']      = $this->model->get_user_course_categories();

        $data['action'] = $action;

        $data['message'] = $message;

        // render to the view
        $this->view->set_data($data);
        $this->view->set_layout('layout');
        $this->view->set_template('courses_list');
        $this->view->render();

    }

    /**
     * It's used for listing categories,
     * render to categories_list view
     * @param string   	action
     * @param string    confirmation message(optional)
     * @param string    error message(optional)
     */
    public function categories_list($action, $message='', $error='') {
        $data = array();
        $data['user_course_categories'] = $this->model->get_user_course_categories();
        $data['action'] = $action;
        $data['message'] = $message;
        $data['error'] = $error;

        // render to the view
        $this->view->set_data($data);
        $this->view->set_layout('layout');
        $this->view->set_template('categories_list');
        $this->view->render();
    }

    /**
     * It's used for listing courses with categories,
     * render to courses_categories view
     * @param string   	action
     * @param string    Category code (optional)
     */
    public function courses_categories($action, $category_code = null, $message = '', $error = '', $content = null) {
        $data = array();
        $browse_course_categories = $this->model->browse_course_categories();
        
        global $_configuration;

        if ($action == 'display_random_courses') {
            $data['browse_courses_in_category'] = $this->model->browse_courses_in_category(null, 10);
        } else {
            if (!isset($category_code)) {
                $category_code = $browse_course_categories[0][1]['code']; // by default first category
            }
            $data['browse_courses_in_category'] = $this->model->browse_courses_in_category($category_code);
        }

        $data['browse_course_categories'] = $browse_course_categories;
        $data['code'] = Security::remove_XSS($category_code);

        // getting all the courses to which the user is subscribed to
        $curr_user_id = api_get_user_id();
        $user_courses = $this->model->get_courses_of_user($curr_user_id);
        $user_coursecodes = array();

        // we need only the course codes as these will be used to match against the courses of the category
        if ($user_courses != '') {
            foreach($user_courses as $key => $value) {
                $user_coursecodes[] = $value['code'];
            }
        }

        if (api_is_drh()) {
            $courses = CourseManager::get_courses_followed_by_drh(api_get_user_id());
            foreach ($courses as $course) {
                $user_coursecodes[] = $course['code'];
            }
        }

        $data['user_coursecodes'] = $user_coursecodes;
        $data['action']           = $action;
        $data['message']          = $message;
        $data['content']          = $content;
        $data['error']            = $error;
        
        $data['catalogShowCoursesSessions'] = 0;
        
        if (isset($_configuration['catalog_show_courses_sessions'])) {
            $data['catalogShowCoursesSessions'] = $_configuration['catalog_show_courses_sessions'];
        }

        // render to the view
        $this->view->set_data($data);
        $this->view->set_layout('layout');
        $this->view->set_template('courses_categories');
        $this->view->render();
    }

    /**
     *
     * @param string $search_term
     * @param string $message
     * @param string $error
     * @param string $content
     */
    public function search_courses($search_term, $message = '', $error = '', $content = null) {

        $data = array();

        $browse_course_categories = $this->model->browse_course_categories();

        $data['browse_courses_in_category'] = $this->model->search_courses($search_term);
        $data['browse_course_categories']   = $browse_course_categories;

        $data['search_term'] = Security::remove_XSS($search_term); //filter before showing in template

        // getting all the courses to which the user is subscribed to
        $curr_user_id = api_get_user_id();
        $user_courses = $this->model->get_courses_of_user($curr_user_id);
        $user_coursecodes = array();

        // we need only the course codes as these will be used to match against the courses of the category
        if ($user_courses != '') {
            foreach ($user_courses as $value) {
                $user_coursecodes[] = $value['code'];
            }
        }

        $data['user_coursecodes'] = $user_coursecodes;
        $data['message']    = $message;
        $data['content']    = $content;
        $data['error']      = $error;
        $data['action']     = 'display_courses';

        // render to the view
        $this->view->set_data($data);
        $this->view->set_layout('layout');
        $this->view->set_template('courses_categories');
        $this->view->render();
    }

    /**
     * Auto user subscription to a course
     */
    public function subscribe_user($course_code, $search_term, $category_code)
    {
        $courseInfo = api_get_course_info($course_code);
        // The course must be open in order to access the auto subscription
        if (in_array($courseInfo['visibility'], array(COURSE_VISIBILITY_CLOSED, COURSE_VISIBILITY_REGISTERED, COURSE_VISIBILITY_HIDDEN))) {
            $error = get_lang('SubscribingNotAllowed');
            //$message = get_lang('SubscribingNotAllowed');
        } else {
            $result = $this->model->subscribe_user($course_code);
            if (!$result) {
                $error = get_lang('CourseRegistrationCodeIncorrect');
            } else {
                // Redirect directly to the course after subscription
                $message = $result['message'];
                $content = $result['content'];
            }
        }

        if (!empty($search_term)) {
            $this->search_courses($search_term, $message, $error, $content);
        } else {
            $this->courses_categories('subscribe', $category_code, $message, $error, $content);
        }
        return $result;
    }

    /**
     * Create a category
     * render to listing view
     * @param   string  Category title
     */
    public function add_course_category($category_title) {
        $result = $this->model->store_course_category($category_title);
        $message = '';
        if ($result) { $message = get_lang("CourseCategoryStored"); }
        else { $error = get_lang('ACourseCategoryWithThisNameAlreadyExists');}
        $action = 'sortmycourses';
        $this->courses_list($action, $message);
    }

    /**
     * Change course category
     * render to listing view
     * @param string    Course code
     * @param int    Category id
     */
    public function change_course_category($course_code, $category_id) {
        $result = $this->model->store_changecoursecategory($course_code, $category_id);
        $message = '';
        if ($result) { $message = get_lang('EditCourseCategorySucces'); }
        $action = 'sortmycourses';
        $this->courses_list($action, $message);
    }

    /**
     * Move up/down courses inside a category
     * render to listing view
     * @param string    move to up or down
     * @param string    Course code
     * @param int    Category id
     */
    public function move_course($move, $course_code, $category_id) {
        $result = $this->model->move_course($move, $course_code, $category_id);
        $message = '';
        if ($result) { $message = get_lang('CourseSortingDone'); }
        $action = 'sortmycourses';
        $this->courses_list($action, $message);
    }

    /**
     * Move up/down categories
     * render to listing view
     * @param string    move to up or down
     * @param int    Category id
     */
    public function move_category($move, $category_id) {
        $result = $this->model->move_category($move, $category_id);
        $message = '';
        if ($result) { $message = get_lang('CategorySortingDone'); }
        $action = 'sortmycourses';
        $this->courses_list($action, $message);
    }

    /**
     * Edit course category
     * render to listing view
     * @param string Category title
     * @param int    Category id
     */
    public function edit_course_category($title, $category) {
        $result = $this->model->store_edit_course_category($title, $category);
        $message = '';
        if ($result) { $message = get_lang('CourseCategoryEditStored'); }
        $action = 'sortmycourses';
        $this->courses_list($action, $message);
    }

    /**
     * Delete a course category
     * render to listing view
     * @param int    Category id
     */
    public function delete_course_category($category_id) {
        $result = $this->model->delete_course_category($category_id);
        $message = '';
        if ($result) { $message = get_lang('CourseCategoryDeleted'); }
        $action = 'sortmycourses';
        $this->courses_list($action, $message);
    }

    /**
     * Unsubscribe user from a course
     * render to listing view
     * @param string    Course code
     */
    public function unsubscribe_user_from_course($course_code, $search_term = null, $category_code = null) {
        $result = $this->model->remove_user_from_course($course_code);
        $message = '';
        if ($result) { $message = get_lang('YouAreNowUnsubscribed'); }
        $action = 'sortmycourses';
        if (!empty($search_term)) {
            $this->search_courses($search_term, $message, $error);
        } else {
            $this->courses_categories('subcribe', $category_code, $message, $error);
        }
    }
    
    /**
     * Get the html block for courses categories
     * @param string $code Current category code
     * @param boolean $hiddenLinks Whether hidden links
     * @return string The HTML block
     */
    public function getCoursesCategoriesBlock($code = null, $hiddenLinks = false)
    {
        $categories = $this->model->browse_course_categories();

        $html = '';

        if (!empty($categories)) {

            foreach ($categories[0] as $category) {
                $categoryName = $category['name'];
                $categoryCode = $category['code'];
                $categoryCourses = $category['count_courses'];

                if ($code == $categoryCode) {
                    $html .= '<li><strong>';
                    $html .= "$categoryName ($categoryCourses)";
                    $html .= '</strong><li>';
                } else {
                    if (!empty($categoryCourses)) {
                        $html .= '<li><a href="' . api_get_self() . '"?action=display_courses&category_code=' . $categoryCode . '&hidden_links=' . $hiddenLinks . '">';
                        $html .= "$categoryName ($categoryCourses)";
                        $html .= '</a></li>';
                    } else {
                        $html .= "<li>$categoryName ($categoryCourses)</li>";
                    }
                }

                if (!empty($categories[$categoryCode])) {
                    foreach ($categories[$categoryCode] as $subCategory1) {
                        $subCategory1Name = $subCategory1['name'];
                        $subCategory1Code = $subCategory1['code'];
                        $subCategory1Courses = $subCategory1['count_courses'];

                        if ($code == $subCategory1Code) {
                            $html .= '<li style="margin-left: 20px">';
                            $html .= "<strong>$subCategory1Name ($subCategory1Courses)</strong>";
                            $html .= '</li>';
                        } else {
                            $html .= '<li style="margin-left: 20px"><a href="' . api_get_self() . '?action=display_courses&category_code=' . $subCategory1Code . '&hidden_links=' . $hiddenLinks . '">';
                            $html .= "$subCategory1Name ($subCategory1Courses)";
                            $html .= '</a></li>';
                        }

                        if (!empty($categories[$subCategory1Code])) {
                            foreach ($categories[$subCategory1Code] as $subCategory2) {
                                $subCategory2Name = $subCategory2['name'];
                                $subCategory2Code = $subCategory2['code'];
                                $subCategory2Courses = $subCategory2['count_courses'];

                                if ($code == $subCategory2Code) {
                                    $html .= '<li style="margin-left: 40px">';
                                    $html .= "<strong>$subCategory2Name ($subCategory2Courses)</strong>";
                                    $html .= '</li>';
                                } else {
                                    $html .= '<li style="margin-left: 40px"><a href="' . api_get_self() . '?action=display_courses&category_code=' . $subCategory2Code . '&hidden_links=' . $hiddenLinks . '">';
                                    $html .= "$subCategory2Name ($subCategory2Courses)";
                                    $html .= '</a></li>';
                                }

                                if (!empty($categories[$subCategory2Code])) {
                                    foreach ($categories[$subCategory2Code] as $subCategory3) {
                                        $subCategory3Name = $subCategory3['name'];
                                        $subCategory3Code = $subCategory3['code'];
                                        $subCategory3Courses = $subCategory3['count_courses'];

                                        if ($code == $subCategory3Code) {
                                            $html .= '<li style="margin-left: 40px">';
                                            $html .= "<strong>$subCategory3Name ($subCategory3Courses)</strong>";
                                            $html .= '</li>';
                                        } else {
                                            $html .= '<li style="margin-left: 40px"><a href="' . api_get_self() . '?action=display_courses&category_code=' . $subCategory3Code . '&hidden_links=' . $hiddenLinks . '">';
                                            $html .= "$subCategory3Name ($subCategory3Courses)";
                                            $html .= '</a></li>';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $html;
    }

    /**
     * Get a HTML button for subscribe to session
     * @param string $sessionName The session name
     * @param array $userInfo The user information
     * @param string $administratorEmail The administrator email
     * @param boolean $allowEmailEditor (Optional) Whether the email editor online is enabled
     * @return string The button
     */
    public function getRegisterInSessionButton($sessionName, $userInfo, $administratorEmail, $allowEmailEditor = false)
    {
        $mailSubject = get_lang('SubscribeToSession') . " '$sessionName'";

        $mailMessage = sprintf(get_lang('PleaseSubscribeMeToSessionX'), $sessionName) . PHP_EOL . PHP_EOL;
        $mailMessage.= get_lang('ContactInformation') . PHP_EOL;
        $mailMessage.= sprintf(get_lang('NameX'), $userInfo['complete_name']) . PHP_EOL;
        $mailMessage.= sprintf(get_lang('UsernameX'), $userInfo['username']) . PHP_EOL;
        $mailMessage.= sprintf(get_lang('EmailX'), $userInfo['email']) . PHP_EOL;

        if ($allowEmailEditor) {
            $mailParams = http_build_query(array(
                'email_title' => $mailSubject,
                'email_text' => $mailMessage
            ));

            $url = "mailto:$administratorEmail&$mailParams";
        } else {
            $mailParams = http_build_query(array(
                'subject' => $mailSubject,
                'body' => $mailMessage
            ));

            $url = "mailto:$administratorEmail?$mailParams";
        }

        return Display::url(get_lang('Subscribe'), $url, array(
                    'class' => 'btn btn-primary clickable_email_link',
        ));
    }

    /**
     * Generate a label if the user has been  registered in session
     * @return string The label
     */
    public function getAlreadyRegisterInSessionLabel()
    {
        $icon = Display::return_icon('students.gif', get_lang('Student'));

        return Display::label($icon . ' ' . get_lang("AlreadyRegisteredToSession"), "info");
    }

    /**
     * Get a icon for a session
     * @param string $sessionName The session name
     * @return string The icon
     */
    public function getSessionIcon($sessionName)
    {
        return Display::return_icon('window_list.png', $sessionName, null, ICON_SIZE_LARGE);
    }

}
