<?php

/* For licensing terms, see /license.txt */
/**
 * @package chamilo.plugin.ticket
 */
/**
 * INIT SECTION
 */
$language_file = array('messages', 'userInfo', 'admin');
$cidReset = true;
require_once '../config.php';
$plugin = TicketPlugin::create();

if (!api_is_platform_admin() && $plugin->get('allow_student_add') != 'true') {
    header('location:' . api_get_path(WEB_PLUGIN_PATH) . PLUGIN_NAME . '/src/myticket.php');
    exit;
}

api_block_anonymous_users();
require_once api_get_path(LIBRARY_PATH) . 'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH) . 'group_portal_manager.lib.php';

$scrollTop = '';
if (api_is_platform_admin()) {
    $scrollTol = '$("html, body").animate({ 
                    scrollTop: $(".divTicket").offset().top - 20
                  }, "slow");';
}

$htmlHeadXtra[] = '
<script>
function load_course_list (div_course, my_user_id, user_email) {
    $.ajax({
            contentType: "application/x-www-form-urlencoded",
            type: "GET",
            url: "course_user_list.php",
            data: "user_id="+my_user_id,
            success: function(datos) {
                $("#user_request").html(datos);
                $("#user_id_request").val(my_user_id);
                $("#personal_email").val(user_email);
                $("#btnsubmit").attr("disabled", false);
                ' . $scrollTol . '
            }
    });
}
function changeType() {
    var selected = document.getElementById("category_id").selectedIndex;
    var id = $("#category_id").val();
    $("#project_id").val(projects[id]);
    $("#other_area").val(other_area[id]);
    $("#email").val(email[id]);
	if(parseInt(course_required[id]) == 0){
            $("#divCourse").css("display", "none");
            if( id != "CUR"){
                $("#divEmail").css("display", "block");
                $("#personal_email").attr("required","required");
            }
            $("#course_id").disabled = true;
            $("#course_id").value = 0;
	}else{
            $("#divCourse").css("display", "block");
            $("#course_id").prop("disabled", false);
            $("#course_id").val(0);
	}
}
function handleClick2(myRadio) {
    var user_id = myRadio.value;
    document.getElementById("user_id_request").value = user_id;
    alert(document.getElementById("user_id_request").value);
}
function validate() {
    var re  = /^([a-zA-Z0-9_.-])+@(([a-zA-Z0-9-])+.)+([a-zA-Z0-9]{2,4})+$/;
    fckEditor1val = FCKeditorAPI.__Instances["content"].GetHTML();
    document.getElementById("content").value= fckEditor1val;
    var selected = document.getElementById("category_id").selectedIndex;
    var id = document.getElementById("category_id").options[selected].value;
    if (document.getElementById("user_id_request").value == "") {
            alert("' . $plugin->get_lang("ValidUser") . '");
            return false;
    } else if(id == 0) {
            alert("' . $plugin->get_lang("ValidType") . '");
            return false;
    } else if(document.getElementById("subject").value == "") {
            alert("' . $plugin->get_lang("ValidSubject") . '");
            return false;
    } else if(parseInt(course_required[id]) == 1 && document.getElementById("course_id").value == 0) {
            alert("' . $plugin->get_lang("ValidCourse") . '");
            return false;
    } else if(id != "CUR" && parseInt(course_required[id]) != 1  && !re.test(document.getElementById("personal_email").value)) {
            alert("' . $plugin->get_lang("ValidEmail") . '");
            return false;
    } else if(fckEditor1val == "") {
            alert("' . $plugin->get_lang("ValidMessage") . '");
            return false;
    }
}

var counter_image = 1;

function remove_image_form(element_id) {
    $("#" + element_id).remove();
    counter_image = counter_image - 1;
    $("#link-more-attach").css("display", "block");
}

