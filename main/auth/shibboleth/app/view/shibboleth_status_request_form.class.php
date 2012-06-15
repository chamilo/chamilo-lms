<?php

namespace Shibboleth;

/**
 * Status request form. Display a form allowing the user to request additional
 * rights/ another status. 
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
class ShibbolethStatusRequestForm
{

    /**
     *
     * @return ShibbolethStatusRequestForm 
     */
    public static function instance()
    {
        static $result = false;
        if (empty($result))
        {
            $result = new self();
        }
        return $result;
    }

    function display()
    {
        if ($this->is_submitted() && $this->get_reason() == '')
        {
            $reason_is_mandatory = get_lang('reason_is_mandatory');
            Display::display_error_message($reason_is_mandatory);
        }

        $status_request_message = get_lang('status_request_message');
        $label_new_status = get_lang('new_status');
        $label_reason = get_lang('reason');
        $label_ok = get_lang('Ok');
        $label_cancel = get_lang('Cancel');

        $user = Shibboleth::session()->user();
        $items = array();
        if ($user['status'] == Shibboleth::UNKNOWN_STATUS)
        {
            $items[Shibboleth::STUDENT_STATUS] = get_lang('Student');
        }
        $items[Shibboleth::TEACHER_STATUS] = get_lang('Teacher');
        $status_options = '';
        foreach ($items as $key => $value)
        {
            $status_options.= "<option value=\"$key\">$value</option>";
        }

        return <<<EOT
            <div id="askAccountText">
                <p>$status_request_message</p>
            </div>
            <form method="post" action="request.php" id="status_request_form">
    
                <input type="hidden" name="formPosted" value="true"/>
    
            <label for="status">$label_new_status:</label>
            <select name="status">
                    $status_options
            </select>
            <label for="reason">$label_reason:</label>
            <textarea name="reason" style="min-width:400px; min-height:100px;"></textarea>
            <p><input name="submit" type="submit" value="$label_ok" style="margin-right:10px;"/><input name="cancel" type="submit" value="$label_cancel" /></p>
            </form>
EOT;
    }

    public function is_submitted()
    {
        return isset($_POST['submit']) ? $_POST['submit'] : false;
    }

    public function cancelled()
    {
        return isset($_POST['cancel']) ? $_POST['cancel'] : false;
    }

    function get_reason()
    {
        return isset($_POST['reason']) ? $_POST['reason'] : '';
    }

    function get_status()
    {
        return isset($_POST['status']) ? $_POST['status'] : '';
    }

}