<?php
/* For licensing terms, see /license.txt */

/**
 * Class CourseDescriptionController
 * This file contains class used like controller,
 * it should be included inside a dispatcher file (e.g: index.php)
 * @author Christian Fasanando <christian1827@gmail.com>
 * @package chamilo.course_description
 */
class CourseDescriptionController
{
    private $toolname;
    private $view;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->toolname = 'course_description';
        $this->view = new View($this->toolname);
    }

    /**
     * It's used for listing course description,
     * render to listing view
     * @param boolean    true for listing history (optional)
     * @param array    message for showing by action['edit','add','destroy'] (optional)
     */
    public function listing($history = false, $messages = array())
    {
        $course_description = new CourseDescription();
        $session_id = api_get_session_id();
        $course_description->set_session_id($session_id);
        $data = array();
        $course_description_data = $course_description->get_description_data();
        $data['descriptions'] = isset($course_description_data['descriptions']) ? $course_description_data['descriptions'] : '';
        $data['default_description_titles'] = $course_description->get_default_description_title();
        $data['default_description_title_editable'] = $course_description->get_default_description_title_editable();
        $data['default_description_icon'] = $course_description->get_default_description_icon();
        $data['messages'] = $messages;
        $browser = api_get_navigator();

        if (!is_array($data['descriptions'])) {
            $data['descriptions'] = array($data['descriptions']);
        }

        foreach ($data['descriptions'] as $description) {
            if (!empty($description['content'])
                && strpos($description['content'], '<iframe') !== false
                && $browser['name'] == 'Chrome'
            ) {
                header("X-XSS-Protection: 0");
            }
        }

        // render to the view
        $this->view->set_data($data);
        $this->view->set_layout('layout');
        $this->view->set_template('listing');
        $this->view->render();
    }

    /**
     * It's used for editing a course description,
     * render to listing or edit view
     * @param int $id description item id
     * @param int $description_type description type id
     */
    public function edit($id, $description_type)
    {
        $course_description = new CourseDescription();
        $session_id = api_get_session_id();
        $course_description->set_session_id($session_id);
        $data = array();
        $data['id'] = $id;
        $affected_rows = null;
        if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {
            if (!empty($_POST['title']) && !empty($_POST['contentDescription'])) {
                if (1) {
                    $title = $_POST['title'];
                    $content = $_POST['contentDescription'];
                    $description_type = $_POST['description_type'];
                    $id = $_POST['id'];
                    if (empty($id)) {
                        // If the ID was not provided, find the first matching description item given the item type
                        $description = $course_description->get_data_by_description_type(
                            $description_type
                        );
                        if (count($description) > 0) {
                            $id = $description['id'];
                        }
                        // If no corresponding description is found, edit a new one
                    }
                    $progress = isset($_POST['progress']) ? $_POST['progress'] : '';
                    $course_description->set_description_type($description_type);
                    $course_description->set_title($title);
                    $course_description->set_content($content);
                    $course_description->set_progress($progress);
                    $thematic_advance = $course_description->get_data_by_id($id);

                    if (!empty($thematic_advance)) {
                        $course_description->set_id($id);
                        $course_description->update();
                    } else {
                        $course_description->insert();
                    }

                    Display::addFlash(
                        Display::return_message(
                            get_lang('CourseDescriptionUpdated')
                        )
                    );
                }
                $this->listing(false);
            } else {
                $data['error'] = 1;
                $data['default_description_titles'] = $course_description->get_default_description_title();
                $data['default_description_title_editable'] = $course_description->get_default_description_title_editable();
                $data['default_description_icon'] = $course_description->get_default_description_icon();
                $data['question'] = $course_description->get_default_question();
                $data['information'] = $course_description->get_default_information();
                $data['description_title'] = $_POST['title'];
                $data['description_content'] = $_POST['contentDescription'];
                $data['description_type'] = $_POST['description_type'];
                $data['progress'] = $_POST['progress'];
                $data['descriptions'] = $course_description->get_data_by_id($_POST['id']);
                // render to the view
                $this->view->set_data($data);
                $this->view->set_layout('layout');
                $this->view->set_template('edit');
                $this->view->render();
            }
        } else {

            $data['default_description_titles'] = $course_description->get_default_description_title();
            $data['default_description_title_editable'] = $course_description->get_default_description_title_editable();
            $data['default_description_icon'] = $course_description->get_default_description_icon();
            $data['question'] = $course_description->get_default_question();
            $data['information'] = $course_description->get_default_information();

            $data['description_type'] = $description_type;
            if (empty($id)) {
                // If the ID was not provided, find the first matching description item given the item type
                $description = $course_description->get_data_by_description_type($description_type);
                if (count($description) > 0) {
                    $id = $description['id'];
                }
                // If no corresponding description is found, edit a new one
            }
            if (!empty($id)) {
                if (isset($_GET['id_session'])) {
                    $session_id = intval($_GET['id_session']);
                }
                $course_description_data = $course_description->get_data_by_id(
                    $id,
                    null,
                    $session_id
                );
                $data['description_type'] = $course_description_data['description_type'];
                $data['description_title'] = $course_description_data['description_title'];
                $data['description_content'] = $course_description_data['description_content'];
                $data['progress'] = $course_description_data['progress'];
                $data['descriptions'] = $course_description->get_data_by_description_type(
                    $description_type,
                    null,
                    $session_id
                );
            }

            // render to the view
            $this->view->set_data($data);
            $this->view->set_layout('layout');
            $this->view->set_template('edit');
            $this->view->render();
        }
    }

    /**
     * It's used for adding a course description,
     * render to listing or add view
     */
    public function add()
    {
        $course_description = new CourseDescription();
        $session_id = api_get_session_id();
        $course_description->set_session_id($session_id);

        $data = array();
        if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {
            if (!empty($_POST['title']) && !empty($_POST['contentDescription'])) {
                if (1) {
                    $title = $_POST['title'];
                    $content = $_POST['contentDescription'];
                    $description_type = $_POST['description_type'];
                    if ($description_type >= ADD_BLOCK) {
                        $course_description->set_description_type($description_type);
                        $course_description->set_title($title);
                        $course_description->set_content($content);
                        $course_description->insert(api_get_course_int_id());
                    }

                    Display::addFlash(
                        Display::return_message(
                            get_lang('CourseDescriptionUpdated')
                        )
                    );
                }
                $this->listing(false);
            } else {
                $data['error'] = 1;
                $data['default_description_titles'] = $course_description->get_default_description_title();
                $data['default_description_title_editable'] = $course_description->get_default_description_title_editable();
                $data['default_description_icon'] = $course_description->get_default_description_icon();
                $data['question'] = $course_description->get_default_question();
                $data['information'] = $course_description->get_default_information();
                $data['description_title'] = $_POST['title'];
                $data['description_content'] = $_POST['contentDescription'];
                $data['description_type'] = $_POST['description_type'];
                $this->view->set_data($data);
                $this->view->set_layout('layout');
                $this->view->set_template('add');
                $this->view->render();
            }
        } else {
            $data['default_description_titles'] = $course_description->get_default_description_title();
            $data['default_description_title_editable'] = $course_description->get_default_description_title_editable();
            $data['default_description_icon'] = $course_description->get_default_description_icon();
            $data['question'] = $course_description->get_default_question();
            $data['information'] = $course_description->get_default_information();
            $data['description_type'] = $course_description->get_max_description_type();
            // render to the view
            $this->view->set_data($data);
            $this->view->set_layout('layout');
            $this->view->set_template('add');
            $this->view->render();
        }
    }

    /**
     * It's used for destroy a course description,
     * render to listing view
     * @param int $id description type
     */
    public function destroy($id)
    {
        $course_description = new CourseDescription();
        $session_id = api_get_session_id();
        $course_description->set_session_id($session_id);
        if (!empty($id)) {
            $course_description->set_id($id);
            $course_description->delete();
            Display::addFlash(
                Display::return_message(get_lang('CourseDescriptionDeleted'))
            );
        }
        $this->listing(false);
    }
}
