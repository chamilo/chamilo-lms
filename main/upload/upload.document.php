<?php
/* For licensing terms, see /license.txt */

/**
 * Process part of the document sub-process for upload. This script MUST BE included by upload/index.php
 * as it prepares most of the variables needed here.
 *
 * @todo check if this file is deprecated ... jmontoya
 *
 * @package chamilo.upload
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
$_course = api_get_course_info();
$courseDir = $_course['path']."/document";
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$base_work_dir = $sys_course_path.$courseDir;
$max_filled_space = DocumentManager::get_course_quota();

//what's the current path?
if (isset($_POST['curdirpath'])) {
    $path = Security::remove_XSS($_POST['curdirpath']);
} else {
    $path = '/';
}

// Check the path
// If the path is not found (no document id), set the path to /
if (!DocumentManager::get_document_id($_course, $path)) {
    $path = '/';
}

/**
 *	Header.
 */
$nameTools = get_lang('UplUploadDocument');
$interbreadcrumb[] = [
    "url" => api_get_path(WEB_CODE_PATH)."document/document.php?curdirpath=".urlencode($path).'&'.api_get_cidreq(),
    "name" => get_lang('Documents'),
];
Display::display_header($nameTools, "Doc");
//show the title
api_display_tool_title($nameTools.$add_group_to_title);

/**
 * Process.
 */
//user has submitted a file
if (isset($_FILES['user_upload'])) {
    $upload_ok = process_uploaded_file($_FILES['user_upload']);
    if ($upload_ok) {
        //file got on the server without problems, now process it
        $new_path = handle_uploaded_document(
            $_course,
            $_FILES['user_upload'],
            $base_work_dir,
            $_POST['curdirpath'],
            api_get_user_id(),
            api_get_group_id(),
            $to_user_id,
            $_POST['unzip'],
            $_POST['if_exists']
        );
        $new_comment = isset($_POST['comment']) ? Database::escape_string(trim($_POST['comment'])) : '';
        $new_title = isset($_POST['title']) ? Database::escape_string(trim($_POST['title'])) : '';

        if ($new_path && ($new_comment || $new_title)) {
            if (($docid = DocumentManager::get_document_id($_course, $new_path))) {
                $table_document = Database::get_course_table(TABLE_DOCUMENT);
                $ct = '';
                if ($new_comment) {
                    $ct .= ", comment='$new_comment'";
                }
                if ($new_title) {
                    $ct .= ", title='$new_title'";
                }
                Database::query("UPDATE $table_document SET".substr($ct, 1)." WHERE id = '$docid'");
            }
        }
        //check for missing images in html files
        $missing_files = check_for_missing_files($base_work_dir.$_POST['curdirpath'].$new_path);
        if ($missing_files) {
            //show a form to upload the missing files
            echo Display::return_message(
                build_missing_files_form(
                    $missing_files,
                    $_POST['curdirpath'],
                    $_FILES['user_upload']['name']
                )
            );
        }
    }
}
//missing images are submitted
if (isset($_POST['submit_image'])) {
    $number_of_uploaded_images = count($_FILES['img_file']['name']);
    //if images are uploaded
    if ($number_of_uploaded_images > 0) {
        //we could also create a function for this, I'm not sure...
        //create a directory for the missing files
        $img_directory = str_replace('.', '_', $_POST['related_file']."_files");
        $folderData = create_unexisting_directory(
            $_course,
            api_get_user_id(),
            api_get_session_id(),
            api_get_group_id(),
            $to_user_id,
            $base_work_dir,
            $img_directory
        );
        $missing_files_dir = $folderData['path'];
        //put the uploaded files in the new directory and get the paths
        $paths_to_replace_in_file = move_uploaded_file_collection_into_directory(
            $_course,
            $_FILES['img_file'],
            $base_work_dir,
            $missing_files_dir,
            $_user['user_id'],
            $to_group_id,
            $to_user_id,
            $max_filled_space
        );

        //open the html file and replace the paths
        replace_img_path_in_html_file(
            $_POST['img_file_path'],
            $paths_to_replace_in_file,
            $base_work_dir.$_POST['related_file']
        );
        //update parent folders
        item_property_update_on_folder($_course, $_POST['curdirpath'], $_user['user_id']);
    }
}
//they want to create a directory
if (isset($_POST['create_dir']) && $_POST['dirname'] != '') {
    $added_slash = $path == '/' ? '' : '/';
    $dir_name = $path.$added_slash.api_replace_dangerous_char($_POST['dirname']);
    $created_dir = create_unexisting_directory(
        $_course,
        api_get_user_id(),
        api_get_session_id(),
        api_get_group_id(),
        $to_user_id,
        $base_work_dir,
        $dir_name,
        $_POST['dirname']
    );
    if ($created_dir) {
        echo Display::return_message(get_lang('DirCr'));
        $path = $created_dir;
    } else {
        echo Display::return_message(get_lang('CannotCreateDir'));
    }
}

