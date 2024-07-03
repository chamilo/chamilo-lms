<?php

/* For licensing terms, see /license.txt */

/**
 * This is the tracking library for Chamilo.
 *
 * @param int    $user_id     the user id
 * @param string $course_code the course code
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @author Jorge Frisancho Jibaja - select between dates
 */
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$allowToTrack = api_is_platform_admin(true, true) ||
    api_is_teacher() || api_is_course_tutor();

if (!$allowToTrack) {
    api_not_allowed(true);
    exit;
}

// the section (for the tabs)
$this_section = SECTION_TRACKING;

$user_id = isset($_REQUEST['student']) ? (int) $_REQUEST['student'] : 0;
$session_id = isset($_REQUEST['id_session']) ? (int) $_REQUEST['id_session'] : 0;
$type = isset($_REQUEST['type']) ? Security::remove_XSS($_REQUEST['type']) : '';
$course_code = isset($_REQUEST['course']) ? Security::remove_XSS($_REQUEST['course']) : '';
$courseInfo = api_get_course_info($course_code);
if (empty($courseInfo)) {
    api_not_allowed(true);
}
$courseId = (!empty($courseInfo['real_id']) ? $courseInfo['real_id'] : null);
$quote_simple = "'";

$form = new FormValidator(
    'myform',
    'get',
    api_get_self(),
    null,
    ['id' => 'myform']
);
$form->addElement('text', 'from', get_lang('From'), ['id' => 'date_from']);
$form->addElement('text', 'to', get_lang('Until'), ['id' => 'date_to']);
$form->addElement(
    'select',
    'type',
    get_lang('Type'),
    ['day' => get_lang('Day'), 'month' => get_lang('Month')],
    ['id' => 'type']
);
$form->addElement('hidden', 'student', $user_id);
$form->applyFilter('student', 'html_filter');
$form->addElement('hidden', 'course', $course_code);
$form->applyFilter('course', 'html_filter');
$form->addRule('from', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('to', get_lang('ThisFieldIsRequired'), 'required');
$group = [
    $form->createElement(
        'label',
        null,
        Display::url(
            get_lang('Search'),
            'javascript://',
            ['onclick' => 'loadGraph();', 'class' => 'btn btn-default']
        )
    ),
];
$form->addGroup($group);
$from = null;
$to = null;
$course = $course_code;
if ($form->validate()) {
    $values = $form->exportValues();
    $from = $values['from'];
    $to = $values['to'];
    $type = $values['type'];
    $course = $values['course'];
}

$url = api_get_path(WEB_AJAX_PATH).'myspace.ajax.php?a=access_detail_by_date&course='.$course.'&student='.$user_id.'&session_id='.$session_id;

$htmlHeadXtra[] = '<script src="slider.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="slider.css" />';
$htmlHeadXtra[] = "<script>
function loadGraph() {
    var startDate = $('#date_from').val();
    var endDate = $('#date_to').val();
    var type = $('#type option:selected').val();
    var url = '".$url."&startDate='+startDate+'&endDate='+endDate+'&type='+type;
    $.ajax({
        url: url,
        dataType: 'json',
        success: function(db) {
            if (!db.is_empty) {
                // Display confirmation message to the user
                $('#messages').html(db.result).stop().css('opacity', 1).fadeIn(30);

                var exportLink = $('<a></a>').
                    attr(\"href\", url+'&export=excel')
                    .attr('class', 'btn btn-default')
                    .attr('target', '_blank')
                    .html('".addslashes(get_lang('ExportAsXLS'))."');

                $('#messages').append(exportLink);

                $('#cev_cont_stats').html(db.stats);
                $('#graph').html(db.graph_result);
            } else {
                $('#messages').text('".get_lang('NoDataAvailable')."');
                $('#messages').addClass('warning-message');
                $('#cev_cont_stats').html('');
                $('#graph').empty();
            }
        }
    });
}

$(function() {
    var dates = $('#date_from, #date_to').datepicker({
        dateFormat: ".$quote_simple."yy-mm-dd".$quote_simple.",
        changeMonth: true,
        changeYear: true
    });

    $(\"#cev_button\").hide();
});

</script>";

$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('AccessDetails')];

Display::display_header('');
$userInfo = api_get_user_info($user_id);

echo Display::page_header(get_lang('DetailsStudentInCourse'));
echo Display::page_subheader(
    get_lang('User').': '.$userInfo['complete_name'].' - '.
    get_lang('Course').': '.$courseInfo['title'].' ('.$course_code.')'
);

$form->setDefaults(['from' => $from, 'to' => $to]);
$form->display();
?>
<br />
<br />
<div class="text-center" id="graph"></div>
<br />
<br />
<div class="row">
    <div id="cev_results" class="ui-tabs ui-widget ui-widget-content ui-corner-all col-md-6">
        <div class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
            <?php echo get_lang('Statistics'); ?>
        </div><br />
        <div id="cev_cont_stats">
        <?php
            $data = MySpace::getStats($user_id, $courseInfo, $session_id);
            if (!empty($data)) {
                $stats = '<strong>'.get_lang('Total').': </strong>'.$data['total'].'<br />';
                $stats .= '<strong>'.get_lang('Average').': </strong>'.$data['avg'].'<br />';
                $stats .= '<strong>'.get_lang('Quantity').' : </strong>'.$data['times'].'<br />';
                echo $stats;
            } else {
                echo Display::return_message(get_lang('NoDataAvailable'), 'warning');
            }
        ?>
        </div>
        <br />
    </div>
    <div class="ui-tabs ui-widget ui-widget-content ui-corner-all col-md-6 col-md-6">
        <div class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
            <?php echo get_lang('Details'); ?>
        </div><br />
        <div id="messages"></div>
    </div>
</div>

<?php
Display::display_footer();
