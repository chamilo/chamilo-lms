<?php

namespace CourseDescription;

use Security;

/**
 * Edit/create a course description.
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Genevas
 * @license /license.txt
 */
class CourseDescriptionForm extends \FormValidator
{

    /**
     *
     * @param string $action
     * @param \CourseDescription\CourseDescription $description
     * @return \CourseDescription\CourseDescription 
     */
    static function create($action, $description = null)
    {
        $result = new self('course_description', 'post', $action);
        if ($description) {
            $result->init($description);
        }
        return $result;
    }

    protected $course_description;

    function __construct($form_name = 'course_description', $method = 'post', $action = '', $target = '', $attributes = null, $track_submit = true)
    {
        parent::__construct($form_name, $method, $action, $target, $attributes, $track_submit);
    }

    /**
     *
     * @return \CourseDescription\CourseDescription
     * 
     */
    public function get_course_description()
    {
        return $this->course_description;
    }

    public function set_course_description($value)
    {
        $this->course_description = $value;
    }

    /**
     *
     * @param \CourseDescription\CourseDescription $description 
     */
    function init($description = null)
    {
        $this->set_course_description($description);

        $defaults = array();
        $defaults['title'] = $description->title;
        $defaults['content'] = $description->content;

        $this->add_header($description->get_title());
        $this->add_hidden('description_type', $description->get_description_type());
        $this->add_hidden('c_id', $description->c_id);
        $this->add_hidden('id', $description->id);
        $this->add_textfield('title', get_lang('Title'), true, array('size' => 'width: 350px;'));
        $this->applyFilter('title', 'html_filter');
        $this->add_html_editor('content', get_lang('Content'), true, false, array('ToolbarSet' => 'TrainingDescription', 'Width' => '100%', 'Height' => '200'));
        $this->add_button('save', get_lang('Save'), 'class="save"');

        $this->setDefaults($defaults);
    }

    function update_model()
    {
        $values = $this->exportValues();
        $course_description = $this->get_course_description();

        $course_description->title = $values['title'];
        $course_description->title = Security::remove_XSS($course_description->title);

        $course_description->content = $values['content'];
        $course_description->content = Security::remove_XSS($course_description->content, COURSEMANAGERLOWSECURITY);
    }

    function validate()
    {
        $result = parent::validate();
        if ($result) {
            $this->update_model();
        }
        return $result;
    }

}