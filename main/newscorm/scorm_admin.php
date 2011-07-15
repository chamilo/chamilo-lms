<?php
/* For licensing terms, see /license.txt */

// This file is probably deprecated - 2009-05-14 - ywarnier
/**
* This script handles SCO administration features
* @package chamilo.learnpath.scorm
* @author Denes Nagy, principal author
* @author Isthvan Mandak, several new features
* @author Roan Embrechts, code improvements and refactoring
* @author Yannick Warnier, complete refactoring <ywarnier@beeznest.org>
*/

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

// Name of the language file that needs to be included.
$language_file = 'scormdocument';

$uncompress = 1; // TODO: This variable shouldn't be found here (find its usage before removal).

require_once 'back_compat.inc.php';
include 'learnpath_functions.inc.php';
include_once 'scorm.lib.php';

$is_allowedToEdit = api_is_allowed_to_edit();

/* Variables */

// Escapable integers.
if ($_REQUEST['id'] != strval(intval($_REQUEST['id']))) { $id = $_REQUEST['id']; } else { $id = null; }

// Imported strings.
$path 					= (!empty($_REQUEST['path']) ? $_REQUEST['path'] : null);
$Submit 				= (!empty($_POST['Submit']) ? $_POST['Submit'] : null);
$submitImage 			= (!empty($_POST['submitImage']) ? $_POST['submitImage'] : null);
$cancelSubmitImage 		= (!empty($_POST['cancelSubmitImage']) ? $_POST['cancelSubmitImage'] : null);
$action					= (!empty($_REQUEST['action']) ? $_REQUEST['action'] : null);
$delete					= (!empty($_REQUEST['delete']) ? $_REQUEST['delete'] : null);
$createDir				= (!empty($_REQUEST['createDir']) ? $_REQUEST['createDir'] : null);
$make_directory_visible = (!empty($_REQUEST['make_directory_visible']) ? $_REQUEST['make_directory_visible'] : '');
$make_directory_invisible = (!empty($_REQUEST['make_directory_invisible']) ? $_REQUEST['make_directory_invisible'] : '');

// Values from POST form to add directory.
$newDirPath				= (!empty($_POST['newDirPath']) ? $_POST['newDirPath'] : null);
$newDirName				= (!empty($_POST['newDirName']) ? $_POST['newDirName'] : null);
// Initialising internal variables.
$dialogbox = '';

if (! $is_allowed_in_course) api_not_allowed();
$is_allowedToUnzip = $is_courseAdmin;

/* Main code */

