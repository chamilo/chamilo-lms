<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This is the profile social main page
 * @author Julio Montoya <gugli100@gmail.com>
 * @author Isaac Flores Paz <florespaz_isaac@hotmail.com>
 * @todo use Display::panel()
 * @package chamilo.social
 */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

if (api_get_setting('allow_social_tool') != 'true') {
    $url = api_get_path(WEB_PATH).'whoisonline.php?id='.intval($_GET['u']);
    header('Location: '.$url);
    exit;
}

$user_id = api_get_user_id();
$friendId = isset($_GET['u']) ? intval($_GET['u']) : api_get_user_id();
$isAdmin = api_is_platform_admin($user_id);
$userGroup = new UserGroup();

$show_full_profile = true;
//social tab
$this_section = SECTION_SOCIAL;

//Initialize blocks
$social_extra_info_block = null;
$social_course_block = null;
$social_group_info_block = null;
$social_rss_block = null;
$social_session_block = null;

if (!empty($_POST['social_wall_new_msg_main']) || !empty($_FILES['picture']['tmp_name'])) {
    $messageId = 0;
    $messageContent = $_POST['social_wall_new_msg_main'];
    if (!empty($_POST['url_content'])) {
        $messageContent = $_POST['social_wall_new_msg_main'].'<br /><br />'.$_POST['url_content'];
    }
    $idMessage = SocialManager::sendWallMessage(
        api_get_user_id(),
        $friendId,
        $messageContent,
        $messageId,
        MESSAGE_STATUS_WALL_POST
    );
    if (!empty($_FILES['picture']['tmp_name']) && $idMessage > 0) {
        $error = SocialManager::sendWallMessageAttachmentFile(
            api_get_user_id(),
            $_FILES['picture'],
            $idMessage,
            $fileComment = ''
        );
    }

    Display::addFlash(Display::return_message(get_lang('MessageSent')));

    $url = api_get_path(WEB_CODE_PATH).'social/profile.php';
    $url .= empty($_SERVER['QUERY_STRING']) ? '' : '?'.Security::remove_XSS($_SERVER['QUERY_STRING']);
    header('Location: '.$url);
    exit;

} elseif (!empty($_POST['social_wall_new_msg']) && !empty($_POST['messageId'])) {
    $messageId = intval($_POST['messageId']);
    $messageContent = $_POST['social_wall_new_msg'];

    $res = SocialManager::sendWallMessage(
        api_get_user_id(),
        $friendId,
        $messageContent,
        $messageId,
        MESSAGE_STATUS_WALL
    );
    Display::addFlash(Display::return_message(get_lang('MessageSent')));
    $url = api_get_path(WEB_CODE_PATH).'social/profile.php';
    $url .= empty($_SERVER['QUERY_STRING']) ? '' : '?'.Security::remove_XSS($_SERVER['QUERY_STRING']);
    header('Location: '.$url);
    exit;

} elseif (isset($_GET['messageId'])) {
    $messageId = intval($_GET['messageId']);
    $messageInfo = MessageManager::get_message_by_id($messageId);
    if (!empty($messageInfo)) {
        // I can only delete messages of my own wall
        if ($messageInfo['user_receiver_id'] == $user_id) {
            $status = SocialManager::deleteMessage($messageId);

            Display::addFlash(Display::return_message(get_lang('MessageDeleted')));
            header('Location: '.api_get_path(WEB_CODE_PATH).'social/profile.php');
            exit;
        }
    }
    api_not_allowed(true);
} elseif (isset($_GET['u'])) { //I'm your friend? I can see your profile?
    $user_id = intval($_GET['u']);
    if (api_is_anonymous($user_id, true)) {
        api_not_allowed(true);
    }
    // It's me!
    if (api_get_user_id() != $user_id) {
        $user_info = api_get_user_info($user_id);
        $show_full_profile = false;
        if (!$user_info) {
            // user does no exist !!
            api_not_allowed(true);
        } else {
            //checking the relationship between me and my friend
            $my_status = SocialManager::get_relation_between_contacts(
                api_get_user_id(),
                $user_id
            );
            if (in_array($my_status, array(
                    USER_RELATION_TYPE_PARENT,
                    USER_RELATION_TYPE_FRIEND,
                    USER_RELATION_TYPE_GOODFRIEND
                ))) {
                $show_full_profile = true;
            }
            //checking the relationship between my friend and me
            $my_friend_status = SocialManager::get_relation_between_contacts(
                $user_id,
                api_get_user_id()
            );
            if (in_array($my_friend_status, array(
                    USER_RELATION_TYPE_PARENT,
                    USER_RELATION_TYPE_FRIEND,
                    USER_RELATION_TYPE_GOODFRIEND
                ))) {
                $show_full_profile = true;
            } else {
                // im probably not a good friend
                $show_full_profile = false;
            }
        }
    } else {
        $user_info = api_get_user_info($user_id);
    }
} else {
    $user_info = api_get_user_info($user_id);
}

