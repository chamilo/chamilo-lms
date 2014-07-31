<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 *	@package chamilo.work
 * 	@author Thomas, Hugues, Christophe - original version
 * 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University - ability for course admins to specify wether uploaded documents are visible or invisible by default.
 * 	@author Roan Embrechts, code refactoring and virtual course support
 * 	@author Frederic Vauthier, directories management
 *   @author Julio Montoya <gugli100@gmail.com> BeezNest 2011 LOTS of bug fixes
 *
 * 	@todo refactor more code into functions, use quickforms, coding standards, ... jm
 */

/**
 * 	STUDENT PUBLICATIONS MODULE
 *
 * Note: for a more advanced module, see the dropbox tool.
 * This one is easier with less options.
 * This tool is better used for publishing things,
 * sending in assignments is better in the dropbox.
 *
 * GOALS
 * *****
 * Allow student to quickly send documents immediately visible on the Course
 *
 * The script does 5 things:
 *
 * 	1. Upload documents
 * 	2. Give them a name
 * 	3. Modify data about documents
 * 	4. Delete link to documents and simultaneously remove them
 * 	5. Show documents list to students and visitors
 *
 * On the long run, the idea is to allow sending realvideo . Which means only
 * establish a correspondence between RealServer Content Path and the user's
 * documents path.
 *
 *
 */

/* INIT SECTION */

$language_file = array('exercice', 'work', 'document', 'admin', 'gradebook');

//require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_STUDENTPUBLICATION;

/*	Configuration settings */

api_protect_course_script(true);

// Including necessary files
require_once 'work.lib.php';

$course_id      = api_get_course_int_id();
$course_info    = api_get_course_info();
$user_id 	    = api_get_user_id();
$id_session     = api_get_session_id();

// Section (for the tabs)
$this_section = SECTION_COURSES;
$work_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$my_folder_data = get_work_data_by_id($work_id);

$curdirpath = '';
$htmlHeadXtra[] = api_get_jqgrid_js();
$htmlHeadXtra[] = to_javascript_work();
$htmlHeadXtra[] = '<script>
function setFocus(){
    $("#work_title").focus();
}
$(document).ready(function () {
    setFocus();
});
</script>';

// Table definitions
$main_course_table 	= Database :: get_main_table(TABLE_MAIN_COURSE);
$work_table 		= Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
$TSTDPUBASG			= Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
$table_course_user	= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$table_user			= Database :: get_main_table(TABLE_MAIN_USER);
$table_session		= Database :: get_main_table(TABLE_MAIN_SESSION);
$table_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$_course = api_get_course_info();

/*	Constants and variables */

$tool_name 		= get_lang('StudentPublications');
$course_code 	= api_get_course_id();
$session_id 	= api_get_session_id();

$currentCourseRepositorySys = api_get_path(SYS_COURSE_PATH) . $_course['path'] . '/';
$currentCourseRepositoryWeb = api_get_path(WEB_COURSE_PATH) . $_course['path'] . '/';

$currentUserFirstName 	= $_user['firstName'];
$currentUserLastName 	= $_user['lastName'];
$currentUserEmail 		= $_user['mail'];

$item_id 		        = isset($_REQUEST['item_id']) ? intval($_REQUEST['item_id']) : null;
$parent_id 		        = isset($_REQUEST['parent_id']) ? Database::escape_string($_REQUEST['parent_id']) : '';
$origin 		        = isset($_REQUEST['origin']) ? Security::remove_XSS($_REQUEST['origin']) : '';
$submitGroupWorkUrl     = isset($_REQUEST['submitGroupWorkUrl']) ? Security::remove_XSS($_REQUEST['submitGroupWorkUrl']) : '';
$title 			        = isset($_REQUEST['title']) ? $_REQUEST['title'] : '';
$description 	        = isset($_REQUEST['description']) ? $_REQUEST['description'] : '';
$uploadvisibledisabled  = isset($_REQUEST['uploadvisibledisabled']) ? Database::escape_string($_REQUEST['uploadvisibledisabled']) : $course_info['show_score'];

//directories management
$sys_course_path 	= api_get_path(SYS_COURSE_PATH);
$course_dir 		= $sys_course_path . $_course['path'];
$base_work_dir 		= $course_dir . '/work';