switch ($action) {
    case 'exportpath':
        if (!empty($id)) {
              $export = exportpath($id);
              $dialogBox .= "This LP has been exported to the Document folder "
                          ."of your course.";
        }
        break;
    case 'exportscorm':
        exportSCORM($path);
        break;
    case 'deletepath':
        /*
            DELETE A DOKEOS LEARNPATH
            and all the items in it
        */
        if (!empty($id)){
            $l="learnpath/learnpath_handler.php?learnpath_id=$id";
            $sql="DELETE FROM $tbl_tool where (link='$l' AND image='scormbuilder.gif')";
            $result=Database::query($sql);
            $sql="SELECT * FROM $tbl_learnpath_chapter where learnpath_id=$id";
            $result=Database::query($sql);
            while ($row=Database::fetch_array($result))
            {
                $c=$row['id'];
                $sql2="DELETE FROM $tbl_learnpath_item where chapter_id=$c";
                $result2=Database::query($sql2);
            }
            $sql="DELETE FROM $tbl_learnpath_chapter where learnpath_id=$id";
            $result=Database::query($sql);
            deletepath($id);
            $dialogBox=get_lang('_learnpath_deleted');
        }
        break;
    case 'publishpath':
        /* PUBLISHING (SHOWING) A DOKEOS LEARNPATH */
        if (!empty($id)){
            $sql = "SELECT * FROM $tbl_learnpath_main where learnpath_id=$id";
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            $name = domesticate($row['learnpath_name']);
            if ($set_visibility == 'i') {
                $s = $name.' '.get_lang('_no_published');
                $dialogBox = $s;
                $v = 0;
            }
            if ($set_visibility == 'v') {
                $s=$name.' '.get_lang('_published');
                $dialogBox = $s;
                $v = 1;
            }
            $sql = "SELECT * FROM $tbl_tool where (name='$name' and image='scormbuilder.gif')";
            $result = Database::query($sql);
            $row2 = Database::fetch_array($result);
            $num = Database::num_rows($result);
            if (($set_visibility == 'i') && ($num > 0)) {
                // It is visible or hidden but once was published.
                if (($row2['visibility']) == 1) {
                    $sql = "DELETE FROM $tbl_tool WHERE (name='$name' and image='scormbuilder.gif')";
                } else {
                    $sql = "UPDATE $tbl_tool set visibility=1 WHERE (name='$name' and image='scormbuilder.gif')";
                }
            } elseif (($set_visibility == 'v') && ($num == 0)) {
                $sql ="INSERT INTO $tbl_tool (id, name, link, image, visibility, admin, address, added_tool) VALUES ('$theid','$name','learnpath/learnpath_handler.php?learnpath_id=$id','scormbuilder.gif','$v','0','pastillegris.gif',0)";
            } else {
                // Parameter and database incompatible, do nothing.
            }
            $result = Database::query($sql);
        }
        break;
    case 'editpath':
        /* EDITING A DOKEOS NEW LEARNPATH */
        if (!empty($Submit)) {
            $l = "learnpath/learnpath_handler.php?learnpath_id=$id";
            $sql = "UPDATE $tbl_tool set name='".domesticate($learnpath_name)."' where (link='$l' and image='scormbuilder.gif')";
            $result = Database::query($sql);
            $sql = "UPDATE $tbl_learnpath_main SET learnpath_name='".domesticate($learnpath_name)."', learnpath_description='".domesticate($learnpath_description)."' WHERE learnpath_id=$id";
            $result = Database::query($sql);
            $dialogBox = get_lang('_learnpath_edited');
        }
        break;
    case 'add':
        /* ADDING A NEW LEARNPATH : treating the form */
        if (!empty($Submit)) {
            $sql = "INSERT INTO $tbl_learnpath_main (learnpath_name, learnpath_description) VALUES ('".domesticate($learnpath_name)."','".domesticate($learnpath_description)."')";
            Database::query($sql);
            $my_lp_id = Database::insert_id();
            $sql = "INSERT INTO $tbl_tool (name, link, image, visibility, admin, address, added_tool) VALUES ('".domesticate($learnpath_name)."','learnpath/learnpath_handler.php?learnpath_id=$my_lp_id','scormbuilder.gif','1','0','pastillegris.gif',0)";
            Database::query($sql);
            // Instead of displaying this info text, get the user directly to the learnpath edit page.
            //$dialogBox = get_lang('_learnpath_added');
            header('location:../learnpath/learnpath_handler.php?'.api_get_cidreq().'&learnpath_id='.$my_lp_id);
            exit();
        }
        break;
    case 'editscorm':
        /* EDITING A SCORM PACKAGE */
        if (!empty($Submit)) {
            $sql = "UPDATE $tbl_document SET comment='".domesticate($learnpath_description)."', name='".domesticate($learnpath_name)."' WHERE path='$path'";
            $result = Database::query($sql);
            $dialogBox = get_lang('_learnpath_edited');
        }
        break;
    default:
        break;
}

