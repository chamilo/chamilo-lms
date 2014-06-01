<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.plugin.ticket
 */
$language_file = array('messages', 'userInfo', 'admin', 'trad4all');
$cidReset = true;
require_once '../config.php';
$plugin = TicketPlugin::create();

api_block_anonymous_users();
require_once api_get_path(LIBRARY_PATH) . 'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH) . 'group_portal_manager.lib.php';

if (!api_is_allowed_to_edit()) {
    api_not_allowed();
}
//$nameTools = api_xml_http_response_encode(get_lang('Soporte Virtual'));
$this_section = 'Reports';
unset($_SESSION['this_section']);

$htmlHeadXtra[] = '
<script language="javascript">
$(document).ready(function(){
    $( "#keyword_start_date_start" ).datepicker({ dateFormat: ' . "'yy-mm-dd'" . ' });
    $( "#keyword_start_date_end" ).datepicker({ dateFormat: ' . "'yy-mm-dd'" . ' });
});
function validate(){
    if( $("#keyword_start_date_start").val() != "" &&  $("#keyword_start_date_end").val() != ""){
        datestart = $("#keyword_start_date_start").val();
        dateend = $("#keyword_start_date_end").val();
        dif = $.datepicker.parseDate("dd/mm/yy", datestart) -  $.datepicker.parseDate("dd/mm/yy", dateend);
        if(dif > 0){
            alert("La fecha final no puede ser mayor a la fecha inicial");
            return false;
        }
    }
}
function load_course_list (div_course,my_user_id) {
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		type: "GET",
		url: "course_user_list.php",
		data: "user_id="+my_user_id,
		success: function(datos) {
			$("div#user_request").html(datos);
			$("#btnsubmit").attr("disabled", false);
		}
	});
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
div.ticket-form {
    width: 70%;
    float: center;
    margin-left: 15%;

}

</style>';
$types = TicketManager::get_all_tickets_categories();
$htmlHeadXtra[] = '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/tag/jquery.fcbkcomplete.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link  href="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/tag/style.css" rel="stylesheet" type="text/css" />';
$tools = array();
$tools['todas'] = array('id' => '', 'name' => get_lang('Todas'));
$tools['announcement'] = array('id' => 'announcement', 'name' => get_lang('Announcement'));
// $tools[]= array('id'=>'assignment','name'=>get_lang('Assignment'));
$tools['calendar_event'] = array('id' => 'calendar_event', 'name' => get_lang('Calendar_event'));
$tools['chat'] = array('id' => 'chat', 'name' => get_lang('Chat'));
$tools['conference'] = array('id' => 'conference', 'name' => get_lang('Conference'));
$tools['course_description'] = array('id' => 'course_description', 'name' => get_lang('Course_description'));
$tools['document'] = array('id' => 'document', 'name' => get_lang('Document'));
$tools['dropbox'] = array('id' => 'dropbox', 'name' => get_lang('Dropbox'));
$tools['group'] = array('id' => 'group', 'name' => get_lang('Group'));
$tools['learnpath'] = array('id' => 'learnpath', 'name' => get_lang('Learnpath'));
$tools['link'] = array('id' => 'link', 'name' => get_lang('Link'));
$tools['quiz'] = array('id' => 'quiz', 'name' => get_lang('Quiz'));
$tools['student_publication'] = array('id' => 'student_publication', 'name' => get_lang('Student_publication'));
$tools['user'] = array('id' => 'user', 'name' => get_lang('User'));
$tools['forum'] = array('id' => 'forum', 'name' => get_lang('Forum'));
/**
 * Returns the escaped string.
 * @param string $s
 * @return string
 */
function js_str($s)
{
    return '"' . addcslashes($s, "\0..\37\"\\") . '"';
}
/**
 * This function is to show the ticket form
 * @global array $tools
 */
