<?php
/* For licensing terms, see /license.txt */

/**
 *
 * @package chamilo.plugin.ticket
 */
/**
 * INIT SECTION
 */
$language_file = array('messages', 'userInfo', 'admin');
$cidReset = true;
require_once '../config.php';
$plugin = TicketPlugin::create();

api_block_anonymous_users();
require_once api_get_path(LIBRARY_PATH) . 'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH) . 'group_portal_manager.lib.php';

$htmlHeadXtra[] = '
<script>
$(document).ready(function(){
	if(document.getElementById("divEmail")){
		document.getElementById("divEmail").style.display="none";
	}
});
function changeType() {
var selected = document.getElementById("category_id").selectedIndex;
var id = document.getElementById("category_id").options[selected].value  ;
	document.getElementById("project_id").value= projects[id];
	document.getElementById("other_area").value= other_area[id];
	document.getElementById("email").value= email[id];
	document.getElementById("divEmail").style.display="none";
	if(parseInt(course_required[id]) == 0){
		document.getElementById("divCourse").style.display="none";
		if( id != "CUR"){
			document.getElementById("divEmail").style.display="";
			document.getElementById("personal_email").required="required";
		}
		document.getElementById("course_id").disabled=true;
		document.getElementById("course_id").value=0;
	}else{
		document.getElementById("divCourse").style.display = "";
		document.getElementById("course_id").disabled=false;
		document.getElementById("course_id").value=0;
		document.getElementById("personal_email").value="";
	}
}

function validate() {
	var re  = /^([a-zA-Z0-9_.-])+@(([a-zA-Z0-9-])+.)+([a-zA-Z0-9]{2,4})+$/;
	fckEditor1val = FCKeditorAPI.__Instances["content"].GetHTML();
	document.getElementById("content").value= fckEditor1val;
	var selected = document.getElementById("category_id").selectedIndex;
	var id = document.getElementById("category_id").options[selected].value;
	if( id == 0){
		alert("' . $plugin->get_lang("ValidType") . '");
		return false;
	}else if(document.getElementById("subject").value == ""){
		alert("' . $plugin->get_lang("ValidSubject") . '");
		return false;
	}else if(parseInt(course_required[id]) == 1 && document.getElementById("course_id").value == 0){
		alert("' . $plugin->get_lang("ValidCourse") . '");
		return false;
	}else if(id !="CUR" && parseInt(course_required[id]) != 1  && !re.test(document.getElementById("personal_email").value)){
		alert("' . $plugin->get_lang("ValidEmail") . '");
		return false;
	}else if(fckEditor1val ==""){
		alert("' . $plugin->get_lang("ValidMessage") . '");
		return false;
	}
}

