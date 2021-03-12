<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
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
        'name' => get_lang('Assessments'),
    ];
}
$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('Learning paths'),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => $learnPath->getNameNoTags(),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&type=step&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => get_lang('Add learning object or activity'),
];

// Theme calls.
$show_learn_path = true;
$lp_theme_css = $learnPath->get_theme();

Display::display_header(get_lang('Edit'), 'Path');
$suredel = trim(get_lang('Are you sure to delete'));
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
    $('.scrollbar-inner').scrollbar();
    expandColumnToggle('#hide_bar_template', {
        selector: '#lp_sidebar'
    }, {
        selector: '#doc_form'
    });

    $('.lp-btn-associate-forum').on('click', function (e) {
        var associate = confirm('<?php echo get_lang('This action will associate a forum thread to this learning path item. Do you want to proceed?'); ?>');

        if (!associate) {
            e.preventDefault();
        }
    });

    $('.lp-btn-dissociate-forum').on('click', function (e) {
        var dissociate = confirm('<?php echo get_lang('This action will dissociate the forum thread of this learning path item. Do you want to proceed?'); ?>');
        if (!dissociate) {
            e.preventDefault();
        }
    });
});
</script>
<?php

echo $learnPath->build_action_menu();

echo '<div class="row">';
echo '<div id="lp_sidebar" class="col-md-4">';
$documentId = isset($_GET['path_item']) ? (int) $_GET['path_item'] : 0;
$repo = Container::getDocumentRepository();
$document = $repo->find($documentId);

if ($document) {
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
if (isset($is_success) && true === $is_success) {
    $msg = '<div class="lp_message" style="margin-bottom:10px;">';
    $msg .= 'The item has been edited.';
    $msg .= '</div>';
    echo $learnPath->display_item($lpItem, $msg);
} else {
    echo $learnPath->display_edit_item($lpItem, $excludeExtraFields);
    $finalItem = Session::read('finalItem');
    if ($finalItem) {
        echo '<script>$("#frmModel").remove()</script>';
    }
    Session::erase('finalItem');
}

echo '</div>';
echo '</div>';

Display::display_footer();
