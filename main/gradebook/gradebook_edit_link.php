<?php
/* For licensing terms, see /license.txt */

/**
 * Script.
 *
 * @package chamilo.gradebook
 */
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
GradebookUtils::block_students();
$tbl_grade_links = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
//selected name of database
$course_id = GradebookUtils::get_course_id_by_link_id($_GET['editlink']);
$tbl_forum_thread = Database::get_course_table(TABLE_FORUM_THREAD);
$tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
$em = Database::getManager();

$linkarray = LinkFactory::load($_GET['editlink']);
/** @var AbstractLink $link */
$link = $linkarray[0];
if ($link->is_locked() && !api_is_platform_admin()) {
    api_not_allowed();
}

$linkcat = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;
$linkedit = isset($_GET['editlink']) ? Security::remove_XSS($_GET['editlink']) : '';
$course_code = api_get_course_id();
$session_id = api_get_session_id();

if ($session_id == 0) {
    $cats = Category::load(
        null,
        null,
        $course_code,
        null,
        null,
        $session_id,
        false
    ); //already init
} else {
    $cats = Category::loadSessionCategories(null, $session_id);
}

$form = new LinkAddEditForm(
    LinkAddEditForm::TYPE_EDIT,
    $cats,
    null,
    $link,
    'edit_link_form',
    api_get_self().'?selectcat='.$linkcat.'&editlink='.$linkedit.'&'.api_get_cidreq()
);
if ($form->validate()) {
    $values = $form->getSubmitValues();
    $parent_cat = Category::load($values['select_gradebook']);
    $final_weight = $values['weight_mask'];
    $link->set_weight($final_weight);

    if (!empty($values['select_gradebook'])) {
        $link->set_category_id($values['select_gradebook']);
    }
    $link->set_visible(empty($values['visible']) ? 0 : 1);
    $link->save();

    //Update weight for attendance
    $sql = 'SELECT ref_id FROM '.$tbl_grade_links.'
            WHERE id = '.intval($_GET['editlink']).' AND type='.LINK_ATTENDANCE;
    $rs_attendance = Database::query($sql);
    if (Database::num_rows($rs_attendance) > 0) {
        $row_attendance = Database::fetch_array($rs_attendance);
        $attendance_id = $row_attendance['ref_id'];
        $sql = 'UPDATE '.$tbl_attendance.' SET
                    attendance_weight ='.api_float_val($final_weight).'
                WHERE c_id = '.$course_id.' AND id = '.intval($attendance_id);
        Database::query($sql);
    }

    //Update weight into forum thread
    $sql = 'UPDATE '.$tbl_forum_thread.' SET
                thread_weight = '.api_float_val($final_weight).'
            WHERE
			    c_id = '.$course_id.' AND
			    thread_id = (
                    SELECT ref_id FROM '.$tbl_grade_links.'
			        WHERE id='.intval($_GET['editlink']).' AND type = 5
            )';
    Database::query($sql);

    //Update weight into student publication(work)
    $em
        ->createQuery('
            UPDATE ChamiloCourseBundle:CStudentPublication w
            SET w.weight = :final_weight
            WHERE w.cId = :course
                AND w.id = (
                    SELECT l.refId FROM ChamiloCoreBundle:GradebookLink l
                    WHERE l.id = :link AND l.type = :type
                )
        ')
        ->execute([
            'final_weight' => $final_weight,
            'course' => $course_id,
            'link' => intval($_GET['editlink']),
            'type' => LINK_STUDENTPUBLICATION,
        ]);

    $logInfo = [
        'tool' => TOOL_GRADEBOOK,
        'tool_id' => 0,
        'tool_id_detail' => 0,
        'action' => 'edit-link',
        'action_details' => '',
    ];
    Event::registerLog($logInfo);

    header('Location: '.Category::getUrl().'linkedited=&selectcat='.$link->get_category_id());
    exit;
}

$interbreadcrumb[] = [
    'url' => Category::getUrl().'selectcat='.$linkcat,
    'name' => get_lang('Gradebook'),
];

$htmlHeadXtra[] = '<script>
$(function() {
    $("#hide_category_id").change(function() {
       $("#hide_category_id option:selected").each(function () {
           var cat_id = $(this).val();
            $.ajax({
                url: "'.api_get_path(WEB_AJAX_PATH).'gradebook.ajax.php?a=get_gradebook_weight",
                data: "cat_id="+cat_id,
                success: function(return_value) {
                    if (return_value != 0 ) {
                        $("#max_weight").html(return_value);
                    }
                }
            });
       });
    });
});
</script>';

Display::display_header(get_lang('EditLink'));
$form->display();
Display::display_footer();