function add_image_form() {
    // Multiple filepaths for image form
    var filepaths = $("#filepaths");
    var new_elem, input_file, link_remove, img_remove, new_filepath_id;

    if ($("#filepath_"+counter_image)) {
        counter_image = counter_image + 1;
    }  else {
        counter_image = counter_image;
    }

    new_elem = "filepath_"+counter_image;

    $("<div/>", {
        id: new_elem,
        class: "controls"
    }).appendTo(filepaths);
    
    input_file = $("<input/>", {
        type: "file",
        name: "attach_" + counter_image,
        size: 20
    });

    link_remove = $("<a/>", {
        onclick: "remove_image_form(\'" + new_elem + "\')",
        style: "cursor: pointer"
    });

    img_remove = $("<img/>", {
        src: "' . api_get_path(WEB_CODE_PATH) . 'img/delete.gif"
    });

    new_filepath_id = $("#filepath_" + counter_image);
    new_filepath_id.append(input_file, link_remove.append(img_remove));
    
    if (counter_image === 6) {
        var link_attach = $("#link-more-attach");
        if (link_attach) {
            $(link_attach).css("display", "none");
        }
    }
}
</script>

<style>
div.row div.label2 {
	float:left;
	width:10%;
}
div.row div.formw2 {
    width:90%;
	float:left
}
div.divTicket {
    padding-top: 100px;
}
</style>';
$types = TicketManager::get_all_tickets_categories();
$htmlHeadXtra[] = '<script language="javascript">
                        var projects = ' . js_array($types, 'projects', 'project_id') . '
                        var course_required = ' . js_array($types, 'course_required', 'course_required') . '
                        var other_area = ' . js_array($types, 'other_area', 'other_area') . '
                        var email = ' . js_array($types, 'email', 'email') . '
		   </script>';
$htmlHeadXtra[] = '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/tag/jquery.fcbkcomplete.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link  href="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/tag/style.css" rel="stylesheet" type="text/css" />';

/**
 * @param $s
 * @return string
 */
function js_str($s)
{
    return '"' . addcslashes($s, "\0..\37\"\\") . '"';
}

/**
 * @param $array
 * @param $name
 * @param $key
 * @return string
 */
function js_array($array, $name, $key)
{
    $return = "new Array(); ";
    foreach ($array as $value) {
        $return .= $name . "['" . $value['category_id'] . "'] ='" . $value[$key] . "'; ";
    }

    return $return;
}

/**
 *
 */
