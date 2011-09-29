<?php
/* For licensing terms, see /license.txt */
/**
 * Special exports
 *
 * @author Jhon Hinojosa 
 * @author Julio Montoya Fixing pclzip folder + some clean <gugli100@gmail.com>
 * @package chamilo.include.export
 */

// name of the language file that needs to be included
$language_file = array('admin');
// including the global file
$cidReset = true;
require_once  '../inc/global.inc.php';
// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
// setting breadcrumbs
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
// Access restrictions
api_protect_admin_script(true);
$nameTools = get_lang('SpecialExports');

// include additional libraries
require_once '../document/document.inc.php';
// include additional libraries
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once '../coursecopy/classes/CourseBuilder.class.php';
require_once '../coursecopy/classes/CourseArchiver.class.php';
require_once '../coursecopy/classes/CourseRestorer.class.php';
require_once '../coursecopy/classes/CourseSelectForm.class.php';

if(function_exists('ini_set')) {
	ini_set('memory_limit','256M');
	ini_set('max_execution_time',0);
}

// Displaying the header
Display::display_header($nameTools);
// Display the tool title
api_display_tool_title($nameTools);
if (count($_POST) == 0) {
    Display::display_normal_message(get_lang('SpecialExportsIntroduction'));
}
$error =0;
/* MAIN CODE */

$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

if ((isset ($_POST['action']) && $_POST['action'] == 'course_select_form') || (isset ($_POST['backup_option']) && $_POST['backup_option'] == 'full_backup')) {
	require_once api_get_path(LIBRARY_PATH).'document.lib.php';
	require_once api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php';
	$export = false;
	if (isset ($_POST['action']) && $_POST['action'] == 'course_select_form') {
		$FileZip = create_zip();
		$to_group_id = 0;
		$code_course = '';
		$sql_session = "SELECT id, name FROM $tbl_session ";
		$query_session = Database::query($sql_session);
		$ListSession = array();
		while ($rows_session = Database::fetch_assoc($query_session)) {
			$ListSession[$rows_session['id']] = $rows_session['name'];
		}
		$zip_folder=new PclZip($FileZip['TEMP_FILE_ZIP']);
		if(!isset($_POST['resource']) || count($_POST['resource']) == 0 ) {
			Display::display_error_message(get_lang('ErrorMsgSpecialExport'));
		} else {
			$Resource = $_POST['resource'];
			foreach($Resource as $Code_course => $Sessions) {
				$_course 		= Database::get_course_info($Code_course);
				$tbl_document 	= Database::get_course_table(TABLE_DOCUMENT);
				$tbl_property 	= Database::get_course_table(TABLE_ITEM_PROPERTY);
				$course_id 		= $_course['real_id'];
				
				//Add tem to the zip file course
				$sql = "SELECT path FROM $tbl_document AS docs, $tbl_property AS props
					WHERE props.tool='".TOOL_DOCUMENT."'
						AND docs.id=props.ref
						AND docs.path LIKE '".$querypath."/%'
						AND docs.filetype='file'
						AND docs.session_id = '0'
						AND props.visibility<>'2'
						AND props.to_group_id=".$to_group_id." AND docs.c_id = $course_id AND props.c_id = $course_id";
				$query = Database::query($sql );
				while ($rows_course_file = Database::fetch_assoc($query)) {
					$zip_folder->add($FileZip['PATH_COURSE'].$_course['directory']."/document".$rows_course_file['path'],
									 PCLZIP_OPT_ADD_PATH, $_course['directory'],
									 PCLZIP_OPT_REMOVE_PATH, $FileZip['PATH_COURSE'].$_course['directory']."/document".$FileZip['PATH_REMOVE']
									);
				}
				foreach($Sessions as $IdSession => $value){
					$session_id = Security::remove_XSS($IdSession);
					//Add tem to the zip file session course
					$sql_session_doc = "SELECT path FROM $tbl_document AS docs,$tbl_property AS props
						WHERE props.tool='".TOOL_DOCUMENT."'
							AND docs.id=props.ref
							AND docs.path LIKE '".$querypath."/%'
							AND docs.filetype='file'
							AND docs.session_id = '$session_id'
							AND props.visibility<>'2'
							AND props.to_group_id=".$to_group_id."";
					$query_session_doc = Database::query($sql_session_doc);
					while ($rows_course_session_file = Database::fetch_assoc($query_session_doc)) {
						$zip_folder->add($FileZip['PATH_COURSE'].$_course['directory'].'/document'.$rows_course_session_file['path'],
										 PCLZIP_OPT_ADD_PATH, $_course['directory']."/".$ListSession[$session_id],
										 PCLZIP_OPT_REMOVE_PATH, $FileZip['PATH_COURSE'].$_course['directory'].'/document'.$FileZip['PATH_REMOVE']
										);
					}
				}
			}
			$name = rename_zip($FileZip);
			$export = true;
		}
	} else {
		$name = fullexportspecial();
	}
?>
<!-- Manual download <script language="JavaScript">
 // setTimeout(\'download_backup()\',2000);
 function download_backup()
 {
	window.location="../course_info/download.php?archive=<?php echo $name; ?>&session=true";
 }
</script> //-->
<?php
}

