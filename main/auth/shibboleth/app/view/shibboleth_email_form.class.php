<?php

namespace Shibboleth;

/**
 * Enter email form. When the email is mandatory and the Shibboleth email user field
 * is empty the system display this form and ask the user to provide an email.
 * 
 * @todo: add email validation
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
class ShibbolethEmailForm
{
        
    /**
     *
     * @return ShibbolethEmailForm 
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
        
        $email = get_lang('Email');
        $submit = get_lang('Submit');
        return <<<EOT
        <form id="email_form" action="" method="post">
            <label for="">$email</label>
            <input type="text" value="" tabindex="1" name="email" id="email_email" class=""><br/>
            <input type="submit" value="$submit" tabindex="2" name="submit" id="email_submit" class="submit">
        </form>
        
EOT;
    }
    
    function get_email()
    {
        return isset($_POST['email']) ? $_POST['email'] : '';
    }

}
