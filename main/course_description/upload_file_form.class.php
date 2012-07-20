<?php

namespace CourseDescription;

use Chamilo;

/**
 * Form to upload a file.
 * 
 * @license /licence.txt
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class UploadFileForm extends \FormValidator
{

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
        $form_name = get_lang('UploadFile');
        $this->add_header($form_name);

        $label = get_lang('File');
        $this->add_file('file', $label);
        $this->addRule('file', get_lang('ThisFieldIsRequired'), 'required');
        //$this->add_checkbox('replace', '', get_lang('ReplaceExistingEntries'));

        $this->add_button('save', get_lang('Save'), array('class' => 'btn save'));

//        $label = get_lang('CSVMustLookLike');
//        $label = "<h4>$label</h4>";
//        $help = '<pre>
//                    <strong>"url"</strong>;"title";"description";"target";"category_title";"category_description"
//                    "http://chamilo.org";"Chamilo";"";"_self";"";""
//                    "http://google.com";"Google";"";"_self";"Google";""
//                    "http://mail.google.com";"Google";"";"_self";"Google";""
//                    </pre>';
//
//        $this->add_html($label . $help);
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
        return (object)$result;
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