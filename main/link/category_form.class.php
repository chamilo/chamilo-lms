<?php

namespace Link;

/**
 * Edit/create a LinkCategory.
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Genevas
 * @license /license.txt
 */
class CategoryForm extends \FormValidator
{

    protected $category;

    function __construct($form_name = 'category', $method = 'post', $action = '', $target = '', $attributes = null, $track_submit = true)
    {
        parent::__construct($form_name, $method, $action, $target, $attributes, $track_submit);
    }
    
    /**
     *
     * @return object
     */
    public function get_category()
    {
        return $this->category;
    }

    public function set_category($value)
    {
        $this->category = $value;
    }
    /**
     *
     * @param \Link\LinkCategory $category 
     */
    function init($category = null)
    {
        $this->set_category($category);
        
        $defaults = array();
        $defaults['category_title'] = $category->category_title;
        $defaults['category_description'] =  $category->description;
        
        $this->addElement('hidden', 'c_id', $category->c_id);
        $this->addElement('hidden', 'id', $category->id);
        $this->addElement('hidden', 'session_id', $category->session_id);

        $form_name = $category->id ? get_lang('ModifyCategory') : get_lang('AddCategory');
        $this->add_header($form_name);


        $this->add_textfield('category_title', get_lang('Title'));
        $this->addRule('category_title', get_lang('Required'), 'required');
        
        $this->addElement('textarea', 'category_description', get_lang('Description'));
        $this->addElement('button', 'save', get_lang('Save'), array('class' => 'btn save'));
        $this->setDefaults($defaults);
    }
    
    function update_model()
    {
        $values = $this->exportValues();
        $category = $this->get_category();
        $category->category_title = $values['category_title'];
        $category->description = $values['category_description'];
    }
}