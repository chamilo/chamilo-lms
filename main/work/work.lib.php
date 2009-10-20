<?php //$Id: work.lib.php 22357 2009-07-24 17:44:17Z juliomontoya $
/* For licensing terms, see /dokeos_license.txt */
/**
*	@package dokeos.work
* 	@author Thomas, Hugues, Christophe - original version
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University - ability for course admins to specify wether uploaded documents are visible or invisible by default.
* 	@author Roan Embrechts, code refactoring and virtual course support
* 	@author Frederic Vauthier, directories management
* 	@version $Id: work.lib.php 22357 2009-07-24 17:44:17Z juliomontoya $
*/
/**
 * Displays action links (for admins, authorized groups members and authorized students)
 * @param	string	Current dir
 * @param	integer	Whether to show tool options
 * @param	integer	Whether to show upload form option
 * @return	void
 */

require_once '../document/document.inc.php';
require_once '../inc/lib/fileDisplay.lib.php';

function display_action_links($cur_dir_path, $always_show_tool_options, $always_show_upload_form)
{
	global $gradebook;
	$display_output = "";
	$origin = isset($_GET['origin'])?Security::remove_XSS($_GET['origin']):'';

	$origin = api_get_tools_lists($origin);

	if (strlen($cur_dir_path) > 0 && $cur_dir_path != '/') {
		$parent_dir = dirname($cur_dir_path);
		$display_output .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.$origin.'&gradebook='.$gradebook.'&curdirpath='.$parent_dir.'">'.Display::return_icon('folder_up.gif', get_lang('Up')).' '.get_lang('Up').'</a>';
	}
	echo '<div class="actions">';
	if ($_GET['display_tool_options'] == 'true' OR $_GET['display_upload_form'] == 'true' )
	{
		if ($origin!='learnpath') {
			echo '<a href="work.php?gradebook='.$gradebook.'">'.Display::return_icon('back.png',get_lang('BackToWorksList')).' '.get_lang('BackToWorksList').'</a>';
		}
	}
	if (! $always_show_tool_options && api_is_allowed_to_edit(null,true) && $origin != 'learnpath') {
		// Create dir
		$display_output .=	'<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;toolgroup='.Security::remove_XSS($_GET['toolgroup']).'&amp;curdirpath='.$cur_dir_path.'&amp;createdir=1&origin='.$origin.'&gradebook='.$gradebook.'">'.Display::return_icon('folder_new.gif', get_lang('CreateAssignment')).' '.get_lang('CreateAssignment').' </a>';
		// Options
		$display_output .=	"<a href=\"".api_get_self()."?".api_get_cidreq()."&curdirpath=".$cur_dir_path."&amp;origin=".$origin."&amp;display_tool_options=true&amp;origin=".$origin."&amp;gradebook=".$gradebook."\">".Display::return_icon('acces_tool.gif', get_lang("EditToolOptions")).' ' . get_lang("EditToolOptions") . "</a>";
	}

	if (! $always_show_upload_form && api_is_allowed_to_session_edit(false,true)) {

			$display_output .= "<a href=\"".api_get_self()."?".api_get_cidreq()."&curdirpath=".$cur_dir_path."&amp;display_upload_form=true&amp;origin=".$origin."&amp;gradebook=".$gradebook."\">".Display::return_icon('submit_file.gif', get_lang("UploadADocument"))." ". get_lang("UploadADocument") .'</a>';

	}

	if (api_is_allowed_to_edit(null,true) && $origin != 'learnpath' && api_is_allowed_to_session_edit(false,true))
	{
		// delete all files
		$display_output .= 	"<a href=\"".api_get_self()."?".api_get_cidreq()."&amp;curdirpath=".$cur_dir_path."&amp;origin=$origin&amp;gradebook=$gradebook&amp;delete=all\" ".
			"onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."')) return false;\">".
			Display::return_icon('delete.gif', get_lang('Delete')).' '.get_lang('DeleteAllFiles')."</a>";

		// make all files visible or invisible
		$work_table 		= Database::get_course_table(TABLE_STUDENT_PUBLICATION);
		$sql_query = "SHOW COLUMNS FROM ".$work_table." LIKE 'accepted'";
		$sql_result = Database::query($sql_query,__FILE__,__LINE__);

		if ($sql_result) {
			$columnStatus = Database::fetch_array($sql_result);

			if ($columnStatus['Default'] == 1) {
				$display_output .=	"<a href=\"".api_get_self()."?".api_get_cidreq()."&curdirpath=".$cur_dir_path."&amp;origin=$origin&amp;gradebook=$gradebook&amp;make_invisible=all\">".
						Display::return_icon('visible.gif', get_lang('MakeAllPapersInvisible')).' '.get_lang('MakeAllPapersInvisible').
						"</a>\n";
			} else {
				$display_output .=	"<a href=\"".api_get_self()."?".api_get_cidreq()."&amp;curdirpath=".$cur_dir_path."&amp;origin=$origin&amp;gradebook=$gradebook&amp;make_visible=all\">".
						Display::return_icon('invisible.gif', get_lang('MakeAllPapersVisible')).' '.get_lang('MakeAllPapersVisible').
						"</a>\n";
			}
		}

	}

	if ($display_output != "")
	{
		echo $display_output;
	}
	echo '</div>';
}

/**
* Displays all options for this tool.
* These are
* - make all files visible / invisible
* - set the default visibility of uploaded files
*
* @param $uploadvisibledisabled
* @param $origin
* @param $base_work_dir Base working directory (up to '/work')
* @param $cur_dir_path	Current subdirectory of 'work/'
* @param $cur_dir_path_url Current subdirectory of 'work/', url-encoded
*/
function display_tool_options($uploadvisibledisabled, $origin,$base_work_dir,$cur_dir_path,$cur_dir_path_url) {
	global $charset, $group_properties,$gradebook;
	$is_allowed_to_edit = api_is_allowed_to_edit(null,true);
	$work_table 		= Database::get_course_table(TABLE_STUDENT_PUBLICATION);

	if (! $is_allowed_to_edit) {
		return;
	}
	echo '<form method="post" action="'.api_get_self().'?origin='.$origin.'&gradebook='.$gradebook.'&display_tool_options=true">';
	echo '<div class="row"><div class="form_header">'.get_lang('EditToolOptions').'</div></div>';
	display_default_visibility_form($uploadvisibledisabled);
	echo '<div class="row">
				<div class="formw">
					<button type="submit" class="save" name="changeProperties" value="'.get_lang('Ok').'">'.get_lang('Ok').'</button>
				</div>
			</div>';
	echo '</form>';

}

/**
* Displays the form where course admins can specify wether uploaded documents
* are visible or invisible by default.
*
* @param $uploadvisibledisabled
* @param $origin
*/
function display_default_visibility_form($uploadvisibledisabled) {
	?>
	<div class="row">
		<div class="label">
			<?php echo get_lang("_default_upload"); ?>
		</div>
		<div class="formw">
			<input class="checkbox" type="radio" name="uploadvisibledisabled" value="0"
			<?php if($uploadvisibledisabled==0) echo "checked";  ?> />
		<?php echo get_lang("_new_visible");?><br />
		<input class="checkbox" type="radio" name="uploadvisibledisabled" value="1"
			<?php if($uploadvisibledisabled==1) echo "checked";  ?> />
				<?php echo get_lang("_new_unvisible"); ?>
		</div>
	</div>
	<?php
}

