<?php

namespace Glossary;

use Chamilo;

/**
 * Form to edit/Create glossary entries.
 * 
 * @license /licence.txt
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class GlossaryForm extends \FormValidator
{

    /**
     *
     * @param string $action
     * @param \Glossary\Glossary $item
     * @return \Glossary\GlossaryForm 
     */
    static function create($action, $item = null)
    {
        $result = new self('glossary', 'post', $action);
        if ($item) {
            $result->init($item);
        }
        return $result;
    }
    
    protected $glossary;

    function __construct($form_name = 'glossary', $method = 'post', $action = '', $target = '', $attributes = null, $track_submit = true)
    {
        parent::__construct($form_name, $method, $action, $target, $attributes, $track_submit);
    }

    /**
     *
     * @return \Glossary\Glossary
     */
    public function get_glossary()
    {
        return $this->glossary;
    }

    public function set_glossary($value)
    {
        $this->glossary = $value;
    }

    /**
     *
     * @param \Glossary\Glossary $glossary
     */
    function init($glossary = null)
    {
        $this->set_glossary($glossary);

        $defaults = array();
        $defaults['name'] = $glossary->name;
        $defaults['description'] = $glossary->description;

        $this->add_hidden('c_id', $glossary->c_id);
        $this->add_hidden('id', $glossary->id);
        $this->add_hidden('session_id', $glossary->session_id);
        $this->add_hidden(Request::PARAM_SEC_TOKEN, Access::instance()->get_token());

        $form_name = $glossary->id ? get_lang('TermEdit') : get_lang('TermAddNew');
        $this->add_header($form_name);

        $this->add_textfield('name', get_lang('TermName'), $required = true, array('class' => 'span3'));
        $this->add_html_editor('description', get_lang('TermDefinition'), true, array('ToolbarSet' => 'Glossary', 'Width' => '90%', 'Height' => '300'));
        $this->add_button('save', get_lang('Save'), array('class' => 'btn save'));

        $this->setDefaults($defaults);
    }

    function update_model()
    {
        $values = $this->exportValues();
        $glossary = $this->get_glossary();
        $glossary->name = $values['name'];
        $glossary->description = $values['description'];
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