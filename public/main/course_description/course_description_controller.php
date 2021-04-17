<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CCourseDescription;

/**
 * Class CourseDescriptionController
 * This file contains class used like controller,
 * it should be included inside a dispatcher file (e.g: index.php).
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 */
class CourseDescriptionController
{
    private $toolname;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->toolname = 'course_description';
    }

    public function getToolbar()
    {
        $is_allowed_to_edit = api_is_allowed_to_edit(null, true);
        $course_description = new CourseDescription();
        $list = $course_description->get_default_description_title();
        $iconList = $course_description->get_default_description_icon();
        $actions = '';
        $actionLeft = '';
        if ($is_allowed_to_edit) {
            $categories = [];
            foreach ($list as $id => $title) {
                $categories[$id] = $title;
            }
            $categories[ADD_BLOCK] = get_lang('Other');
            $i = 1;

            ksort($categories);
            foreach ($categories as $id => $title) {
                if (ADD_BLOCK == $i) {
                    $actionLeft .= '<a href="index.php?'.api_get_cidreq().'&action=add">'.
                        Display::return_icon(
                            $iconList[$id],
                            $title,
                            '',
                            ICON_SIZE_MEDIUM
                        ).
                        '</a>';
                    break;
                } else {
                    $actionLeft .= '<a href="index.php?action=edit&'.api_get_cidreq().'&description_type='.$id.'">'.
                        Display::return_icon(
                            $iconList[$id],
                            $title,
                            '',
                            ICON_SIZE_MEDIUM
                        ).
                        '</a>';
                    $i++;
                }
            }
            $actions = Display::toolbarAction('toolbar', [$actionLeft]);
        }

        return $actions;
    }

    /**
     * It's used for listing course description,
     * render to listing view.
     *
     * @param bool    true for listing history (optional)
     * @param array    message for showing by action['edit','add','destroy'] (optional)
     */
    public function listing($history = false, $messages = [])
    {
        $is_allowed_to_edit = api_is_allowed_to_edit(null, true);
        $course_description = new CourseDescription();
        $session_id = api_get_session_id();
        $data = [];
        $course_description->set_session_id($session_id);
        $data['descriptions'] = $course_description->get_description_data();
        $data['default_description_titles'] = $course_description->get_default_description_title();
        $data['default_description_title_editable'] = $course_description->get_default_description_title_editable();
        $data['default_description_icon'] = $course_description->get_default_description_icon();
        $data['messages'] = $messages;

        api_protect_course_script(true);

        // Prepare confirmation code for item deletion
        global $htmlHeadXtra;
        $htmlHeadXtra[] = "<script>
        function confirmation(name) {
            if (confirm(\" ".trim(get_lang('Are you sure to delete'))." \"+name+\"?\")) {
                return true;
            } else {
                return false;
            }
        }
        </script>";

        /*foreach ($data['descriptions'] as $id => $description) {
            if (!empty($description['content'])
                && false !== strpos($description['content'], '<iframe')
            ) {
                header("X-XSS-Protection: 0");
            }
            // Add an escape version for the JS code of delete confirmation
            if ($description) {
                $data['descriptions'][$id]['title_js'] = addslashes(strip_tags($description['title']));
            }
        }*/
        $actions = self::getToolbar();

        $tpl = new Template(get_lang('Description'));
        $tpl->assign('listing', $data);
        $tpl->assign('is_allowed_to_edit', $is_allowed_to_edit);
        $tpl->assign('actions', $actions);
        $tpl->assign('session_id', $session_id);
        $templateName = $tpl->get_template('course_description/index.tpl');
        $content = $tpl->fetch($templateName);
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();
    }

    /**
     * It's used for editing a course description,
     * render to listing or edit view.
     *
     * @param int $id               description item id
     * @param int $description_type description type id
     */
    public function edit($id, $description_type)
    {
        $course_description = new CourseDescription();
        $session_id = api_get_session_id();
        $course_description->set_session_id($session_id);
        $data = [];
        $affected_rows = null;
        if ('POST' === strtoupper($_SERVER['REQUEST_METHOD'])) {
            if (!empty($_POST['title']) && !empty($_POST['contentDescription'])) {
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
                        $id = $description['iid'];
                    }
                    // If no corresponding description is found, edit a new one
                }
                $progress = isset($_POST['progress']) ? $_POST['progress'] : 0;
                $repo = Container::getCourseDescriptionRepository();

                /** @var CCourseDescription $courseDescription */
                $courseDescription = $repo->find($id);
                if ($courseDescription) {
                    $courseDescription
                        ->setTitle($title)
                        ->setProgress($progress)
                        ->setContent($content)
                    ;
                    $repo->update($courseDescription);
                } else {
                    $course_description->set_description_type($description_type);
                    $course_description->set_title($title);
                    $course_description->set_progress($progress);
                    $course_description->set_content($content);
                    $course_description->insert(api_get_course_int_id());
                }

                Display::addFlash(
                    Display::return_message(
                        get_lang('The description has been updated')
                    )
                );

                $url = api_get_path(WEB_CODE_PATH).'course_description/index.php?'.api_get_cidreq();
                api_location($url);
            }
        } else {
            $default_description_titles = $course_description->get_default_description_title();
            $default_description_title_editable = $course_description->get_default_description_title_editable();
            $default_description_icon = $course_description->get_default_description_icon();
            $question = $course_description->get_default_question();
            $information = $course_description->get_default_information();
            $description_type = $description_type;
            if (empty($id)) {
                // If the ID was not provided, find the first matching description item given the item type
                $description = $course_description->get_data_by_description_type($description_type);
                if (count($description) > 0) {
                    $id = $description['iid'];
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
                $description_type = $course_description_data['description_type'];
                $description_title = $course_description_data['description_title'];
                $description_content = $course_description_data['description_content'];
                $progress = $course_description_data['progress'];
                $descriptions = $course_description->get_data_by_description_type(
                    $description_type,
                    null,
                    $session_id
                );
            }

            if (empty($id)) {
                $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : '';
                if (empty($id)) {
                    // If the ID was not provided, find the first matching description item given the item type
                    $course_description = new CourseDescription();
                    $description = $course_description->get_data_by_description_type($description_type);
                    if (count($description) > 0) {
                        $id = $description['id'];
                    }
                    // If no corresponding description is found, edit a new one
                    unset($course_description);
                }
            }
            $original_id = $id;
            // display categories
            $categories = [];
            foreach ($default_description_titles as $id => $title) {
                $categories[$id] = $title;
            }
            $categories[ADD_BLOCK] = get_lang('Other');

            // default header title form
            $description_type = intval($description_type);
            $header = $default_description_titles[$description_type];
            if ($description_type >= ADD_BLOCK) {
                $header = $default_description_titles[ADD_BLOCK];
            }

            // display form
            $form = new FormValidator(
                'course_description',
                'POST',
                'index.php?action=edit&id='.$original_id.'&description_type='.$description_type.'&'.api_get_cidreq()
            );
            $form->addElement('header', $header);
            $form->addElement('hidden', 'id', $original_id);
            $form->addElement('hidden', 'description_type', $description_type);
            //$form->addElement('hidden', 'sec_token', $token);

            if (api_get_configuration_value('save_titles_as_html')) {
                $form->addHtmlEditor(
                    'title',
                    get_lang('Title'),
                    true,
                    false,
                    ['ToolbarSet' => 'TitleAsHtml']
                );
            } else {
                $form->addText('title', get_lang('Title'));
                $form->applyFilter('title', 'html_filter');
            }
            $form->addHtmlEditor(
                'contentDescription',
                get_lang('Content'),
                true,
                false,
                [
                    'ToolbarSet' => 'Basic',
                    'Width' => '100%',
                    'Height' => '200',
                ]
            );
            $form->addButtonCreate(get_lang('Save'));

            $actions = self::getToolbar();
            // Set some default values
            if (!empty($description_title)) {
                $default['title'] = Security::remove_XSS($description_title);
            }
            if (!empty($description_content)) {
                $default['contentDescription'] = Security::remove_XSS($description_content, COURSEMANAGERLOWSECURITY);
            }
            $default['description_type'] = $description_type;

            $form->setDefaults($default);

            if (isset($question[$description_type])) {
                $message = '<strong>'.get_lang('Help').'</strong><br />';
                $message .= $question[$description_type];
                Display::addFlash(Display::return_message($message, 'normal', false));
            }
            $tpl = new Template(get_lang('Description'));
            //$tpl->assign('is_allowed_to_edit', $is_allowed_to_edit);
            $tpl->assign('actions', $actions);
            $tpl->assign('session_id', $session_id);
            $tpl->assign('content', $form->returnForm());
            $tpl->display_one_col_template();
        }
    }

    /**
     * It's used for adding a course description,
     * render to listing or add view.
     */
    public function add()
    {
        $course_description = new CourseDescription();
        $session_id = api_get_session_id();
        $course_description->set_session_id($session_id);
        $actions = self::getToolbar();

        if ('POST' === strtoupper($_SERVER['REQUEST_METHOD'])) {
            if (!empty($_POST['title']) && !empty($_POST['contentDescription'])) {
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
                        get_lang('The description has been added')
                    )
                );
                $url = api_get_path(WEB_CODE_PATH).'course_description/index.php?'.api_get_cidreq();
                api_location($url);
            }
        } else {
            // display form
            $form = new FormValidator(
                'course_description',
                'POST',
                'index.php?action=add&'.api_get_cidreq()
            );
            //$form->addElement('header', $header);
            $form->addElement('hidden', 'description_type', ADD_BLOCK);
            if (api_get_configuration_value('save_titles_as_html')) {
                $form->addHtmlEditor(
                    'title',
                    get_lang('Title'),
                    true,
                    false,
                    ['ToolbarSet' => 'TitleAsHtml']
                );
            } else {
                $form->addText('title', get_lang('Title'));
                $form->applyFilter('title', 'html_filter');
            }
            $form->addHtmlEditor(
                'contentDescription',
                get_lang('Content'),
                true,
                false,
                [
                    'ToolbarSet' => 'Basic',
                    'Width' => '100%',
                    'Height' => '200',
                ]
            );
            $form->addButtonCreate(get_lang('Save'));

            $tpl = new Template(get_lang('Description'));
            //$tpl->assign('is_allowed_to_edit', $is_allowed_to_edit);
            $tpl->assign('actions', $actions);
            $tpl->assign('session_id', $session_id);
            $tpl->assign('content', $form->returnForm());
            $tpl->display_one_col_template();
        }
    }

    /**
     * It's used for destroy a course description,
     * render to listing view.
     *
     * @param int $id description type
     */
    public function delete($id)
    {
        $course_description = new CourseDescription();
        $session_id = api_get_session_id();
        $course_description->set_session_id($session_id);
        if (!empty($id)) {
            $course_description->delete($id);
            Display::addFlash(
                Display::return_message(get_lang('Description has been deleted'))
            );
        }

        $url = api_get_path(WEB_CODE_PATH).'course_description/index.php?'.api_get_cidreq();
        api_location($url);
    }
}
