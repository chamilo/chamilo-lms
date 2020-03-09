<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicPlan;
use ChamiloSession as Session;

/**
 * Template (front controller in MVC pattern) used for distpaching to the controllers depend on the current action.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 * @author Julio Montoya <gugli100@gmail.com> Bugfixes session support
 */

// including files
require_once __DIR__.'/../inc/global.inc.php';
require_once 'thematic_controller.php';

// current section
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_COURSE_PROGRESS;

// protect a course script
api_protect_course_script(true);

// get actions
$actions = [
    'thematic_details',
    'thematic_list',
    'thematic_add',
    'thematic_edit',
    'thematic_copy',
    'thematic_delete',
    'moveup',
    'movedown',
    'thematic_import_select',
    'thematic_import',
    'thematic_export',
    'thematic_export_pdf',
    'export_documents',
    'thematic_plan_list',
    'thematic_plan_add',
    'thematic_plan_edit',
    'thematic_plan_delete',
    'thematic_advance_list',
    'thematic_advance_add',
    'thematic_advance_edit',
    'thematic_advance_delete',
    'export_single_thematic',
    'export_single_documents',
];

$action = 'thematic_details';
if (isset($_REQUEST['action']) && in_array($_REQUEST['action'], $actions)) {
    $action = $_REQUEST['action'];
}

$isTeacher = api_is_allowed_to_edit(null, true);
$currentUrl = api_get_path(WEB_CODE_PATH).'course_progress/index.php?'.api_get_cidreq();
$requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);
$displayHeader = !empty($_REQUEST['display']) && 'no_header' === $_REQUEST['display'] ? false : true;
$thematic_id = isset($_REQUEST['thematic_id']) ? (int) $_REQUEST['thematic_id'] : null;
$thematic_advance_id = isset($_REQUEST['thematic_advance_id']) ? (int) $_REQUEST['thematic_advance_id'] : null;

$attendance = new Attendance();
// get data for attendance input select
$attendance_list = $attendance->get_attendances_list();
$attendance_select = [];
$attendance_select[0] = get_lang('Select an attendance');
foreach ($attendance_list as $attendance_id => $attendance_data) {
    $attendance_select[$attendance_id] = $attendance_data['name'];
}

$token = Security::get_token();
$url_token = '&sec_token='.$token;
$user_info = api_get_user_info();
$params = '&'.api_get_cidreq();

if (isset($_POST['action']) && 'thematic_delete_select' == $_POST['action']) {
    $action = 'thematic_delete_select';
}

if (isset($_GET['isStudentView']) && 'true' == $_GET['isStudentView']) {
    $action = 'thematic_details';
}

$actionLeft = '';
if ($isTeacher) {
    switch ($action) {
        case 'thematic_add':
        case 'thematic_import_select':
            $actionLeft = '<a href="index.php?'.api_get_cidreq().'">';
            $actionLeft .= Display::return_icon(
                'back.png',
                get_lang('Back to').' '.get_lang('Thematic view with details'),
                '',
                ICON_SIZE_MEDIUM
            );
            $actionLeft .= '</a>';
            break;
        case 'thematic_list':
            $actionLeft = '<a href="index.php?'.api_get_cidreq().'&action=thematic_add'.$url_token.'">'.
                Display::return_icon('new_course_progress.png', get_lang('New thematic section'), '', ICON_SIZE_MEDIUM).'</a>';
            break;
        case 'thematic_details':
            $actionLeft = '<a href="index.php?'.api_get_cidreq().'&action=thematic_add'.$url_token.'">'.
                Display::return_icon('new_course_progress.png', get_lang('New thematic section'), '', ICON_SIZE_MEDIUM).'</a>';
            $actionLeft .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_import_select'.$url_token.'">'.
                Display::return_icon('import_csv.png', get_lang('Import course progress'), '', ICON_SIZE_MEDIUM).'</a>';
            $actionLeft .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_export'.$url_token.'">'.
                Display::return_icon('export_csv.png', get_lang('Export course progress'), '', ICON_SIZE_MEDIUM).'</a>';
            $actionLeft .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_export_pdf'.$url_token.'">'.
                Display::return_icon('pdf.png', get_lang('Export to PDF'), '', ICON_SIZE_MEDIUM).'</a>';
            $actionLeft .= Display::url(
                Display::return_icon('export_to_documents.png', get_lang('Export latest version of this page to Documents'), [], ICON_SIZE_MEDIUM),
                api_get_self().'?'.api_get_cidreq().'&'.http_build_query(['action' => 'export_documents']).$url_token
            );
            break;
        default:
            $actionLeft = '<a href="index.php?'.api_get_cidreq().'&action=thematic_add'.$url_token.'">'.
                Display::return_icon(
                    'new_course_progress.png',
                    get_lang('New thematic section'),
                    '',
                    ICON_SIZE_MEDIUM
                ).'</a>';
    }
}

// instance thematic object for using like library here
$thematic = new Thematic();

// thematic controller object
$controller = new ThematicController();

$thematic_data = null;
if (!empty($thematic_id)) {
    $repo = Container::getThematicRepository();
    // thematic data by id
    /** @var CThematic $thematic_data */
    $thematic_data = $repo->find($thematic_id);
}
$cleanThematicTitle = null !== $thematic_data ? strip_tags($thematic_data->getTitle()) : null;

// get default thematic plan title
$default_thematic_plan_title = $thematic->get_default_thematic_plan_title();

// Only when I see the 3 columns. Avoids double or triple click binding for onclick event