/**
* This function displays the firstname and lastname of the user as a link to the user tool.
*
* @see this is the same function as in the new forum, so this probably has to move to a user library.
*
* @todo move this function to the user library
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function display_user_link($user_id, $name='')
{
	global $_otherusers;

	if ($user_id<>0) {

		$table_user = Database::get_main_table(TABLE_MAIN_USER);
		$sql="SELECT * FROM $table_user WHERE user_id='".Database::escape_string($user_id)."'";
		$result=Database::query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($result);
		if ($name=='') {
			return "<a href=\"../user/userInfo.php?cidReq=".api_get_course_id()."&gradebook=$gradebook&origin=&uInfo=".$row['user_id']."\">".api_get_person_name($row['firstname'], $row['lastname'])."</a>";
		} else {
			return "<a href=\"../user/userInfo.php?cidReq=".api_get_course_id()."&gradebook=$gradebook&origin=&uInfo=".$user_id."\">".$name."</a>";
		}
	} else {
		return $name.' ('.get_lang('Anonymous').')';
	}
}

/**
* converts 2008-10-06 12:45:00 to timestamp
*/
function convert_date_to_number($default)
{
	// 2008-10-12 00:00:00 ---to--> 12345672218 (timestamp)
	$parts = split(' ',$default);
	list($d_year,$d_month,$d_day) = split('-',$parts[0]);
	list($d_hour,$d_minute,$d_second) = split(':',$parts[1]);
	return mktime($d_hour, $d_minute, $d_second, $d_month, $d_day, $d_year);
}

/**
* converts 1-9 to 01-09
*/
function two_digits($number)
{
	$number = (int)$number;
	return ($number < 10) ? '0'.$number : $number;
}

/**
* converts 2008-10-06 12:45:00 to -> array($data'year'=>2008,$data'month'=>10 etc...)
*/
function convert_date_to_array($date,$group)
{
	$parts = split(' ',$date);
	list($data[$group.'[year]'],$data[$group.'[month]'],$data[$group.'[day]']) = split('-',$parts[0]);
	list($data[$group.'[hour]'],$data[$group.'[minute]']) = split(':',$parts[1]);
	return $data;
}

/**
* get date from a group of date
*/
function get_date_from_group($group) {
	return $_POST[$group]['year'].'-'.two_digits($_POST[$group]['month']).'-'.two_digits($_POST[$group]['day']).' '.two_digits($_POST[$group]['hour']).':'.two_digits($_POST[$group]['minute']).':00';
}

/**
* create a group of select from a date
*/
function create_group_date_select($prefix='') {
	$minute = range(10,59);
	$d_year=date('Y');
	array_unshift($minute,'00','01','02','03','04','05','06','07','08','09');
	$group_name[] = FormValidator :: createElement('select',$prefix.'day','',array_combine(range(1,31),range(1,31)));
	$group_name[] = FormValidator :: createElement('select',$prefix.'month','',array_combine(range(1,12),array(get_lang('JanuaryLong'),get_lang('FebruaryLong'),get_lang('MarchLong'),get_lang('AprilLong'),get_lang('MayLong'),get_lang('JuneLong'),get_lang('JulyLong'),get_lang('AugustLong'),get_lang('SeptemberLong'),get_lang('OctoberLong'),get_lang('NovemberLong'),get_lang('DecemberLong'))));
	$group_name[] = FormValidator :: createElement('select',$prefix.'year','',array($d_year=>$d_year,$d_year+1=>$d_year+1));
	$group_name[] = FormValidator :: createElement('select',$prefix.'hour','',array_combine(range(0,23),range(0,23)));
	$group_name[] = FormValidator :: createElement('select',$prefix.'minute','',$minute);
	return $group_name;
}