if ($user_info['user_id'] == api_get_user_id()) {
    $isSelfUser = true;
} else {
    $isSelfUser = false;
}
$userIsOnline = user_is_online($user_id);

$libpath = api_get_path(LIBRARY_PATH);
$ajax_url = api_get_path(WEB_AJAX_PATH).'message.ajax.php';
$socialAjaxUrl = api_get_path(WEB_AJAX_PATH).'social.ajax.php';
$javascriptDir = api_get_path(LIBRARY_PATH).'javascript/';
api_block_anonymous_users();
$locale = api_get_language_isocode();
// Add Jquery scroll pagination plugin
$htmlHeadXtra[] = api_get_js('jscroll/jquery.jscroll.js');
// Add Jquery Time ago plugin
$htmlHeadXtra[] = api_get_asset('jquery-timeago/jquery.timeago.js');
$timeAgoLocaleDir = $javascriptDir.'jquery-timeago/locales/jquery.timeago.'.$locale.'.js';
if (file_exists($timeAgoLocaleDir)) {
    $htmlHeadXtra[] = api_get_js('jquery-timeago/locales/jquery.timeago.'.$locale.'.js');
}

$htmlHeadXtra[] = '<script>
$(document).ready(function (){
    var container = $("#wallMessages");
    container.jscroll({
        loadingHtml: "<div class=\"well_border\">' . get_lang('Loading').' </div>",
        nextSelector: "a.nextPage:last",
        contentSelector: "",
        callback: timeAgo
    });
    timeAgo();
});

function timeAgo() {
    $(".timeago").timeago();
}
</script>';

$link_shared = '';

$nametool = get_lang('ViewMySharedProfile');
if (isset($_GET['shared'])) {
    $my_link = '../social/profile.php';
    $link_shared = 'shared='.Security::remove_XSS($_GET['shared']);
} else {
    $my_link = '../social/profile.php';
    $link_shared = '';
}
$interbreadcrumb[] = array(
    'url' => 'home.php',
    'name' => get_lang('SocialNetwork'),
);

if (isset($_GET['u']) && is_numeric($_GET['u']) && $_GET['u'] != api_get_user_id()) {
    $info_user = api_get_user_info($_GET['u']);
    $interbreadcrumb[] = array(
        'url' => '#',
        'name' => $info_user['complete_name']
    );
    $nametool = '';
}
if (isset($_GET['u'])) {
    $param_user = 'u='.Security::remove_XSS($_GET['u']);
} else {
    $info_user = api_get_user_info(api_get_user_id());
    $param_user = '';
}

Session::write('social_user_id', (int) $user_id);

// Setting some course info
$my_user_id = isset($_GET['u']) ? intval($_GET['u']) : api_get_user_id();
$personal_course_list = UserManager::get_personal_session_course_list($my_user_id, 50);
$course_list_code = array();
$i = 1;
$list = [];
if (is_array($personal_course_list)) {
    foreach ($personal_course_list as $my_course) {
        if ($i <= 10) {
            $list[] = SocialManager::get_logged_user_course_html($my_course, $i);
            $course_list_code[] = array('code' => $my_course['code']);
        } else {
            break;
        }
        $i++;
    }
    //to avoid repeted courses
    $course_list_code = array_unique_dimensional($course_list_code);
}

//Social Block Menu
$social_menu_block = SocialManager::show_social_menu(
    'shared_profile',
    null,
    $user_id,
    $show_full_profile
);

//Setting some session info
$user_info = api_get_user_info($my_user_id);
$sessionList = SessionManager::getSessionsFollowedByUser(
    $my_user_id,
    $user_info['status']
);