$htmlHeadXtra[] = '<script>
$(function() {
    $(".thematic_advance_actions, .thematic_tools ").hide();
	$(".thematic_content").mouseover(function() {
		var id = parseInt(this.id.split("_")[3]);
		$("#thematic_id_content_"+id ).show();
	});

	$(".thematic_content").mouseleave(function() {
		var id = parseInt(this.id.split("_")[3]);
		$("#thematic_id_content_"+id ).hide();
	});

	$(".thematic_advance_content").mouseover(function() {
		var id = parseInt(this.id.split("_")[4]);
		$("#thematic_advance_tools_"+id ).show();
	});

	$(".thematic_advance_content").mouseleave(function() {
		var id = parseInt(this.id.split("_")[4]);
		$("#thematic_advance_tools_"+id ).hide();
	});
});
</script>';

$htmlHeadXtra[] = '<script>
function datetime_by_attendance(attendance_id, thematic_advance_id) {
	$.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(myObject) {},
		type: "GET",
		url: "'.api_get_path(WEB_AJAX_PATH).'thematic.ajax.php?a=get_datetime_by_attendance",
		data: "attendance_id="+attendance_id+"&thematic_advance_id="+thematic_advance_id,
		success: function(data) {
			$("#div_datetime_attendance").html(data);
            if (thematic_advance_id == 0) {
                $("#start_date_select_calendar").val($("#start_date_select_calendar option:first").val());
            }
		}
	});
}

function update_done_thematic_advance(selected_value) {
	$.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(myObject) {},
		type: "GET",
		url: "'.api_get_path(WEB_AJAX_PATH).'thematic.ajax.php?a=update_done_thematic_advance",
		data: "thematic_advance_id="+selected_value,
		success: function(data) {
			$("#div_result").html(data);
		}
	});

	// clean all radios
	for (var i=0; i< $(".done_thematic").length;i++) {
		var id_radio_thematic = $(".done_thematic").get(i).id;
		$("#td_"+id_radio_thematic).css({"background-color":"#FFF"});
	}

	// set background to previous radios
	for (var i=0; i < $(".done_thematic").length;i++) {
		var id_radio_thematic = $(".done_thematic").get(i).id;
		$("#td_"+id_radio_thematic).css({"background-color":"#E5EDF9"});
		if ($(".done_thematic").get(i).value == selected_value) {
			break;
		}
	}
}

function check_per_attendance(obj) {
	if (obj.checked) {
        $("#div_datetime_by_attendance").show();
        $("#div_custom_datetime").hide();
	} else {
        $("#div_datetime_by_attendance").hide();
        $("#div_custom_datetime").show();
	}
}

function check_per_custom_date(obj) {
	if (obj.checked) {
        $("#div_custom_datetime").show();
        $("#div_datetime_by_attendance").hide();
	} else {
        $("#div_custom_datetime").hide();
        $("#div_datetime_by_attendance").show();
	}
}
</script>';

$thematicControl = Session::read('thematic_control');

if ('thematic_list' === $action) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Thematic control')];
}
if ('thematic_add' === $action) {
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq().'&action='.$thematicControl,
        'name' => get_lang('Thematic control'),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('New thematic section')];
}
if ('thematic_edit' === $action) {
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq().'&action='.$thematicControl,
        'name' => get_lang('Thematic control'),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit thematic section')];
}
if ('thematic_details' === $action) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Thematic control')];
}
if ('thematic_plan_list' === $action || 'thematic_plan_delete' === $action) {
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq().'&action='.$thematicControl,
        'name' => get_lang('Thematic control'),
    ];
    if (!empty($thematic_data)) {
        $interbreadcrumb[] = [
            'url' => '#',
            'name' => get_lang('Thematic plan').' ('.$cleanThematicTitle.') ',
        ];
    }
}
if ('thematic_plan_add' === $action || 'thematic_plan_edit' === $action) {
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq().'&action='.$thematicControl,
        'name' => get_lang('Thematic control'),
    ];
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq().'&action=thematic_plan_list&thematic_id='.$thematic_id,
        'name' => get_lang('Thematic plan').' ('.$cleanThematicTitle.')',
    ];
}
if ('thematic_advance_list' === $action || 'thematic_advance_delete' === $action) {
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq().'&action='.$thematicControl,
        'name' => get_lang('Thematic control'),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Thematic advance').' ('.$cleanThematicTitle.')'];
}
if ('thematic_advance_add' === $action || 'thematic_advance_edit' === $action) {
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq().'&action='.$thematicControl,
        'name' => get_lang('Thematic control'),
    ];
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq().'&action=thematic_advance_list&thematic_id='.$thematic_id,
        'name' => get_lang('Thematic advance').' ('.$cleanThematicTitle.')',
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('NewThematic advance')];
}

if ('thematic_plan_list' === $action) {
    $actionLeft = '<a href="index.php?'.api_get_cidreq().'&action=thematic_add'.$url_token.'">'.
        Display::return_icon('new_course_progress.png', get_lang('New thematic section'), '', ICON_SIZE_MEDIUM).'</a>';
    $htmlHeadXtra[] = "
        <script>
            $(function () {
                $('.btn-delete').on('click', function (e) {
                    e.preventDefault();
                    var id = $(this).data('id') || 0;
                    if (!id) {
                        return;
                    }
                    //$('[name=\"title[' + id + ']\"]').val('');
                    CKEDITOR.instances['description[' + id + ']'].setData('');
                });
            });
        </script>
    ";
}

$tpl = new Template(get_lang('Thematic control'));

