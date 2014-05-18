<?php

namespace Notebook;

use Chamilo;

/**
 * Form to edit/Create notebook entries.
 * 
 * @license /licence.txt
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class NotebookForm extends \FormValidator
{

    /**
     *
     * @param string $action
     * @param \Notebook\Notebook $item
     * @return \Notebook\NotebookForm 
     */
    static function create($action, $item = null)
    {
        $result = new self('notebook', 'post', $action);
        if ($item) {
            $result->init($item);
        }
        return $result;
    }

    protected $notebook;

    function __construct($form_name = 'notebook', $method = 'post', $action = '', $target = '', $attributes = null, $track_submit = true)
    {
        parent::__construct($form_name, $method, $action, $target, $attributes, $track_submit);
    }

    /**
     *
     * @return \Notebook\Notebook
     */
    public function get_notebook()
    {
        return $this->notebook;
    }

    public function set_notebook($value)
    {
        $this->notebook = $value;
    }

    /**
     *
     * @param \Notebook\Notebook $notebook
     */
    function init($notebook = null)
    {
        $this->set_notebook($notebook);

        $defaults = array();
        $defaults['title'] = $notebook->title;
        $defaults['description'] = $notebook->description;

        $this->add_hidden('c_id', $notebook->c_id);
        $this->add_hidden('id', $notebook->id);
        $this->add_hidden('session_id', $notebook->session_id);
        $this->add_hidden(Request::PARAM_SEC_TOKEN, Access::instance()->get_token());

        $form_name = $notebook->id ? get_lang('ModifyNote') : get_lang('NoteAddNew');
        $this->add_header($form_name);

        $this->add_textfield('title', get_lang('NoteTitle'), $required = true, array('class' => 'span3'));
        
        if (api_is_allowed_to_edit()) {
            $toolbar = array('ToolbarSet' => 'Notebook', 'Width' => '100%', 'Height' => '300');
        } else {
            $toolbar = array('ToolbarSet' => 'NotebookStudent', 'Width' => '100%', 'Height' => '300', 'UserStatus' => 'student');
        }
        $this->add_html_editor('description', get_lang('NoteComment'), true, api_is_allowed_to_edit(), $toolbar);
        
        $this->add_button('save', get_lang('Save'), array('class' => 'btn save'));

        $this->setDefaults($defaults);
    }

    function update_model()
    {
        $values = $this->exportValues();
        $notebook = $this->get_notebook();
        $notebook->title = $values['title'];
        $notebook->description = $values['description'];
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