$link_target_parameter = ""; // e.g. "target=\"_blank\"";

$display_list_users_without_publication = isset($_GET['list']) && Security::remove_XSS($_GET['list']) == 'without' ? true : false;

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

//Download folder
if ($action == 'downloadfolder') {
    require 'downloadfolder.inc.php';
}

/*	More init stuff */

if (isset ($_POST['cancelForm']) && !empty ($_POST['cancelForm'])) {
    header('Location: '.api_get_self().'?origin='.$origin.'&amp;gradebook='.$gradebook);
    exit;
}

// If the POST's size exceeds 8M (default value in php.ini) the $_POST array is emptied
// If that case happens, we set $submitWork to 1 to allow displaying of the error message
// The redirection with header() is needed to avoid apache to show an error page on the next request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !sizeof($_POST)) {
    if (strstr($_SERVER['REQUEST_URI'], '?')) {
        header('Location: ' . $_SERVER['REQUEST_URI'] . '&submitWork=1');
        exit();
    } else {
        header('Location: ' . $_SERVER['REQUEST_URI'] . '?submitWork=1');
        exit();
    }
}

$group_id = api_get_group_id();

$display_upload_form = false;
if ($action == 'upload_form') {
    $display_upload_form = true;
}

/*	Header */

if (!empty($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
    $_SESSION['gradebook'] = Security::remove_XSS($_GET['gradebook']);
    $gradebook =	$_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
    unset($_SESSION['gradebook']);
    $gradebook = '';
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array ('url' => '../gradebook/' . $_SESSION['gradebook_dest'],'name' => get_lang('ToolGradebook'));
}

if (!empty($group_id)) {
    $group_properties  = GroupManager::get_group_properties($group_id);
    $show_work = false;

    if (api_is_allowed_to_edit(false, true)) {
        $show_work = true;
    } else {
        // you are not a teacher
        $show_work = GroupManager::user_has_access($user_id, $group_id, GroupManager::GROUP_TOOL_WORK);
    }

    if (!$show_work) {
        api_not_allowed();
    }

    $interbreadcrumb[] = array ('url' => '../group/group.php', 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array ('url' => '../group/group_space.php?gidReq='.$group_id, 'name' => get_lang('GroupSpace').' '.$group_properties['name']);

    $url_dir ='';
    $interbreadcrumb[] = array ('url' =>'work.php?gidReq='.$group_id,'name' => get_lang('StudentPublications'));

    $url_dir = 'work.php?&id=' . $work_id;
    $interbreadcrumb[] = array ('url' => $url_dir,'name' =>  $my_folder_data['title']);

    if ($action == 'upload_form') {
        $interbreadcrumb[] = array ('url' => 'work.php','name' => get_lang('UploadADocument'));
    }

    if ($action == 'create_dir') {
        $interbreadcrumb[] = array ('url' => 'work.php','name' => get_lang('CreateAssignment'));
    }
    Display :: display_header(null);
} else {
    if (isset($origin) && $origin != 'learnpath') {

        if (isset($_GET['id']) && !empty($_GET['id']) || $display_upload_form || $action == 'settings' || $action == 'create_dir') {
            $interbreadcrumb[] = array ('url' => 'work.php', 'name' => get_lang('StudentPublications'));
        } else {
            $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('StudentPublications'));
        }

        $url_dir = 'work.php?id=' . $work_id;
        $interbreadcrumb[] = array ('url' => $url_dir,'name' =>  $my_folder_data['title']);

        if ($action == 'upload_form') {
            $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('UploadADocument'));
        }
        if ($action == 'settings') {
            $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('EditToolOptions'));
        }
        if ($action == 'create_dir') {
            $interbreadcrumb[] = array ('url' => '#','name' => get_lang('CreateAssignment'));
        }
        Display :: display_header(null);

    } else {
        //we are in the learnpath tool
        Display::display_reduced_header();
    }
}

//stats
event_access_tool(TOOL_STUDENTPUBLICATION);

$is_allowed_to_edit = api_is_allowed_to_edit();

$student_can_edit_in_session = api_is_allowed_to_session_edit(false, true);

Display::display_introduction_section(TOOL_STUDENTPUBLICATION);

