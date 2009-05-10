<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) 2009 Julio Montoya Armas <gugli100@gmail.com>

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	@package dokeos.admin
==============================================================================
*/

// name of the language file that needs to be included
$language_file='admin';

// resetting the course id
$cidReset=true;

// including some necessary dokeos files
require('../inc/global.inc.php');

require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
require_once ('../inc/lib/xajax/xajax.inc.php');
$xajax = new xajax();
//$xajax->debugOn();
$xajax -> registerFunction ('search_courses');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script();
if (!$_configuration['multiple_access_urls'])
	header('Location: index.php');


// Database Table Definitions
$tbl_access_url_rel_course = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
$tbl_course 			 = Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_access_url 		 = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);

// setting breadcrumbs
$tool_name = get_lang('EditCoursesToURL');
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'access_urls.php', 'name' => get_lang('MultipleAccessURLs'));

$add_type = 'multiple';
if(isset($_REQUEST['add_type']) && $_REQUEST['add_type']!=''){
	$add_type = Security::remove_XSS($_REQUEST['add_type']);
	 
}

$access_url_id=1;
if(isset($_REQUEST['access_url_id']) && $_REQUEST['access_url_id']!=''){
	$access_url_id = Security::remove_XSS($_REQUEST['access_url_id']); 
}

function search_courses($needle, $id)
{
	global $tbl_course;	
	$xajax_response = new XajaxResponse();
	$return = '';
				
	if(!empty($needle)) {		
		// xajax send utf8 datas... datas in db can be non-utf8 datas
		$charset = api_get_setting('platform_charset');
		$needle = mb_convert_encoding($needle, $charset, 'utf-8');
		// search courses where username or firstname or lastname begins likes $needle
		$sql = 'SELECT code, title FROM '.$tbl_course.' u 
				WHERE (title LIKE "'.$needle.'%"
				OR code LIKE "'.$needle.'%"
				) 
				ORDER BY title, code
				LIMIT 11';				
		$rs = api_sql_query($sql, __FILE__, __LINE__);		
        $i=0;        
		while ($course = Database :: fetch_array($rs)) {
			$i++;
            if ($i<=10) {
			     $return .= '<a href="#" onclick="add_user_to_url(\''.addslashes($course['code']).'\',\''.addslashes($course['title']).' ('.addslashes($course['code']).')'.'\')">'.$course['title'].' ('.$course['code'].')</a><br />';
            } else {
            	$return .= '...<br />';
            }
		}
	}
	$xajax_response -> addAssign('ajax_list_courses','innerHTML',api_utf8_encode($return));
	return $xajax_response;
}

$xajax -> processRequests();
$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '
<script type="text/javascript">
function add_user_to_url(code, content) {

	document.getElementById("course_to_add").value = "";
	document.getElementById("ajax_list_courses").innerHTML = "";
	
	destination = document.getElementById("destination_users");
	destination.options[destination.length] = new Option(content,code);
	
	destination.selectedIndex = -1;
	sortOptions(destination.options);	
}
	
function send() {
	
	if (document.formulaire.access_url_id.value!=0) {	
		document.formulaire.form_sent.value=0; 
		document.formulaire.add_type.value=\''.$add_type.'\';		
		document.formulaire.submit();
	}	
}
	
function remove_item(origin)
{
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			origin.options[i]=null;
			i = i-1;
		}
	}
}
</script>';

$form_sent=0;
$errorMsg='';
$UserList=$SessionList=array();
$users=$sessions=array();

if($_POST['form_sent']) {	
	$form_sent=$_POST['form_sent'];
	$course_list=$_POST['course_list'];	
	
	if(!is_array($course_list)) {
		$course_list=array();
	}
	
	if($form_sent == 1) { 
		if ($access_url_id==0) {						
			header('Location: access_url_edit_users_to_url.php?action=show_message&message='.get_lang('SelectURL'));
		}
		elseif(is_array($course_list) ) {									
			UrlManager::update_urls_rel_course($course_list,$access_url_id);
			header('Location: access_urls.php?action=show_message&message='.get_lang('CoursesWereEdited'));
		}		
	}
}

Display::display_header($tool_name);

echo '<div class="actions" style="height:22px;">';
echo '<div style="float:right;">		
		<a href="'.api_get_path(WEB_CODE_PATH).'admin/access_url_add_courses_to_url.php">'.Display::return_icon('view_more_stats.gif',get_lang('AddUserToURL'),'').get_lang('AddCoursesToURL').'</a>												
	  </div><br />';		  
