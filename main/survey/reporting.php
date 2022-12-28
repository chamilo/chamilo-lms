<?php

/* For licensing terms, see /license.txt */

/**
 * @author unknown, the initial survey that did not make it in 1.8 because of bad code
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup,
 * refactoring and rewriting large parts of the code
 *
 * @todo The question has to be more clearly indicated (same style as when filling the survey)
 */
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;
$survey_id = isset($_GET['survey_id']) ? (int) $_GET['survey_id'] : 0;
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'overview';
$lpItemId = isset($_GET['lp_item_id']) ? (int) $_GET['lp_item_id'] : 0;
$survey_data = SurveyManager::get_survey($survey_id);

if (empty($survey_data)) {
    api_not_allowed(true);
}

if (0 == $survey_data['anonymous']) {
    $people_filled_full_data = true;
} else {
    $people_filled_full_data = false;
}
$people_filled = SurveyManager::get_people_who_filled_survey($survey_id, $people_filled_full_data);

// Checking the parameters
SurveyUtil::check_parameters($people_filled);

$isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
    api_get_user_id(),
    api_get_course_info()
);

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if ($isDrhOfCourse || !api_is_allowed_to_edit(false, true)) {
    // Show error message if the survey can be seen only by tutors
    if (SURVEY_VISIBLE_TUTOR == $survey_data['visible_results']) {
        api_not_allowed(true);
    }

    Display::display_header(get_lang('ToolSurvey'));
    SurveyUtil::handle_reporting_actions($survey_data, $people_filled);
    Display::display_footer();
    exit;
}

/**
 * @todo use Export::arrayToCsv($data, $filename = 'export')
 */
$exportReport = isset($_REQUEST['export_report']) ? $_REQUEST['export_report'] : '';
$format = isset($_REQUEST['export_format']) ? $_REQUEST['export_format'] : '';

if (!empty($exportReport) && !empty($format)) {
    $compact = false;
    switch ($format) {
        case 'xls':
            $filename = 'survey_results_'.$survey_id.'.xlsx';

            SurveyUtil::export_complete_report_xls($survey_data, $filename, $userId, false, $lpItemId);
            exit;
            break;
        case 'csv-compact':
            $compact = true;
            // no break
        case 'csv':
        default:
            $data = SurveyUtil::export_complete_report($survey_data, $userId, $compact, $lpItemId);
            $filename = 'survey_results_'.$survey_id.($compact ? '_compact' : '').'.csv';
            header('Content-type: application/octet-stream');
            header('Content-Type: application/force-download');

            if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT'])) {
                header('Content-Disposition: filename= '.$filename);
            } else {
                header('Content-Disposition: attachment; filename= '.$filename);
            }
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
                header('Pragma: ');
                header('Cache-Control: ');
                header('Cache-Control: public'); // IE cannot download from sessions without a cache
            }
            header('Content-Description: '.$filename);
            header('Content-transfer-encoding: binary');
            echo $data;
            exit;
            break;
    }
}

$urlname = strip_tags(
    api_substr(api_html_entity_decode($survey_data['title'], ENT_QUOTES), 0, 40)
);
if (api_strlen(strip_tags($survey_data['title'])) > 40) {
    $urlname .= '...';
}

// Breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq(),
    'name' => get_lang('SurveyList'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$survey_id.'&'.api_get_cidreq(),
    'name' => $urlname,
];

$actions = '';
if ($action === 'overview') {
    $tool_name = get_lang('Reporting');
} else {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'survey/reporting.php?survey_id='.$survey_id,
        'name' => get_lang('Reporting'),
    ];
    switch ($action) {
        case 'questionreport':
            $singlePage = isset($_GET['single_page']) ? (int) $_GET['single_page'] : 0;
            $tool_name = $singlePage ? get_lang('QuestionsOverallReport') : get_lang('DetailedReportByQuestion');
            break;
        case 'userreport':
            $tool_name = get_lang('DetailedReportByUser');
            break;
        case 'comparativereport':
            $tool_name = get_lang('ComparativeReport');
            break;
        case 'completereport':
            $tool_name = get_lang('CompleteReport');
            break;
    }
}

$htmlHeadXtra[] = api_get_asset('jspdf/dist/jspdf.umd.min.js');
$htmlHeadXtra[] = api_get_asset('svgtopdf/svg2pdf.js');
$htmlHeadXtra[] = api_get_asset('html2canvas/html2canvas.js');
$htmlHeadXtra[] = api_get_js('d3/d3.v3.5.4.min.js');
$htmlHeadXtra[] = api_get_js('dimple.v2.1.2.min.js');

$htmlHeadXtra[] = '<script>

