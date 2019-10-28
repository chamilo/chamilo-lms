<?php
/* For licensing terms, see /license.txt */

/**
 * Allow the user to login to a course after reaching a course URL
 * (e.g. http://chamilo.chamilo.org/courses/MYCOURSE/?id_session=0 )
 * See https://support.chamilo.org/issues/6768.
 *
 * Author : hubert.borderiou@grenet.fr
 */
require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(SYS_PATH).'main/auth/cas/authcas.php';
$msg = null;
if (isset($_GET['firstpage'])) {
    $firstpage = $_GET['firstpage'];

    // if course is public, go to course without auth
    $tab_course_info = api_get_course_info($firstpage);

    api_set_firstpage_parameter($firstpage);

    $tpl = new Template(null, 1, 1);

    $action = api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']);
    $action = str_replace('&amp;', '&', $action);
    $form = new FormValidator('formLogin', 'post', $action, null, ['class' => 'form-stacked']);
    $params = [
        'placeholder' => get_lang('Username'),
    ];
    // Avoid showing the autocapitalize option if the browser doesn't
    // support it: this attribute is against the HTML5 standard
    if (api_browser_support('autocapitalize')) {
        $params['autocapitalize'] = 'none';
    }
    $form->addElement(
        'text',
        'login',
        null,
        $params
    );
    $params = [
        'placeholder' => get_lang('Password'),
    ];
    if (api_browser_support('autocapitalize')) {
        $params['autocapitalize'] = 'none';
    }
    $form->addElement(
        'password',
        'password',
        null,
        $params
    );
    $form->addButtonNext(get_lang('Login'), 'submitAuth');
    // see same text in main_api.lib.php function api_not_allowed
    if (api_is_cas_activated()) {
        $msg .= Display::return_message(sprintf(get_lang('You already have an institutional account'), api_get_setting("Institution")), '', false);
        $msg .= Display::div("<br/><a href='".get_cas_direct_URL(api_get_course_id())."'>".getCASLogoHTML()." ".sprintf(get_lang('Login with your account'), api_get_setting("Institution"))."</a><br/><br/>", ['align' => 'center']);
        $msg .= Display::return_message(get_lang('You don\'t have an institutional account'));
        $msg .= "<p style='text-align:center'><a href='#' onclick='$(this).parent().next().toggle()'>".get_lang('Login without an institutional account')."</a></p>";
        $msg .= "<div style='display:none;'>";
    }

    $msg .= '<div class="well_login">';
    $msg .= $form->returnForm();
    $msg .= '</div>';
    if (api_is_cas_activated()) {
        $msg .= "</div>";
    }
    $msg .= '<hr/><p style="text-align:center"><a href="'.api_get_path(WEB_PATH).'">'.get_lang('Return to Course Homepage').'</a></p>';

    $tpl->assign('content', '<h4>'.get_lang('Please login to go to this course').'</h4>'.$msg);
    $tpl->display_one_col_template();
} else {
    api_delete_firstpage_parameter();
    header('Location: '.api_get_path(WEB_PATH).'index.php');
    exit;
}