echo '</div>';	

api_display_tool_title($tool_name);

if ($_GET['action'] == 'show_message')
	Display :: display_normal_message(Security::remove_XSS(stripslashes($_GET['message'])));

$no_course_list = $course_list = array();
$ajax_search = $add_type == 'unique' ? true : false;

if($ajax_search) {		
	$courses=UrlManager::get_url_rel_course_data($access_url_id);
	foreach($courses as $course) {
		$course_list[$course['course_code']] = $course ;
	}	
} else {	
	$courses=UrlManager::get_url_rel_course_data();
		
	foreach($courses as $course) {
		if($course['access_url_id'] == $access_url_id) {
			$course_list[$course['course_code']] = $course ;
		}
	}
		
	$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
	$sql="SELECT code, title
	  	  	FROM $tbl_course u	
			ORDER BY title, code";	
	$result=api_sql_query($sql,__FILE__,__LINE__);	
	$courses=api_store_result($result);
	$course_list_leys = array_keys($course_list);
	foreach($courses as $course) {	
		if (!in_array($course['code'],$course_list_leys))
			$no_course_list[$course['code']] = $course ;
	}	
}


if($add_type == 'multiple') {
	$link_add_type_unique = '<a href="'.api_get_self().'?add_type=unique&access_url_id='.$access_url_id.'">'.get_lang('SessionAddTypeUnique').'</a>';
	$link_add_type_multiple = get_lang('SessionAddTypeMultiple');
} else {
	$link_add_type_unique = get_lang('SessionAddTypeUnique');
	$link_add_type_multiple = '<a href="'.api_get_self().'?add_type=multiple&access_url_id='.$access_url_id.'">'.get_lang('SessionAddTypeMultiple').'</a>';
}

$url_list = UrlManager::get_url_data();

?>	

<div style="text-align: left;">
	<?php echo $link_add_type_unique ?>&nbsp;|&nbsp;<?php echo $link_add_type_multiple ?>
</div>
<br /><br />
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;" <?php if($ajax_search){echo ' onsubmit="valide();"';}?> >
<?php echo get_lang('SelectUrl').' : '; ?>
<select name="access_url_id" onchange="javascript:send();">
<option value="0"> <?php echo get_lang('SelectUrl')?></option>
	<?php	
	$url_selected='';
	foreach ($url_list as $url_obj) {
		$checked = '';
		if (!empty($access_url_id)) {
			if ($url_obj[0]==$access_url_id) {
			$checked = 'selected=true';
			$url_selected=$url_obj[1];
			}
		}
		if ($url_obj['active']==1) {						
			?>
				<option <?php echo $checked;?> value="<?php echo $url_obj[0]; ?>"> <?php echo $url_obj[1]; ?></option>
			<?php
		}
	}
	?>		
</select>
<br /><br />
<input type="hidden" name="form_sent" value="1" />
<input type="hidden" name="add_type" value = "<?php echo $add_type ?>" /> 

<?php
if(!empty($errorMsg)) {
	Display::display_normal_message($errorMsg); //main API
}
?>

<table border="0" cellpadding="5" cellspacing="0" width="100%">

<!-- Users -->
<tr>
  <td align="center"><b><?php echo get_lang('CourseListInPlatform') ?> :</b>
  </td>
  <td></td>  
  <td align="center"><b><?php echo get_lang('CourseListIn').' '.$url_selected; ?></b></td>
</tr>