/**
* Display the list of student publications, taking into account the user status
*
* @param $currentCourseRepositoryWeb, the web location of the course folder
* @param $link_target_parameter - should there be a target parameter for the links
* @param $dateFormatLong - date format
* @param $origin - typically empty or 'learnpath'
*/
function display_student_publications_list($work_dir,$sub_course_dir,$currentCourseRepositoryWeb, $link_target_parameter, $dateFormatLong, $origin,$add_in_where_query='')
{
	global $charset,$timeNoSecFormat,$dateFormatShort,$gradebook;
	// Database table names
	$work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$iprop_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
	$work_assigment = Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
	$is_allowed_to_edit = api_is_allowed_to_edit(null,true);
	$user_id = api_get_user_id();
	$publications_list = array();
	$sort_params = array();

	if( isset($_GET['column'])) {
		$sort_params[] = 'column='.Security::remove_XSS($_GET['column']);
	}
	if( isset($_GET['page_nr'])) {
		$sort_params[] = 'page_nr='.Security::remove_XSS($_GET['page_nr']);
	}
	if( isset($_GET['per_page'])) {
		$sort_params[] = 'per_page='.Security::remove_XSS($_GET['per_page']);
	}
	if( isset($_GET['direction'])) {
		$sort_params[] = 'direction='.Security::remove_XSS($_GET['direction']);
	}
	$sort_params = implode('&amp;',$sort_params);
	$my_params=$sort_params;
	$origin=Security::remove_XSS($origin);

	if(substr($sub_course_dir,-1,1)!='/' && !empty($sub_course_dir)) {
		$sub_course_dir = $sub_course_dir.'/';
	}
	if($sub_course_dir == '/') {
		$sub_course_dir='';
	}

	//condition for the session
	$session_id = api_get_session_id();
	$condition_session = api_get_session_condition($session_id);

	//Get list from database
	if($is_allowed_to_edit) {
		$sql_get_publications_list = 	"SELECT * " .
										"FROM  ".$work_table." " .
										"WHERE url LIKE BINARY '$sub_course_dir%' " .
										"AND url NOT LIKE BINARY '$sub_course_dir%/%' " .$add_in_where_query.
												 $condition_session.
		                 				"ORDER BY sent_date DESC";

		$sql_get_publications_num = 	"SELECT count(*) " .
										"FROM  ".$work_table." " .
										"WHERE url LIKE BINARY '$sub_course_dir%' " .
										"AND url NOT LIKE BINARY '$sub_course_dir%/%' " .$add_in_where_query.
										 $condition_session.
		                 				"ORDER BY id";

	} else {
		if (!empty($_SESSION['toolgroup'])) {
			$group_query = " WHERE post_group_id = '".$_SESSION['toolgroup']."' "; // set to select only messages posted by the user's group
			$subdirs_query = "AND url NOT LIKE BINARY '$sub_course_dir%/%' AND url LIKE BINARY '$sub_course_dir%'";
		} else {
			$group_query = " WHERE post_group_id = '0' ";
			$subdirs_query = "AND url NOT LIKE '$sub_course_dir%/%' AND url LIKE '$sub_course_dir%'";
		}

	   	$sql_get_publications_list = "SELECT * FROM  $work_table $group_query $subdirs_query ".$add_in_where_query."  $condition_session ORDER BY id";
		$sql_get_publications_num = "SELECT count(url) " .
										"FROM  ".$work_table." " .
										"WHERE url LIKE BINARY '$sub_course_dir%' " .
										"AND url NOT LIKE BINARY '$sub_course_dir%/%' " .$add_in_where_query.
										 $condition_session.
		                 				"ORDER BY id";

	}

	$sql_result = Database::query($sql_get_publications_list,__FILE__,__LINE__);
	$sql_result_num = Database::query($sql_get_publications_num,__FILE__,__LINE__);

	$row=Database::fetch_array($sql_result_num);
	$count_files=$row[0];

	$table_header[] = array(get_lang('Type'),true,'style="width:40px"');
	$table_header[] = array(get_lang('Title'),true);

	if ($count_files!=0) {
		$table_header[] = array(get_lang('Authors'),true);
	}

	$table_header[] = array(get_lang('Date'),true);

	if ($origin != 'learnpath') {
		if ($is_allowed_to_edit) {
		$table_header[] = array(get_lang('Modify'),true);
		}
		$table_header[] = array('RealDate',false);
	}


	// An array with the setting of the columns -> 1: columns that we will show, 0:columns that will be hide
	$column_show[]=1; // type
	$column_show[]=1; // title

	if ($count_files!=0) {
		$column_show[]=1;	 // authors
	}

	$column_show[]=1; //date

	if( $is_allowed_to_edit && $origin != 'learnpath') {
		$column_show[]=1; //modify
	}

	$column_show[]=0;	 //real date in correct format


	// Here we change the way how the colums are going to be sort
	// in this case the the column of LastResent ( 4th element in $column_header) we will be order like the column RealDate
	// because in the column RealDate we have the days in a correct format "2008-03-12 10:35:48"

	$column_order[]=1; //type
	$column_order[]=2; // title

	if ($count_files!=0) {
		$column_order[]=3; //authors
	}

	$column_order[]=6; // date

	if( $is_allowed_to_edit) {
		$column_order[]=5;
	}

	$column_order[]=6;

	$table_data = array();
	$dirs_list = get_subdirs_list($work_dir);

	$my_sub_dir = str_replace('work/','',$sub_course_dir);

	// List of all folders
	foreach($dirs_list as $dir) {
		if ($my_sub_dir=='') {
				$mydir_temp = '/'.$dir;
		}else {
			$mydir_temp = '/'.$my_sub_dir.$dir;
		}

		$sql_select_directory= "SELECT prop.lastedit_date, id, author, has_properties, view_properties, description, qualification,id FROM ".$iprop_table." prop INNER JOIN ".$work_table." work ON (prop.ref=work.id) WHERE ";
					if (!empty($_SESSION['toolgroup'])) {
						$sql_select_directory.=" work.post_group_id = '".$_SESSION['toolgroup']."' "; // set to select only messages posted by the user's group
					} else {
						$sql_select_directory.=" work.post_group_id = '0' ";
					}
		$sql_select_directory.=" AND work.url LIKE BINARY '".$mydir_temp."' AND work.filetype = 'folder' AND prop.tool='work' $condition_session";
		$result=Database::query($sql_select_directory,__FILE__,__LINE__);
		$row=Database::fetch_array($result);


		 if(!$row) {
			 // the folder belongs to another session
	         continue;
		 }
		$direc_date= $row['lastedit_date']; //directory's date
		$author= $row['author']; //directory's author
		$view_properties=$row['view_properties'];
		$is_assignment = $row['has_properties'];
		$id2=$row['id'];
		$mydir = $my_sub_dir.$dir;

		if ($is_allowed_to_edit) {
			isset($_GET['edit_dir'])?$clean_edit_dir=Security :: remove_XSS(Database::escape_string($_GET['edit_dir'])):$clean_edit_dir='';

			// form edit directory
			if(isset($clean_edit_dir) && $clean_edit_dir==$mydir) {
				if(!empty($row['has_properties'])) {
					$sql = Database::query('SELECT * FROM '.$work_assigment.' WHERE id = '."'".$row['has_properties']."'".' LIMIT 1',__FILE__,__LINE__);
					$homework = Database::fetch_array($sql);
				}

				$form_folder = new FormValidator('edit_dir', 'post', api_get_self().'?curdirpath='.$my_sub_dir.'&origin='.$origin.'&gradebook='.$gradebook.'&edit_dir='.$mydir);

				$group_name[] = FormValidator :: createElement('text','dir_name');
				$form_folder -> addGroup($group_name,'my_group',get_lang('Title'));
				$form_folder -> addGroupRule('my_group',get_lang('ThisFieldIsRequired'),'required');
				$defaults = array('my_group[dir_name]'=>html_entity_decode($dir),'description'=>html_entity_decode($row['description']));
				$form_folder-> addElement('textarea','description',get_lang('Description'),array('rows'=>5,'cols'=>50));
				$qualification_input[] = FormValidator :: createElement('text','qualification');

				$form_folder -> addGroup($qualification_input,'qualification',get_lang('QualificationNumberOver'),'size="10"');
				$there_is_a_end_date =false;
				if($row['view_properties']=='1') {
					if($homework['expires_on']!='0000-00-00 00:00:00'){
						$there_is_a_expire_date = true;
						$form_folder -> addGroup(create_group_date_select(),'expires',get_lang('ExpiresAt'));
					}
					if($homework['ends_on']!='0000-00-00 00:00:00') {
						$there_is_a_end_date = true;
						$form_folder -> addGroup(create_group_date_select(),'ends',get_lang('EndsAt'));
					}
					$form_folder -> addRule(array('expires','ends'), get_lang('DateExpiredNotBeLessDeadLine'), 'comparedate');
				} else {
						$form_folder -> addElement('html','<div class="row">
	 	                         <div class="label">&nbsp;</div>
 	  	                         <div class="formw">
 	  	                                 <a href="javascript://" onclick=" return plus();" ><span id="plus">&nbsp;<img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'</span></a>
 	  	                         </div>
	  	                         </div>	');

	  	                $form_folder -> addElement('html','<div id="options" style="display: none;">');
									if(empty($default)) {
										$default = date('Y-m-d 12:00:00');
									}

										$parts = split(' ',$default);
										list($d_year,$d_month,$d_day) = split('-',$parts[0]);
										list($d_hour,$d_minute) = split(':',$parts[1]);

									if($homework['expires_on']='0000-00-00 00:00:00') {
										$homework['expires_on']=date("Y-m-d H:i:s");
										$there_is_a_expire_date = true;
										$form_folder -> addElement('checkbox', 'enableExpiryDate',null,get_lang('EnableExpiryDate'),'onclick="javascript:if(this.checked==true){document.getElementById(\'option1\').style.display = \'block\';}else{document.getElementById(\'option1\').style.display = \'none\';}"');
										$form_folder -> addElement('html','<div id=\'option1\' style="display:none">');
										$form_folder -> addGroup(create_group_date_select(),'expires',get_lang('ExpiresAt'));
										$form_folder -> addElement('html','</div>');
									}
									if($homework['ends_on']='0000-00-00 00:00:00') {
										$homework['ends_on']=date("Y-m-d H:i:s");
										$there_is_a_end_date = true;
										$form_folder -> addElement('checkbox', 'enableEndDate', null, get_lang('EnableEndDate'),'onclick="javascript:if(this.checked==true){document.getElementById(\'option2\').style.display = \'block\';}else{document.getElementById(\'option2\').style.display = \'none\';}"');
										$form_folder -> addElement('html','<div id=\'option2\' style="display:none">');
										$form_folder -> addGroup(create_group_date_select(),'ends',get_lang('EndsAt'));
										$form_folder -> addElement('html','</div>');
									}
									$form_folder -> addRule (array('expires','ends'), get_lang('DateExpiredNotBeLessDeadLine'), 'comparedate');

						$form_folder -> addElement('html','</div>');
				}

				$form_folder -> addElement('style_submit_button','submit',get_lang('ModifyDirectory'),'class="save"');

				if($there_is_a_end_date == true) {
					$defaults = array_merge($defaults,convert_date_to_array($homework['ends_on'],'ends'));
				}

				if($there_is_a_expire_date == true) {
					$defaults = array_merge($defaults,convert_date_to_array($homework['expires_on'],'expires'));
				}

				if(!empty($row['qualification'])) {
					$defaults = array_merge($defaults,array('qualification[qualification]'=>$row['qualification']));
				}
				$form_folder -> setDefaults($defaults);
				$display_edit_form=true;

				if($form_folder -> validate()) {
					$TABLEAGENDA 		= Database::get_course_table(TABLE_AGENDA);
					if($there_is_a_end_date == true || $there_is_a_expire_date == true) {
						if($row['view_properties']=='1') {
								$sql_add_publication = "UPDATE ".$work_table." SET has_properties  = '".$row['has_properties'].  "', view_properties=1 where id ='".$row['id']."'";
								Database::query($sql_add_publication, __FILE__, __LINE__);
								$expires_query= ' SET expires_on = '."'".(($there_is_a_expire_date == true)?get_date_from_group('expires'):'0000-00-00 00:00:00')."'".',';
								$ends_query =   ' ends_on = '."'".(($there_is_a_end_date == true) ? get_date_from_group('ends') : '0000-00-00 00:00:00')."'";
								Database::query('UPDATE '.$work_assigment.$expires_query.$ends_query.' WHERE id = '."'".$row['has_properties']."'",__FILE__,__LINE__);
						} else if($row['view_properties']=='0') {
								if ($_POST['enableExpiryDate']=='1') {
									$expires_query= ' SET expires_on = '."'".(($there_is_a_expire_date == true)?get_date_from_group('expires'):'0000-00-00 00:00:00')."'";
									//$ends_query =   ' ends_on = '."'".(($there_is_a_end_date == true) ? get_date_from_group('ends') : '0000-00-00 00:00:00')."'";
									Database::query('UPDATE '.$work_assigment.$expires_query.' WHERE id = '."'".$row['has_properties']."'",__FILE__,__LINE__);
									$sql_add_publication = "UPDATE ".$work_table." SET has_properties  = '".$row['has_properties'].  "', view_properties=1 where id ='".$row['id']."'";
									Database::query($sql_add_publication, __FILE__, __LINE__);
								}
								if ($_POST['enableEndDate']=='1') {
									//$expires_query= ' SET expires_on = '."'".(($there_is_a_expire_date == true)?get_date_from_group('expires'):'0000-00-00 00:00:00')."'".',';
									$ends_query =   ' SET ends_on = '."'".(($there_is_a_end_date == true) ? get_date_from_group('ends') : '0000-00-00 00:00:00')."'";
									Database::query('UPDATE '.$work_assigment.$ends_query.' WHERE id = '."'".$row['has_properties']."'",__FILE__,__LINE__);
									$sql_add_publication = "UPDATE ".$work_table." SET has_properties  = '".$row['has_properties'].  "', view_properties=1 where id ='".$row['id']."'";
									Database::query($sql_add_publication, __FILE__, __LINE__);
								}
						}

					}
					//if($_POST['qualification']['qualification']!='')
						Database::query('UPDATE '.$work_table.' SET description = '."'".Database::escape_string(Security::remove_XSS($_POST['description']))."'".', qualification = '."'".Database::escape_string($_POST['qualification']['qualification'])."'".' WHERE id = '."'".$row['id']."'",__FILE__,__LINE__);
						//Database::query('UPDATE '.Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK).' SET weight = '."'".Database::escape_string($_POST['qualification']['qualification'])."'".' WHERE course_code = '."'".api_get_course_id()."'".' AND ref_id = '."'".$row['id']."'".'',__FILE__,__LINE__);

					Display::display_confirmation_message(get_lang('FolderEdited'));

					$values = $form_folder -> exportValues();
					$values = $values['my_group'];
					$dir_name = replace_dangerous_char($values['dir_name']);
					$dir_name = disable_dangerous_file($dir_name);
					update_dir_name($mydir,$dir_name);
					$mydir = $my_sub_dir.$dir_name;
					$dir = $dir_name;
					$display_edit_form=false;

					// gets calendar_id from student_publication_assigment
					$sql = "SELECT add_to_calendar FROM $work_assigment WHERE publication_id ='".$row['id']."'";
					$res = Database::query($sql,__FILE__,__LINE__);
					$calendar_id = Database::fetch_row($res);
					// update from agenda if it exists
					if (!empty($calendar_id[0])) {
					$sql = "UPDATE ".$TABLEAGENDA."
							SET title='".$dir_name."',
								content = '".$dir_name."',
								end_date='".get_date_from_group('ends')."'
							WHERE id='".$calendar_id[0]."'";
					Database::query($sql,__FILE__,__LINE__);
					}


				}
			}
		}

		$action = '';
		$row = array();
		$class = '';
		$row[] = '<img src="../img/folder_document.gif" border="0" hspace="5" align="middle" alt="'.get_lang('Assignment').'" title="'.get_lang('Assignment').'" />'; //image
		//$a_count_directory=count_dir($work_dir.'/'.$dir,false);

		$cant_files=0;
		$cant_dir = 0;
		if(api_is_allowed_to_edit()) {
		$sql_document = "SELECT count(*) FROM $work_table WHERE  url NOT LIKE '".$sub_course_dir.$dir."/%/%' AND url LIKE '".$sub_course_dir.$dir."/%'";
		} else {
			// gets admin_course
			$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
			$table_user = Database :: get_main_table(TABLE_MAIN_USER);
			$sql = "SELECT course_user.user_id FROM $table_user user, $table_course_user course_user
					  WHERE course_user.user_id=user.user_id AND course_user.course_code='".api_get_course_id()."' AND course_user.status='1'";
			$res = Database::query($sql,__FILE__,__LINE__);
			$admin_course = '';
			while($row_admin = Database::fetch_row($res)) {
				$admin_course .='\''.$row_admin[0].'\',';
			}
		$sql_document = "SELECT count(*) FROM $work_table s, $iprop_table p WHERE s.id = p.ref AND p.tool='work' AND lastedit_user_id IN(".$admin_course.'\''.api_get_user_id().'\''.") AND s.accepted='1' AND url NOT LIKE '".$sub_course_dir.$dir."/%/%' AND url LIKE '".$sub_course_dir.$dir."/%'";
		}
		//count documents
		$res_document = Database::query($sql_document,__FILE__,__LINE__);
		$count_document = Database::fetch_row($res_document);
		$cant_files = $count_document[0];
		//count directories
		$sql_directory = "SELECT count(*) FROM $work_table s WHERE  url NOT LIKE '/".$mydir."/%/%' AND url LIKE '/".$mydir."/%'";
		$res_directory = Database::query($sql_directory,__FILE__,__LINE__);
		$count_directory = Database::fetch_row($res_directory);
		$cant_dir = $count_directory[0];

		$text_file=get_lang('FilesUpload');
		$text_dir=get_lang('Directories');

		if ($cant_files==1) {
			$text_file=api_strtolower(get_lang('FileUpload'));
		}

		if ($cant_dir==1) {
			$text_dir=get_lang('directory');
		}

		if ($cant_dir!=0) {
			$dirtext=' ('.$cant_dir.' '.$text_dir.')';
		} else {
			$dirtext='';
		}

		if (!empty($display_edit_form) && isset($clean_edit_dir) && $clean_edit_dir==$mydir) {
			$row[] = '<span class="invisible" style="display:none">'.$dir.'</span>'.$form_folder->toHtml(); // form to edit the directory's name
		} else {
			$tbl_gradebook_link = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
			$add_to_name = '';
			$sql = "SELECT weight FROM ". $tbl_gradebook_link ." WHERE type='3' AND ref_id= '".$id2."'";
			$result=Database::query($sql, __FILE__, __LINE__);
			$count = Database::num_rows($result);
			if($count>0) {
				$add_to_name = ' / <span style="color:blue">'.get_lang('Assignment').'</span>';
			} else {
				$add_to_name = '';
			}
			$show_as_icon = get_work_id($mydir); //true or false
			if ($show_as_icon){
				if (is_allowed_to_edit()) {
					$zip='<a href="'.api_get_self().'?cidReq='.api_get_course_id().'&gradebook='.$gradebook.'&action=downloadfolder&path=/'.$mydir.'"><img src="../img/zip_save.gif" style="float:right;" alt="'.get_lang('Save').'" title="'.get_lang('Save').'" width="17" height="17"/></a>';
				}
				$row[] = $zip.'<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.$origin.'&gradebook='.Security::remove_XSS($_GET['gradebook']).'&curdirpath='.$mydir.'"'.$class.'>'.$dir.'</a>'.$add_to_name.'<br>'.$cant_files.' '.$text_file.$dirtext;
			} else {
				$row[] = '<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.$origin.'&gradebook='.$gradebook.'&curdirpath='.$mydir.'"'.$class.'>'.$dir.'</a>'.$add_to_name.'<br>'.$cant_files.' '.$text_file.$dirtext;
			}
		}
		if ($count_files!=0) {
			$row[] = "";
		}

		if ($direc_date!='' && $direc_date!='0000-00-00 00:00:00') {
			$my_direc_date = api_ucfirst(format_locale_date($dateFormatShort,strtotime($direc_date))).'&nbsp;&nbsp;&nbsp;&nbsp;';
			$my_direc_date .= ucfirst(strftime($timeNoSecFormat,strtotime($direc_date)));
			$row[]= date_to_str_ago($direc_date).'<br /><span class="dropbox_date">'.$my_direc_date.'</span>';
		} else {
			$row[]='';
		}

		if ($origin != 'learnpath') {
			if( $is_allowed_to_edit) {
				$action .= '<a href="'.api_get_self().'?cidReq='.api_get_course_id().
					'&curdirpath='.$my_sub_dir.'&origin='.$origin.'&gradebook='.$gradebook.'&edit_dir='.$mydir.'"><img src="../img/edit.gif" alt="'.get_lang('Modify').'" title="'.get_lang('Modify').'"></a>';
				$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.$origin.'&gradebook='.$gradebook.'&delete_dir='.$mydir.'&delete2='.$id2.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."'".')) return false;" title="'.get_lang('DirDelete').'"  ><img src="'.api_get_path(WEB_IMG_PATH).'delete.gif" alt="'.get_lang('DirDelete').'" title="'.get_lang('DirDelete').'"></a>';
				$row[] = $action;
			} else {
				$row[] = "";
			}
		}
		$table_data[] = $row;
	}

	while( $work = Database::fetch_object($sql_result)) {
		//Get the author ID for that document from the item_property table
		$is_author = false;
		$author_sql = "SELECT * FROM $iprop_table WHERE tool = 'work' AND ref=".$work->id;
		$author_qry = Database::query($author_sql,__FILE__,__LINE__);
		$row2=Database::fetch_array($author_qry);


		if(Database::num_rows($author_qry)==1) {
			$is_author = true;
		}

		//display info depending on the permissions
		if( $work->accepted == '1' || $is_allowed_to_edit) {
			$row = array();
			if($work->accepted == '0')
			{
				$class='class="invisible"';
			} else {
				$class='';
			}

			$qualification_string = '';
			$add_string = '';
			if(defined('IS_ASSIGNMENT')):
				if($work->qualification=='') {
					$qualification_string = ' / <b style="color:orange">'.get_lang('NotRevised').'<b>';
				} else {
					$qualification_string = ' / <b style="color:blue">'.get_lang('Qualification').': '.$work->qualification.'<b>';
				}
				if(defined('ASSIGNMENT_EXPIRES') && (ASSIGNMENT_EXPIRES < convert_date_to_number($work->sent_date))) {
					$add_string = ' <b style="color:red">'.get_lang('Expired').'</b>';
				}
			endif;

			$url = implode("/", array_map("rawurlencode", explode("/", $work->url)));

			//$full_file_name = 'download.php?file='.$realname;
			$row[]= build_document_icon_tag('file',$work->url);
			$row[]= '<a href="download.php?file='.$url.'"'.$class.'><img src="../img/filesave.gif" style="float:right;" alt="'.get_lang('Save').'" title="'.get_lang('Save').'" />'.$work->title.'</a><br />'.$work->description;
			$row[]= display_user_link($row2['insert_user_id'],$work->author).$qualification_string;// $work->author;
			$sent_date = api_ucfirst(format_locale_date($dateFormatShort,strtotime($work->sent_date))).'&nbsp;&nbsp;&nbsp;&nbsp;';
			$sent_date .= ucfirst(strftime($timeNoSecFormat,strtotime($work->sent_date)));
			$row[]= date_to_str_ago($work->sent_date).$add_string.'<br><span class="dropbox_date">'.$sent_date.'</span>';

			if( $is_allowed_to_edit) {

				$action = '';
				$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&gradebook='.$gradebook.'&amp;edit='.$work->id.'&gradebook='.Security::remove_XSS($_GET['gradebook']).'&amp;parent_id='.$work->parent_id.'" title="'.get_lang('Modify').'"  ><img src="../img/edit.gif" alt="'.get_lang('Modify').'" title="'.get_lang('Modify').'"></a>';
				$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&gradebook='.$gradebook.'&amp;delete='.$work->id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."'".')) return false;" title="'.get_lang('WorkDelete').'" ><img src="../img/delete.gif" alt="'.get_lang('WorkDelete').'" title="'.get_lang('WorkDelete').'"></a>';
				$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&gradebook='.$gradebook.'&amp;move='.$work->id.'" title="'.get_lang('Move').'"><img src="../img/deplacer_fichier.gif" border="0" title="'.get_lang('Move').'" alt="'.get_lang('Move').'" /></a>';
				if($work->accepted == '1') {
					$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&gradebook='.$gradebook.'&amp;make_invisible='.$work->id.'&amp;'.$sort_params.'" title="'.get_lang('Invisible').'" ><img src="../img/visible.gif" alt="'.get_lang('Invisible').'" title="'.get_lang('Invisible').'"></a>';
				} else {
					$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&gradebook='.$gradebook.'&amp;make_visible='.$work->id.'&amp;'.$sort_params.'" title="'.get_lang('Visible').'" ><img src="../img/invisible.gif" alt="'.get_lang('Visible').'"  title="'.get_lang('Visible').'"></a>';
				}

				$row[] = $action;
			} elseif($is_author) {
				$action = '';
				$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode($my_sub_dir).'&gradebook='.Security::remove_XSS($_GET['gradebook']).'&amp;origin='.$origin.'&gradebook='.$gradebook.'&amp;edit='.$work->id.'" title="'.get_lang('Modify').'"  ><img src="../img/edit.gif"  alt="'.get_lang('Modify').'" title="'.get_lang('Modify').'"></a>';
				$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&gradebook='.$gradebook.'&amp;delete='.$work->id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."'".')) return false;" title="'.get_lang('WorkDelete').'"  ><img src="../img/delete.gif" alt="'.get_lang('WorkDelete').'" title="'.get_lang('WorkDelete').'" ></a>';

				$row[] = $action;
			} else {
				$row[] = " ";
			}
			$table_data[] = $row;
		}
	}

	$sorting_options=array();
	$sorting_options['column']=1;

	$paging_options=array();
	if (isset($_GET['curdirpath'])) {
		$my_params = array ('curdirpath' => Security::remove_XSS($_GET['curdirpath']));
	}
	if (isset($_GET['edit_dir'])) {
		$my_params = array ('edit_dir' => Security::remove_XSS($_GET['edit_dir']));
	}
	$my_params['origin'] = $origin;
	Display::display_sortable_config_table($table_header,$table_data,$sorting_options, $paging_options,$my_params,$column_show,$column_order);
}

/**
 * Returns a list of subdirectories found in the given directory.
 *
 * The list return starts from the given base directory.
 * If you require the subdirs of /var/www/ (or /var/www), you will get 'abc/', 'def/', but not '/var/www/abc/'...
 * @param	string	Base dir
 * @param	integer	0 if we only want dirs from this level, 1 if we want to recurse into subdirs
 * @return	strings_array	The list of subdirs in 'abc/' form, -1 on error, and 0 if none found
 * @todo	Add a session check to see if subdirs_list doesn't exist yet (cached copy)
 */
function get_subdirs_list($basedir='',$recurse=0){
	//echo "Looking for subdirs of $basedir";
	if(empty($basedir) or !is_dir($basedir)) {
		return -1;
	}
	if(substr($basedir,-1,1)!='/') {
		$basedir = $basedir.'/';
	}
	$dirs_list = array();
	$dh = opendir($basedir);
	while($entry = readdir($dh)) {
		$entry = replace_dangerous_char($entry);
		$entry = disable_dangerous_file($entry);
		if(is_dir($basedir.$entry) && $entry!='..' && $entry!='.') {
			$dirs_list[] = $entry;
			if($recurse==1) {
				foreach(get_subdirs_list($basedir.$entry) as $subdir) {
					$dirs_list[] = $entry.'/'.$subdir;
				}
			}
		}
	}
	closedir($dh);
	return $dirs_list;
}

/**
 * Builds the form thats enables the user to
 * select a directory to browse/upload in
 * This function has been copied from the document/document.inc.php library
 *
 * @param array $folders
 * @param string $curdirpath
 * @param string $group_dir
 * @return string html form
 */
function build_work_directory_selector($folders,$curdirpath,$group_dir='')
{
	$form = '<form name="selector" action="'.api_get_self().'?'.api_get_cidreq().'" method="POST">'."\n";
	$form .= get_lang('CurrentDirectory').' <select name="curdirpath" onchange="javascript:document.selector.submit()">'."\n";
	//group documents cannot be uploaded in the root
	if ($group_dir=='') {
		$form .= '<option value="/">/ ('.get_lang('Root').')</option>';
		if (is_array($folders)) {
			foreach ($folders as $folder) {
				$selected = ($curdirpath==$folder)?' selected="selected"':'';
				$form .= '<option'.$selected.' value="'.$folder.'">'.$folder.'</option>'."\n";
			}
		}
	} else {
		foreach ($folders as $folder) {
			$selected = ($curdirpath==$folder)?' selected="selected"':'';
			$display_folder = substr($folder,strlen($group_dir));
			$display_folder = ($display_folder == '')?'/ ('.get_lang('Root').')':$display_folder;
			$form .= '<option'.$selected.' value="'.$folder.'">'.$display_folder.'</option>'."\n";
		}
	}

	$form .= '</select>'."\n";
	$form .= '<noscript><input type="submit" name="change_path" value="'.get_lang('Ok').'" /></noscript>'."\n";
	$form .= '</form>';

	return $form;
}
/**
 * Builds the form thats enables the user to
 * move a document from one directory to another
 * This function has been copied from the document/document.inc.php library
 *
 * @param array $folders
 * @param string $curdirpath
 * @param string $move_file
 * @return string html form
 */
function build_work_move_to_selector($folders,$curdirpath,$move_file,$group_dir='')
{
	//gets file title
	$tbl_work = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql = "SELECT title FROM $tbl_work WHERE id ='".(int)$move_file."'";
	$result = Database::query($sql,__FILE__,__LINE__);
	$title = Database::fetch_row($result);
	global $gradebook;

	$form = '<form name="move_to" action="'.api_get_self().'?gradebook='.$gradebook.'" method="POST">'."\n";
	$form .= '<div class="row"><div class="form_header">'.get_lang('MoveFile').'</div></div>';
	$form .= '<input type="hidden" name="move_file" value="'.$move_file.'" />'."\n";
	$form .= '<div class="row">
				<div class="label">
					<span class="form_required">*</span>'.sprintf(get_lang('MoveXTo'),$title[0]).'
				</div>
				<div class="formw">';
	$form .= ' <select name="move_to">'."\n";

	//group documents cannot be uploaded in the root
	if($group_dir=='') {
		if($curdirpath!='/') {
			$form .= '<option value="/">/ ('.get_lang('Root').')</option>';
		}
		if (is_array($folders)) {
			foreach ($folders as $folder) {
				//you cannot move a file to:
				//1. current directory
				//2. inside the folder you want to move
				//3. inside a subfolder of the folder you want to move
				if(($curdirpath!=$folder) && ($folder!=$move_file) && (substr($folder,0,strlen($move_file)+1) != $move_file.'/')) {
					$form .= '<option value="'.$folder.'">'.$folder.'</option>'."\n";
				}
			}
		}
	} else {
		if ($curdirpath!='/') {
			$form .= '<option value="/">/ ('.get_lang('Root').')</option>';
		}
		foreach ($folders as $folder) {
			if (($curdirpath!=$folder) && ($folder!=$move_file) && (substr($folder,0,strlen($move_file)+1) != $move_file.'/')) {
				//cannot copy dir into his own subdir
				$display_folder = substr($folder,strlen($group_dir));
				$display_folder = ($display_folder == '')?'/ ('.get_lang('Root').')':$display_folder;
				$form .= '<option value="'.$folder.'">'.$display_folder.'</option>'."\n";
			}
		}
	}

	$form .= '</select>'."\n";
	$form .= '	</div>
			</div>';
	$form .= '<div class="row">
					<div class="label">
											</div>
					<div class="formw">
						<button type="submit" class="save" name="move_file_submit">'.get_lang('MoveFile').'</button>
					</div>
				</div>';
	$form .= '</form>';
	$form .= '<div style="clear: both; margin-bottom: 10px;"></div>';

	return $form;
}

/**
 * Checks if the first given directory exists as a subdir of the second given directory
 * This function should now be deprecated by Security::check_abs_path()
 * @param	string	Subdir
 * @param	string	Base dir
 * @return	integer	-1 on error, 0 if not subdir, 1 if subdir
 */
function is_subdir_of($subdir,$basedir)
{
	if(empty($subdir) or empty($basedir)) {
		return -1;
	}
	if(substr($basedir,-1,1)!='/') {
		$basedir=$basedir.'/';
	}
	if(substr($subdir,0,1)=='/') {
		$subdir = substr($subdir,1);
	}
	if(is_dir($basedir.$subdir)) {
		return 1;
	} else {
		return 0;
	}
}

/**
 * creates a new directory trying to find a directory name
 * that doesn't already exist
 * (we could use unique_name() here...)
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @author Bert Vanderkimpen
 * @author Yannick Warnier <ywarnier@beeznest.org> Adaptation for work tool
 * @param	string	Base work dir (.../work)
 * @param 	string $desiredDirName complete path of the desired name
 * @return 	string actual directory name if it succeeds,
 *         boolean false otherwise
 */
function create_unexisting_work_directory($base_work_dir,$desired_dir_name)
{
	$nb = '';
	$base_work_dir = (substr($base_work_dir,-1,1)=='/'?$base_work_dir:$base_work_dir.'/');
	while ( file_exists($base_work_dir.$desired_dir_name.$nb) ) {
		$nb += 1;
	}
	//echo "creating ".$base_work_dir.$desired_dir_name.$nb."#...";
	$perm = api_get_setting('permissions_for_new_directories');
	$perm = octdec(!empty($perm)?$perm:'0770');
	if ( mkdir($base_work_dir.$desired_dir_name.$nb, $perm)) {
		chmod($base_work_dir.$desired_dir_name.$nb, $perm);
		return $desired_dir_name.$nb;
	} else {
	return false;
	}
}

/**
 * Delete a work-tool directory
 * @param	string	Base "work" directory for this course as /var/www/dokeos/courses/ABCD/work/
 * @param	string	The directory name as the bit after "work/", without trailing slash
 * @return	integer	-1 on error
 */
function del_dir($base_work_dir,$dir,$id) {
	if(empty($dir) or $dir=='/') {
		return -1;
	}
	$check = Security::check_abs_path($base_work_dir.$dir,$base_work_dir);
	if (!$check || !is_dir($base_work_dir.$dir)) {
		return -1;
	}
	$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql = "DELETE FROM $table WHERE url LIKE BINARY 'work/".$dir."/%'";
	$res = Database::query($sql,__FILE__,__LINE__);

	//delete from DB the directories
	$sql = "DELETE FROM $table WHERE filetype = 'folder' AND url LIKE BINARY '/".$dir."%'";
	$res = Database::query($sql,__FILE__,__LINE__);

	require_once(api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
	//my_delete($base_work_dir.$dir);
	$new_dir= $dir.'_DELETED_'.$id;
	rename($base_work_dir.$dir, $base_work_dir.$new_dir);
}

/**
 * Get the path of a document in the student_publication table (path relative to the course directory)
 * @param	integer	Element ID
 * @return	string	Path (or -1 on error)
 */
function get_work_path($id)
{
	$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql = "SELECT * FROM $table WHERE id=$id";
	$res = Database::query($sql);
	if(Database::num_rows($res)!=1) {
		return -1;
	} else {
		$row = Database::fetch_array($res);
		return $row['url'];
	}
}
/**
 * Update the url of a work in the student_publication table
 * @param	integer	ID of the work to update
 * @param	string	Destination directory where the work has been moved (must end with a '/')
 * @return	-1 on error, sql query result on success
 */
function update_work_url($id,$new_path)
{
	if(empty($id)) return -1;
	$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql = "SELECT * FROM $table WHERE id=$id";
	$res = Database::query($sql);
	if(Database::num_rows($res)!=1) {
		return -1;
	} else {
		$row = Database::fetch_array($res);
		$filename = basename($row['url']);
		$new_url = $new_path.$filename;
		$sql2 = "UPDATE $table SET url = '$new_url' WHERE id=$id";
		$res2 = Database::query($sql2);
		return $res2;
	}
}

/**
 * Update the url of a dir in the student_publication table
 * @param	string old path
 * @param	string new path
 */
function update_dir_name($path, $new_name) {

	if (!empty($new_name)){

		global $base_work_dir;
		include_once(api_get_path(LIBRARY_PATH) . "/fileManage.lib.php");
		include_once(api_get_path(LIBRARY_PATH) . "/fileUpload.lib.php");
		$path_to_dir = dirname($path);
		if($path_to_dir=='.') {
			$path_to_dir = '';
		} else {
			$path_to_dir .= '/';
		}
		$new_name = Security::remove_XSS($new_name);
		$new_name = replace_dangerous_char($new_name);
		$new_name = disable_dangerous_file($new_name);

		my_rename($base_work_dir.'/'.$path,$new_name);
		$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

		//update all the files in the other directories according with the next query
		$sql = 'SELECT id, url FROM '.$table.' WHERE url LIKE BINARY "work/'.$path.'/%"'; // like binary (Case Sensitive)

		$rs = Database::query($sql, __FILE__, __LINE__);
		$work_len=strlen('work/'.$path);

		while($work = Database :: fetch_array($rs)) {
			$new_dir=$work['url'];
			$name_with_directory=substr($new_dir,$work_len,strlen($new_dir));
			$sql = 'UPDATE '.$table.' SET url="work/'.$path_to_dir.$new_name.$name_with_directory.'" WHERE id= '.$work['id'];
			Database::query($sql, __FILE__, __LINE__);
		}

		//update all the directory's children according with the next query
		$sql = 'SELECT id, url FROM '.$table.' WHERE url LIKE BINARY "/'.$path.'%"';
		$rs = Database::query($sql, __FILE__, __LINE__);
		$work_len=strlen('/'.$path);
		while($work = Database :: fetch_array($rs)) {
			$new_dir=$work['url'];
			$name_with_directory=substr($new_dir,$work_len,strlen($new_dir));
			$url=$path_to_dir.$new_name.$name_with_directory;
			$sql = 'UPDATE '.$table.' SET url="/'.$url.'" WHERE id= '.$work['id'];
			Database::query($sql, __FILE__, __LINE__);
		}
	}
}

/**
 * Return an array with all the folder's ids that are in the given path
 * @param	string Path of the directory
 * @return	array The list of ids of all the directories in the path
 * @author 	Julio Montoya Dokeos
 * @version April 2008
 */

function get_parent_directories($my_cur_dir_path) {
	$list_id = array();
	if (!empty($my_cur_dir_path)) {
		$list_parents = explode('/', $my_cur_dir_path);
		$dir_acum = '';
		global $work_table;
		for ($i = 0; $i < count($list_parents) - 1; $i++) {
			$item = Database::escape_string($list_parents[$i]);
			$where_sentence = "url  LIKE BINARY '" . $dir_acum . "/" . $item."'";
			$dir_acum .= '/' . $list_parents[$i];
			$sql = "SELECT id FROM ". $work_table . " WHERE ". $where_sentence;
			$result = Database::query($sql, __FILE__, __LINE__);
			$row= Database::fetch_array($result);
			$list_id[]=$row['id'];
		}
	}
	return $list_id;
}

/**
 * Transform an all directory structure (only directories) in an array
 * @param	string path of the directory
 * @return	array the directory structure into an array
 * @author 	Julio Montoya Dokeos
 * @version April 2008
 */
function directory_to_array($directory)
{
	$array_items = array();
	if ($handle = opendir($directory)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				if (is_dir($directory. "/" . $file)) {
					$array_items = array_merge($array_items, directory_to_array($directory. "/" . $file));
					$file = $directory . "/" . $file;
					$array_items[] = preg_replace("/\/\//si", "/", $file);
				}
			}
		}
		closedir($handle);
	}
	return $array_items;
}

