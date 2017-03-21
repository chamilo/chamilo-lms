<?php
/* For licensing terms, see /license.txt */

/**
*	This is the tracking library for Chamilo.
*
*	@package chamilo.reporting
*
* Calculates the time spent on the course
* @param integer $user_id the user id
* @param string $course_code the course code
* @author Julio Montoya <gugli100@gmail.com>
* @author Jorge Frisancho Jibaja - select between dates
*
*/

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

// the section (for the tabs)
$this_section = SECTION_TRACKING;

/* MAIN */
$user_id = intval($_REQUEST['student']);
$session_id = intval($_GET['id_session']);
$type = isset($_REQUEST['type']) ? Security::remove_XSS($_REQUEST['type']) : '';
$course_code = isset($_REQUEST['course']) ? Security::remove_XSS($_REQUEST['course']) : '';
$courseInfo = api_get_course_info($course_code);
$courseId = (!empty($courseInfo['real_id'])?$courseInfo['real_id']:null);
$connections = MySpace::get_connections_to_course($user_id, $courseId, $session_id);
$quote_simple = "'";

$form = new FormValidator(
    'myform',
    'get',
    api_get_self(),
    null,
    array('id' => 'myform')
);
$form->addElement('text', 'from', get_lang('From'), array('id' => 'date_from'));
$form->addElement('text', 'to', get_lang('Until'), array('id' => 'date_to'));
$form->addElement(
    'select',
    'type',
    get_lang('Type'),
    array('day' => get_lang('Day'), 'month' => get_lang('Month')),
    array('id' => 'type')
);
$form->addElement('hidden', 'student', $user_id);
$form->addElement('hidden', 'course', $course_code);
$form->addRule('from', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('to', get_lang('ThisFieldIsRequired'), 'required');
$group = array(
    $form->createElement(
        'label',
        null,
        Display::url(get_lang('Search'), 'javascript://', array('onclick'=> 'loadGraph();', 'class' => 'btn btn-default'))
    )
);
$form->addGroup($group);
$from = null;
$to = null;
$course = $course_code;
if ($form->validate()) {
    $values = $form->getSubmitValues();
    $from = $values['from'];
    $to = $values['to'];
    $type = $values['type'];
    $course = $values['course'];
}

$url = api_get_path(WEB_AJAX_PATH).'myspace.ajax.php?a=access_detail_by_date&course='.$course.'&student='.$user_id;

$htmlHeadXtra[] = '<script src="slider.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="slider.css" />';
$htmlHeadXtra[] = "<script>
function loadGraph() {
    var startDate = $('#date_from').val();
    var endDate = $('#date_to').val();
    var type = $('#type option:selected').val();
    $.ajax({
        url: '".$url."&startDate='+startDate+'&endDate='+endDate+'&type='+type,
        dataType: 'json',
        success: function(db) {
            if (!db.is_empty) {
                // Display confirmation message to the user
                $('#messages').html(db.result).stop().css('opacity', 1).fadeIn(30);
                $('#cev_cont_stats').html(db.stats);
                $('#graph' ).html(db.graph_result);
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
});

</script>";

$htmlHeadXtra[] = '<script>
$(function() {
    $("#cev_button").hide();
    $("#container-9").tabs({remote: true});
});
</script>';

//Changes END
$interbreadcrumb[] = array('url' => '#', 'name' => get_lang('AccessDetails'));

Display :: display_header('');
$userInfo = api_get_user_info($user_id);
$result_to_print = '';
$sql_result = MySpace::get_connections_to_course($user_id, $courseId);
$result_to_print = convert_to_string($sql_result);

echo Display::page_header(get_lang('DetailsStudentInCourse'));
echo Display::page_subheader(
    get_lang('User').': '.$userInfo['complete_name'].' - '.get_lang('Course').': '.$courseInfo['title'] . ' (' . $course_code . ')'
);

$form->setDefaults(array('from' => $from, 'to' => $to));
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
            if ($result_to_print != "") {
                $rst = get_stats($user_id, $courseId);
                $foo_stats = '<strong>'.get_lang('Total').': </strong>'.$rst['total'].'<br />';
                $foo_stats .= '<strong>'.get_lang('Average').': </strong>'.$rst['avg'].'<br />';
                $foo_stats .= '<strong>'.get_lang('Quantity').' : </strong>'.$rst['times'].'<br />';
                echo $foo_stats;
            } else {
                echo Display::display_warning_message(get_lang('NoDataAvailable'));
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
Display:: display_footer();