function formExportSubmit(formId) {
    $("#"+formId).submit();
}
async function exportToPdf() {
    window.scrollTo(0, 0);
    
    $("#dialog-confirm").dialog({
        autoOpen: false,
        show: "blind",
        resizable: false,
        height: 100,
        modal: true
    });
    $("#dialog-confirm").dialog("open");

    window.jsPDF = window.jspdf.jsPDF;
    $(".question-item img, #pdf_table img").hide();
    $(".question-item video, #pdf_table video").hide();
    $(".question-item audio, #pdf_table audio").hide();

    var doc = document.getElementById("question_results");
    var pdf = new jsPDF("p", "pt", "a4");
    //A4 height = 842 pts ; A4 width = 595 pts

    // Adding title
    pdf.setFontSize(16);
    pdf.text(40, 40, "'.get_lang('Reporting').'");

    const table = document.getElementById("pdf_table");
    var headerY = 0;
    var canvas = await html2canvas(table);
    var pageData = canvas.toDataURL("image/jpeg", 1);
    headerY = 515/canvas.width * canvas.height;
    pdf.addImage(pageData, "JPEG", 35, 60, 515, headerY);

    var divs = doc.getElementsByClassName("question-item");
    var pages = [];
    var page = 1;
    for (var i = 0; i < divs.length; i += 1) {
        // Two parameters after addImage control the size of the added image,
        // where the page height is compressed according to the width-height ratio column of a4 paper.
        if (!pages[page]) {
            pages[page] = 0;
        }

        var positionY = 150;
        var diff = 250;
        pages[page] += 1;
        if (page > 1) {
            headerY = 0;
            positionY = 60;
            diff = 220;
        }
        if (pages[page] > 1) {
            positionY = pages[page] * diff + 5;
        }

        const title = $(divs[i]).find(".title-question");
        pdf.setFontSize(10);
        const lines = pdf.splitTextToSize(title.text(), 515);
        pdf.text(40, positionY, lines);

        var svg = divs[i].querySelector("svg");
        if (svg) {
            svg2pdf(svg, pdf, {
                  xOffset: 150,
                  yOffset: positionY + (lines.length * 1),
                  scale: 0.4,
            });
        }
        var tables = divs[i].getElementsByClassName("display-survey");
        var config= {};
        for (var j = 0; j < tables.length; j += 1) {
            const canvas = await html2canvas(tables[j], config);
            var pageData = canvas.toDataURL("image/jpeg", 0.7);
            if (pageData) {
                pdf.addImage(pageData, "JPEG", 40, positionY + 180, 500, 500 / canvas.width * canvas.height);
            }
        }

        var tables = divs[i].getElementsByClassName("open-question");
        for (var j = 0; j < tables.length; j += 1) {
            var canvas = await html2canvas(tables[j], config);
            var pageData = canvas.toDataURL("image/jpeg", 0.7);
            if (pageData) {
                pdf.addImage(pageData, "JPEG", 40, positionY + (10 * lines.length), 500, 500 / canvas.width * canvas.height);
            }
        }

        if (i > 0 && (i -1) % 2 === 0 && (i+1 !== divs.length)) {
             pdf.addPage();
             page++;
        }
    }

    $(".question-item img, #pdf_table img").show();
    $(".question-item video, #pdf_table video").show();
    $(".question-item audio, #pdf_table audio").show();

    pdf.save("reporting.pdf", {returnPromise: true}).then(function (response) {
        $( "#dialog-confirm" ).dialog("close");
    });
}
</script>';

Display::display_header($tool_name, 'Survey');
echo '<div id="dialog-confirm" style="display: none;"> '.
    Display::returnFontAwesomeIcon('spinner', null, true, 'fa-spin').
    get_lang('PleaseWait').
    '</div>';
SurveyUtil::handle_reporting_actions($survey_data, $people_filled);

// Content
if ($action === 'overview') {
    $html = null;
    $url = api_get_path(WEB_CODE_PATH).'survey/reporting.php?'.api_get_cidreq().'&';

    $html .= '<div class="survey-reports">';
    $html .= '<div class="list-group">';
    $html .= Display::url(
        Display::return_icon(
            'survey_reporting_overall.png',
            get_lang('QuestionsOverallReport'),
            null,
            ICON_SIZE_MEDIUM
        ).'<h4>'.get_lang('QuestionsOverallReport').'</h4><p>'.get_lang('QuestionsOverallReportDetail').'</p>',
        $url.'action=questionreport&survey_id='.$survey_id.'&single_page=1',
        ['class' => 'list-group-item']
    );

    $html .= Display::url(
        Display::return_icon(
            'survey_reporting_question.png',
            get_lang('DetailedReportByQuestion'),
            null,
            ICON_SIZE_MEDIUM
        ).'<h4>'.get_lang('DetailedReportByQuestion').'</h4><p>'.get_lang('DetailedReportByQuestionDetail').'</p>',
        $url.'action=questionreport&survey_id='.$survey_id,
        ['class' => 'list-group-item']
    );

    $html .= Display::url(
        Display::return_icon(
            'survey_reporting_user.png',
            get_lang('DetailedReportByUser'),
            null,
            ICON_SIZE_MEDIUM
        ).'<h4>'.get_lang('DetailedReportByUser').'</h4><p>'.get_lang('DetailedReportByUserDetail').'</p>',
        $url.'action=userreport&survey_id='.$survey_id,
        ['class' => 'list-group-item']
    );

    $html .= Display::url(
        Display::return_icon(
            'survey_reporting_comparative.png',
            get_lang('ComparativeReport'),
            null,
            ICON_SIZE_MEDIUM
        ).'<h4>'.get_lang('ComparativeReport').'</h4><p>'.get_lang('ComparativeReportDetail').'</p>',
        $url.'action=comparativereport&survey_id='.$survey_id,
        ['class' => 'list-group-item']
    );

    $html .= Display::url(
        Display::return_icon(
            'survey_reporting_complete.png',
            get_lang('CompleteReport'),
            null,
            ICON_SIZE_MEDIUM
        ).'<h4>'.get_lang('CompleteReport').'</h4><p>'.get_lang('CompleteReportDetail').'</p>',
        $url.'action=completereport&survey_id='.$survey_id,
        ['class' => 'list-group-item']
    );

    $html .= '</div>';
    $html .= '</div>';

    echo $html;
}

Display::display_footer();