// Dispatch actions to controller
switch ($action) {
    case 'thematic_add':
    case 'thematic_edit':
        if ('POST' === $requestMethod && '' !== trim($_POST['title']) &&
            api_is_allowed_to_edit(null, true)
        ) {
            $title = trim($_POST['title']);
            $content = trim($_POST['content']);
            $session_id = api_get_session_id();
            $thematic->set_thematic_attributes($thematic_id, $title, $content, $session_id);
            $thematic->thematic_save();
            Display::addFlash(Display::return_message(get_lang('Update successful')));

            header('Location: '.$currentUrl);
            break;
        } else {
            // Display form
            $form = new FormValidator('thematic_add', 'POST', 'index.php?action=thematic_add&'.api_get_cidreq());
            if ('thematic_edit' === $action) {
                $form->addElement('header', '', get_lang('Edit thematic section'));
            }

            $form->addElement('hidden', 'sec_token', $token);
            $form->addElement('hidden', 'action', $action);

            if (!empty($thematic_id)) {
                $form->addElement('hidden', 'thematic_id', $thematic_id);
            }

            if (api_get_configuration_value('save_titles_as_html')) {
                $form->addHtmlEditor(
                    'title',
                    get_lang('Title'),
                    true,
                    false,
                    ['ToolbarSet' => 'TitleAsHtml']
                );
            } else {
                $form->addText('title', get_lang('Title'), true, ['size' => '50']);
            }
            $form->addHtmlEditor(
                'content',
                get_lang('Content'),
                false,
                false,
                ['ToolbarSet' => 'Basic', 'Height' => '150']
            );
            $form->addButtonSave(get_lang('Save'));

            if (!empty($thematic_data)) {
                if (api_get_session_id()) {
                    /*if ($thematic_data['session_id'] != api_get_session_id()) {
                        $show_form = false;
                        echo Display::return_message(get_lang('NotAllowedClickBack'), 'error', false);
                    }*/
                }
                // set default values
                $default['title'] = $thematic_data->getTitle();
                $default['content'] = $thematic_data->getContent();
                $form->setDefaults($default);
            }
            $content = $form->returnForm();
        }
        break;
    case 'thematic_copy':
        // Copy a thematic to a session
        $thematic->copy($thematic_id);

        header('Location: '.$currentUrl);
        exit;
    case 'thematic_delete_select':
        if ('POST' === $requestMethod && api_is_allowed_to_edit(null, true)) {
            $thematic_ids = $_POST['id'];
            $thematic->delete($thematic_ids);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }

        header('Location: '.$currentUrl);
        exit;

    case 'thematic_delete':
        // Delete a thematic
        if (isset($thematic_id) && api_is_allowed_to_edit(null, true)) {
            $thematic->delete($thematic_id);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }
        header('Location: '.$currentUrl);
        exit;
    case 'thematic_import':
        $csv_import_array = Import::csv_reader($_FILES['file']['tmp_name'], false);

        if (isset($_POST['replace']) && $_POST['replace']) {
            // Remove current thematic.
            $list = $thematic->get_thematic_list();
            foreach ($list as $i) {
                $thematic->delete($i);
            }
        }

        // Import the progress.
        $current_thematic = null;
        foreach ($csv_import_array as $key => $item) {
            if (!$key) {
                continue;
            }

            switch ($item[0]) {
                case 'title':
                    $thematic->set_thematic_attributes(
                        null,
                        $item[1],
                        $item[2],
                        api_get_session_id()
                    );
                    $current_thematic = $thematic->thematic_save();
                    $description_type = 1;
                    break;
                case 'plan':
                    $thematic->set_thematic_plan_attributes(
                        $current_thematic,
                        $item[1],
                        $item[2],
                        $description_type
                    );
                    $thematic->thematic_plan_save();
                    ++$description_type;
                    break;
                case 'progress':
                    $thematic->set_thematic_advance_attributes(
                        null,
                        $current_thematic,
                        0,
                        $item[3],
                        $item[1],
                        $item[2]
                    );
                    $thematic->thematic_advance_save();
                    break;
            }
        }

        Display::addFlash(Display::return_message(get_lang('Import')));

        header('Location: '.$currentUrl);
        exit;

        break;
    case 'thematic_import_select':
        // Create form to upload csv file.
        $form = new FormValidator(
            'thematic_import',
            'POST',
            'index.php?action=thematic_import&'.api_get_cidreq().$url_token
        );
        $form->addElement('header', get_lang('Import course progress'));
        $form->addElement('file', 'file');
        $form->addElement('checkbox', 'replace', null, get_lang('Delete all course progress'));
        $form->addButtonImport(get_lang('Import'), 'SubmitImport');
        $content = $form->returnForm();
        break;
    case 'moveup':
        if (!api_is_allowed_to_edit(null, true)) {
            api_not_allowed();
        }
        $thematic->move_thematic('up', $thematic_id);

        header('Location: '.$currentUrl);
        exit;
        break;
    case 'movedown':
        if (!api_is_allowed_to_edit(null, true)) {
            api_not_allowed();
        }
        $thematic->move_thematic('down', $thematic_id);

        header('Location: '.$currentUrl);
        exit;
        break;
    case 'thematic_export':
        $list = $thematic->get_thematic_list();
        $csv = [];
        $csv[] = ['type', 'data1', 'data2', 'data3'];
        foreach ($list as $theme) {
            $csv[] = ['title', strip_tags($theme['title']), strip_tags($theme['content'])];
            $data = $thematic->get_thematic_plan_data($theme['id']);
            if (!empty($data)) {
                foreach ($data as $plan) {
                    if (empty($plan['description'])) {
                        continue;
                    }

                    $csv[] = [
                        'plan',
                        strip_tags($plan['title']),
                        strip_tags($plan['description']),
                    ];
                }
            }
            $data = $thematic->get_thematic_advance_by_thematic_id($theme['id']);
            if (!empty($data)) {
                foreach ($data as $advance) {
                    $csv[] = [
                        'progress',
                        strip_tags($advance['start_date']),
                        strip_tags($advance['duration']),
                        strip_tags($advance['content']),
                    ];
                }
            }
        }
        Export::arrayToCsv($csv);
        exit;

    case 'export_documents':
    case 'thematic_export_pdf':
        $pdfOrientation = api_get_configuration_value('thematic_pdf_orientation');

        $list = $thematic->get_thematic_list();
        $item = [];
        $listFinish = [];
        foreach ($list as $theme) {
            $dataPlan = $thematic->get_thematic_plan_data($theme['id']);
            if (!empty($dataPlan)) {
                foreach ($dataPlan as $plan) {
                    if (empty($plan['description'])) {
                        continue;
                    }
                    $item[] = [
                        'title' => $plan['title'],
                        'description' => $plan['description'],
                    ];
                }
                $theme['thematic_plan'] = $item;
            }
            $dataAdvance = $thematic->get_thematic_advance_by_thematic_id($theme['id']);
            if (!empty($dataAdvance)) {
                $theme['thematic_advance'] = $dataAdvance;
            }
            $listFinish[] = $theme;
        }

        $view = new Template('', false, false, false, true, false, false);
        $view->assign('data', $listFinish);
        $template = $view->get_template('course_progress/pdf_general_thematic.tpl');

        $format = 'portrait' !== $pdfOrientation ? 'A4-L' : 'A4-P';
        $orientation = 'portrait' !== $pdfOrientation ? 'L' : 'P';
        $fileName = get_lang('Thematic').'-'.api_get_local_time();
        $title = get_lang('Thematic');
        $signatures = ['Drh', 'Teacher', 'Date'];

        if ('export_documents' === $action) {
            $pdf = new PDF(
                $format,
                $orientation,
                [
                    'filename' => $fileName,
                    'pdf_title' => $fileName,
                    'add_signatures' => $signatures,
                ]
            );
            $pdf->exportFromHtmlToDocumentsArea($view->fetch($template), $fileName, $courseId);

            header('Location: '.$currentUrl);
            exit;
        }

        Export::export_html_to_pdf(
            $view->fetch($template),
            [
                'filename' => $fileName,
                'pdf_title' => $title,
                'add_signatures' => $signatures,
                'format' => $format,
                'orientation' => $orientation,
            ]
        );
        break;
    case 'export_single_thematic':
    case 'export_single_documents':
        $theme = $thematic->get_thematic_list($thematic_id);
        $plans = $thematic->get_thematic_plan_data($theme['id']);
        $plans = array_filter(
            $plans,
            function ($plan) {
                return !empty($plan['description']);
            }
        );
        $advances = $thematic->get_thematic_advance_by_thematic_id($theme['id']);

        $view = new Template('', false, false, false, true, false, false);
        $view->assign('theme', $theme);
        $view->assign('plans', $plans);
        $view->assign('advances', $advances);

        $template = $view->get_template('course_progress/pdf_single_thematic.tpl');

        $pdfOrientation = api_get_configuration_value('thematic_pdf_orientation');
        $format = 'portrait' !== $pdfOrientation ? 'A4-L' : 'A4-P';
        $orientation = 'portrait' !== $pdfOrientation ? 'L' : 'P';
        $title = get_lang('Thematic').'-'.$theme['title'];
        $fileName = $title.'-'.api_get_local_time();
        $signatures = ['Drh', 'Teacher', 'Date'];

        if ('export_single_documents' === $action) {
            $pdf = new PDF(
                $format,
                $orientation,
                [
                    'filename' => $fileName,
                    'pdf_title' => $fileName,
                    'add_signatures' => $signatures,
                ]
            );
            $pdf->exportFromHtmlToDocumentsArea(
                $view->fetch($template),
                $fileName,
                $courseId
            );

            header('Location: '.$currentUrl);
            exit;
        }

        Export::export_html_to_pdf(
            $view->fetch($template),
            [
                'filename' => $fileName,
                'pdf_title' => $title,
                'add_signatures' => $signatures,
                'format' => $format,
                'orientation' => $orientation,
            ]
        );
        break;
    case 'thematic_details':
        $tpl->assign('token', $url_token);
        $tpl->assign('is_allowed_to_edit', $isTeacher);
        $toolbar = null;

        if (!empty($thematic_id)) {
            $thematic_data_result = $thematic->get_thematic_list($thematic_id);
            if (!empty($thematic_data_result)) {
                $thematic_data[$thematic_id] = $thematic_data_result;
            }
            $data['total_average_of_advances'] = $thematic->get_average_of_advances_by_thematic($thematic_id);
        } else {
            $thematic_data = $thematic->get_thematic_list(null, api_get_course_id(), api_get_session_id());
            $max_thematic_item = $thematic->get_max_thematic_item();
            $last_done_thematic_advance = $thematic->get_last_done_thematic_advance();
            $total_average_of_advances = $thematic->get_total_average_of_thematic_advances();
        }

        // Second column
        $thematic_plan_data = $thematic->get_thematic_plan_data();

        // Third column
        $thematic_advance_data = $thematic->get_thematic_advance_list(null, null, true);

        if (!empty($message) && !empty($total_average_of_advances)) {
            $tpl->assign('message', $message);
            $tpl->assign('score_progress', $total_average_of_advances);
        }

        if (isset($last_id) && $last_id) {
            $link_to_thematic_plan = '<a href="index.php?'.api_get_cidreq().'&action=thematic_plan_list&thematic_id='.$last_id.'">'.
                Display::return_icon('lesson_plan.png', get_lang('Thematic plan'), ['style' => 'vertical-align:middle;float:none;'], ICON_SIZE_SMALL).'</a>';
            $link_to_thematic_advance = '<a href="index.php?'.api_get_cidreq().'&action=thematic_advance_list&thematic_id='.$last_id.'">'.
                Display::return_icon('lesson_plan_calendar.png', get_lang('Thematic advance'), ['style' => 'vertical-align:middle;float:none;'], ICON_SIZE_SMALL).'</a>';
            Display::addFlash(Display::return_message(
                get_lang('Thematic section has been created successfully').'<br />'.sprintf(get_lang('NowYouShouldAddThematic planXAndThematic advanceX'), $link_to_thematic_plan, $link_to_thematic_advance),
                'confirmation',
                false
            ));
        }

        if (empty($thematic_id)) {
            // display information
            $text = '<strong>'.get_lang('Information').': </strong>';
            $text .= get_lang('Thematic view with detailsDescription');
            $message = Display::return_message($text, 'info', false);
        }

        $list = [];
        // Display thematic data
        if (!empty($thematic_data)) {
            // display progress
            foreach ($thematic_data as $thematic) {
                $list['id'] = $thematic['id'];
                $list['id_course'] = $thematic['c_id'];
                $list['id_session'] = $thematic['session_id'];
                $list['title'] = Security::remove_XSS($thematic['title'], STUDENT);
                $list['content'] = Security::remove_XSS($thematic['content'], STUDENT);
                $list['display_orden'] = $thematic['display_order'];
                $list['active'] = $thematic['active'];
                $my_thematic_id = $thematic['id'];

                $session_star = '';
                if (api_is_allowed_to_edit(null, true)) {
                    if (api_get_session_id() == $thematic['session_id']) {
                        $session_star = api_get_session_image(api_get_session_id(), $user_info['status']);
                    }
                }

                $tpl->assign('session_star', $session_star);

                //@todo add a validation in order to load or not course thematics in the session thematic
                $toolbarThematic = '';
                if (api_is_allowed_to_edit(null, true)) {
                    // Thematic title
                    $toolbarThematic = Display::url(
                        Display::return_icon(
                            'cd.png',
                            get_lang('Copy'),
                            null,
                            ICON_SIZE_TINY
                        ),
                        'index.php?'.api_get_cidreq().'&action=thematic_copy&thematic_id='.$my_thematic_id.$params.$url_token,
                        ['class' => 'btn btn-default']
                    );
                    if (0 == api_get_session_id()) {
                        if ($thematic['display_order'] > 1) {
                            $toolbarThematic .= ' <a class="btn btn-default" href="'.api_get_self().'?action=moveup&'.api_get_cidreq().'&thematic_id='.$my_thematic_id.$params.$url_token.'">'.
                                Display::return_icon('up.png', get_lang('Up'), '', ICON_SIZE_TINY).'</a>';
                        } else {
                            $toolbarThematic .= '<div class="btn btn-default">'.
                                Display::return_icon('up_na.png', '&nbsp;', '', ICON_SIZE_TINY).'</div>';
                        }
                        if (isset($thematic['max_thematic_item']) && $thematic['display_order'] < $thematic['max_thematic_item']) {
                            $toolbarThematic .= ' <a class="btn btn-default" href="'.api_get_self().'?action=movedown&a'.api_get_cidreq().'&thematic_id='.$my_thematic_id.$params.$url_token.'">'.
                                Display::return_icon('down.png', get_lang('down'), '', ICON_SIZE_TINY).'</a>';
                        } else {
                            $toolbarThematic .= '<div class="btn btn-default">'.
                                Display::return_icon('down_na.png', '&nbsp;', '', ICON_SIZE_TINY).'</div>';
                        }
                    }
                    if (api_get_session_id() == $thematic['session_id']) {
                        $toolbarThematic .= Display::url(
                            Display::return_icon('pdf.png', get_lang('Export to PDF'), null, ICON_SIZE_TINY),
                            api_get_self().'?'.api_get_cidreq()."$url_token&".http_build_query([
                                'action' => 'export_single_thematic',
                                'thematic_id' => $my_thematic_id,
                            ]),
                            ['class' => 'btn btn-default']
                        );
                        $toolbarThematic .= Display::url(
                            Display::return_icon(
                                'export_to_documents.png',
                                get_lang('Export latest version of this page to Documents'),
                                [],
                                ICON_SIZE_TINY
                            ),
                            api_get_self().'?'.api_get_cidreq().$url_token.'&'.http_build_query(
                                ['action' => 'export_single_documents', 'thematic_id' => $my_thematic_id]
                            ),
                            ['class' => 'btn btn-default']
                        );
                        $toolbarThematic .= '<a class="btn btn-default" href="index.php?'.api_get_cidreq().'&action=thematic_edit&thematic_id='
                            .$my_thematic_id.$params.$url_token.'">'
                            .Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_TINY).'</a>';
                        $toolbarThematic .= '<a class="btn btn-default" onclick="javascript:if(!confirm(\''
                            .get_lang('Are you sure you want to delete')
                            .'\')) return false;" href="index.php?'.api_get_cidreq().'&action=thematic_delete&thematic_id='
                            .$my_thematic_id.$params.$url_token.'">'
                            .Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_TINY).'</a>';
                    }
                }
                $list['thematic_plan'] = $thematic_plan_data;
                $list['thematic_advance'] = $thematic_advance_data;
                $list['last_done'] = $last_done_thematic_advance;
                $list['toolbar'] = $toolbarThematic;
                $listThematic[] = $list;

                $tpl->assign('data', $listThematic);
            } //End for
        }

        $thematicLayout = $tpl->get_template('course_progress/progress.html.twig');
        $content = $tpl->fetch($thematicLayout);
        break;
    case 'thematic_list':
        $table = new SortableTable(
            'thematic_list',
            ['Thematic', 'get_number_of_thematics'],
            ['Thematic', 'get_thematic_data']
        );

        $parameters['action'] = $action;
        $table->set_additional_parameters($parameters);
        $table->set_header(0, '', false, ['style' => 'width:20px;']);
        $table->set_header(1, get_lang('Title'), false);
        if (api_is_allowed_to_edit(null, true)) {
            $table->set_header(
                2,
                get_lang('Detail'),
                false,
                ['style' => 'text-align:center;width:40%;']
            );
            $table->set_form_actions(['thematic_delete_select' => get_lang('Delete all thematics')]);
        }
        $content = $table->return_table();
        break;
    case 'thematic_plan_add':
    case 'thematic_plan_edit':
        if (!api_is_allowed_to_edit(null, true)) {
            api_not_allowed();
        }
        if (isset($_POST['title'])) {
            $title_list = $_REQUEST['title'];
            $description_list = $_REQUEST['description'];
            $description_type = $_REQUEST['description_type'];

            for ($i = 1; $i < count($title_list) + 1; ++$i) {
                $thematic->set_thematic_plan_attributes(
                    $_REQUEST['thematic_id'],
                    $title_list[$i],
                    $description_list[$i],
                    $description_type[$i]
                );
                $thematic->thematic_plan_save();
            }

            $saveRedirect = api_get_path(WEB_PATH).'main/course_progress/index.php?';
            $saveRedirect .= api_get_cidreq().'&';

            if (isset($_REQUEST['add_item'])) {
                $thematic->set_thematic_plan_attributes(
                    $_REQUEST['thematic_id'],
                    '',
                    '',
                    $i
                );
                $thematic->thematic_plan_save();
                Display::addFlash(
                    Display::return_message(get_lang('Thematic section has been created successfully'))
                );
            }

            header("Location: $saveRedirect");
            exit;
        }

        if ($description_type >= ADD_THEMATIC_PLAN) {
            $header_form = get_lang('Other');
        } else {
            $header_form = $default_thematic_plan_title[$description_type];
        }
        if (!$error) {
            $token = md5(uniqid(rand(), true));
            Session::write('thematic_plan_token', $token);
        }

        // display form
        $form = new FormValidator(
            'thematic_plan_add',
            'POST',
            'index.php?action=thematic_plan_edit&thematic_id='.$thematic_id.'&'.api_get_cidreq(),
            '',
            'style="width: 100%;"'
        );
        $form->addElement('hidden', 'action', $action);
        $form->addElement('hidden', 'thematic_plan_token', $token);

        if (!empty($thematic_id)) {
            $form->addElement('hidden', 'thematic_id', $thematic_id);
        }
        if (!empty($description_type)) {
            $form->addElement('hidden', 'description_type', $description_type);
        }

        $form->addText('title', get_lang('Title'), true, ['size' => '50']);
        $form->addHtmlEditor(
            'description',
            get_lang('Description'),
            false,
            false,
            [
                'ToolbarStartExpanded' => 'false',
                'ToolbarSet' => 'Basic',
                'Width' => '80%',
                'Height' => '150',
            ]
        );
        $form->addButtonSave(get_lang('Save'));

        if ($description_type < ADD_THEMATIC_PLAN) {
            $default['title'] = $default_thematic_plan_title[$description_type];
        }
        if (!empty($thematic_plan_data)) {
            // set default values
            $default['title'] = $thematic_plan_data[0]['title'];
            $default['description'] = $thematic_plan_data[0]['description'];
        }
        $form->setDefaults($default);

        if (isset($default_thematic_plan_question[$description_type])) {
            $message = '<strong>'.get_lang('Help').'</strong><br />';
            $message .= $default_thematic_plan_question[$description_type];
            Display::addFlash(Display::return_message($message, 'normal', false));
        }

        // error messages
        if ($error) {
            Display::addFlash(
                Display::return_message(
                    get_lang('The form contains incorrect or incomplete data. Please check your input.'),
                    'error',
                    false
                )
            );
        }
        $content = $form->returnForm();
        break;
    case 'thematic_plan_delete':
        if (!api_is_allowed_to_edit(null, true)) {
            api_not_allowed();
        }
        if (api_is_allowed_to_edit(null, true)) {
            $thematic->thematic_plan_destroy(
                $thematic_id,
                $description_type
            );
        }

        header('Location: '.$currentUrl);
        exit;
    case 'thematic_plan_list':

        $thematic_plan_data = $thematic->get_thematic_plan_data($thematic_id);
        $description_type = isset($_GET['description_type']) ? (int) $_GET['description_type'] : null;

        if (!empty($thematic_id) && !empty($description_type)) {
            $thematic_plan_data = $thematic->get_thematic_plan_data($thematic_id, $description_type);
        } elseif (!empty($thematic_id) && 'thematic_plan_list' === $action) {
            $thematic_plan_data = $thematic->get_thematic_plan_data($thematic_id);
        }

        $default_thematic_plan_title = $thematic->get_default_thematic_plan_title();
        $default_thematic_plan_icon = $thematic->get_default_thematic_plan_icon();
        $next_description_type = $thematic->get_next_description_type($thematic_id);
        $default_thematic_plan_question = $thematic->get_default_question();
        //$thematic_data = $thematic->get_thematic_list($thematic_id);
        //$tpl->assign('title_thematic', $thematic_data['title']);
        //$tpl->assign('content_thematic', $thematic_data['content']);
        //$tpl->assign('form_thematic', $formLayout);
        //$thematicLayout = $tpl->get_template('course_progress/thematic_plan.tpl');
        //$content = $tpl->fetch($thematicLayout);

        // actions menu
        $new_thematic_plan_data = [];

        if (!empty($thematic_plan_data)) {
            /** @var CThematicPlan $thematic_item */
            foreach ($thematic_plan_data as $thematic_item) {
                $thematic_simple_list[] = $thematic_item->getDescriptionType();
                $new_thematic_plan_data[$thematic_item->getDescriptionType()] = $thematic_item;
            }
        }

        $new_id = ADD_THEMATIC_PLAN;
        if (!empty($thematic_simple_list)) {
            foreach ($thematic_simple_list as $item) {
                if ($item >= ADD_THEMATIC_PLAN) {
                    $new_id = $item + 1;
                    $default_thematic_plan_title[$item] = $new_thematic_plan_data[$item]->getTitle();
                }
            }
        }

        $content = Display::tag('h2', $thematic_data->getTitle());
        $content .= $thematic_data->getContent();

        $token = Security::get_token();

        Session::write('thematic_plan_token', $token);

        $form = new FormValidator(
            'thematic_plan_add',
            'POST',
            'index.php?action=thematic_plan_list&thematic_id='.$thematic_id.'&'.api_get_cidreq()
        );
        $form->addElement('hidden', 'action', 'thematic_plan_add');
        $form->addElement('hidden', 'thematic_plan_token', $token);
        $form->addElement('hidden', 'thematic_id', $thematic_id);

        foreach ($default_thematic_plan_title as $id => $title) {
            $btnDelete = Display::toolbarButton(
                get_lang('Delete'),
                '#',
                'times',
                'danger',
                ['role' => 'button', 'data-id' => $id, 'class' => 'btn-delete']
            );

            $form->addElement('hidden', 'description_type['.$id.']', $id);
            $form->addText("title[$id]", [get_lang('Title'), null, $btnDelete], false);
            $form->addHtmlEditor(
                'description['.$id.']',
                get_lang('Description'),
                false,
                false,
                [
                    'ToolbarStartExpanded' => 'false',
                    'ToolbarSet' => 'Basic',
                    'Height' => '150',
                ]
            );

            if (!empty($thematic_simple_list) && in_array($id, $thematic_simple_list)) {
                /** @var CThematicPlan $thematic_plan */
                $thematic_plan = $new_thematic_plan_data[$id];
                // set default values
                $default['title['.$id.']'] = $thematic_plan->getTitle();
                $default['description['.$id.']'] = $thematic_plan->getDescription();
                $thematic_plan = null;
            } else {
                $thematic_plan = null;
                $default['title['.$id.']'] = $title;
                $default['description['.$id.']'] = '';
            }
            $form->setDefaults($default);
        }
        $form->addGroup([
            $form->addButton(
                'add_item',
                get_lang('SaveAndAddNewItem'),
                'plus',
                'info',
                'default',
                null,
                [],
                true
            ),
            $form->addButtonSave(get_lang('Save'), 'submit', true),
        ]);

        $content = $form->returnForm();

        break;
    case 'thematic_advance_add':
    case 'thematic_advance_edit':
        if (!api_is_allowed_to_edit(null, true)) {
            api_not_allowed();
        }

        $header_form = get_lang('New thematic advance');
        if ('thematic_advance_edit' === $action) {
            $header_form = get_lang('Edit thematic advance');
        }
        // display form
        $form = new FormValidator(
            'thematic_advance',
            'POST',
            api_get_self().'?'.api_get_cidreq()
        );
        $form->addElement('header', $header_form);
        //$form->addElement('hidden', 'thematic_advance_token',$token);
        $form->addElement('hidden', 'action', $action);

        if (!empty($thematic_advance_id)) {
            $form->addElement('hidden', 'thematic_advance_id', $thematic_advance_id);
        }
        if (!empty($thematic_id)) {
            $form->addElement('hidden', 'thematic_id', $thematic_id);
        }

        $radios = [];
        $radios[] = $form->createElement(
            'radio',
            'start_date_type',
            null,
            get_lang('Start date taken from an attendance date'),
            '1',
            [
                'onclick' => 'check_per_attendance(this)',
                'id' => 'from_attendance',
            ]
        );
        $radios[] = $form->createElement(
            'radio',
            'start_date_type',
            null,
            get_lang('Custom start date'),
            '2',
            [
                'onclick' => 'check_per_custom_date(this)',
                'id' => 'custom_date',
            ]
        );
        $form->addGroup($radios, null, get_lang('Start date options'));

        if (isset($thematic_advance_data['attendance_id']) &&
            0 == $thematic_advance_data['attendance_id']) {
            $form->addElement('html', '<div id="div_custom_datetime" style="display:block">');
        } else {
            $form->addElement('html', '<div id="div_custom_datetime" style="display:none">');
        }

        $form->addElement('DateTimePicker', 'custom_start_date', get_lang('Start Date'));
        $form->addElement('html', '</div>');

        if (isset($thematic_advance_data['attendance_id']) &&
            0 == $thematic_advance_data['attendance_id']
        ) {
            $form->addElement('html', '<div id="div_datetime_by_attendance" style="display:none">');
        } else {
            $form->addElement('html', '<div id="div_datetime_by_attendance" style="display:block">');
        }

        if (count($attendance_select) > 1) {
            $form->addElement(
                'select',
                'attendance_select',
                get_lang('Attendances'),
                $attendance_select,
                ['id' => 'id_attendance_select', 'onchange' => 'datetime_by_attendance(this.value)']
            );
        } else {
            $form->addElement(
                'label',
                get_lang('Attendances'),
                '<strong><em>'.get_lang('There is no attendance sheet in this course').'</em></strong>'
            );
        }

        $form->addElement('html', '<div id="div_datetime_attendance">');
        if (!empty($calendar_select)) {
            $form->addElement(
                'select',
                'start_date_by_attendance',
                get_lang('Start Date'),
                $calendar_select,
                ['id' => 'start_date_select_calendar']
            );
        }
        $form->addElement('html', '</div>');
        $form->addElement('html', '</div>');

        $form->addText(
            'duration_in_hours',
            get_lang('Duration in hours'),
            false,
            [
                'size' => '3',
                'id' => 'duration_in_hours_element',
                'autofocus' => 'autofocus',
            ]
        );

        $form->addHtmlEditor(
            'content',
            get_lang('Content'),
            false,
            false,
            [
                'ToolbarStartExpanded' => 'false',
                'ToolbarSet' => 'Basic',
                'Height' => '150',
            ]
        );

        if ('thematic_advance_add' == $action) {
            $form->addButtonSave(get_lang('Save'));
        } else {
            $form->addButtonUpdate(get_lang('Save'));
        }

        $attendance_select_item_id = null;
        if (count($attendance_select) > 1) {
            $i = 1;
            foreach ($attendance_select as $key => $attendance_select_item) {
                if (2 == $i) {
                    $attendance_select_item_id = $key;
                    break;
                }
                ++$i;
            }
            if (!empty($attendance_select_item_id)) {
                $default['attendance_select'] = $attendance_select_item_id;
                if ($thematic_advance_id) {
                    echo '<script> datetime_by_attendance("'.$attendance_select_item_id.'", "'.$thematic_advance_id.'"); </script>';
                } else {
                    echo '<script> datetime_by_attendance("'.$attendance_select_item_id.'", 0); </script>';
                }
            }
        }

        $default['start_date_type'] = 1;
        $default['custom_start_date'] = date('Y-m-d H:i:s', api_strtotime(api_get_local_time()));
        $default['duration_in_hours'] = 1;

        if (!empty($thematic_advance_data)) {
            // set default values
            $default['content'] = isset($thematic_advance_data['content']) ? $thematic_advance_data['content'] : null;
            $default['duration_in_hours'] = isset($thematic_advance_data['duration']) ? $thematic_advance_data['duration'] : 1;
            if (empty($thematic_advance_data['attendance_id'])) {
                $default['start_date_type'] = 1;
                $default['custom_start_date'] = null;
                if (isset($thematic_advance_data['start_date'])) {
                    $default['custom_start_date'] = date(
                        'Y-m-d H:i:s',
                        api_strtotime(api_get_local_time($thematic_advance_data['start_date']))
                    );
                }
            } else {
                $default['start_date_type'] = 1;
                if (!empty($thematic_advance_data['start_date'])) {
                    $default['start_date_by_attendance'] = api_get_local_time($thematic_advance_data['start_date']);
                }

                $default['attendance_select'] = $thematic_advance_data['attendance_id'];
            }
        }
        $form->setDefaults($default);

        if ($form->validate()) {
            $values = $form->exportValues();

            if (isset($_POST['start_date_by_attendance'])) {
                $values['start_date_by_attendance'] = $_POST['start_date_by_attendance'];
            }
            $startDate = $values['custom_start_date'];
            if (2 == $values['start_date_type']) {
                $startDate = $values['start_date_by_attendance'];
            }
            $thematic = new Thematic();
            $thematic->set_thematic_advance_attributes(
                isset($values['thematic_advance_id']) ? $values['thematic_advance_id'] : null,
                $values['thematic_id'],
                1 == $values['start_date_type'] && isset($values['attendance_select']) ? $values['attendance_select'] : 0,
                $values['content'],
                $startDate,
                $values['duration_in_hours']
            );

            $affected_rows = $thematic->thematic_advance_save();

            if ($affected_rows) {
                // get last done thematic advance before move thematic list
                $last_done_thematic_advance = $thematic->get_last_done_thematic_advance();
                // update done advances with de current thematic list
                if (!empty($last_done_thematic_advance)) {
                    $thematic->update_done_thematic_advances($last_done_thematic_advance);
                }
            }

            $redirectUrlParams = 'course_progress/index.php?'.api_get_cidreq().'&'.
                http_build_query([
                    'action' => 'thematic_advance_list',
                    'thematic_id' => $values['thematic_id'],
                ]);

            Display::addFlash(Display::return_message(get_lang('Update successful')));

            header('Location: '.api_get_path(WEB_CODE_PATH).$redirectUrlParams);
            exit;
        }

        $content = $form->returnForm();

        break;
    case 'thematic_advance_delete':
        if (!api_is_allowed_to_edit(null, true)) {
            api_not_allowed();
        }

        if (!empty($thematic_advance_id)) {
            if (api_is_allowed_to_edit(null, true)) {
                $thematic->thematic_advance_destroy($thematic_advance_id);
            }
            Display::addFlash(Display::return_message(get_lang('Deleted')));
            header('Location: '.$currentUrl);
            break;
        }
    //no break
    case 'thematic_advance_list':
        // thematic advance list
        $content = '<div class="actions">';
        $content .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=thematic_details">'.
            Display::return_icon('back.png', get_lang('Back to'), '', ICON_SIZE_MEDIUM).'</a>';
        if (api_is_allowed_to_edit(false, true)) {
            $content .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=thematic_advance_add&thematic_id='.$thematic_id.'"> '.
                Display::return_icon('add.png', get_lang('New thematic advance'), '', ICON_SIZE_MEDIUM).'</a>';
        }
        $content .= '</div>';
        $table = new SortableTable(
            'thematic_advance_list',
            ['Thematic', 'get_number_of_thematic_advances'],
            ['Thematic', 'get_thematic_advance_data']
        );
        //$table->set_additional_parameters($parameters);
        $table->set_header(0, '', false, ['style' => 'width:20px;']);
        $table->set_header(1, get_lang('Start Date'), false);
        $table->set_header(2, get_lang('Duration in hours'), false, ['style' => 'width:80px;']);
        $table->set_header(3, get_lang('Content'), false);

        if (api_is_allowed_to_edit(null, true)) {
            $table->set_header(
                4,
                get_lang('Detail'),
                false,
                ['style' => 'text-align:center']
            );
        }
        $content .= $table->return_table();

        $tpl->assign('form_thematic', $content);
        $thematicLayout = $tpl->get_template('course_progress/thematic_advance.html.twig');
        $content = $tpl->fetch($thematicLayout);
        break;
}

$toolbar = Display::toolbarAction('thematic-bar', [$actionLeft]);
$tpl->assign('content', $content);
$tpl->assign('actions', $toolbar);
$tpl->display_one_col_template();
