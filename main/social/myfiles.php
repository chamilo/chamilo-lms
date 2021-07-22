<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @author  Juan Carlos Trabado herodoto@telefonica.net
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

if (api_get_setting('allow_my_files') === 'false') {
    api_not_allowed(true);
}

$this_section = SECTION_SOCIAL;

$htmlHeadXtra[] = '
<script>
function denied_friend (element_input) {
    name_button=$(element_input).attr("id");
	name_div_id="id_"+name_button.substring(13);
	user_id=name_div_id.split("_");
	friend_user_id=user_id[1];

	$.ajax({
	    contentType: "application/x-www-form-urlencoded",
		beforeSend: function(myObject) {
		$("#id_response").html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
		type: "POST",
		url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=deny_friend",
		data: "denied_friend_id="+friend_user_id,
		success: function(datos) {
		 $("div#"+name_div_id).hide("slow");
		 $("#id_response").html(datos);
		}
	});
}

function register_friend(element_input) {
    if(confirm("'.get_lang('AddToFriends').'")) {
    	name_button=$(element_input).attr("id");
    	name_div_id="id_"+name_button.substring(13);
    	user_id=name_div_id.split("_");
    	user_friend_id=user_id[1];

        $.ajax({
    		contentType: "application/x-www-form-urlencoded",
    		beforeSend: function(myObject) {
    		$("div#dpending_"+user_friend_id).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
    		type: "POST",
    		url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=add_friend",
    		data: "friend_id="+user_friend_id+"&is_my_friend="+"friend",
    		success: function(datos) {  $("div#"+name_div_id).hide("slow");
    			$("form").submit()
    		}
        });
    }
}

$(function() {
    $("#el-finder")
        .elfinder({
            url: "'.api_get_path(WEB_LIBRARY_PATH).'elfinder/php/connector.php",
            lang: "'.api_get_language_isocode().'",
            height: 600,
            resizable: false,
            rememberLastDir: false,
        })
        .elfinder("instance");
});
</script>';

// Social Menu Block
$social_menu_block = SocialManager::show_social_menu('myfiles');
$actions = null;

if (isset($_GET['cidReq'])) {
    $actions = Display::url(
        Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('Documents').' ('.get_lang('Course').')'),
        api_get_self().'?'.api_get_cidreq().'&id='.(int) $_GET['parent_id']
    );
}

if (api_get_setting('allow_social_tool') === 'true') {
    Session::write('this_section', SECTION_SOCIAL);
    $interbreadcrumb[] = [
        'url' => 'profile.php',
        'name' => get_lang('SocialNetwork'),
    ];
} else {
    Session::write('this_section', SECTION_COURSES);
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_PATH).'user_portal.php',
        'name' => get_lang('MyCourses'),
    ];
}

$tpl = new Template(get_lang('MyFiles'));
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'myfiles');
$editor = new \Chamilo\CoreBundle\Component\Editor\Editor();
$template = $tpl->get_template($editor->getEditorStandAloneTemplate());
$editor = $tpl->fetch($template);

$tpl->assign('show_media_element', 0);

if (api_get_setting('allow_social_tool') == 'true') {
    $tpl->assign('social_menu_block', $social_menu_block);
    $tpl->assign('social_right_content', $editor);
    $social_layout = $tpl->get_template('social/myfiles.tpl');
    $tpl->display($social_layout);
} else {
    $controller = new IndexManager(get_lang('MyCourses'));
    $tpl->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actions])
    );
    $tpl->assign('content', $editor);
    $tpl->assign('profile_block', $controller->return_profile_block());
    $tpl->assign('user_image_block', $controller->return_user_image_block());
    $tpl->assign('course_block', $controller->return_course_block());
    $tpl->display_two_col_template();
}