var counter_image = 1;
function remove_image_form(id_elem1) {
	var elem1 = document.getElementById(id_elem1);
	elem1.parentNode.removeChild(elem1);
	counter_image = counter_image - 1;
}
function add_image_form() {
	// Multiple filepaths for image form
	var filepaths = document.getElementById("filepaths");
	if (document.getElementById("filepath_"+counter_image)) {
		counter_image = counter_image + 1;
	}  else {
		counter_image = counter_image;
	}
	var elem1 = document.createElement("div");
	elem1.setAttribute("id","filepath_"+counter_image);
	filepaths.appendChild(elem1);
	id_elem1 = "filepath_"+counter_image;
	id_elem1 = "\'"+id_elem1+"\'";
	document.getElementById("filepath_"+counter_image).innerHTML = "<input type=\"file\" name=\"attach_"+counter_image+"\"  size=\"20\" />&nbsp;<a href=\"javascript:remove_image_form("+id_elem1+")\"><img src=\"' . api_get_path(WEB_CODE_PATH) . 'img/delete.gif\"></a>";
	if (filepaths.childNodes.length == 6) {
		var link_attach = document.getElementById("link-more-attach");
		if (link_attach) {
			link_attach.innerHTML="";
		}
	}
}
function show_question(questionid){
	if(document.getElementById("C"+questionid)){
		if(document.getElementById("A"+questionid).style.display == "none"){
			document.getElementById("A"+questionid).style.display = "";
		}
		else if(document.getElementById("A"+questionid).style.display == ""){
			document.getElementById("A"+questionid).style.display = "none";
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
    width: 70%;
	float: center;
	margin-left: 15%;

}
</style>';
$types = TicketManager::get_all_tickets_categories();
$htmlHeadXtra[] = '<script language="javascript">
		var projects = ' . js_array($types, 'projects', 'project_id') . '
		var course_required = ' . js_array($types, 'course_required', 'course_required') . '
		var other_area = ' . js_array($types, 'other_area', 'other_area') . '
		var email = ' . js_array($types, 'email', 'email') . '
		document.getElementById("divCourse").style.display="none";
		 </script>';
$htmlHeadXtra[] = '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/tag/jquery.fcbkcomplete.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link  href="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/tag/style.css" rel="stylesheet" type="text/css" />';

/**
 * @todo Delete this function, it already exists in report.php
 * @param string $s
 * @return string
 */

function js_str($s)
{
    return '"' . addcslashes($s, "\0..\37\"\\") . '"';
}

/**
 * This is a javascript helper to generate and array
 * @param array $array
 * @param string $name
 * @param integer $key
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
 * @global array $types
 * @global object $plugin
 */
function show_form_send_ticket()
{
    global $types, $plugin;
    $courses_list = CourseManager::get_courses_list_by_user_id(api_get_user_id(), false, true);
    echo '<div class="divTicket">';
    echo '<form enctype="multipart/form-data" action="' . api_get_self() . '" method="post" name="send_ticket" id="send_ticket"
 	onsubmit="return validate()" style="width:100%">';
    $select_types = '<div class="row">
	<div class="label2">' . get_lang('Category') . ': </div>
       <div class="formw2">';
    $select_types .= '<select style="width: 95%; "   name = "category_id" id="category_id" onChange="changeType();">';
    $select_types .= '<option value="0">---' . get_lang('Select') . '---</option>';
    foreach ($types as $type) {
        $select_types.= "<option value = '" . $type['category_id'] . "'>" . $type['name'] . ":  <br/>" . $type['description'] . "</option>";
    }
    $select_types .= "</select>";
    $select_types .= '</div></div>';
    echo $select_types;
    $select_course = '<div class="row" id="divCourse" >
	<div class="label2"  >' . get_lang('Course') . ':</div>
            <div class="formw2">';
    $select_course .= '<select  class="chzn-select" name = "course_id" id="course_id"  style="width: 40%; display:none;">';
    $select_course .= '<option value="0">---' . get_lang('Select') . '---</option>';
    foreach ($courses_list as $course) {
        $select_course.= "<option value = '" . $course['course_id'] . "'>" . $course['title'] . "</option>";
    }
    $select_course .= "</select>";
    $select_course .= '</div></div>';
    echo $select_course;
    echo '<div class="row" ><div class ="label2">' . get_lang('Subject') . ':</div>
       		<div class="formw2"><input type = "text" id ="subject" name="subject" value="" required ="" style="width:94%"/></div>
		  </div>';
    echo '<div class="row" id="divEmail" ><div class ="label2">' . $plugin->get_lang('PersonalEmail') . ':</div>
       		<div class="formw2"><input type = "email" id ="personal_email" name="personal_email" value=""  style="width:94%"/></div>
		  </div>';
    echo '<input name="project_id" id="project_id" type="hidden" value="">';
    echo '<input name="other_area" id="other_area" type="hidden" value="">';
    echo '<input name="email" id="email" type="hidden" value="">';
    echo '<div class="row">
		<div class="label2">' . get_lang('Message') . '</div>
		<div class="formw2">
			<input type="hidden" id="content" name="content" value="" style="display:none">
		<input type="hidden" id="content___Config" value="ToolbarSet=Messages&amp;Width=95%25&amp;Height=250&amp;ToolbarSets={ %22Messages%22: [  [ %22Bold%22,%22Italic%22,%22-%22,%22InsertOrderedList%22,%22InsertUnorderedList%22,%22Link%22,%22RemoveLink%22 ] ], %22MessagesMaximized%22: [  ] }&amp;LoadPlugin=[%22customizations%22]&amp;EditorAreaStyles=body { background: #ffffff; }&amp;ToolbarStartExpanded=false&amp;CustomConfigurationsPath='.api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/myconfig.js&amp;EditorAreaCSS='.api_get_path(WEB_PATH).'main/css/chamilo/default.css&amp;ToolbarComboPreviewCSS='.api_get_path(WEB_CODE_PATH).'css/chamilo/default.css&amp;DefaultLanguage=es&amp;ContentLangDirection=ltr&amp;AdvancedFileManager=true&amp;BaseHref=' . api_get_path(WEB_PLUGIN_PATH) . PLUGIN_NAME . '/s/&amp;&amp;UserIsCourseAdmin=true&amp;UserIsPlatformAdmin=true" style="display:none">
		<iframe id="content___Frame" src="'.api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/fckeditor.html?InstanceName=content&amp;Toolbar=Messages" width="95%" height="250" frameborder="0" scrolling="no" style="margin: 0px; padding: 0px; border: 0px; background-color: transparent; background-image: none; width: 95%; height: 250px;">
		</iframe>
		</div>
	</div>';
    echo '<div class="row" ><div class ="label2">' . get_lang('Phone') . ' (' . $plugin->get_lang('Optional') . '):</div>
       		<div class="formw2"><input type = "text" id ="phone" name="phone" value="" onkeyup="valid(this,' . "'allowspace'" . ')" onblur="valid(this,' . "'allowspace'" . ')" style="width:94%"/></div>
		</div>';
    echo '<div class="row">
		<div class="label2">' . get_lang('FilesAttachment') . '</div>
		<div class="formw2">
				<span id="filepaths">
				<div id="filepath_1">
					<input type="file" name="attach_1" id="attach_1"  size="20" style="width:94%;"/>
				</div></span>
		</div>
	</div>';
    echo '<div class="row">
		<div class="formw2">
			<span id="link-more-attach">
				<a href="javascript://" onclick="return add_image_form()">' . get_lang('AddOneMoreFile') . '</a></span>&nbsp;
					(' . sprintf(get_lang('MaximunFileSizeX'), format_file_size(api_get_setting('message_max_upload_filesize'))) . ')
			</div>
		</div>';
    echo '<div class="row">
		<div class="label2">
		</div>
		<div class="formw2"><button class="save" name="compose"  type="submit" id="btnsubmit">' . get_lang('SendMessage') . '</button>
		</div>
	</div>';
    echo '</form></div>';
}
/**
 * Save ticke function
 */
function save_ticket()
{
    global $plugin;
    $category_id = $_POST['category_id'];
    $content = $_POST['content'];
    if ($_POST['phone'] != "")
        $content.= '<p style="color:red">&nbsp;' . get_lang('Phone') . ': ' . Security::remove_XSS($_POST['phone']). '</p>';
    $course_id = $_POST['course_id'];
    $project_id = $_POST['project_id'];
    $subject = $_POST['subject'];
    $other_area = (int) $_POST['other_area'];
    $email = $_POST['email'];
    $personal_email = $_POST['personal_email'];
    $file_attachments = $_FILES;

    if (TicketManager::insert_new_ticket(
        $category_id,
        $course_id,
        $project_id,
        $other_area,
        $email,
        $subject,
        $content,
        $personal_email,
        $file_attachments
    )
    ) {
        header('location:' . api_get_path(WEB_PLUGIN_PATH) . PLUGIN_NAME . '/src/myticket.php?message=success');
        exit;
    } else {
        Display::display_header(get_lang('ComposeMessage'));
        Display::display_error_message($plugin->get_lang('ErrorRegisterMessage'));
    }
}

if (!isset($_POST['compose'])) {
    Display::display_header(get_lang('ComposeMessage'));
    show_form_send_ticket();
} else {
    save_ticket();
}

Display::display_footer();