if ($export && $name) {
	Display::display_confirmation_message(get_lang('BackupCreated')); 
	echo '<br /><a class="a_button orange medium" href="'.api_get_path(WEB_CODE_PATH).'course_info/download.php?archive='.urlencode($name).'&session=true">'.get_lang('Download').'</a>';	
} else {
	// Display forms especial export
	if (isset ($_POST['backup_option']) && $_POST['backup_option'] == 'select_items') {
		$cb = new CourseBuilder();
		$course = $cb->build_session_course();
		if($course === false){
			Display::display_error_message(get_lang('ErrorMsgSpecialExport'));
			form_special_export();
		} else {
			Display::display_normal_message(get_lang('ToExportSpecialSelect'));
			CourseSelectForm :: display_form_session_export($course);
		}
	} else {
		form_special_export();
	}
}

/* FOOTER */
Display::display_footer();

function form_special_export(){
    $htlm = get_lang('SelectOptionExport');
    include_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
    $form = new FormValidator('special_exports','post');
    $renderer = $form->defaultRenderer();
    $renderer->setElementTemplate('<div>{element}</div> ');
    $form->addElement('radio', 'backup_option', '',  get_lang('SpecialCreateFullBackup'), 'full_backup');
    $form->addElement('radio', 'backup_option', '',  get_lang('SpecialLetMeSelectItems'), 'select_items');
    $form->addElement('html','<br />');
    $form->addElement('style_submit_button', null, get_lang('CreateBackup'), 'class="save"');
    $form->add_progress_bar();
    $values['backup_option'] = 'full_backup';
    $form->setDefaults($values);
    $form->display();
}

function create_zip(){
    $path = '';
    if(empty($path)) { $path='/'; }
    $remove_dir = ($path!='/') ? substr($path,0,strlen($path) - strlen(basename($path))) : '/';
    $to_group_id = $_SESSION['_gid'];
    $sys_archive_path = api_get_path(SYS_ARCHIVE_PATH);
    $sys_course_path = api_get_path(SYS_COURSE_PATH);
    $temp_zip_dir = $sys_archive_path."temp";
    if(!is_dir($temp_zip_dir)) {
        mkdir($temp_zip_dir, api_get_permissions_for_new_directories());
    } else {
        $handle=opendir($temp_zip_dir);
        while (false!==($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                $Diff = (time() - filemtime("$temp_zip_dir/$file"))/60/60;  //the "age" of the file in hours
                if ($Diff > 4) unlink("$temp_zip_dir/$file");   //delete files older than 4 hours
            }
        }
        closedir($handle);
    }
    $temp_zip_file = $temp_zip_dir."/".md5(time()).".zip";  //create zipfile of given directory
    return array('PATH' => $path,
                 'PATH_TEMP_ARCHIVE' => $temp_zip_dir,
                 'PATH_COURSE' => $sys_course_path,
                 'TEMP_FILE_ZIP' => $temp_zip_file,
                 'PATH_REMOVE' => $remove_dir);
}