function show_form_send_ticket()
{
    global $types, $plugin;
    echo '<div class="divTicket">';
    
    //Category List
    $categoryList = array();
    foreach ($types as $type) {
        $categoryList[$type['category_id']] = $type['name'] . ": " . $type['description'];
    }
    //End Category List
    
    //Status List
    $statusList = array();
    $statusAttributes = array(
        'style' => 'display: none;',
        'id' => 'status_id',
        'for' => 'status_id'
    );
    $statusList[NEWTCK] = $plugin->get_lang('StatusNew');
    if (api_is_platform_admin()) {
        $statusAttributes = array(
            'id' => 'status_id',
            'for' => 'status_id',
            'style' => 'width: 562px;'
        );
        $statusList[PENDING] = $plugin->get_lang('StatusPending');
        $statusList[UNCONFIRMED] = $plugin->get_lang('StatusUnconfirmed');
        $statusList[CLOSE] = $plugin->get_lang('StatusClose');
        $statusList[REENVIADO] = $plugin->get_lang('StatusForwarded');
    }
    //End Status List
    
    //Source List
    $sourceList = array();
    $sourceAttributes = array(
        'style' => 'display: none;',
        'id' => 'source_id',
        'for' => 'source_id'
    );
    $sourceList[SRC_PLATFORM] = $plugin->get_lang('SrcPlatform');
    if (api_is_platform_admin()) {
        $sourceAttributes = array(
            'id' => 'source_id',
            'for' => 'source_id',
            'style' => 'width: 562px;'
        );
        $sourceList[SRC_EMAIL] = $plugin->get_lang('SrcEmail');
        $sourceList[SRC_PHONE] = $plugin->get_lang('SrcPhone');
        $sourceList[SRC_PRESC] = $plugin->get_lang('SrcPresential');
    }
    //End Source List
    
    //Priority List
    $priorityList = array();
    $priorityList[NORMAL] = $plugin->get_lang('PriorityNormal');
    $priorityList[HIGH] = $plugin->get_lang('PriorityHigh');
    $priorityList[LOW] = $plugin->get_lang('PriorityLow');
    //End Priority List
    
    $form = new FormValidator('send_ticket', 
        'POST',
        api_get_self(),
        "",
        array(
            'enctype' => 'multipart/form-data',
            'onsubmit' => 'return validate()',
            'class' => 'span8 offset1 form-horizontal'
        )
    );
    
    $form->addElement(
        'hidden', 
        'user_id_request', 
        '', 
        array(
            'id' => 'user_id_request'
        )
    );
    
    $form->addElement(
        'hidden', 
        'project_id', 
        '', 
        array(
            'id' => 'project_id'
        )
    );
    
    $form->addElement(
        'hidden', 
        'other_area', 
        '', 
        array(
            'id' => 'other_area'
        )
    );
    
    $form->addElement(
        'hidden', 
        'email', 
        '', 
        array(
            'id' => 'email'
        )
    );
    
    $form->addElement(
        'select',
        'category_id',
        get_lang('Category'),
        $categoryList,
        array(
            'onchange' => 'changeType()',
            'id' => 'category_id',
            'for' => 'category_id',
            'style' => 'width: 562px;'
        )
    );
    
    $form->addElement(
        'html', 
        Display::div(
            '', 
            array(
                'id' => 'user_request'
            )
        )
    );
    
    $form->addElement(
        'select',
        'status_id',
        get_lang('Status'),
        $statusList,
        $statusAttributes    
    );
    
    $form->addElement(
        'select',
        'source_id',
        $plugin->get_lang('Source'),
        $sourceList,
        $sourceAttributes   
    );
    
    $form->addElement(
        'text', 
        'subject', 
        get_lang('Subject'), 
        array(
            'id' => 'subject',
            'style' => 'width: 550px;'
        )
    );
    
    $form->addElement(
        'text', 
        'personal_email', 
        $plugin->get_lang('PersonalEmail'), 
        array(
            'id' => 'personal_email',
            'style' => 'width: 550px;'
        )
    );
    
    $form->add_html_editor(
        'content', 
        get_lang('Message'),
        false, 
        false, 
        array(
            'ToolbarSet' => 'Profile', 
            'Width' => '600', 
            'Height' => '250'
        )
    );

    $form->addElement(
        'text', 
        'phone', 
        get_lang('Phone') . ' (' . $plugin->get_lang('Optional') . ')', 
        array(
            'id' => 'phone'
        )
    );
    
    $form->addElement(
        'select', 
        'priority_id', 
        $plugin->get_lang('Priority'), 
        $priorityList,
        array(
            'id' => 'priority_id',
            'for' => 'priority_id'
        )
    );
    
    $form->addElement('html', '<span id="filepaths">');
    $form->addElement('html', '<div id="filepath_1">');
    $form->addElement('file', 'attach_1', get_lang('FilesAttachment'));
    $form->addElement('html', '</div>');
    $form->addElement('html', '</span>');
    
    $form->addElement('html', '<div class="controls">');
    $form->addElement('html', '<span id="link-more-attach" >');
    $form->addElement('html', '<span class="label label-info" onclick="return add_image_form()">' . get_lang('AddOneMoreFile') . '</span>');
    $form->addElement('html', '</span>');
    $form->addElement('html', '(' . sprintf(get_lang('MaximunFileSizeX'), format_file_size(api_get_setting('message_max_upload_filesize'))) . ')');
    
    $form->addElement('html', '<br/>');
    $form->addElement(
        'button', 
        'compose', 
        get_lang('SendMessage'), 
        array(
            'class' => 'save',
            'id' => 'btnsubmit'
        )
    );
    
    $form->display();
}

/**
 *
 */
