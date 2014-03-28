<?php
/**
 * For licensing terms, see /license.txt
 *
 * Allow the user to login to a course after reaching a course URL (e.g. http://chamilo.chamilo.org/courses/MYCOURSE/?id_session=0 )
 * See https://support.chamilo.org/issues/6768
 *
 * Author : hubert.borderiou@grenet.fr
 *
 */

require('../inc/global.inc.php');
require_once(api_get_path(SYS_PATH).'main/auth/cas/authcas.php');


if (isset($_GET['firstpage'])) {
    $firstpage = $_GET['firstpage'];

    // if course is public, go to course without auth
    $tab_course_info = api_get_course_info($firstpage);

    api_set_firstpage_parameter($firstpage);

    $tpl = new Template(null, 1, 1);

    $action = api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']);
    $action = str_replace('&amp;', '&', $action);
    $form = new FormValidator('formLogin', 'post', $action, null, array('class'=>'form-stacked'));
    $form->addElement('text', 'login', null, array('placeholder' => get_lang('UserName'), 'class' => 'span3 autocapitalize_off')); //new
    $form->addElement('password', 'password', null, array('placeholder' => get_lang('Password'), 'class' => 'span3')); //new
    $form->addElement('style_submit_button', 'submitAuth', get_lang('LoginEnter'), array('class' => 'btn span3'));
    // see same text in main_api.lib.php function api_not_allowed
    if (api_is_cas_activated()) {
        $msg .= Display::return_message(sprintf(get_lang('YouHaveAnInstitutionalAccount'), api_get_setting("Institution")), '', false);
        $msg .= Display::div("<br/><a href='".get_cas_direct_URL(api_get_course_id())."'>".getCASLogoHTML()." ".sprintf(get_lang('LoginWithYourAccount'), api_get_setting("Institution"))."</a><br/><br/>", array('align'=>'center'));
        $msg .= Display::return_message(get_lang('YouDontHaveAnInstitutionAccount'));
        $msg .= "<p style='text-align:center'><a href='#' onclick='$(this).parent().next().toggle()'>".get_lang('LoginWithExternalAccount')."</a></p>";
        $msg .= "<div style='display:none;'>";
    }

    $msg .= '<div class="well_login">';
    $msg .= $form->return_form();
    $msg .='</div>';
    if (api_is_cas_activated()) {
        $msg .= "</div>";
    }
    $msg .= '<hr/><p style="text-align:center"><a href="'.api_get_path(WEB_PATH).'">'.get_lang('ReturnToCourseHomepage').'</a></p>';

    $tpl->assign('content', '<h4>'.get_lang('LoginToGoToThisCourse').'</h4>'.$msg);
    $tpl->display_one_col_template();

} else {
    api_delete_firstpage_parameter();
    Header('Location: '.api_get_path(WEB_PATH).'index.php');
}