/**
 * Insert into the DB of the course all the directories
 * @param	string path of the /work directory of the course
 * @return	-1 on error, sql query result on success
 * @author 	Julio Montoya Dokeos
 * @version April 2008
 */

function insert_all_directory_in_course_table($base_work_dir) {
	$dir_to_array =directory_to_array($base_work_dir,true);
	$only_dir=array();

	for($i=0;$i<count($dir_to_array);$i++) {
		$only_dir[]=substr($dir_to_array[$i],strlen($base_work_dir), strlen($dir_to_array[$i]));
	}

	for($i=0;$i<count($only_dir);$i++) {
		global $work_table;
        $sql_insert_all= "INSERT INTO " . $work_table . " SET url = '" . $only_dir[$i] . "', " .
							  "title        = '',
			                   description 	= '',
			                   author      	= '',
							   active		= '0',
							   accepted		= '1',
							   filetype		= 'folder',
							   post_group_id = '".intval($_GET['toolgroup'])."',
							   sent_date	= '0000-00-00 00:00:00' ";
        Database::query($sql_insert_all, __FILE__, __LINE__);
	}
}

/**
* This function displays the number of files contained in a directory
*
* @param	string the path of the directory
* @param	boolean true if we want the total quantity of files include in others child directorys , false only  files in the directory
* @return	array the first element is an integer with the number of files in the folder, the second element is the number of directories
* @author 	Julio Montoya Dokeos
* @version	April 2008
*/
function count_dir($path_dir, $recurse)
{
	$count = 0;
	$count_dir= 0;
    $d = dir($path_dir);
    while ($entry = $d->Read()) {
    	if (!(($entry == "..") || ($entry == "."))) {
        	if (is_dir($path_dir.'/'.$entry)) {
        		$count_dir++;
          		if ($recurse) {
            		$count += count_dir($path_dir . '/' . $entry, $recurse);
          		}

        	} else {
        		$count++;
        	}
		}
	}
	$return_array=array();
	$return_array[]=$count;
	$return_array[]=$count_dir;
    return $return_array;
}