function rename_zip($FileZip) {
    event_download(($FileZip['PATH'] == '/')?'full_export_'.date('Ymd').'.zip (folder)': basename($FileZip['PATH']).'.zip (folder)');
    $name = ($FileZip['PATH']=='/')? 'full_export_'.date('Ymd').'.zip':basename($FileZip['PATH']).'.zip';
    if(file_exists($FileZip['PATH_TEMP_ARCHIVE'].'/'.$name)){ unlink($FileZip['PATH_TEMP_ARCHIVE'].'/'.$name); }
    if(file_exists($FileZip['TEMP_FILE_ZIP'])) {
        rename($FileZip['TEMP_FILE_ZIP'], $FileZip['PATH_TEMP_ARCHIVE'].'/'.$name);
        return $name;
    } else { return false; }

}

function fullexportspecial(){
    global $tbl_session, $tbl_session_course, $export;
    $FileZip = create_zip();
    $to_group_id = 0;
    $code_course = '';
    $list_course = array();
    $zip_folder = new PclZip($FileZip['TEMP_FILE_ZIP']);
    $list_course = Database::get_course_list();
    
    $tbl_document = Database::get_course_table(TABLE_DOCUMENT);
    $tbl_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
    
    
    if (count($list_course) >0 ) {
        foreach($list_course as $_course) {
            if($FileZip['PATH'] == '/') {
                $querypath=''; // to prevent ...path LIKE '//%'... in query
            } else {
                $querypath = $FileZip['PATH'];
            }
            $course_id 		= $_course['real_id'];            
            
            //Add tem to the zip file course
            $sql = "SELECT path FROM $tbl_document AS docs, $tbl_property AS props
                WHERE props.tool='".TOOL_DOCUMENT."'
                    AND docs.id=props.ref
                    AND docs.path LIKE '".$querypath."/%'
                    AND docs.filetype='file'
                    AND docs.session_id = '0'
                    AND props.visibility<>'2'
                    AND props.to_group_id=".$to_group_id." AND docs.c_id = $course_id AND props.c_id = $course_id";
            $query = Database::query($sql );
            while ($rows_course_file = Database::fetch_assoc($query)) {
                $rows_course_file['path'];
                $zip_folder->add($FileZip['PATH_COURSE'].$_course['directory']."/document".$rows_course_file['path'],
                                 PCLZIP_OPT_ADD_PATH, $_course['directory'],
                                 PCLZIP_OPT_REMOVE_PATH, $FileZip['PATH_COURSE'].$_course['directory']."/document".$FileZip['PATH_REMOVE']
                                );
            }
            //Add tem to the zip file session course
            $code_course = $_course['code'];
            $sql_session = "SELECT id, name, course_code FROM $tbl_session_course
                INNER JOIN  $tbl_session ON id_session = id
                WHERE course_code = '$code_course' ";
            $query_session = Database::query($sql_session);
            while ($rows_session = Database::fetch_assoc($query_session)) {
                $session_id = $rows_session['id'];
                $sql_session_doc = "SELECT path FROM $tbl_document AS docs, $tbl_property AS props
                    WHERE props.tool='".TOOL_DOCUMENT."'
                        AND docs.id=props.ref
                        AND docs.path LIKE '".$querypath."/%'
                        AND docs.filetype='file'
                        AND docs.session_id = '$session_id'
                        AND props.visibility<>'2'
                        AND props.to_group_id=".$to_group_id." AND docs.c_id = $course_id AND props.c_id = $course_id ";
                $query_session_doc = Database::query($sql_session_doc);
                while ($rows_course_session_file = Database::fetch_assoc($query_session_doc)) {
                    $zip_folder->add($FileZip['PATH_COURSE'].$_course['directory'].'/document'.$rows_course_session_file['path'],
                                     PCLZIP_OPT_ADD_PATH, $_course['directory']."/".$rows_session['name'],
                                     PCLZIP_OPT_REMOVE_PATH, $FileZip['PATH_COURSE'].$_course['directory'].'/document'.$FileZip['PATH_REMOVE']
                                    );
                }
            }
        }
        $name = rename_zip($FileZip);
        if($name === false){
            $export = false;
            return false;
        }else{
            $export = true;
            return $name;
        }
    }else{
        Display::display_error_message(get_lang('ErrorMsgSpecialExport')); //main API
        $export = false;
        return false;
    }
}