<tr>
  <td align="center">
  <div id="content_source">
  	  <?php
  	  if($ajax_search) {
  	  	?>
		<input type="text" id="course_to_add" onkeyup="xajax_search_courses(this.value,document.formulaire.access_url_id.options[document.formulaire.access_url_id.selectedIndex].value)" />
		<div id="ajax_list_courses"></div>
		<?php
  	  } else {
  	  ?>  	  
	  <select id="origin_users" name="no_course_list[]" multiple="multiple" size="15" style="width:300px;">
		<?php
		foreach($no_course_list as $no_course) {
		?>
			<option value="<?php echo $no_course['code']; ?>"><?php echo $no_course['title'].' ('.$no_course['code'].')'; ?></option>

$xajax -> processRequests();

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '
<script type="text/javascript">
function add_user_to_url (code, content) {

	document.getElementById("course_to_add").value = "";
	document.getElementById("ajax_list_courses").innerHTML = "";
	
	destination = document.getElementById("destination_users");
	destination.options[destination.length] = new Option(content,code);
	
	destination.selectedIndex = -1;
	sortOptions(destination.options);
	
}
function remove_item(origin)
{
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			origin.options[i]=null;
			i = i-1;
		}
	}
}
</script>';
		<?php
		}
		unset($no_course_list);
		?>

	  </select>
	<?php
  	  }
  	 ?>
  </div>
  </td>
  <td width="10%" valign="middle" align="center">
  <?php
  if($ajax_search) {
	?>
	<input type="button" onclick="remove_item(document.getElementById('destination_users'))" value="<<" />
  	<?php
  } else {
  	?>
	<input type="button" onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))" value=">>" />
	<br /><br />
	<input type="button" onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))" value="<<" />
	<?php 
  } 
  ?>
	<br /><br /><br /><br /><br /><br />
  </td>
  <td align="center">
  <select id="destination_users" name="course_list[]" multiple="multiple" size="15" style="width:300px;">

<?php

foreach($course_list as $course) {
?>
	<option value="<?php echo $course['course_code']; ?>"><?php echo $course['title'].' ('.$course['course_code'].')'; ?></option>

<?php
}
unset($course_list);
?>

  </select></td>
</tr>

<tr>
	<td colspan="3" align="center">
		<br />
		<?php
		if(isset($_GET['add']))
			echo '<input type="button" value="'.get_lang('EditCourses').'" onclick="valide()" />';
		else
			echo '<input type="button" value="'.get_lang('EditCourses').'" onclick="valide()" />';
		?>
	</td>
</tr>




</table>

</form>
<script type="text/javascript">
<!--
function moveItem(origin , destination) {
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
			origin.options[i]=null;
			i = i-1;
		}
	}
	destination.selectedIndex = -1;
	sortOptions(destination.options);
}

function sortOptions(options) {
	newOptions = new Array();
	for (i = 0 ; i<options.length ; i++)
		newOptions[i] = options[i];

	newOptions = newOptions.sort(mysort);
	options.length = 0;
	for(i = 0 ; i < newOptions.length ; i++)
		options[i] = newOptions[i];

}

function mysort(a, b) {
	if(a.text.toLowerCase() > b.text.toLowerCase()){
		return 1;
	}
	if(a.text.toLowerCase() < b.text.toLowerCase()){
		return -1;
	}
	return 0;
}

function valide(){
	var options = document.getElementById('destination_users').options;
	for (i = 0 ; i<options.length ; i++)
		options[i].selected = true;
	/*
	var options = document.getElementById('destination_classes').options;
	for (i = 0 ; i<options.length ; i++)
		options[i].selected = true;
		*/
	document.forms.formulaire.submit();
}


function loadUsersInSelect(select){

	var xhr_object = null;

	if(window.XMLHttpRequest) // Firefox
		xhr_object = new XMLHttpRequest();
	else if(window.ActiveXObject) // Internet Explorer
		xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
	else  // XMLHttpRequest non supporté par le navigateur
	alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");

	//xhr_object.open("GET", "loadUsersInSelect.ajax.php?id_session=<?php echo $id_session ?>&letter="+select.options[select.selectedIndex].text, false);
	xhr_object.open("POST", "loadUsersInSelect.ajax.php");

	xhr_object.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");


	nosessionUsers = makepost(document.getElementById('origin_users'));
	sessionUsers = makepost(document.getElementById('destination_users'));
	nosessionClasses = makepost(document.getElementById('origin_classes'));
	sessionClasses = makepost(document.getElementById('destination_classes'));
	xhr_object.send("nosessionusers="+nosessionUsers+"&sessionusers="+sessionUsers+"&nosessionclasses="+nosessionClasses+"&sessionclasses="+sessionClasses);

	xhr_object.onreadystatechange = function() {
		if(xhr_object.readyState == 4) {
			document.getElementById('content_source').innerHTML = result = xhr_object.responseText;
			//alert(xhr_object.responseText);
		}
	}
}

function makepost(select){
	var options = select.options;
	var ret = "";
	for (i = 0 ; i<options.length ; i++)
		ret = ret + options[i].value +'::'+options[i].text+";;";

	return ret;

}
-->

</script>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
