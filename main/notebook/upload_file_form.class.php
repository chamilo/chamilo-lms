<?php

namespace Notebook;

use Chamilo;

/**
 * Form to upload a CSV file.
 * 
 * @license /licence.txt
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class UploadFileForm extends \FormValidator
{

    /**
     *
     * @param string $action
     * @return \Glossary\UploadFileForm 
     */
    public static function create($action)
    {
        return new self('upload_file', 'post', $action);
    }

    function __construct($form_name = 'upload_file', $method = 'post', $action = '', $target = '', $attributes = null, $track_submit = true)
    {
        parent::__construct($form_name, $method, $action, $target, $attributes, $track_submit);
    }

    /**
     *
     * 
     */
    function init()
    {
        $form_name = get_lang('Import');
        $this->add_header($form_name);

        $this->add_hidden(Request::PARAM_SEC_TOKEN, Access::instance()->get_token());
        $label = get_lang('File');
        $this->add_file('file', $label);
        $this->addRule('file', get_lang('ThisFieldIsRequired'), 'required');

        $this->add_button('save', get_lang('Save'), array('class' => 'btn save'));

        $label = get_lang('CSVMustLookLike');
        $label = "$label";
        $help = '<pre>
                    <b>title</b>;<b>description</b>;
                    "Hello";"Hola";
                    "Good";"Bueno";
                 </pre>';

        $this->add_html($label . $help);
    }

    /**
     *
     * @return object
     */
    public function get_file()
    {
        $result = Request::file('file', array());
        if (empty($result)) {
            return null;
        }
        $error = isset($result['error']) ? (bool) $result['error'] : false;
        if ($error) {
            return array();
        }
        return (object) $result;
    }

    public function validate()
    {
        $result = (bool) parent::validate();
        if ($result == false) {
            return false;
        }
        $file = $this->get_file();
        if (empty($file)) {
            return false;
        }
        return true;
    }

}