// My friends
$friend_html = SocialManager::listMyFriendsBlock(
    $user_id,
    $link_shared,
    $show_full_profile
);

$wallSocialAddPost = SocialManager::getWallForm($show_full_profile);
$social_wall_block = $wallSocialAddPost;

// Social Post Wall
$posts = SocialManager::getWallMessagesByUser($my_user_id, $friendId);

$social_post_wall_block = empty($posts) ? '<p>'.get_lang("NoPosts").'</p>' : $posts;

$socialAutoExtendLink = Display::url(
    get_lang('SeeMore'),
    $socialAjaxUrl.'?u='.$my_user_id.'&a=list_wall_message&start=10&length=5',
    array(
        'class' => 'nextPage next',
    )
);

// Added a Jquery Function to return the Preview of OpenGraph URL Content
$htmlHeadXtra[] = '<script>
$(document).ready(function() {

    var getUrl = $("[name=\'social_wall_new_msg_main\']");
    var matchUrl = /https?:\/\/w{0,3}\w*?\.(\w*?\.)?\w{2,3}\S*|www\.(\w*?\.)?\w*?\.\w{2,3}\S*|(\w*?\.)?\w*?\.\w{2,3}[\/\?]\S*/ ;

    getUrl.on("paste", function(e) {
        $.ajax({
            contentType: "application/x-www-form-urlencoded",
            beforeSend: function() {
                $("[name=\'wall_post_button\']").prop( "disabled", true );
                $(".panel-preview").hide();
                $(".spinner").html("'.
                    '<div class=\'text-center\'>'.
                        '<em class=\'fa fa-spinner fa-pulse fa-1x\'></em>'.
                        '<p>'.get_lang('Loading').' '.get_lang('Preview').'</p>'.
                    '</div>'.
                '");
            },
            type: "POST",
            url: "'. api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=read_url_with_open_graph",
            data: "social_wall_new_msg_main=" + e.originalEvent.clipboardData.getData("text"),
            success: function(response) {
                $("[name=\'wall_post_button\']").prop( "disabled", false );
                if (!response == false) {
                    $(".spinner").html("");
                    $(".panel-preview").show();
                    $(".url_preview").html(response);
                    $("[name=\'url_content\']").val(response);
                    $(".url_preview img").addClass("img-responsive");
                } else {
                    $(".spinner").html("");
                }
            }
        });
    });
});
</script>';

$socialRightInformation = '';
$social_right_content = '';
$listInvitations = '';

if ($show_full_profile) {
    $t_ufo = Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS);
    $extra_user_data = UserManager::get_extra_user_data($user_id, false, true);

    $extra_information = '';
    if (is_array($extra_user_data) && count($extra_user_data) > 0) {
        $extra_information_value = '';
        $extraField = new ExtraField('user');
        foreach ($extra_user_data as $key => $data) {
            if (empty($data)) {
                continue;
            }
            // Avoiding parameters
            if (in_array(
                $key,
                array(
                    'mail_notify_invitation',
                    'mail_notify_message',
                    'mail_notify_group_message',
                )
            )) {
                continue;
            }
            // get display text, visibility and type from user_field table
            $field_variable = str_replace('extra_', '', $key);

            $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable(
                $field_variable
            );

            if (in_array($extraFieldInfo['variable'], ['skype', 'linkedin_url'])) {
                continue;
            }

            // if is not visible skip
            if ($extraFieldInfo['visible_to_self'] != 1) {
                continue;
            }

            // if is not visible to others skip also
            if ($extraFieldInfo['visible_to_others'] != 1) {
                continue;
            }

            if (is_array($data)) {
                $extra_information_value .= '<li class="list-group-item">'.ucfirst($extraFieldInfo['display_text']).' '
                    .' '.implode(',', $data).'</li>';
            } else {
                switch ($extraFieldInfo['field_type']) {
                    case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                        $id_options = explode(';', $data);
                        $value_options = array();
                        // get option display text from user_field_options table
                        foreach ($id_options as $id_option) {
                            $sql = "SELECT display_text FROM $t_ufo WHERE id = '$id_option'";
                            $res_options = Database::query($sql);
                            $row_options = Database::fetch_row($res_options);
                            $value_options[] = $row_options[0];
                        }
                        $extra_information_value .= '<li class="list-group-item">'.ucfirst($extraFieldInfo['display_text']).': '
                            .' '.implode(' ', $value_options).'</li>';
                        break;
                    case ExtraField::FIELD_TYPE_TAG:
                        $user_tags = UserManager::get_user_tags($user_id, $extraFieldInfo['id']);

                        $tag_tmp = array();
                        foreach ($user_tags as $tags) {
                            $tag_tmp[] = '<a class="label label_tag"'
                                .' href="'.api_get_path(WEB_PATH).'main/social/search.php?q='.$tags['tag'].'">'
                                .$tags['tag']
                                .'</a>';
                        }
                        if (is_array($user_tags) && count($user_tags) > 0) {
                            $extra_information_value .= '<li class="list-group-item">'.ucfirst($extraFieldInfo['display_text']).': '
                                .' '.implode('', $tag_tmp).'</li>';
                        }
                        break;
                    case ExtraField::FIELD_TYPE_SOCIAL_PROFILE:
                        $icon_path = UserManager::get_favicon_from_url($data);
                        if (SocialManager::verifyUrl($icon_path) == false) {
                            break;
                        }
                        $bottom = '0.2';
                        //quick hack for hi5
                        $domain = parse_url($icon_path, PHP_URL_HOST);
                        if ($domain == 'www.hi5.com' or $domain == 'hi5.com') {
                            $bottom = '-0.8';
                        }
                        $data = '<a href="'.$data.'">'
                            .'<img src="'.$icon_path.'" alt="icon"'
                            .' style="margin-right:0.5em;margin-bottom:'.$bottom.'em;" />'
                            .$extraFieldInfo['display_text']
                            .'</a>';
                        $extra_information_value .= '<li class="list-group-item">'.$data.'</li>';
                        break;
                    default:
                        $extra_information_value .= '<li class="list-group-item">'.ucfirst($extraFieldInfo['display_text']).': '.$data.'</li>';
                        break;
                }
            }
        }

        // if there are information to show
        if (!empty($extra_information_value)) {

            $extra_information_value = '<ul class="list-group">'.$extra_information_value.'</ul>';

            $extra_information .= Display::panelCollapse(
                get_lang('ExtraInformation'),
                $extra_information_value,
                'sn-extra-information',
                null,
                'sn-extra-accordion',
                'sn-extra-collapse'
            );
        }
    }

    // If there are information to show Block Extra Information

    if (!empty($extra_information_value)) {
        $social_extra_info_block = $extra_information;
    }

    // MY GROUPS
    $results = $userGroup->get_groups_by_user($my_user_id, 0);
    $grid_my_groups = array();
    $max_numbers_of_group = 4;

    if (is_array($results) && count($results) > 0) {
        $i = 1;
        foreach ($results as $result) {
            if ($i > $max_numbers_of_group) {
                break;
            }
            $id = $result['id'];
            $url_open  = '<a href="group_view.php?id='.$id.'">';
            $url_close = '</a>';
            $icon = '';
            $name = cut($result['name'], CUT_GROUP_NAME, true);
            if ($result['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {
                $icon = Display::return_icon(
                    'social_group_admin.png',
                    get_lang('Admin'),
                    array('style'=>'vertical-align:middle;width:16px;height:16px;')
                );
            } elseif ($result['relation_type'] == GROUP_USER_PERMISSION_MODERATOR) {
                $icon = Display::return_icon(
                    'social_group_moderator.png',
                    get_lang('Moderator'),
                    array('style'=>'vertical-align:middle;width:16px;height:16px;')
                );
            }
            $count_users_group = count($userGroup->get_all_users_by_group($id));
            if ($count_users_group == 1) {
                $count_users_group = $count_users_group.' '.get_lang('Member');
            } else {
                $count_users_group = $count_users_group.' '.get_lang('Members');
            }
            $item_name = $url_open.$name.$icon.$url_close;
            $item_actions = '';
            $grid_my_groups[] = array(
                $item_name,
                $url_open.$result['picture'].$url_close,
                $item_actions,
            );
            $i++;
        }
    }

    // Block My Groups
    if (count($grid_my_groups) > 0) {
        $my_groups = '';
        $count_groups = 0;
        if (count($results) == 1) {
            $count_groups = count($results);
        } else {
            $count_groups = count($results);
        }

        $my_groups .= '<div class="panel panel-default">';
        $my_groups .= '<div class="panel-heading">'.get_lang('MyGroups').' ('.$count_groups.') </div>';

        if ($i > $max_numbers_of_group) {
            if (api_get_user_id() == $user_id) {
                $my_groups .= '<div class="box_shared_profile_group_actions">'
                    .'<a href="groups.php?#tab_browse-1">'.get_lang('SeeAllMyGroups').'</a></div>';
            } else {
                $my_groups .= '<div class="box_shared_profile_group_actions">'
                    .'<a href="'.api_get_path(WEB_CODE_PATH).'social/profile_friends_and_groups.inc.php'
                    .'?view=mygroups&height=390&width=610&user_id='.$user_id.'"'
                    .' class="ajax" title="'.get_lang('SeeAll').'" >'
                    .get_lang('SeeAllMyGroups')
                    .'</a></div>';
            }
        }

        $total = count($grid_my_groups);
        $i = 1;
        foreach ($grid_my_groups as $group) {
            $my_groups .= '<div class="panel-body">';
            $my_groups .= $group[0];
            $my_groups .= '</div>';
            $i++;
        }
        $my_groups .= '</div>';
        $social_group_info_block = $my_groups;
    }

    // Block Social Course
    $my_courses = null;

    // COURSES LIST
    if (is_array($list)) {
        // Courses without sessions
        $my_course = '';
        $i = 1;

        foreach ($list as $key => $value) {
            if (empty($value[2])) { //if out of any session
                $my_courses .= $value[1];
                $i++;
            }
        }
        $social_course_block .= $my_courses;
    }

    // Block Social Sessions
    if (count($sessionList) > 0) {
        //$social_session_block = $htmlSessionList;
        $social_session_block = $sessionList;
    }

    // Block Social User Feeds
    $user_feeds = SocialManager::get_user_feeds($user_id);

    if (!empty($user_feeds)) {
        $social_rss_block = Display::panel($user_feeds, get_lang('RSSFeeds'));
    }

    // Productions
    $production_list = UserManager::build_production_list($user_id);

    // Images uploaded by course
    $file_list = '';
    if (is_array($course_list_code) && count($course_list_code) > 0) {
        foreach ($course_list_code as $course) {
            $file_list .= UserManager::get_user_upload_files_by_course(
                $user_id,
                $course['code'],
                $resourcetype = 'images'
            );
        }
    }

    $count_pending_invitations = 0;
    if (!isset($_GET['u']) ||
        (isset($_GET['u']) && $_GET['u'] == api_get_user_id())
    ) {
        $pending_invitations = SocialManager::get_list_invitation_of_friends_by_user_id(api_get_user_id());
        $list_get_path_web = SocialManager::get_list_web_path_user_invitation_by_user_id(api_get_user_id());
        $count_pending_invitations = count($pending_invitations);
    }

    if (!empty($production_list) || !empty($file_list) || $count_pending_invitations > 0) {
        // Pending invitations
        if (!isset($_GET['u']) || (isset($_GET['u']) && $_GET['u'] == api_get_user_id())) {
            if ($count_pending_invitations > 0) {
                $invitations = '<ul class="list-group">';
                for ($i = 0; $i < $count_pending_invitations; $i++) {
                    $user_invitation_id = $pending_invitations[$i]['user_sender_id'];
                    $invitations .= '<li id="dpending_'.$user_invitation_id.'" class="list-group-item">';
                    $invitations .= '<img class="img-rounded" '
                                .' src="'.$list_get_path_web[$i]['dir'].'/'.$list_get_path_web[$i]['file'].'"'
                                .' width="40px">';
                    $userInfo = api_get_user_info($user_invitation_id);
                    $invitations .= '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$user_invitation_id.'">'
                                 .api_get_person_name($userInfo['firstname'], $userInfo['lastname']).'</a>';

                    $invitations .= '<div class="pull-right">';
                    $invitations .= Display::toolbarButton(
                        get_lang('SocialAddToFriends'),
                        api_get_path(WEB_AJAX_PATH).'social.ajax.php?'.http_build_query([
                            'a' => 'add_friend',
                            'friend_id' => $user_invitation_id,
                            'is_my_friend' => 'friend'
                        ]),
                        'plus',
                        'default',
                        ['class' => 'btn-sm'],
                        false
                    );
                    $invitations .= '</div>';
                    $invitations .= '<div id="id_response"></div>';
                    $invitations .= '</li>';
                }
                $invitations .= '</ul>';
                $listInvitations = Display::panelCollapse(
                    get_lang('PendingInvitations'),
                    $invitations,
                    'invitations',
                    null,
                    'invitations-acordion',
                    'invitations-collapse'
                );
            }
        }

        // Productions
        $production_list = UserManager::build_production_list($user_id);
        $product_content = '';
        if (!empty($production_list)) {
            $product_content .= '<div><h3>'.get_lang('MyProductions').'</h3></div>';
            $product_content .= $production_list;
            $socialRightInformation .= SocialManager::social_wrapper_div($product_content, 4);
        }

        $images_uploaded = null;
        // Images uploaded by course
        if (!empty($file_list)) {
            $images_uploaded .= '<div><h3>'.get_lang('ImagesUploaded').'</h3></div>';
            $images_uploaded .= '<div class="social-content-information">';
            $images_uploaded .= $file_list;
            $images_uploaded .= '</div>';
            $socialRightInformation .= SocialManager::social_wrapper_div($images_uploaded, 4);
        }
    }

    if (!empty($user_info['competences']) || !empty($user_info['diplomas'])
        || !empty($user_info['openarea']) || !empty($user_info['teach'])) {

        $more_info .= '<div><h3>'.get_lang('MoreInformation').'</h3></div>';
        if (!empty($user_info['competences'])) {
            $more_info .= '<br />';
                $more_info .= '<div class="social-actions-message"><strong>'.get_lang('MyCompetences').'</strong></div>';
                $more_info .= '<div class="social-profile-extended">'.$user_info['competences'].'</div>';
            $more_info .= '<br />';
        }
        if (!empty($user_info['diplomas'])) {
            $more_info .= '<div class="social-actions-message"><strong>'.get_lang('MyDiplomas').'</strong></div>';
            $more_info .= '<div class="social-profile-extended">'.$user_info['diplomas'].'</div>';
            $more_info .= '<br />';
        }
        if (!empty($user_info['openarea'])) {
            $more_info .= '<div class="social-actions-message"><strong>'.get_lang('MyPersonalOpenArea').'</strong></div>';
            $more_info .= '<div class="social-profile-extended">'.$user_info['openarea'].'</div>';
            $more_info .= '<br />';
        }
        if (!empty($user_info['teach'])) {
            $more_info .= '<div class="social-actions-message"><strong>'.get_lang('MyTeach').'</strong></div>';
            $more_info .= '<div class="social-profile-extended">'.$user_info['teach'].'</div>';
            $more_info .= '<br />';
        }
        $socialRightInformation .= SocialManager::social_wrapper_div($more_info, 4);
    }
}

$tpl = new Template(get_lang('Social'));
// Block Avatar Social
SocialManager::setSocialUserBlock(
    $tpl,
    $friendId,
    'shared_profile',
    0,
    $show_full_profile
);

$tpl->assign('social_friend_block', $friend_html);
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_wall_block', $social_wall_block);
$tpl->assign('social_post_wall_block', $social_post_wall_block);
$tpl->assign('social_extra_info_block', $social_extra_info_block);
$tpl->assign('social_course_block', $social_course_block);
$tpl->assign('social_group_info_block', $social_group_info_block);
$tpl->assign('social_rss_block', $social_rss_block);
$tpl->assign('social_skill_block', SocialManager::getSkillBlock($my_user_id));
$tpl->assign('session_list', $social_session_block);
$tpl->assign('invitations', $listInvitations);
$tpl->assign('social_right_information', $socialRightInformation);
$tpl->assign('social_auto_extend_link', $socialAutoExtendLink);

$formModalTpl = new Template();
$formModalTpl->assign('invitation_form', MessageManager::generate_invitation_form('send_invitation'));
$template = $formModalTpl->get_template('social/form_modals.tpl');
$formModals = $formModalTpl->fetch($template);

$tpl->assign('form_modals', $formModals);
$social_layout = $tpl->get_template('social/profile.tpl');
$tpl->display($social_layout);

