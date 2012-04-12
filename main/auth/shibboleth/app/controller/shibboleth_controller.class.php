<?php

/**
 * Controller for the Shibboleth authentication system. 
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
class ShibbolethController
{

    /**
     *
     * @return ShibbolethController 
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

    /**
     * Log user in with Shibboleth authentication 
     */
    function login()
    {

        if (Shibboleth::session()->is_logged_in())
        {
            Shibboleth::redirect();
        }

        $user = Shibboleth::store()->get_user();

        if ($user->is_empty())
        {
            $message = get_lang('no_login');
            Shibboleth::display()->error_page($message);
        }

        $is_new_user = !User::store()->shibboleth_id_exists($user->unique_id);

        if ($is_new_user && empty($user->email) && Shibboleth::config()->is_email_mandatory)
        {
            $form = ShibbolethEmailForm::instance();
            if ($email = $form->get_email())
            {
                $user->email = $email;
            }
            else
            {
                $content = $form->display();
                Shibboleth::display()->page($content);
            }
        }

        Shibboleth::save($user);
        $chamilo_user = User::store()->get_by_shibboleth_id($user->unique_id);
        Shibboleth::session()->login($chamilo_user->user_id);

        if ($is_new_user && $user->status_request)
        {
            Shibboleth::redirect('main/auth/shibboleth/app/view/request.php');
        }
        else
        {
            Shibboleth::redirect();
        }
    }

    /**
     * Log user in using the standard Chamilo way of logging in.
     * Useful when the normal login screen is removed from the user interface
     * - replaced by Shibboleth login - and user want to login using a standard
     * account
     */
    public function admin_login()
    {
        $title = get_lang('internal_login');
        if (Shibboleth::session()->is_logged_in())
        {
            $message = get_lang('already_logged_in');
            Shibboleth::display()->message_page($message, $title);
        }
        $index_manager = new IndexManager('');
        $html = $index_manager->display_login_form();
        Shibboleth::display()->page($html, $title);
    }

    /**
     * Display the request new status page to administrator for new users. 
     */
    public function request_status()
    {
        /*
         * That may happen if a user visit that url again.
         */
        if (!Shibboleth::session()->is_logged_in())
        {
            Shibboleth::redirect();
        }
        $user = Shibboleth::session()->user();
        if ($user['status'] == Shibboleth::TEACHER_STATUS)
        {
            //Maximum user right is reached.
            Shibboleth::redirect();
        }

        $form = ShibbolethStatusRequestForm::instance();

        if ($form->cancelled())
        {
            Shibboleth::redirect();
        }

        if ($reason = $form->get_reason())
        {
            $subject = get_lang('request_status');
            $status = $form->get_status();
            $status = Shibboleth::format_status($status);

            $message = <<<EOT
New status: $status
            
Reason:
$reason
EOT;

            $success = Shibboleth::email_admin($subject, $message);
            if ($success)
            {
                $request_submitted = get_lang('request_submitted');
                Shibboleth::display()->message_page($request_submitted);
            }
            else
            {
                $request_failed = get_lang('request_failed');
                Shibboleth::display()->error_page($request_failed);
            }
        }

        $title = get_lang('request_status');
        Display :: display_header($title);
        echo $form->display();
        Display :: display_footer();
    }

}