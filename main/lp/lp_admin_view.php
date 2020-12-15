<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This is a learning path creation and player tool in Chamilo - previously learnpath_handler.php.
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update for new SCORM tool
 */
$this_section = SECTION_COURSES;

api_protect_course_script();

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');

$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);

$isStudentView = isset($_REQUEST['isStudentView']) ? (int) $_REQUEST['isStudentView'] : null;
$learnpath_id = (int) $_REQUEST['lp_id'];
$submit = isset($_POST['submit_button']) ? $_POST['submit_button'] : null;
$_course = api_get_course_info();

$excludeExtraFields = [
    'authors',
    'authorlp',
    'authorlpitem',
    'price',
];
if (api_is_platform_admin()) {
    // Only admins can edit this items
    $excludeExtraFields = [];
}

if (!$is_allowed_to_edit || $isStudentView) {
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
    exit;
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('LearningPaths'),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=build&lp_id=$learnpath_id&".api_get_cidreq(),
    "name" => Security::remove_XSS($learnPath->getNameNoTags()),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&type=step&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => get_lang('NewStep'),
];

if (isset($_REQUEST['updateaudio'])) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('UpdateAllAudioFragments')];
} else {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('BasicOverview')];
}

$htmlHeadXtra[] = '<script>'.$learnPath->get_js_dropdown_array().'</script>';
// Theme calls.
$show_learn_path = true;
$lp_theme_css = $learnPath->get_theme();

// POST action handling (uploading mp3, deleting mp3)
if (isset($_POST['save_audio'])) {
    // Updating the lp.modified_on
    $learnPath->set_modified_on();

    $lp_items_to_remove_audio = [];
    $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
    // Deleting the audio fragments.
    foreach ($_POST as $key => $value) {
        if (substr($key, 0, 9) === 'removemp3') {
            $lp_items_to_remove_audio[] = str_ireplace('removemp3', '', $key);
            // Removing the audio from the learning path item.
            $in = implode(',', $lp_items_to_remove_audio);
        }
    }
    if (count($lp_items_to_remove_audio) > 0) {
        $sql = "UPDATE $tbl_lp_item SET audio = ''
                WHERE iid IN (".$in.")";
        Database::query($sql);
    }

    // Create the audio folder if it does not exist yet.
    DocumentManager::createDefaultAudioFolder($_course);

    // Uploading the audio files.
    foreach ($_FILES as $key => $value) {
        if (substr($key, 0, 7) === 'mp3file' &&
            !empty($_FILES[$key]['tmp_name'])
        ) {
            // The id of the learning path item.
            $lp_item_id = str_ireplace('mp3file', '', $key);
            // Check if file already exits into document/audio/
            $file_name = $_FILES[$key]['name'];
            $file_name = stripslashes($file_name);
            // Add extension to files without one (if possible).
            $file_name = add_ext_on_mime($file_name, $_FILES[$key]['type']);
            $clean_name = api_replace_dangerous_char($file_name);
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
                $clean_name = implode($filename_components).'.'.$file_extension;
                // Using the new name in the $_FILES superglobal.
                $_FILES[$key]['name'] = $clean_name;
            }

            // Upload the file in the documents tool.
            $filePath = handle_uploaded_document(
                $_course,
                $_FILES[$key],
                api_get_path(SYS_COURSE_PATH).$_course['path'].'/document',
                '/audio',
                api_get_user_id(),
                '',
                '',
                '',
                '',
                false
            );

            // Store the mp3 file in the lp_item table.
            $sql = "UPDATE $tbl_lp_item
                    SET audio = '".Database::escape_string($filePath)."'
                    WHERE iid = ".(int) $lp_item_id;
            Database::query($sql);
        }
    }

    Display::addFlash(Display::return_message(get_lang('ItemUpdated'), 'confirm'));
    $url = api_get_self().'?action=add_item&type=step&lp_id='.$learnPath->get_id().'&'.api_get_cidreq();
    header('Location: '.$url);
    exit;
}

Display::display_header(null, 'Path');
$suredel = trim(get_lang('AreYouSureToDeleteJS'));

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
        placeholder: "ui-state-highlight" //defines the yellow highlight
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
        $.post("<?php echo api_get_path(WEB_AJAX_PATH); ?>lp.ajax.php", order, function(reponse) {
            $("#message").html(reponse);
        });

        setTimeout(function() {
            $("#message").html('');
        }, 3000);

        return false;

    }); //end of lp_item_list event assignment
    <?php
    } ?>

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

echo $learnPath->build_action_menu();

echo '<div class="row">';
echo '<div class="col-md-4">';
echo $learnPath->return_new_tree(null, true);
echo '</div>';

echo '<div class="col-md-8">';
switch ($_GET['action']) {
    case 'edit_item':
        if (isset($is_success) && $is_success === true) {
            echo Display::return_message(
                get_lang('LearnpathItemEdited'),
                'confirm'
            );
        } else {
            echo $learnPath->display_edit_item(
                $_GET['id'],
                $excludeExtraFields
            );
        }
        break;
    case 'delete_item':
        if (isset($is_success) && $is_success === true) {
            echo Display::return_message(
                get_lang('LearnpathItemDeleted'),
                'confirm'
            );
        }
        break;
}
if (!empty($_GET['updateaudio'])) {
    // list of items to add audio files
    echo $learnPath->overview();
}

echo '</div>';
echo '</div>';

Display::display_footer();
