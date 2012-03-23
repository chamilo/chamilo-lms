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
    public function courses_categories($action, $category_code = null, $message = '', $error = '') {
        $data = array();
        $browse_course_categories = $this->model->browse_course_categories();        
        
        if ($action == 'display_random_courses') {
            $data['browse_courses_in_category'] = $this->model->browse_courses_in_category(null, 20);
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
        
        $data['user_coursecodes'] = $user_coursecodes;
        $data['action']           = $action;
        $data['message']          = $message;
        $data['error']            = $error;

        // render to the view
        $this->view->set_data($data);
        $this->view->set_layout('layout');
        $this->view->set_template('courses_categories');
        $this->view->render();
    }

    /**
     * Search courses
     */
    public function search_courses($search_term, $message = '', $error = '') {

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
            foreach ($user_courses as $key => $value) {
                    $user_coursecodes[] = $value['code'];
            }
        }

        $data['user_coursecodes'] = $user_coursecodes;
        $data['message']    = $message;
        $data['error']      = $error;
        $data['action']     = 'display_courses';

        // render to the view
        $this->view->set_data($data);
        $this->view->set_layout('layout');
        $this->view->set_template('courses_categories');
        $this->view->render();

    }

    /**
     *
     */
    public function subscribe_user($course_code, $search_term, $category_code) {

        $data = array();

        $result = $this->model->subscribe_user($course_code);
        
        if (!$result) {
            $error = get_lang('CourseRegistrationCodeIncorrect');
        } else {
            $message = $result;
        }

        if (!empty($search_term)) {
            $this->search_courses($search_term, $message, $error);
        } else {
            $this->courses_categories('subcribe', $category_code, $message, $error);
        }

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
    public function unsubscribe_user_from_course($course_code) {
        $result = $this->model->remove_user_from_course($course_code);
        $message = '';
        if ($result) { $message = get_lang('YouAreNowUnsubscribed'); }
        $action = 'sortmycourses';
        $this->courses_list($action, $message);
    }
}
