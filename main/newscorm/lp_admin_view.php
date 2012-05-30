<?php
/* For licensing terms, see /license.txt */

/**
 * This is a learning path creation and player tool in Chamilo - previously learnpath_handler.php
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update for new SCORM tool
 * @package chamilo.learnpath
*/

/**
 * INIT SECTION 
 */

$this_section = SECTION_COURSES;

api_protect_course_script();

/* Libraries */

// The main_api.lib.php, database.lib.php and display.lib.php
// libraries are included by default.

include 'learnpath_functions.inc.php';
//include '../resourcelinker/resourcelinker.inc.php';
include 'resourcelinker.inc.php';
// Rewrite the language file, sadly overwritten by resourcelinker.inc.php.
$language_file = "learnpath";

/* Constants and variables */

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$tbl_lp      = Database::get_course_table(TABLE_LP_MAIN);
$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_lp_view = Database::get_course_table(TABLE_LP_VIEW);

$isStudentView  = (int) $_REQUEST['isStudentView'];
$learnpath_id   = (int) $_REQUEST['lp_id'];
$submit			= $_POST['submit_button'];

/* MAIN CODE */

// Using the resource linker as a tool for adding resources to the learning path.
if ($action == 'add' and $type == 'learnpathitem') {
     $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}
if ((!$is_allowed_to_edit) || ($isStudentView)) {
    error_log('New LP - User not authorized in lp_admin_view.php');
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
}
// From here on, we are admin because of the previous condition, so don't check anymore.

$course_id = api_get_course_int_id(); 

$sql_query = "SELECT * FROM $tbl_lp WHERE c_id = $course_id AND id = $learnpath_id";
$result = Database::query($sql_query);
$therow = Database::fetch_array($result);

//$admin_output = '';
/*
    Course admin section
    - all the functions not available for students - always available in this case (page only shown to admin)
*/

/* SHOWING THE ADMIN TOOLS */

if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
            'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
            'name' => get_lang('ToolGradebook')
        );
}

$interbreadcrumb[] = array('url' => 'lp_controller.php?action=list', 'name' => get_lang('LearningPaths'));
$interbreadcrumb[] = array('url' => api_get_self()."?action=build&lp_id=$learnpath_id", "name" => stripslashes("{$therow['name']}"));
if (isset($_REQUEST['updateaudio'])) {
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('UpdateAllAudioFragments'));
} else {
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('BasicOverview'));
}

// Theme calls.
$show_learn_path = true;
$lp_theme_css = $_SESSION['oLP']->get_theme();

Display::display_header(null, 'Path');
$suredel = trim(get_lang('AreYouSureToDelete'));

?>
<script>

var newOrderData= "";
//source code found in http://www.swartzfager.org/blog/dspNestedList.cfm

$(function() {
    <?php
    if (!isset($_REQUEST['updateaudio'])) { 
    ?>
	$("#lp_item_list").sortable({ 
		items: "li",
		handle: ".moved", //only the class "moved" 
		cursor: "move",  
		placeholder: "ui-state-highlight", //defines the yellow highlight			   
	});	

	$("#listSubmit").click(function () {
		//Disable the submit button to prevent a double-click
		$(this).attr("disabled","disabled");
		//Initialize the variable that will contain the data to submit to the form
		newOrderData= "";
		//All direct descendants of the lp_item_list will have a parentId of 0
		var parentId= 0;
		
		//Walk through the direct descendants of the lp_item_list <ul>
		$("#lp_item_list").children().each(function () {
			
			/*Only process elements with an id attribute (in order to skip the blank,
			unmovable <li> elements.*/
			
			if ($(this).attr("id")) {
					/*Build a string of data with the child's ID and parent ID, 
					 using the "|" as a delimiter between the two IDs and the "^" 
					 as a record delimiter (these delimiters were chosen in case the data
					 involved includes more common delimiters like commas within the content)
					*/
					newOrderData= newOrderData + $(this).attr("id") + "|" + "0" + "^";
					
					//Determine if this child is a containter
					if ($(this).is(".li_container")) {
						  //Process the child elements of the container
                        processChildren($(this).attr("id"));
					}
				}
			
		}); //end of lp_item_list children loop
		
		//Write the newOrderData string out to the listResults form element
		//$("#listResults").val(newOrderData);
		var order = "new_order="+ newOrderData + "&a=update_lp_item_order";
		$.post("<?php echo api_get_path(WEB_AJAX_PATH)?>lp.ajax.php", order, function(reponse){
            $("#message").html(reponse);
        }); 

		 setTimeout(function() {
		        $("#message").html('');
		    }, 3000);
						
		return false;
		
	}); //end of lp_item_list event assignment
	
	<?php } ?>
	function processChildren(parentId) {
		//Loop through the children of the UL element defined by the parentId
		var ulParentID= "UL_" + parentId;
		$("#" + ulParentID).children().each(function () {
			
			/*Only process elements with an id attribute (in order to skip the blank,
				unmovable <li> elements.*/
				
			if ($(this).attr("id")) {
                /*Build a string of data with the child's ID and parent ID, 
                    using the "|" as a delimiter between the two IDs and the "^" 
                    as a record delimiter (these delimiters were chosen in case the data
                    involved includes more common delimiters like commas within the content)
                */
                newOrderData= newOrderData + $(this).attr("id") + "|" + parentId + "^";

                //Determine if this child is a containter
                if ($(this).is(".container")) {
                    //Process the child elements of the container
                    processChildren($(this).attr("id"));
                }
            }				
		});  //end of children loop		
	} //end of processChildren function	
});