if (isset($_GET['createdir'])) {
    //create the form that asks for the directory name
    $new_folder_text = '<form action="'.api_get_self().'" method="POST">';
    $new_folder_text .= '<input type="hidden" name="curdirpath" value="'.$path.'"/>';
    $new_folder_text .= get_lang('NewDir').' ';
    $new_folder_text .= '<input type="text" name="dirname"/>';
    $new_folder_text .= '<input type="submit" name="create_dir" value="'.get_lang('Ok').'"/>';
    $new_folder_text .= '</form>';
    //show the form
    echo Display::return_message($new_folder_text, 'normal');
} else {
    //give them a link to create a directory?>
    <p>
        <a href="<?php echo api_get_self(); ?>?path=<?php echo $path; ?>&amp;createdir=1">
            <?php echo Display::return_icon('new_folder.gif'); ?>
            <?php echo get_lang('CreateDir'); ?>
        </a>
    </p>
<?php
}
?>

<div id="folderselector">
</div>
<!-- start upload form -->
<form action="<?php echo api_get_self(); ?>" method="POST" name="upload" enctype="multipart/form-data">
<!-- <input type="hidden" name="MAX_FILE_SIZE" value="5400"> -->
<input type="hidden" name="curdirpath" value="<?php echo $path; ?>">
<table>
<tr>
<td valign="top">
<?php echo get_lang('File'); ?>
</td>
<td>
<input type="file" name="user_upload"/>
</td>
</tr>
<tr>
<td><?php echo get_lang('Title'); ?></td>
<td><input type="text" size="20" name="title" style="width:300px;"></td>
</tr>
<tr>
<td valign="top"><?php echo get_lang('Comment'); ?></td>
<td><textarea rows="3" cols="20" name="comment" wrap="virtual" style="width:300px;"></textarea></td>
</tr>
<tr>
<td valign="top">
<?php echo get_lang('Options'); ?>
</td>
<td>
- <input type="checkbox" name="unzip" value="1" onclick="check_unzip()"/> <?php echo get_lang('Uncompress'); ?><br/>
- <?php echo get_lang('UplWhatIfFileExists'); ?><br/>
&nbsp;&nbsp;&nbsp;<input type="radio" name="if_exists" value="nothing" title="<?php echo get_lang('UplDoNothingLong'); ?>" checked="checked"/>  <?php echo get_lang('UplDoNothing'); ?><br/>
&nbsp;&nbsp;&nbsp;<input type="radio" name="if_exists" value="overwrite" title="<?php echo get_lang('UplOverwriteLong'); ?>"/> <?php echo get_lang('UplOverwrite'); ?><br/>
&nbsp;&nbsp;&nbsp;<input type="radio" name="if_exists" value="rename" title="<?php echo get_lang('UplRenameLong'); ?>"/> <?php echo get_lang('UplRename'); ?>
</td>
</tr>
</table>

<input type="submit" value="<?php echo get_lang('Ok'); ?>">
</form>
<!-- end upload form -->

 <!-- so they can get back to the documents   -->
 <p><?php echo get_lang('Back'); ?> <?php echo get_lang('To'); ?> <a href="document.php?curdirpath=<?php echo $path; ?>"><?php echo get_lang('DocumentsOverview'); ?></a></p>
<?php

Display::display_footer();