/**
* returns all the javascript that is required for easily
* validation when you create a work
* this goes into the $htmlHeadXtra[] array
*/
function to_javascript_work() {
	return '<script>
			function plus() {
				if(document.getElementById(\'options\').style.display == \'none\') {
					document.getElementById(\'options\').style.display = \'block\';
					document.getElementById(\'plus\').innerHTML=\'&nbsp;'.Display::return_icon('div_hide.gif',get_lang('Hide', ''),array('style'=>'vertical-align:middle')).'&nbsp;'.addslashes(get_lang('AdvancedParameters', '')).'\';
				} else {
					document.getElementById(\'options\').style.display = \'none\';
					document.getElementById(\'plus\').innerHTML=\'&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show', ''),array('style'=>'vertical-align:middle')).'&nbsp;'.addslashes(get_lang('AdvancedParameters', '')).'\';
				}
			}


			function updateDocumentTitle(value){

				var temp = value.indexOf("/");

				//linux path
				if(temp!=-1){
					var temp=value.split("/");
				}
				else{
					var temp=value.split("\\\");
				}

				document.getElementById("file_upload").value=temp[temp.length-1];
			}

			function checkDate(month, day, year)
			{
			  var monthLength =
			    new Array(31,28,31,30,31,30,31,31,30,31,30,31);

			  if (!day || !month || !year)
			    return false;

			  // check for bisestile year
			  if (year/4 == parseInt(year/4))
			    monthLength[1] = 29;

			  if (month < 1 || month > 12)
			    return false;

			  if (day > monthLength[month-1])
			    return false;

			  return true;
			}

			function mktime() {

			    var no, ma = 0, mb = 0, i = 0, d = new Date(), argv = arguments, argc = argv.length;
			    d.setHours(0,0,0); d.setDate(1); d.setMonth(1); d.setYear(1972);

			    var dateManip = {
			        0: function(tt){ return d.setHours(tt); },
			        1: function(tt){ return d.setMinutes(tt); },
			        2: function(tt){ set = d.setSeconds(tt); mb = d.getDate() - 1; return set; },
			        3: function(tt){ set = d.setMonth(parseInt(tt)-1); ma = d.getFullYear() - 1972; return set; },
			        4: function(tt){ return d.setDate(tt+mb); },
			        5: function(tt){ return d.setYear(tt+ma); }
			    };

			    for( i = 0; i < argc; i++ ){
			        no = parseInt(argv[i]*1);
			        if (isNaN(no)) {
			            return false;
			        } else {
			            // arg is number, lets manipulate date object
			            if(!dateManip[i](no)){
			                // failed
			                return false;
			            }
			        }
			    }

			    return Math.floor(d.getTime()/1000);
			}

			function validate(){
				var expires_day = document.form1.expires_day.value;
				var expires_month = document.form1.expires_month.value;
				var expires_year = document.form1.expires_year.value;
				var expires_hour = document.form1.expires_hour.value;
				var expires_minute = document.form1.expires_minute.value;
				var expires_date = mktime(expires_hour,expires_minute,0,expires_month,expires_day,expires_year)

				var ends_day = document.form1.ends_day.value;
				var ends_month = document.form1.ends_month.value;
				var ends_year = document.form1.ends_year.value;
				var ends_hour = document.form1.ends_hour.value;
				var ends_minute = document.form1.ends_minute.value;
				var ends_date = mktime(ends_hour,ends_minute,0,ends_month,ends_day,ends_year)

				var new_dir = document.form1.new_dir.value;

				msg_id1 = document.getElementById("msg_error1");
				msg_id2 = document.getElementById("msg_error2");
				msg_id3 = document.getElementById("msg_error3");
				msg_id4 = document.getElementById("msg_error4");
				msg_id5	= document.getElementById("msg_error_weight");


				if(new_dir==""){

					msg_id1.style.display ="block";
					msg_id1.innerHTML="'.get_lang('FieldRequired', '').'";
					msg_id2.innerHTML="";msg_id3.innerHTML="";msg_id4.innerHTML="";msg_id5.innerHTML="";
				}
				else if(document.form1.type1.checked==true && document.form1.type2.checked==true && expires_date > ends_date) {
						msg_id2.style.display ="block";
						msg_id2.innerHTML="'.get_lang('EndDateCannotBeBeforeTheExpireDate', '').'";
						msg_id1.innerHTML="";msg_id3.innerHTML="";msg_id4.innerHTML="";msg_id5.innerHTML="";
				}
				else if (checkDate(expires_month,expires_day,expires_year) == false)
				{
					msg_id3.style.display ="block";
					msg_id3.innerHTML="'.get_lang('InvalidDate', '').'";
					msg_id1.innerHTML="";msg_id2.innerHTML="";msg_id4.innerHTML="";msg_id5.innerHTML="";
				}
				else if (checkDate(ends_month,ends_day,ends_year) == false)
				{

					msg_id4.style.display ="block";
					msg_id4.innerHTML="'.get_lang('InvalidDate', '').'";
					msg_id1.innerHTML="";msg_id2.innerHTML="";msg_id3.innerHTML="";msg_id5.innerHTML="";
				}
				else
				{

					if (document.form1.make_calification.checked == true)
				 	{
					 	var weight = document.form1.weight.value;
						 	if(weight==""){
								msg_id5.style.display ="block";
								msg_id5.innerHTML="'.get_lang('WeightNecessary', '').'";
								msg_id1.innerHTML="";msg_id2.innerHTML="";msg_id3.innerHTML="";msg_id4.innerHTML="";
							    return false;
							}
				 	}

					document.form1.action = "work.php?origin='.api_get_tools_lists($_REQUEST['origin']).'&gradebook='.(empty($_GET['gradebook'])?'':'view').'";
					document.form1.submit();
				}


			}
			</script>';
}

/**
 * Gets the id of a student publication with a given path
 * @param string $path
 * @return true if is found / false if not found
 */
function get_work_id($path) {
	$TBL_STUDENT_PUBLICATION = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
	$TBL_PROP_TABLE = Database::get_course_table(TABLE_ITEM_PROPERTY);
	if (is_allowed_to_edit()) {
		$sql = "SELECT id FROM $TBL_STUDENT_PUBLICATION AS work,$TBL_PROP_TABLE AS props  WHERE props.tool='work' AND work.id=props.ref AND work.url LIKE 'work/".$path."%' AND work.filetype='file' AND props.visibility<>'2'";
	} else {
		$sql = "SELECT id FROM $TBL_STUDENT_PUBLICATION AS work,$TBL_PROP_TABLE AS props  WHERE props.tool='work' AND work.id=props.ref AND work.url LIKE 'work/".$path."%' AND work.filetype='file' AND props.visibility<>'2' AND props.lastedit_user_id='".api_get_user_id()."'";
	}
	$result = Database::query($sql, __FILE__, __LINE__);
	$num_rows = Database::num_rows($result);

	if ($result && $num_rows > 0) {
		return true;
	} else {
		return false;
	}
}