/* <![CDATA[ */
function stripslashes(str) {
    str=str.replace(/\\'/g,'\'');
    str=str.replace(/\\"/g,'"');
    str=str.replace(/\\\\/g,'\\');
    str=str.replace(/\\0/g,'\0');
    return str;
}

function confirmation(name) {
    name=stripslashes(name);
    if (confirm("<?php echo $suredel; ?> " + name + " ?")) {
        return true;
    } else {
        return false;
    }
}
</script>
<?php

/* DISPLAY SECTION */

switch ($_GET['action']) {
    case 'edit_item':
        if (isset($is_success) && $is_success === true) {
            Display::display_confirmation_message(get_lang('_learnpath_item_edited'));
        } else {
            echo $_SESSION['oLP']->display_edit_item($_GET['id']);
        }
        break;
    case 'delete_item':
        if (isset($is_success) && $is_success === true) {
            Display::display_confirmation_message(get_lang('_learnpath_item_deleted'));
        }
        break;
}

// POST action handling (uploading mp3, deleting mp3)
if (isset($_POST['save_audio'])) {
    
    //Updating the lp.modified_on
    $_SESSION['oLP']->set_modified_on();                
                
    // Deleting the audio fragments.
    foreach ($_POST as $key => $value) {
        if (substr($key, 0, 9) == 'removemp3') {
            $lp_items_to_remove_audio[] = str_ireplace('removemp3', '', $key);
            // Removing the audio from the learning path item.
            $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
            $in = implode(',', $lp_items_to_remove_audio);
        }
    }
    if (count($lp_items_to_remove_audio)>0) {
        $sql 	= "UPDATE $tbl_lp_item SET audio = '' WHERE c_id = $course_id AND id IN (".$in.")";
        $result = Database::query($sql);
    }           

    // Uploading the audio files.
    foreach ($_FILES as $key => $value) {
        if (substr($key, 0, 7) == 'mp3file' AND !empty($_FILES[$key]['tmp_name'])) {
            // The id of the learning path item.
            $lp_item_id = str_ireplace('mp3file', '', $key);

            // Create the audio folder if it does not exist yet.
            global $_course;
            $filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
            if (!is_dir($filepath.'audio')) {
                mkdir($filepath.'audio', api_get_permissions_for_new_directories());
                $audio_id = add_document($_course, '/audio', 'folder', 0, 'audio');
                api_item_property_update($_course, TOOL_DOCUMENT, $audio_id, 'FolderCreated', api_get_user_id(), null, null, null, null, api_get_session_id());
            }

            // Check if file already exits into document/audio/
            $file_name = $_FILES[$key]['name'];
            $file_name = stripslashes($file_name);
            // Add extension to files without one (if possible).
            $file_name = add_ext_on_mime($file_name, $_FILES[$key]['type']);

            $clean_name = replace_dangerous_char($file_name);
            // No "dangerous" files.
            $clean_name = disable_dangerous_file($clean_name);

            $check_file_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/audio/'.$clean_name;

            // If the file exists we generate a new name.
            if (file_exists($check_file_path)) {
                $filename_components = explode('.', $clean_name);
                // Gettting the extension of the file.
                $file_extension = $filename_components[count($filename_components) - 1];
                // Adding something random to prevent overwriting.
                $filename_components[count($filename_components) - 1] = time();
                // Reconstructing the new filename.
                $clean_name = implode($filename_components) .'.'.$file_extension;
                // Using the new name in the $_FILES superglobal.
                $_FILES[$key]['name'] = $clean_name;
            }

            // Upload the file in the documents tool.
            include_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
            $file_path = handle_uploaded_document($_course, $_FILES[$key], api_get_path(SYS_COURSE_PATH).$_course['path'].'/document','/audio', api_get_user_id(), '', '', '', '', '', false);

            // Getting the filename only.
            $file_components = explode('/', $file_path);
            $file = $file_components[count($file_components) - 1];

            // Store the mp3 file in the lp_item table.
            $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
            $sql_insert_audio = "UPDATE $tbl_lp_item SET audio = '".Database::escape_string($file)."' 
                                 WHERE c_id = $course_id AND id = '".Database::escape_string($lp_item_id)."'";
            Database::query($sql_insert_audio);
        }
    }
    Display::display_confirmation_message(get_lang('ItemUpdated'));
}
echo $_SESSION['oLP']->overview();

/* FOOTER */
Display::display_footer();