<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.social
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') != 'true') {
    api_not_allowed();
}

$this_section = SECTION_SOCIAL;

$htmlHeadXtra[] = '<script>
function delete_friend (element_div) {
	id_image = $(element_div).attr("id");
	user_id = id_image.split("_");
	if (confirm("'.get_lang('Delete', '').'")) {
        $.ajax({
            contentType: "application/x-www-form-urlencoded",
			type: "POST",
			url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=delete_friend",
			data: "delete_friend_id="+user_id[1],
			success: function(datos) {			
			    $("#user_card_"+user_id[1]).hide("slow");
			}
		});
	}
}

function search_image_social()  {
	var name_search = $("#id_search_image").val();
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		type: "POST",
		url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=show_my_friends",
		data: "search_name_q="+name_search,
		success: function(data) {
			$("#friends").html(data);
		}
	});
}

function show_icon_delete(element_html) {
	elem_id=$(element_html).attr("id");
	id_elem=elem_id.split("_");
	ident="#img_"+id_elem[1];
	$(ident).attr("src","'.Display::returnIconPath('delete.png').'");
	$(ident).attr("alt","'.get_lang('Delete', '').'");
	$(ident).attr("title","'.get_lang('Delete', '').'");
}

function hide_icon_delete(element_html)  {
	elem_id=$(element_html).attr("id");
	id_elem=elem_id.split("_");
	ident="#img_"+id_elem[1];
	$(ident).attr("src","'.Display::returnIconPath('blank.gif').'");
	$(ident).attr("alt","");
	$(ident).attr("title","");
}

</script>';

$interbreadcrumb[] = ['url' => 'profile.php', 'name' => get_lang('SocialNetwork')];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Friends')];

//Block Social Menu
$social_menu_block = SocialManager::show_social_menu('friends');
$user_id = api_get_user_id();
$name_search = isset($_POST['search_name_q']) ? $_POST['search_name_q'] : null;
$number_friends = 0;

if (isset($name_search) && $name_search != 'undefined') {
    $friends = SocialManager::get_friends($user_id, USER_RELATION_TYPE_FRIEND, $name_search);
} else {
    $friends = SocialManager::get_friends($user_id, USER_RELATION_TYPE_FRIEND);
}

$social_right_content = '<div class="col-md-12">';

if (count($friends) == 0) {
    $social_right_content .= Display::return_message(
        Display::tag('p', get_lang('NoFriendsInYourContactList')),
        'warning',
        false
    );
    $social_right_content .= Display::toolbarButton(
        get_lang('TryAndFindSomeFriends'),
        'search.php',
        'search',
        'success'
    );
} else {
    $filterForm = new FormValidator('filter');
    $filterForm->addText(
        'id_search_image',
        get_lang('Search'),
        false,
        [
            'onkeyup' => 'search_image_social()',
            'id' => 'id_search_image',
        ]
    );

    $social_right_content .= $filterForm->returnForm();

    $friend_html = '<div id="whoisonline">';
    $friend_html .= '<div class="row">';
    $number_friends = count($friends);
    $j = 0;

    for ($k = 0; $k < $number_friends; $k++) {
        while ($j < $number_friends) {
            if (isset($friends[$j])) {
                $friend = $friends[$j];
                $toolBar = '<button class="btn btn-danger" onclick="delete_friend(this)" id=img_'.$friend['friend_user_id'].'>
                    '.get_lang('Delete').'
                </button>';
                $url = api_get_path(WEB_PATH).'main/social/profile.php?u='.$friend['friend_user_id'];
                $friend['user_info']['complete_name'] = Display::url($friend['user_info']['complete_name'], $url);
                $friend_html .= Display::getUserCard($friend['user_info'], '', $toolBar);
            }
            $j++;
        }
    }
    $friend_html .= '</div>';
    $friend_html .= '</div>';
    $social_right_content .= $friend_html;
}
$social_right_content .= '</div>';

$tpl = new Template(get_lang('Social'));
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'friends');

$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_right_content', $social_right_content);

$social_layout = $tpl->get_template('social/friends.tpl');
$tpl->display($social_layout);