function show_form()
{
    global $tools;
    echo '<div class="ticket-form">';
    echo '<form enctype="multipart/form-data" action="' . api_get_self() . '" method="post" name="send_ticket" id="send_ticket"
 	onsubmit="return validate()" style="width:100%">';
    $select_course = '<div id="user_request" >
	 </div>';
    echo $select_course;
    //select status
    $select_tool = '<div class="row"  >
	<div class="label2"  >' . get_lang('Tool') .':</div>
	<div class="formw2">';
    $select_tool .= '<select style="width: 95%; " name = "tool" id="tool" >';

    foreach ($tools as $tool) {
        $select_tool .= "<option value = '" . $tool['id'] . "' selected >" . $tool['name'] . "</option>";
    }
    $select_tool .= "</select>";
    $select_tool .= '</div></div>';
    echo $select_tool;
    echo '<div class="row">
	      <div class="label2">' . get_lang('From') . ':</div>
              <div class="formw2"><input id="keyword_start_date_start" name="keyword_start_date_start" type="text"></div>
          </div>
	  <div class="row">
	      <div class="label2"> ' . get_lang('To') . '</div>
	      <div class="formw2"><input id="keyword_start_date_end" name="keyword_start_date_end" type="text"></div>
	  </div>';
    echo '</div>';
    echo '<div class="row">
		<div class="label2">
		</div>
		<div class="formw2">
			<button class="save" name="report" type="submit" id="btnsubmit" disabled="disabled">' . get_lang('CompleteReport') .'</button>
		</div>
	</div>';
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
                  concat(u.firstname,' ',u.lastname) LIKE '%$keyword%' OR
                  concat(u.lastname,' ',u.firstname) LIKE '%$keyword%' OR
                  u.username LIKE '%$keyword%' OR
                  u.email LIKE '%$keyword %'  OR
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
                 u.user_id AS col9,
              u.expiration_date AS exp
           FROM $user_table u ";

    if (isset($_GET['keyword'])) {
        $keyword = Database::escape_string(trim($_GET['keyword']));
        $sql .= " WHERE (u.firstname LIKE '%$keyword%' OR
                  u.lastname LIKE '%$keyword%' OR
                  concat(u.firstname,' ',u.lastname) LIKE '%$keyword%' OR
                  concat(u.lastname,' ',u.firstname) LIKE '%$keyword%' OR
                  u.username LIKE '%$keyword%'  OR
                  u.official_code LIKE '%$keyword%'
                  OR u.email LIKE '%$keyword%' )";
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
    $webPath = api_get_path(WEB_PATH);
    $selfPath = api_get_self();
    while ($user = Database::fetch_row($res)) {
        $image_path = UserManager::get_user_picture_path_by_id($user[0], 'web', false, true);
        $user_profile = UserManager::get_picture_user($user[0], $image_path['file'], 22, USER_IMAGE_SIZE_SMALL, ' width="22" height="22" ');
        if (!api_is_anonymous()) {
            $photo = '<center><a href="' . $webPath . 'whoisonline.php?origin=user_list&id=' . $user[0] . '" title="' . get_lang('Info') . '"><img src="' . $user_profile['file'] . '" ' . $user_profile['style'] . ' alt="' . api_get_person_name($user[2], $user[3]) . '"  title="' . api_get_person_name($user[2], $user[3]) . '" /></a></center>';
        } else {
            $photo = '<center><img src="' . $user_profile['file'] . '" ' . $user_profile['style'] . ' alt="' . api_get_person_name($user[2], $user[3]) . '" title="' . api_get_person_name($user[2], $user[3]) . '" /></center>';
        }
        $user_id = $user[0];
        $button = '<a href="' . $selfPath . '?user_request=' . $user[0] . '">' . Display::return_icon('view_more_stats.gif', get_lang('Info')) . '</a>';
        $button = '<a  href="javascript:void(0)" onclick="load_course_list(\'div_' . $user_id . '\',' . $user_id . ')">
					<img onclick="load_course_list(\'div_' . $user_id . '\',' . $user_id . ')"  src="' . $webPath . 'img/view_more_stats.gif" title="' . get_lang('Courses') . '" alt="' . get_lang('Courses') . '"/>
					</a>&nbsp;&nbsp;';
        $users[] = array($photo, $user[1], $user[2], $user[3], $user[4], $user[5], $button);
    }
    return $users;
}

Display::display_header('Reports');
echo '<div class="actions">
    <form action="' . api_get_self() . '" method="get" name="search_simple" id="search_simple">
        <input name="user_id_request" id="user_id_request" type="hidden" value="">
        <span><label for="keyword">B&uacute;squeda del usuario: </label><input size="25" name="keyword" type="text" id="keyword"></span>
        <span><button class="search" name="submit" type="submit">Buscar</button></span>
        <div class="clear">&nbsp;</div>
    </form></div>';
if (isset($_GET['keyword'])) {
    $table = new SortableTable('users', 'get_number_of_users', 'get_user_data', (api_is_western_name_order() || api_sort_by_first_name()) ? 3 : 2);
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
}

if (isset($_POST['report'])) {
    $course_info = api_get_course_info_by_id($course_id);
    $course_id = Database::escape_string($_POST['course_id']);
    $tool = Database::escape_string($_POST['tool']);
    $user_id = intval($_POST['user_id_request']);

    $sql = "SELECT
                u.username , CONCAT(u.lastname, ' ', u.firstname) AS fullname,
                DATE_SUB(access.access_date,INTERVAL 5 HOUR) AS  access_date,
                c.title AS course, access_tool AS tool
            FROM  " . Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS) . " access
            LEFT JOIN  " . Database::get_main_table(TABLE_MAIN_USER) . " u ON access.access_user_id = u.user_id
            LEFT JOIN  " . Database::get_main_table(TABLE_MAIN_COURSE) . " c ON access.access_cours_code = c.CODE
            WHERE access.access_cours_code = '" . $course_info['code'] . "' AND u.user_id = '$user_id' ";
    if ($tool != '') {
        $sql.="AND access.access_tool = '$tool' ";
    }

    $start_date = Database::escape_string($_POST['keyword_start_date_start']);
    $end_date = Database::escape_string($_POST['keyword_start_date_end']);

    if ($start_date != '' || $end_date != '') {
        $sql .= " HAVING ";
        if ($start_date != '')
            $sql .= "  access_date >= '$start_date'   ";
        if ($end_date != '') {
            $sql = ($start_date == '') ? $sql : ($sql . " AND ");
            $sql .= "  access_date <= '$end_date'   ";
        }
    }
    $result = Database::query($sql);
    $table_result = new SortableTable();
    $table_result->set_header(0, get_lang('User'), false);
    $table_result->set_header(1, get_lang('Fullname'), false);
    $table_result->set_header(2, get_lang('Date'), false);
    $table_result->set_header(3, get_lang('Course'), false);
    $table_result->set_header(4, get_lang('Tool'), false);
    while ($row = Database::fetch_assoc($result)) {
        $row = array(
                $row['username'],
                $row['fullname'],
                $row['access_date'],
                $row['course'],
                get_lang($tools[$row['tool']]['name'])
            );
        $table_result->addRow($row);
    }
    $table_result->display();
} else {
    show_form();
}

Display::display_footer();