if ($is_allowedToEdit) { // TEACHER ONLY

    /* UPLOAD SCORM */

    /*
     * Check the request method instead of a variable from POST
     * because if the file size exceeds the maximum file upload
     * size set in php.ini, all variables from POST are cleared !
     */

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && count($_FILES) > 0 && empty($submitImage) && empty($cancelSubmitImage) && empty($action)) {

        // A SCORM upload has been detected, now deal with the file...
        // Directory creation.
        $s = $_FILES['userFile']['name'];
        $pathInfo = pathinfo($s);
        // Check the filename has at least several letters in it :-)
        // This is a very loose check as later on we might accept other formats of packages.
        // Sent than just "zip".
        if (preg_match('/[\w-_]+/', $pathInfo['basename'])) {
            // get the basename without extension.
            $newDirName = substr($pathInfo['basename'], 0, strlen($pathInfo['basename']) - (strlen($pathInfo['extension']) + 1));
            $newDirName = replace_dangerous_char(trim($newDirName), 'strict');
            if (check_name_exist($baseWorkDir.$newDirPath.$openDir.'/'.$newDirName)) {
                /** @todo change this output. Inaccurate at least in french. In this case, the
                 * file might not exist or the transfer might have been wrong (no $_FILES at all)
                 * but we still get the error message
                 */
                $dialogBox = get_lang('FileExists');
                $createDir = $newDirPath; unset($newDirPath); // Return to step 1.
            } else {
                if (mkdir($baseWorkDir.$newDirPath.$openDir.'/'.$newDirName, api_get_permissions_for_new_directories())) {
                    FileManager::set_default_settings($newDirPath.$openDir, $newDirName, 'folder', $tbl_document);
                    // RH: was:  set_default_settings($newDirPath.$openDir, $newDirName, 'folder');
                    $dialogBox = get_lang('DirCr');
                } else {
                    //Display msg "could not create dir..."
                    //exit();
                }
                // Directory creation end.

                $uploadPath = $openDir.'/'.$newDirName;
                if (!$_FILES['userFile']['size']) {
                    $dialogBox .= get_lang('FileError').'<br />'.get_lang('Notice').' : '.get_lang('MaxFileSize').' '.ini_get('upload_max_filesize');
                } else { // The file size is alright, we can assume the file is OK too.
                    if ($uncompress == 1 && $is_allowedToUnzip) {
                        $unzip = 'unzip';
                    } else {
                        $unzip = '';
                    }
                    if (treat_uploaded_file($_FILES['userFile'], $baseWorkDir, $uploadPath, $maxFilledSpace, $unzip)) {
                        if ($uncompress == 1) {
                            //$dialogBox .= get_lang('DownloadAndZipEnd');
                            // Modified by darkden : I omitted this part, so the user can see
                            // the scorm content message at once.
                        } else {
                            $dialogBox = get_lang('DownloadEnd');
                        }
                        // "WHAT'S NEW" notification: update table last_tooledit.
                        //update_last_tooledit($_course, $nameTools, $id, get_lang('_new_document'), $_user['user_id']);
                        item_property_update($_course, TOOL_LEARNPATH, $id, "LearnpathAdded", $_user['user_id']);
                    } else {
                        if (api_failure::get_last_failure() == 'not_enough_space') {
                            $dialogBox = get_lang('NoSpace');
                        } elseif (api_failure::get_last_failure() == 'php_file_in_zip_file') {
                            $dialogBox = get_lang('ZipNoPhp');
                        } elseif (api_failure::get_last_failure() == 'not_scorm_content') {
                            $dialogBox = get_lang('NotScormContent');
                        }
                    }
                }
                $uploadPath = '';
                if (api_failure::get_last_failure()) {
                    rmdir($baseWorkDir.$newDirPath.$openDir.'/'.$newDirName);
                }

            }
        } else { // The filename doesn't contain any alphanum chars (empty filename?)
            // Get a more detailed message?
            $dialogBox .= get_lang('FileError').'<br />';
        }

        /* DELETE FILE OR DIRECTORY */

        if (isset($delete)) {
            if ( scorm_delete($baseWorkDir.$delete)) {
                //$tbl_document = substr($tbl_document, 1, strlen($tbl_document) - 2);  // RH...
                update_db_info('delete', $delete);
                $dialogBox = get_lang('DocDeleted');
            }
        }

        /* CREATE DIRECTORY */

        /*
         * The code begin with STEP 2 so it allows to return to STEP 1 if STEP 2 unsucceds.
         */

        /* STEP 2 */

        if (isset($newDirPath) && isset($newDirName)) {
            // echo $newDirPath . $newDirName;
            $newDirName = replace_dangerous_char(trim(stripslashes($newDirName)), 'strict');
            if (check_name_exist($baseWorkDir.$newDirPath.'/'.$newDirName)) {
                $dialogBox = get_lang('FileExists');
                $createDir = $newDirPath; unset($newDirPath);// return to step 1
            } else {
                if (mkdir($baseWorkDir.$newDirPath.'/'.$newDirName, api_get_permissions_for_new_directories()))
                FileManager::set_default_settings($newDirPath, $newDirName, 'folder', $tbl_document);
                // RH: was:  set_default_settings($newDirPath, $newDirName, 'folder');
                $dialogBox = get_lang('DirCr');
            }
        }

        /* STEP 1 */

        if (isset($createDir)) {
            $dialogBox .= "<!-- create dir -->\n"
                ."<form name='createdir' action='' method='POST'>\n"
                ."<input type=\"hidden\" name=\"newDirPath\" value=\"$createDir\" />\n"
                .get_lang('NameDir')." : \n"
                ."<input type=\"text\" name=\"newDirName\" />\n"
                ."<input type=\"submit\" value=\"".get_lang('Ok')."\" />\n"
                ."</form>\n";
          }

        /* VISIBILITY COMMANDS */

          if (!empty($make_directory_visible) || !empty($make_directory_invisible)) {
              $visibilityPath = $make_directory_visible.$make_directory_invisible;
            // At least one of these variables are empty. So it's okay to proceed this way
            /* Check if there is yet a record for this file in the DB */
            $result = Database::query ("SELECT * FROM $tbl_document WHERE path LIKE '".$visibilityPath."'");
            while($row = Database::fetch_array($result, 'ASSOC')) {
                $attribute['path'      ] = $row['path'      ];
                $attribute['visibility'] = $row['visibility'];
                $attribute['comment'   ] = $row['comment'   ];
            }

            if ($make_directory_visible) {
                $newVisibilityStatus = 'v';
            } elseif ($make_directory_invisible) {
                $newVisibilityStatus = 'i';
            }
            $query = "UPDATE $tbl_document SET visibility='$newVisibilityStatus' WHERE path=\"".$visibilityPath."\""; // Added by Toon.
            Database::query($query);
            if (Database::affected_rows() == 0) { // Extra check added by Toon, normally not necessary anymore because all files are in the db.
                Database::query("INSERT INTO $tbl_document SET path=\"".$visibilityPath."\", visibility=\"".$newVisibilityStatus."\"");
            }
            unset($attribute);
            $dialogBox = get_lang('ViMod');
          }
    } // END is Allowed to edit;.
}