function save_ticket()
{
    global $plugin;
    $category_id = $_POST['category_id'];
    $content = $_POST['content'];
    if ($_POST['phone'] != "") {
        $content .= '<p style="color:red">&nbsp;' . get_lang('Phone') . ': ' . Security::remove_XSS($_POST['phone']). '</p>';
    }
    $course_id = $_POST['course_id'];
    $project_id = $_POST['project_id'];
    $subject = $_POST['subject'];
    $other_area = (int) $_POST['other_area'];
    $email = $_POST['email'];
    $personal_email = $_POST['personal_email'];
    $source = $_POST['source_id'];
    $user_id = $_POST['user_id_request'];
    $priority = $_POST['priority_id'];
    $status = $_POST['status_id'];
    $file_attachments = $_FILES;
    $responsible = (api_is_platform_admin() ? api_get_user_id() : 0);
    if (TicketManager::insert_new_ticket(
        $category_id,
        $course_id,
        $project_id,
        $other_area,
        $email,
        $subject,
        $content,
        $personal_email,
        $file_attachments,
        $source,
        $priority,
        $status,
        $user_id,
        $responsible
    )
    ) {
        header('location:' . api_get_path(WEB_PLUGIN_PATH) . PLUGIN_NAME . '/src/myticket.php?message=success');
        exit;
    } else {
        Display::display_header(get_lang('ComposeMessage'));
        Display::display_error_message($plugin->get_lang('ErrorRegisterMessage'));
    }
}

/**
 * Get the total number of users on the platform
 * @see SortableTable#get_total_number_of_items()
 */
function get_number_of_users()
{
    $user_table = Database :: get_main_table(TABLE_MAIN_USER);
    $sql = "SELECT COUNT(u.user_id) AS total_number_of_items FROM $user_table u";
    if ((api_is_platform_admin() || api_is_session_admin()) && api_get_multiple_access_url()) {
        $access_url_rel_user_table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $sql.= " INNER JOIN $access_url_rel_user_table url_rel_user ON (u.user_id=url_rel_user.user_id)";
    }
    if (isset($_GET['keyword'])) {
        $keyword = Database::escape_string(trim($_GET['keyword']));
        $sql .= " WHERE (u.firstname LIKE '%$keyword%' OR
                  u.lastname LIKE '%$keyword%'  OR
                  concat(u.firstname,' ',u.lastname) LIKE '%$keyword%'  OR
                  concat(u.lastname,' ',u.firstname) LIKE '%$keyword%' OR
                  u.username LIKE '%$keyword%' OR
                  u.email LIKE '%$keyword%'  OR
                  u.official_code LIKE '%$keyword%') ";
    }
    $res = Database::query($sql);
    $obj = Database::fetch_object($res);
    return $obj->total_number_of_items;
}

/**
 * Get the users to display on the current page (fill the sortable-table)
 * @param   int     offset of first user to recover
 * @param   int     Number of users to get
 * @param   int     Column to sort on
 * @param   string  Order (ASC,DESC)
 * @see SortableTable#get_table_data($from)
 */