// introduction section

if ($origin == 'learnpath') {
    echo '<div style="height:15px">&nbsp;</div>';
}

/*	Display links to upload form and tool options */

if (!in_array($action, array('add','create_dir'))) {
    $token = Security::get_token();
}

display_action_links($work_id, $curdirpath, $action);

// for teachers

switch ($action) {
    case 'settings':
        //if posts
        if ($is_allowed_to_edit && !empty($_POST['changeProperties'])) {
            // Changing the tool setting: default visibility of an uploaded document
            // @todo
            $query = "UPDATE ".$main_course_table." SET show_score='" . $uploadvisibledisabled . "' WHERE code='" . api_get_course_id() . "'";
            $res = Database::query($query);

            /**
             * Course data are cached in session so we need to update both the database
             * and the session data
             */
            $_course['show_score'] = $uploadvisibledisabled;
            Session::write('_course', $course);

            // changing the tool setting: is a student allowed to delete his/her own document
            // database table definition
            $table_course_setting = Database :: get_course_table(TOOL_COURSE_SETTING);

            // counting the number of occurrences of this setting (if 0 => add, if 1 => update)
            $query = "SELECT * FROM " . $table_course_setting . " WHERE c_id = $course_id AND variable = 'student_delete_own_publication'";
            $result = Database::query($query);
            $number_of_setting = Database::num_rows($result);

            if ($number_of_setting == 1) {
                $query = "UPDATE " . $table_course_setting . " SET value='" . Database::escape_string($_POST['student_delete_own_publication']) . "'
                        WHERE variable='student_delete_own_publication' AND c_id = $course_id";
                Database::query($query);
            } else {
                $query = "INSERT INTO " . $table_course_setting . " (c_id, variable, value, category) VALUES
                ($course_id, 'student_delete_own_publication','" . Database::escape_string($_POST['student_delete_own_publication']) . "','work')";
                Database::query($query);
            }
            Display::display_confirmation_message(get_lang('Saved'));
        }
        /*	Display of tool options */
        display_tool_options($uploadvisibledisabled, $origin);
        break;
    case 'mark_work':
        if (!api_is_allowed_to_edit()) {
            echo Display::return_message(get_lang('ActionNotAllowed'), 'error');
            Display::display_footer();
        }
        break;
    case 'create_dir':
    case 'add':
        //$check = Security::check_token('post');
        //show them the form for the directory name

        if ($is_allowed_to_edit && in_array($action, array('create_dir','add'))) {
            //create the form that asks for the directory name
            $form = new FormValidator('form1', 'post', api_get_self().'?action=create_dir&'. api_get_cidreq());

            $form->addElement('header', get_lang('CreateAssignment').$token);
            $form->addElement('hidden', 'action', 'add');
            $form->addElement('hidden', 'curdirpath', Security :: remove_XSS($curdirpath));
            // $form->addElement('hidden', 'sec_token', $token);

            $form->addElement('text', 'new_dir', get_lang('AssignmentName'));
            $form->addRule('new_dir', get_lang('ThisFieldIsRequired'), 'required');

            $form->add_html_editor('description', get_lang('Description'), false, false, getWorkDescriptionToolbar());

            $form->addElement('advanced_settings', 'add_work', get_lang('AdvancedParameters'));
            $form->addElement('html', '<div id="add_work_options" style="display: none;">');

            // QualificationOfAssignment
            $form->addElement('text', 'qualification_value', get_lang('QualificationNumeric'));

            if (Gradebook::is_active()) {
                $form->addElement('checkbox', 'make_calification', null, get_lang('MakeQualifiable'), array('id' =>'make_calification_id', 'onclick' => "javascript: if(this.checked){document.getElementById('option1').style.display='block';}else{document.getElementById('option1').style.display='none';}"));
            } else {
                //QualificationOfAssignment
                //$form->addElement('hidden', 'qualification_value',0);
                $form->addElement('hidden', 'make_calification', false);
            }

            $form->addElement('html', '<div id="option1" style="display: none;">');

            //Loading gradebook select
            load_gradebook_select_in_tool($form);

            $form->addElement('text', 'weight', get_lang('WeightInTheGradebook'));
            $form->addElement('html', '</div>');

            $form->addElement('checkbox', 'type1', null, get_lang('EnableExpiryDate'), array('id' =>'make_calification_id', 'onclick' => "javascript: if(this.checked){document.getElementById('option2').style.display='block';}else{document.getElementById('option2').style.display='none';}"));

            $form->addElement('html', '<div id="option2" style="display: none;">');
            $form->addElement('advanced_settings',draw_date_picker('expires'));
            $form->addElement('html', '</div>');

            $form->addElement('checkbox', 'type2', null, get_lang('EnableEndDate'), array('id' =>'make_calification_id', 'onclick' => "javascript: if(this.checked){document.getElementById('option3').style.display='block';}else{document.getElementById('option3').style.display='none';}"));

            $form->addElement('html', '<div id="option3" style="display: none;">');
            $form->addElement('advanced_settings', draw_date_picker('ends'));
            $form->addElement('html', '</div>');

            $form->addElement('checkbox', 'add_to_calendar', null, get_lang('AddToCalendar'));
            $form->addElement('checkbox', 'allow_text_assignment', null, get_lang('AllowTextAssignments'));
            $form->addElement('html', '</div>');
            $form->addElement('style_submit_button', 'submit', get_lang('CreateDirectory'));

            if ($form->validate()) {

                $directory 		= Security::remove_XSS($_POST['new_dir']);
                $directory 		= replace_dangerous_char($directory);
                $directory 		= disable_dangerous_file($directory);
                $dir_name 		= $curdirpath.$directory;
                $created_dir 	= create_unexisting_work_directory($base_work_dir, $dir_name);

                // we insert here the directory in the table $work_table
                $dir_name_sql = '';

                if (!empty($created_dir)) {
                    if ($curdirpath == '/') {
                        $dir_name_sql = $created_dir;
                    } else {
                        $dir_name_sql = '/'.$created_dir;
                    }
                    $time = time();
                    $today = api_get_utc_datetime($time);

                    $sql_add_publication = "INSERT INTO " . $work_table . " SET
                                            c_id				= $course_id,
                                            url         		= '".Database::escape_string($dir_name_sql)."',
                                            title               = '".Database::escape_string($_POST['new_dir'])."',
                                            description 		= '".Database::escape_string($_POST['description'])."',
                                            author      		= '',
                                            active              = '1',
                                            accepted			= '1',
                                            filetype            = 'folder',
                                            post_group_id       = '".$group_id."',
                                            sent_date           = '".$today."',
                                            qualification       = '".(($_POST['qualification_value']!='') ? Database::escape_string($_POST['qualification_value']) : '') ."',
                                            parent_id           = '',
                                            qualificator_id     = '',
                                            date_of_qualification	= '0000-00-00 00:00:00',
                                            weight              = '".Database::escape_string($_POST['weight'])."',
                                            session_id          = '".intval($id_session)."',
                                            allow_text_assignment = '".Database::escape_string($_POST['allow_text_assignment'])."',
                                            contains_file       = 0,
                                            user_id 			= '".$user_id."'";

                    Database::query($sql_add_publication);

                    // add the directory
                    $id = Database::insert_id();
                    if ($id) {
                        // Insert into agenda
                        $agenda_id = 0;
                        $end_date = '';
                        if (isset($_POST['add_to_calendar']) && $_POST['add_to_calendar'] == 1) {
                            require_once api_get_path(SYS_CODE_PATH).'calendar/agenda.inc.php';
                            require_once api_get_path(SYS_CODE_PATH).'resourcelinker/resourcelinker.inc.php';

                            // Setting today date
                            $date = $end_date = $time;

                            $title = sprintf(get_lang('HandingOverOfTaskX'), $_POST['new_dir']);
                            if (!empty($_POST['type1'])) {
                                $end_date = get_date_from_select('expires');
                                $date	  = $end_date;
                            }
                            $description = isset($_POST['description']) ? $_POST['description'] : '';
                            $content = '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;curdirpath='.api_substr($dir_name_sql, 1).'" >'.$_POST['new_dir'].'</a>'.$description;

                            $agenda_id = agenda_add_item($course_info, $title, $content, $date, $end_date, array('GROUP:'.$group_id), 0);
                        }
                    }

                    //Folder created
                    api_item_property_update($course_info, 'work', $id, 'DirectoryCreated', $user_id, $group_id);
                    Display :: display_confirmation_message(get_lang('DirectoryCreated'), false);

                    // insert into student_publication_assignment
                    //return something like this: 2008-02-45 00:00:00

                    $enable_calification = isset($_POST['qualification_value']) && !empty($_POST['qualification_value']) ? 1 : 0;

                    if (!empty($_POST['type1']) || !empty($_POST['type2'])) {

                        $sql_add_homework = "INSERT INTO $TSTDPUBASG SET
                                                c_id = $course_id ,
                                                expires_on       		= '".((isset($_POST['type1']) && $_POST['type1']==1) ? api_get_utc_datetime(get_date_from_select('expires')) : '0000-00-00 00:00:00'). "',
                                                ends_on        	 		= '".((isset($_POST['type2']) && $_POST['type2']==1) ? api_get_utc_datetime(get_date_from_select('ends')) : '0000-00-00 00:00:00')."',
                                                add_to_calendar  		= '$agenda_id',
                                                enable_qualification 	= '$enable_calification',
                                                publication_id 			= '$id'";
                        Database::query($sql_add_homework);
                        $my_last_id = Database::insert_id();
                        $sql_add_publication = "UPDATE $work_table SET has_properties  = $my_last_id , view_properties = 1  WHERE c_id = $course_id AND id = $id";
                        Database::query($sql_add_publication);
                    } else {
                        $sql_add_homework = "INSERT INTO $TSTDPUBASG SET
                                                c_id = $course_id ,
                                                expires_on     = '0000-00-00 00:00:00',
                                                ends_on        = '0000-00-00 00:00:00',
                                                add_to_calendar  = '$agenda_id',
                                                enable_qualification = '".$enable_calification."',
                                                publication_id = '".$id."'";
                        Database::query($sql_add_homework);
                        $inserted_id = Database::insert_id();
                        $sql_add_publication = "UPDATE $work_table SET has_properties  = $inserted_id, view_properties = 0 WHERE c_id = $course_id AND id = $id";
                        Database::query($sql_add_publication);
                    }
                    if (!empty($_POST['category_id'])) {

                        if (isset($_POST['make_calification']) && $_POST['make_calification'] == 1) {

                            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/gradebookitem.class.php';
                            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/evaluation.class.php';
                            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/abstractlink.class.php';
                            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';

                            $resource_name = $_POST['new_dir'];
                            add_resource_to_course_gradebook($_POST['category_id'], api_get_course_id(), 3, $id, $resource_name, $_POST['weight'], $_POST['qualification_value'], $_POST['description'], 1, api_get_session_id());
                        }
                    }

                    if (api_get_course_setting('email_alert_students_on_new_homework') == 1) {
                        send_email_on_homework_creation(api_get_course_id());
                    }
                } else {
                    Display :: display_error_message(get_lang('CannotCreateDir'));
                }
            } else {
                $form->display();
            }
        }
    case 'make_visible':
    case 'delete':
    case 'make_invisible':
    case 'move':
    case 'move_to':
    case 'list':
        /* Move file command */
        if ($is_allowed_to_edit && $action == 'move_to') {
            $move_to_path = get_work_path($_REQUEST['move_to_id']);

            if ($move_to_path==-1) {
                $move_to_path = '/';
            } elseif (substr($move_to_path, -1, 1) != '/') {
                $move_to_path = $move_to_path .'/';
            }

            //security fix: make sure they can't move files that are not in the document table
            if ($path = get_work_path($item_id)) {

                if (move($course_dir.'/'.$path, $base_work_dir . $move_to_path)) {
                    // Update db
                    update_work_url($item_id, 'work' . $move_to_path, $_REQUEST['move_to_id']);
                    api_item_property_update($_course, 'work', $_REQUEST['move_to_id'], 'FolderUpdated', $user_id);

                    Display :: display_confirmation_message(get_lang('DirMv'));
                } else {
                    Display :: display_error_message(get_lang('Impossible'));
                }
            } else {
                Display :: display_error_message(get_lang('Impossible'));
            }
        }

        /*	Move file form request */
        if ($is_allowed_to_edit && $action == 'move') {
            if (!empty($item_id)) {
                $folders = array();
                $session_id = api_get_session_id();
                $session_id == 0 ? $withsession = " AND session_id = 0 " : $withsession = " AND session_id='".$session_id."'";
                $sql = "SELECT id, url, title FROM $work_table
                        WHERE c_id = $course_id AND active IN (0, 1) AND url LIKE '/%' AND post_group_id = '".$group_id."'".$withsession;
                $res = Database::query($sql);
                while ($folder = Database::fetch_array($res)) {
                    $folders[$folder['id']] = $folder['title'];
                }
                echo build_work_move_to_selector($folders, $curdirpath, $item_id);
            }
        }

        /*	MAKE VISIBLE WORK COMMAND */
        if ($is_allowed_to_edit && $action == 'make_visible') {
            if (!empty($item_id)) {
                if (isset($item_id) && $item_id == 'all') {
                } else {
                    $sql = "UPDATE " . $work_table . "	SET accepted = 1 WHERE c_id = $course_id AND id = '" . $item_id . "'";
                    Database::query($sql);
                    api_item_property_update($course_info, 'work', $item_id, 'visible', api_get_user_id());
                    Display::display_confirmation_message(get_lang('FileVisible'));
                }
            }
        }

        if ($is_allowed_to_edit && $action == 'make_invisible') {

            /*	MAKE INVISIBLE WORK COMMAND */
            if (!empty($item_id)) {
                if (isset($item_id) && $item_id == 'all') {
                } else {
                    $sql = "UPDATE  " . $work_table . " SET accepted = 0
                            WHERE c_id = $course_id AND id = '" . $item_id . "'";
                    Database::query($sql);
                    api_item_property_update($course_info, 'work', $item_id, 'invisible', api_get_user_id());
                    Display::display_confirmation_message(get_lang('FileInvisible'));
                }
            }
        }

        /*	Delete dir command */

        if ($is_allowed_to_edit && !empty($_REQUEST['delete_dir'])) {
            $delete_dir_id = intval($_REQUEST['delete_dir']);
            $locked = api_resource_is_locked_by_gradebook($delete_dir_id, LINK_STUDENTPUBLICATION);

            if ($locked == false) {

                $work_to_delete = get_work_data_by_id($delete_dir_id);
                del_dir($delete_dir_id);

                // gets calendar_id from student_publication_assigment
                $sql = "SELECT add_to_calendar FROM $TSTDPUBASG WHERE c_id = $course_id AND publication_id ='$delete_dir_id'";
                $res = Database::query($sql);
                $calendar_id = Database::fetch_row($res);

                // delete from agenda if it exists
                if (!empty($calendar_id[0])) {
                    $t_agenda   = Database::get_course_table(TABLE_AGENDA);
                    $sql = "DELETE FROM $t_agenda WHERE c_id = $course_id AND id ='".$calendar_id[0]."'";
                    Database::query($sql);
                }
                $sql = "DELETE FROM $TSTDPUBASG WHERE c_id = $course_id AND publication_id ='$delete_dir_id'";
                Database::query($sql);

                $link_info = is_resource_in_course_gradebook(api_get_course_id(), 3 , $delete_dir_id, api_get_session_id());
                $link_id = $link_info['id'];
                if ($link_info !== false) {
                    remove_resource_from_course_gradebook($link_id);
                }
                Display :: display_confirmation_message(get_lang('DirDeleted') . ': '.$work_to_delete['title']);
            } else {
                Display::display_warning_message(get_lang('ResourceLockedByGradebook'));
            }
        }

        /*	DELETE WORK COMMAND */

        if ($action == 'delete' && $item_id) {

            $file_deleted = false;
            $is_author = user_is_author($item_id);
            $work_data = get_work_data_by_id($item_id);
            $locked = api_resource_is_locked_by_gradebook($work_data['parent_id'], LINK_STUDENTPUBLICATION);

            if (($is_allowed_to_edit && $locked == false) || ($locked == false AND $is_author && api_get_course_setting('student_delete_own_publication') == 1 && $work_data['qualificator_id'] == 0)) {
                //we found the current user is the author
                $queryString1 	= "SELECT url, contains_file FROM ".$work_table." WHERE c_id = $course_id AND id = $item_id";
                $result1 		= Database::query($queryString1);
                $row 			= Database::fetch_array($result1);


                if (Database::num_rows($result1) > 0) {
                    $queryString2 	= "UPDATE " . $work_table . "  SET active = 2 WHERE c_id = $course_id AND id = $item_id";
                    $queryString3 	= "DELETE FROM  ".$TSTDPUBASG ." WHERE c_id = $course_id AND publication_id = $item_id";
                    Database::query($queryString2);
                    Database::query($queryString3);

                    api_item_property_update($_course, 'work', $item_id, 'DocumentDeleted', $user_id);
                    $work = $row['url'];

                    if ($row['contains_file'] == 1) {
                        if (!empty($work)) {
                            if (api_get_setting('permanently_remove_deleted_files') == 'true') {
                                my_delete($currentCourseRepositorySys.'/'.$work);
                                Display::display_confirmation_message(get_lang('TheDocumentHasBeenDeleted'));
                                $file_deleted = true;
                            } else {
                                $extension = pathinfo($work, PATHINFO_EXTENSION);
                                $new_dir = $work.'_DELETED_'.$item_id.'.'.$extension;

                                if (file_exists($currentCourseRepositorySys.'/'.$work)) {
                                    rename($currentCourseRepositorySys.'/'.$work, $currentCourseRepositorySys.'/'.$new_dir);
                                    Display::display_confirmation_message(get_lang('TheDocumentHasBeenDeleted'));
                                    $file_deleted = true;
                                }
                            }
                        }
                    } else {
                        $file_deleted = true;
                    }
                }
            }

            if (!$file_deleted) {
                Display::display_error_message(get_lang('YouAreNotAllowedToDeleteThisDocument'));
            }
        }

        /*	Display list of student publications */

        if (!empty($my_folder_data['description'])) {
            echo '<p><div><strong>'.get_lang('Description').':</strong><p>'.Security::remove_XSS($my_folder_data['description'], STUDENT).'</p></div></p>';
        }

        $my_folder_data = get_work_data_by_id($work_id);

        $work_parents = array();
        if (empty($my_folder_data)) {
            $work_parents = getWorkList($work_id, $my_folder_data, $add_query);
        }

        if (api_is_allowed_to_edit()) {

            // Work list
            echo '<div class="row">';
            echo '<div class="span9">';

            if (!empty($group_id)) {
                $userList = GroupManager::get_users($group_id);
            } else {
                if (empty($session_id)) {
                    $userList = CourseManager::get_user_list_from_course_code($course_code, $session_id, null, null, STUDENT);
                } else {
                    $userList = CourseManager::get_user_list_from_course_code($course_code, $session_id, null, null, 0);
                }
                $userList = array_keys($userList);
            }

            display_student_publications_list($work_id, $my_folder_data, $work_parents, $origin, $add_query, $userList);

            echo '</div>';
            echo '<div class="span3">';

            $table = new HTML_Table(array('class' => 'data_table'));
            $column = 0;
            $row = 0;
            $headers = array(get_lang('Students'), get_lang('Works'));
            foreach ($headers as $header) {
                $table->setHeaderContents($row, $column, $header);
                $column++;
            }
            $row++;
            $column = 0;

            foreach ($userList as $userId) {
                $user = api_get_user_info($userId);
                $link = api_get_path(WEB_CODE_PATH).'work/student_work.php?'.api_get_cidreq().'&studentId='.$user['user_id'];
                $url = Display::url(api_get_person_name($user['firstname'], $user['lastname']), $link);
                $table->setCellContents($row, $column, $url);
                $column++;
                $userWorks = 0;
                foreach ($work_parents as $work) {
                    $userWorks += getUniqueStudentAttempts($work->id, $group_id, $course_id, $session_id, $user['user_id']);
                }
                $cell = $userWorks." / ".count($work_parents);
                $table->setCellContents($row, $column, $cell);
                $row++;
                $column = 0;
            }

            echo $table->toHtml();
            echo '</div>';
        } else {
            display_student_publications_list($work_id, $my_folder_data, $work_parents, $origin, $add_query, null);
        }
    break;
}
if ($origin != 'learnpath') {
    //we are not in the learning path tool
    Display :: display_footer();
}
