<?php
/* For licensing terms, see /license.txt */

/**
 * Class UserForm
 * Extends formvalidator with import and export forms.
 *
 * @author Stijn Konings
 */
class UserForm extends FormValidator
{
    public const TYPE_USER_INFO = 1;
    public const TYPE_SIMPLE_SEARCH = 3;

    /**
     * Builds a form containing form items based on a given parameter.
     *
     * @param int form_type 1 = user_info
     * @param user array
     * @param string form name
     * @param string $method
     * @param string $action
     */
    public function __construct($form_type, $user, $form_name, $method = 'post', $action = null)
    {
        parent::__construct($form_name, $method, $action);
        $this->form_type = $form_type;
        if (isset($user)) {
            $this->user_info = $user;
        }
        if (isset($result_object)) {
            $this->result_object = $result_object;
        }
        if (self::TYPE_USER_INFO == $this->form_type) {
            $this->build_user_info_form();
        } elseif (self::TYPE_SIMPLE_SEARCH == $this->form_type) {
            $this->build_simple_search();
        }
        $this->setDefaults();
    }

    public function display()
    {
        parent::display();
    }

    public function setDefaults($defaults = [], $filter = null)
    {
        parent::setDefaults($defaults, $filter);
    }

    protected function build_simple_search()
    {
        if (isset($_GET['search']) && (!empty($_GET['search']))) {
            $this->setDefaults([
                'keyword' => Security::remove_XSS($_GET['search']),
            ]);
        }
        $renderer = &$this->defaultRenderer();
        $renderer->setCustomElementTemplate('<span>{element}</span> ');
        $this->addElement('text', 'keyword', '');
        $this->addButtonSearch(get_lang('Search'), 'submit');
    }

    protected function build_user_info_form()
    {
        if (api_is_western_name_order()) {
            $this->addElement('static', 'fname', get_lang('FirstName'), $this->user_info['firstname']);
            $this->addElement('static', 'lname', get_lang('LastName'), $this->user_info['lastname']);
        } else {
            $this->addElement('static', 'lname', get_lang('LastName'), $this->user_info['lastname']);
            $this->addElement('static', 'fname', get_lang('FirstName'), $this->user_info['firstname']);
        }
        $this->addElement('static', 'uname', get_lang('UserName'), $this->user_info['username']);
        $this->addElement(
            'static',
            'email',
            get_lang('Email'),
            '<a href="mailto:'.$this->user_info['email'].'">'.$this->user_info['email'].'</a>'
        );
        $this->addElement('static', 'ofcode', get_lang('OfficialCode'), $this->user_info['official_code']);
        $this->addElement('static', 'phone', get_lang('Phone'), $this->user_info['phone']);
        $this->addButtonSave(get_lang('Back'), 'submit');
    }
}
