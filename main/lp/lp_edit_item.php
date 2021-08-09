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
 * @author Julio Montoya  - Improving the list of templates
 */
$this_section = SECTION_COURSES;
api_protect_course_script();

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');

/* Header and action code */
$htmlHeadXtra[] = '<script>'.$learnPath->get_js_dropdown_array().'
$(function() {
    CKEDITOR.on("instanceReady", function (e) {
        showTemplates("content_lp");
    });
});
</script>';

/* Constants and variables */
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);

$isStudentView = isset($_REQUEST['isStudentView']) ? intval($_REQUEST['isStudentView']) : null;
$learnpath_id = (int) $_REQUEST['lp_id'];
$submit = isset($_POST['submit_button']) ? $_POST['submit_button'] : null;

if (!$is_allowed_to_edit || $isStudentView) {
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id.'&'.api_get_cidreq());
    exit;
}
// From here on, we are admin because of the previous condition, so don't check anymore.
/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');
$course_id = api_get_course_int_id();

/*
    Course admin section
    - all the functions not available for students - always available in this case (page only shown to admin)
*/
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
    'name' => $learnPath->getNameNoTags(),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&type=step&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => get_lang('NewStep'),
];

// Theme calls.
$show_learn_path = true;
$lp_theme_css = $learnPath->get_theme();

Display::display_header(get_lang('Edit'), 'Path');
$suredel = trim(get_lang('AreYouSureToDeleteJS'));
?>
<script>
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

$(function() {
    jQuery('.scrollbar-inner').scrollbar();
    expandColumnToogle('#hide_bar_template', {
        selector: '#lp_sidebar'
    }, {
        selector: '#doc_form'
    });

    $('.lp-btn-associate-forum').on('click', function (e) {
        var associate = confirm('<?php echo get_lang('ConfirmAssociateForumToLPItem'); ?>');

        if (!associate) {
            e.preventDefault();
        }
    });

    $('.lp-btn-dissociate-forum').on('click', function (e) {
        var dissociate = confirm('<?php echo get_lang('ConfirmDissociateForumToLPItem'); ?>');

        if (!dissociate) {
            e.preventDefault();
        }
    });
});
</script>
<?php

$extraField = [];
$field = new ExtraField('lp_item');
$authorLpField = $field->get_handler_field_info_by_field_variable('authorlpitem');
if ($authorLpField != null) {
    $extraField['authorlp'] = $authorLpField;
}
echo $learnPath->build_action_menu(
    false,
    true,
    false,
    true,
    '',
    $extraField
);

echo '<div class="row">';
echo '<div id="lp_sidebar" class="col-md-4">';
$documentId = isset($_GET['path_item']) ? (int) $_GET['path_item'] : 0;
$documentInfo = DocumentManager::get_document_data_by_id($documentId, api_get_course_id(), false, null, true);
if (empty($documentInfo)) {
    // Try with iid
    $table = Database::get_course_table(TABLE_DOCUMENT);
    $sql = "SELECT path FROM $table
            WHERE c_id = $course_id AND iid = $documentId AND path NOT LIKE '%_DELETED_%'";
    $res_doc = Database::query($sql);
    $path_file = Database::result($res_doc, 0, 0);
} else {
    $path_file = $documentInfo['path'];
}

$path_parts = pathinfo($path_file);

if (!empty($path_file) && isset($path_parts['extension']) && $path_parts['extension'] === 'html') {
    echo $learnPath->return_new_tree();
    // Show the template list
    echo '<div id="frmModel" class="scrollbar-inner lp-add-item"></div>';
} else {
    echo $learnPath->return_new_tree();
}
echo '</div>';
echo '<div id="doc_form" class="col-md-8">';

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
if (isset($is_success) && $is_success === true) {
    $msg = '<div class="lp_message" style="margin-bottom:10px;">';
    $msg .= 'The item has been edited.';
    $msg .= '</div>';
    echo $learnPath->display_item($_GET['id'], $msg);
} else {
    $item = $learnPath->getItem($_GET['id']);
    if ('document' !== $item->get_type()) {
        $excludeExtraFields[] = 'no_automatic_validation';
    }
    echo $learnPath->display_edit_item($item->getIid(), $excludeExtraFields);
    $finalItem = Session::read('finalItem');
    if ($finalItem) {
        echo '<script>$("#frmModel").remove()</script>';
    }
    Session::erase('finalItem');
}

echo '</div>';
echo '</div>';

Display::display_footer();