function get_user_data($from, $number_of_items, $column, $direction)
{
    $user_table = Database :: get_main_table(TABLE_MAIN_USER);

    if (api_is_western_name_order()) {
        $col34 = "u.firstname AS col3,
                  u.lastname AS col4,";
    } else {
        $col34 = "u.lastname AS col3,
                  u.firstname AS col4,";
    }

    $sql = "SELECT
                u.user_id AS col0,
                u.official_code AS col2,
        	$col34
                u.username AS col5,
                u.email AS col6,
                u.status AS col7,
                u.active AS col8,
                u.user_id AS col9 ,
                u.expiration_date AS exp
            FROM $user_table u ";

    if (isset($_GET['keyword'])) {
        $keyword = Database::escape_string(trim($_GET['keyword']));
        $sql .= " WHERE (u.firstname LIKE '%$keyword%' OR
                  u.lastname LIKE '%$keyword%' OR
                  concat(u.firstname,' ',u.lastname) LIKE '%$keyword%' OR
                  concat(u.lastname,' ',u.firstname) LIKE '%$keyword%' OR
                  u.username LIKE '%$keyword%'  OR
                  u.official_code LIKE '%$keyword%' OR
                  u.email LIKE '%$keyword%' )";
    }
    if (!in_array($direction, array('ASC', 'DESC'))) {
        $direction = 'ASC';
    }
    $column = intval($column);
    $from = intval($from);
    $number_of_items = intval($number_of_items);

    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from,$number_of_items";

    $res = Database::query($sql);

    $users = array();
    while ($user = Database::fetch_row($res)) {
        $user_id = $user[0];
        $image_path = UserManager::get_user_picture_path_by_id($user_id, 'web', false, true);
        $user_profile = UserManager::get_picture_user($user_id, $image_path['file'], 22, USER_IMAGE_SIZE_SMALL, ' width="22" height="22" ');
        if (!api_is_anonymous()) {
            $photo = '<center><a href="' . api_get_path(WEB_PATH) . 'whoisonline.php?origin=user_list&id=' . $user_id . '" title="' . get_lang('Info') . '"><img src="' . $user_profile['file'] . '" ' . $user_profile['style'] . ' alt="' . api_get_person_name($user[2], $user[3]) . '"  title="' . api_get_person_name($user[2], $user[3]) . '" /></a></center>';
        } else {
            $photo = '<center><img src="' . $user_profile['file'] . '" ' . $user_profile['style'] . ' alt="' . api_get_person_name($user[2], $user[3]) . '" title="' . api_get_person_name($user[2], $user[3]) . '" /></center>';
        }
        $button = '<a  href="javascript:void(0)" onclick="load_course_list(\'div_' . $user_id . '\',' . $user_id . ', \'' . $user[5] . '\')">'
                    . Display::return_icon('view_more_stats.gif', get_lang('Info')) .
                   '</a>&nbsp;&nbsp;';
        $users[] = array($photo, $user_id, $user[2], $user[3], $user[4], $user[5], $button);
    }
    return $users;
}

if (!isset($_POST['compose'])) {
     if (api_is_platform_admin()) {
        Display::display_header(get_lang('ComposeMessage'));
        $message = $plugin->get_lang('PleaseBeforeRegisterATicketSelectOneUser');
        Display::display_warning_message($message);
        echo '
            <div class="actions">
              <span style="float: right;">&nbsp;</span>
              <form id="search_simple" name="search_simple" method="get" action="' . api_get_self() . '" class="form-search">
                <fieldset>
                <span><label for="keyword">' . get_lang('langSearchAUser') . ': &nbsp;</label><input type="text" name="keyword" size="25"></span>
                <span><button type="submit" name="submit" class="btn btn">' . get_lang('Search') . '</button></span>
                <div class="clear"></div>
                </fieldset>
              </form>
            </div>';
        echo '<div class="users-list">';
            $order = (api_is_western_name_order() || api_sort_by_first_name()) ? 3 : 2;
            $table = new SortableTable(
                        'users', 'get_number_of_users', 
                        'get_user_data', $order, 10
                     );
            $table->set_header(0, '', false, 'width="18px"');
            $table->set_header(0, get_lang('Photo'), false);
            $table->set_header(1, get_lang('OfficialCode'));
            if (api_is_western_name_order()) {
                $table->set_header(2, get_lang('FirstName'));
                $table->set_header(3, get_lang('LastName'));
            } else {
                $table->set_header(2, get_lang('LastName'));
                $table->set_header(3, get_lang('FirstName'));
            }
            $table->set_header(4, get_lang('LoginName'));
            $table->set_header(5, get_lang('Email'));
            $table->set_header(6, get_lang('Action'));
            $table->display();
        echo '</div>';
     } else {
        $userInfo = api_get_user_info();
        $htmlHeadXtra[] = "
             <script>
                $(document).ready(function(){
                    load_course_list('div_{$userInfo['user_id']}', '{$userInfo['user_id']}', '{$userInfo['email']}');
                });
             </script>
             ";
        Display::display_header(get_lang('ComposeMessage'));
     }
    show_form_send_ticket();
} else {
    save_ticket();
}

Display::display_footer();
