<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Entity\CThematicPlan;
use ChamiloSession as Session;

require_once __DIR__.'/../inc/global.inc.php';

// current section
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_COURSE_PROGRESS;

// protect a course script
api_protect_course_script(true);
$courseId = api_get_course_int_id();
$course = api_get_course_entity();
$session = api_get_session_entity();
$description_type = null;

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
$thematicId = isset($_REQUEST['thematic_id']) ? (int) $_REQUEST['thematic_id'] : null;
$thematicAdvanceId = isset($_REQUEST['thematic_advance_id']) ? (int) $_REQUEST['thematic_advance_id'] : null;
$url = api_get_path(WEB_AJAX_PATH).'thematic.ajax.php?a=get_datetime_by_attendance&'.api_get_cidreq();


$studentViewKey = 'student_view_course_' . $courseId;
if (isset($_GET['switch_student_view'])) {
    $want = $_GET['switch_student_view'] === '1' ? 1 : 0;
    Session::write($studentViewKey, $want);
    header('Location: ' . $currentUrl);
    exit;
}

$forcedStudentView = isset($_GET['isStudentView']) && $_GET['isStudentView'] === 'true';
$isStudentView = $forcedStudentView ? true : (bool) (Session::read($studentViewKey) ?? false);
$readOnly = !$isTeacher || $isStudentView;

$htmlHeadXtra[] = '<script>
$(function() {
    if ($("#div_result").html() !== undefined && $("#div_result").html().length == 0) {
        $("#div_result").html("0");
    }

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

function datetime_by_attendance(attendance_id, thematic_advance_id) {
	$.ajax({
		contentType: "application/x-www-form-urlencoded",
		type: "GET",
		url: "'.$url.'",
		data: "attendance_id="+attendance_id+"&thematic_advance_id="+thematic_advance_id,
		success: function(data) {
			$("#div_datetime_attendance").html(data);
            if (thematic_advance_id == 0) {
                $("#from_attendance option:first").attr("checked", true);
                $("#div_datetime_by_attendance").show();
                $("#div_custom_datetime").hide();
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
</script>';

$attendance = new Attendance();
// get data for attendance input select
$attendance_list = $attendance->getAttendanceList($course, $session);
$attendance_select = [];
$attendance_select[0] = get_lang('Select an attendance');
foreach ($attendance_list as $attendanceEntity) {
    $attendance_select[$attendanceEntity->getIid()] = $attendanceEntity->getTitle();
}

$token = Security::get_token();
$url_token = '&sec_token='.$token;
$user_info = api_get_user_info();
$params = '&'.api_get_cidreq();

if (isset($_POST['action']) && 'thematic_delete_select' === $_POST['action']) {
    $action = 'thematic_delete_select';
}

if (isset($_GET['isStudentView']) && 'true' === $_GET['isStudentView']) {
    $action = 'thematic_details';
}

$actionLeft = '';
// instance thematic object for using like library here
$thematicManager = new Thematic();
$thematicEntity = null;
$repo = Container::getThematicRepository();
if (!empty($thematicId)) {
    /** @var CThematic $thematicEntity */
    $thematicEntity = $repo->find($thematicId);
}
$cleanThematicTitle = null !== $thematicEntity ? strip_tags($thematicEntity->getTitle()) : null;

// get default thematic plan title
$default_thematic_plan_title = $thematicManager->get_default_thematic_plan_title();

$tpl = new Template('');
$interbreadcrumb[] = [
    'url' => 'index.php?'.api_get_cidreq().'&action=thematic_details',
    'name' => get_lang('Thematic control')
];

Event::event_access_tool(TOOL_COURSE_PROGRESS);

// Dispatch actions to controller
switch ($action) {
    case 'thematic_add':
    case 'thematic_edit':
        // Block edition when in student view
        if ($readOnly) {
            api_not_allowed();
        }

        if (empty($thematicId)) {
            $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('New thematic section')];
        } else {
            $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit thematic section')];
        }
        if ('POST' === $requestMethod && '' !== trim($_POST['title']) &&
            api_is_allowed_to_edit(null, true)
        ) {
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $thematicManager->thematicSave($thematicId, $title, $content, $course, $session);
            Display::addFlash(Display::return_message(get_lang('Update successful')));

            header('Location: '.$currentUrl);
            exit;
            break;
        } else {
            // Display form
            $form = new FormValidator('thematic_add', 'POST', 'index.php?action=thematic_add&'.api_get_cidreq());
            if ('thematic_edit' === $action) {
                $form->addHeader(get_lang('Edit thematic section'));
            }

            $form->addHidden('sec_token', $token);
            $form->addHidden('action', $action);

            if (!empty($thematicId)) {
                $form->addHidden('thematic_id', $thematicId);
            }

            if ('true' === api_get_setting('editor.save_titles_as_html')) {
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

            if (!empty($thematicEntity)) {
                if (api_get_session_id()) {
                    /* if ($thematic['session_id'] != api_get_session_id()) { ... } */
                }
                // set default values
                $default['title'] = $thematicEntity->getTitle();
                $default['content'] = $thematicEntity->getContent();
                $form->setDefaults($default);
            }
            $content = $form->returnForm();
        }
        break;
    case 'thematic_copy':
        // Copy a thematic to a session
        if ($readOnly) {
            api_not_allowed();
        }
        $thematicManager->copy($thematicId);

        header('Location: '.$currentUrl);
        exit;
        break;
    case 'thematic_delete_select':
        if ($readOnly) {
            api_not_allowed();
        }
        if ('POST' === $requestMethod && api_is_allowed_to_edit(null, true)) {
            $thematicManager->delete($_POST['id']);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }

        header('Location: '.$currentUrl);
        exit;
        break;
    case 'thematic_delete':
        if ($readOnly) {
            api_not_allowed();
        }
        // Delete a thematic
        if (!empty($thematicId) && api_is_allowed_to_edit(null, true)) {
            $thematicManager->delete($thematicId);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }
        header('Location: '.$currentUrl);
        exit;
        break;
    case 'thematic_import':
        if ($readOnly) {
            api_not_allowed();
        }

        $csv_import_array = Import::csv_reader($_FILES['file']['tmp_name'], false);

        if (isset($_POST['replace']) && $_POST['replace']) {
            // Remove current thematic.
            $list = $thematicManager->getThematicList($course, $session);
            foreach ($list as $id) {
                $thematicManager->delete($id);
            }
        }

        // Import the progress.
        $currentThematic = null;
        foreach ($csv_import_array as $key => $item) {
            if (!$key) {
                continue;
            }

            switch ($item[0]) {
                case 'title':
                    $currentThematic = $thematicManager->thematicSave(null, $item[1], $item[2], $course, $session);
                    $description_type = 1;
                    break;
                case 'plan':
                    $thematicManager->thematicPlanSave($currentThematic, $item[1], $item[2], $description_type);
                    $description_type++;
                    break;
                case 'progress':
                    $thematicManager->thematicAdvanceSave(
                        $currentThematic,
                        null,
                        null,
                        $item[3],
                        $item[1],
                        $item[2]
                    );
                    break;
            }
        }

        Display::addFlash(Display::return_message(get_lang('Import')));

        header('Location: '.$currentUrl);
        exit;

        break;
    case 'thematic_import_select':
        if ($readOnly) {
            api_not_allowed();
        }

        $actionLeft = '<a href="index.php?'.api_get_cidreq().'">';
        $actionLeft .= Display::getMdiIcon(
            ActionIcon::BACK,
            'ch-tool-icon',
            null,
            ICON_SIZE_MEDIUM,
            get_lang('Back to').' '.get_lang('Thematic view with details')
        );
        $actionLeft .= '</a>';

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
        if ($readOnly || !api_is_allowed_to_edit(null, true)) {
            api_not_allowed();
        }
        $thematicManager->moveThematic('up', $thematicId, $course, $session);

        header('Location: '.$currentUrl);
        exit;
        break;
    case 'movedown':
        if ($readOnly || !api_is_allowed_to_edit(null, true)) {
            api_not_allowed();
        }
        $thematicManager->moveThematic('down', $thematicId, $course, $session);

        header('Location: '.$currentUrl);
        exit;
        break;
    case 'thematic_export':
        if ($readOnly) {
            api_not_allowed();
        }
        $list = $thematicManager->getThematicList($course, $session);
        $csv = [];
        $csv[] = ['type', 'data1', 'data2', 'data3'];
        foreach ($list as $thematicEntity) {
            $csv[] = ['title', strip_tags($thematicEntity->getTitle()), strip_tags($thematicEntity->getContent())];
            $data = $thematicEntity->getPlans();
            if (!empty($data)) {
                foreach ($data as $plan) {
                    if (empty($plan->getDescription())) {
                        continue;
                    }

                    $csv[] = [
                        'plan',
                        strip_tags($plan->getTitle()),
                        strip_tags($plan->getDescription()),
                    ];
                }
            }

            $data = $thematicEntity->getAdvances();
            if (!empty($data)) {
                foreach ($data as $advance) {
                    $csv[] = [
                        'progress',
                        strip_tags(api_get_local_time($advance->getStartDate())),
                        strip_tags($advance->getDuration()),
                        strip_tags($advance->getContent()),
                    ];
                }
            }
        }
        Export::arrayToCsv($csv);
        exit;
        break;
    case 'export_documents':
    case 'thematic_export_pdf':
        if ($readOnly) {
            api_not_allowed();
        }

        $pdfOrientation = api_get_setting('document.thematic_pdf_orientation');
        $view = new Template('', false, false, false, true, false, false);
        $list = $thematicManager->getThematicList($course, $session);
        $view->assign('data', $list);
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
        if ($readOnly) {
            api_not_allowed();
        }

        $view = new Template('', false, false, false, true, false, false);
        $view->assign('thematic', $thematicEntity);
        $template = $view->get_template('course_progress/pdf_single_thematic.tpl');

        $pdfOrientation = api_get_setting('document.thematic_pdf_orientation');
        $format = 'portrait' !== $pdfOrientation ? 'A4-L' : 'A4-P';
        $orientation = 'portrait' !== $pdfOrientation ? 'L' : 'P';
        $title = get_lang('Thematic').'-'.$thematicEntity->getTitle();
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
        // Left toolbar actions (hidden in student view)
        if (!$readOnly) {
            $actionLeft = '<a href="index.php?'.api_get_cidreq().'&action=thematic_add'.$url_token.'">'.
                Display::getMdiIcon('progress-star', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('New thematic section')).'</a>';
            $actionLeft .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_import_select'.$url_token.'">'.
                Display::getMdiIcon('file-delimited', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Import course progress')).'</a>';
            $actionLeft .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_export'.$url_token.'">'.
                Display::getMdiIcon(ActionIcon::EXPORT_CSV, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export course progress')).'</a>';
            $actionLeft .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_export_pdf'.$url_token.'">'.
                Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export to PDF')).'</a>';
        }
        /*$actionLeft .= Display::url(
            Display::getMdiIcon('export_to_documents', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export latest version of this page to Documents')),
            api_get_self().'?'.api_get_cidreq().'&'.http_build_query(['action' => 'export_documents']).$url_token
        );*/
        $total_average_of_advances = null;
        $tpl->assign('token', $url_token);
        $tpl->assign('is_allowed_to_edit', !$readOnly);
        $tpl->assign('student_view_active', $isStudentView);
        $toolbar = null;
        $last_done_thematic_advance = null;
        if ($thematicEntity) {
            $thematic_data[$thematicId] = $thematicEntity;
            $data['total_average_of_advances'] = $thematicManager->get_average_of_advances_by_thematic($thematicEntity);
        } else {
            $thematic_data = $thematicManager->getThematicList($course, $session);
            //$max_thematic_item = $thematicManager->get_max_thematic_item($course, $session);
            $max_thematic_item = 0;
            $last_done_thematic_advance = $thematicManager->get_last_done_thematic_advance($course, $session);
            $total_average_of_advances = $thematicManager->get_total_average_of_thematic_advances($course, $session);
        }

        if (!empty($message) && !empty($total_average_of_advances)) {
            $tpl->assign('message', $message);
        }
        $tpl->assign('score_progress', $total_average_of_advances);

        if (!$readOnly && isset($last_id) && $last_id) {
            $link_to_thematic_plan = '<a
                href="index.php?'.api_get_cidreq().'&action=thematic_plan_list&thematic_id='.$last_id.'">'.
                Display::getMdiIcon('progress-check', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Thematic plan')).'</a>';
            $link_to_thematic_advance = '<a
                href="index.php?'.api_get_cidreq().'&action=thematic_advance_list&thematic_id='.$last_id.'">'.
                Display::getMdiIcon('progress-clock', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Thematic advance')).'</a>';
            Display::addFlash(
                Display::return_message(
                    get_lang('Thematic section has been created successfully').'<br />'.sprintf(
                        get_lang('Now you should add thematic plan %s and thematic advance %s'),
                        $link_to_thematic_plan,
                        $link_to_thematic_advance
                    ),
                    'confirmation',
                    false
                )
            );
        }

        if (empty($thematicId)) {
            // display information
            $text = '<strong>'.get_lang('Information').': </strong>';
            $text .= get_lang('Thematic view with details');
            $message = Display::return_message($text, 'info', false);
        }

        $list = [];
        $listThematic = [];
        $extra = [];
        $noData = '';
        // Display thematic data
        if (!empty($thematic_data)) {
            /** @var CThematic $thematic */
            foreach ($thematic_data as $thematic) {
                $id = $thematic->getIid();

                //@todo add a validation in order to load or not course thematics in the session thematic
                $toolbarThematic = '';
                if (!$readOnly && api_is_allowed_to_edit(null, true)) {
                    // Thematic title actions (copy/move/export/edit/delete)
                    $toolbarThematic = Display::url(
                        Display::getMdiIcon('disc', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Copy')),
                        'index.php?'.api_get_cidreq().'&action=thematic_copy&thematic_id='.$id.$params.$url_token,
                        ['class' => 'btn btn--default']
                    );
                    if (0 == api_get_session_id()) {
                        $link = $thematic->getResourceNode()->getResourceLinkByContext($course, $session);
                        $currentOrder = $link ? $link->getDisplayOrder() : 0;
                        $moveButtons = $thematicManager->getMoveActions($id, $currentOrder, count($thematic_data));
                        $toolbarThematic .= $moveButtons;
                    }

                    $toolbarThematic .= Display::url(
                        Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export to PDF')),
                        api_get_self().'?'.api_get_cidreq()."$url_token&".http_build_query(
                            [
                                'action' => 'export_single_thematic',
                                'thematic_id' => $id,
                            ]
                        ),
                        ['class' => 'btn btn--default']
                    );
                    $toolbarThematic .= '<a
                            class="btn btn--default"
                            href="index.php?'.api_get_cidreq().'&action=thematic_edit&thematic_id='.$id.$params.$url_token.'">'
                        .Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit')).'</a>';
                    $toolbarThematic .= '<a
                            class="btn btn--default"
                            onclick="javascript:if(!confirm(\''.get_lang('Are you sure you want to delete').'\')) return false;"
                            href="index.php?'.api_get_cidreq().'&action=thematic_delete&thematic_id='.$id.$params.$url_token.'">'
                        .Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Delete')).'</a>';
                }
                $extra[$thematic->getIid()]['toolbar'] = $toolbarThematic;
                $extra[$thematic->getIid()]['last_done'] = $last_done_thematic_advance;
                $listThematic[] = $thematic;
            }
        } else {
            if (!$readOnly && api_is_allowed_to_edit(null, true)) {
                $noData = Display::noDataView(
                    get_lang('Course program'),
                    Display::getMdiIcon(ToolIcon::COURSE_PROGRESS, 'ch-tool-icon', null, ICON_SIZE_BIG),
                    get_lang('Add'),
                    api_get_path(WEB_CODE_PATH).'course_progress/index.php?'.api_get_cidreq().'&action=thematic_add'
                );
            }
        }

        $tpl->assign('extra', $extra);
        $tpl->assign('data', $listThematic);
        $tpl->assign('no_data', $noData);
        $thematicLayout = $tpl->get_template('course_progress/progress.html.twig');
        $content = $tpl->fetch($thematicLayout);
        break;
    case 'thematic_list':
        if (!$readOnly) {
            $actionLeft = '<a href="index.php?'.api_get_cidreq().'&action=thematic_add'.$url_token.'">'.
                Display::getMdiIcon('progress-star', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('New thematic section')).'</a>';
        }

        $table = new SortableTable(
            'thematic_list',
            ['Thematic', 'get_number_of_thematics'],
            ['Thematic', 'get_thematic_data']
        );

        $parameters['action'] = $action;
        $table->set_additional_parameters($parameters);
        $table->set_header(0, '', false, ['style' => 'width:20px;']);
        $table->set_header(1, get_lang('Title'), false);
        if (!$readOnly && api_is_allowed_to_edit(null, true)) {
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
        if ($readOnly) {
            api_not_allowed();
        }

        $interbreadcrumb[] = [
            'url' => 'index.php?'.api_get_cidreq().'&action=thematic_plan_list&thematic_id='.$thematicId,
            'name' => get_lang('Thematic plan').' ('.$cleanThematicTitle.')',
        ];

        if (!api_is_allowed_to_edit(null, true)) {
            api_not_allowed();
        }
        if (isset($_POST['title'])) {
            $title_list = $_REQUEST['title'];
            $description_list = $_REQUEST['description'];
            $description_type = $_REQUEST['description_type'];

            for ($i = 1; $i < count($title_list) + 1; $i++) {
                $thematicManager->thematicPlanSave(
                    $thematicEntity,
                    $title_list[$i],
                    $description_list[$i],
                    $description_type[$i]
                );
            }

            $saveRedirect = api_get_path(WEB_PATH).'main/course_progress/index.php?';
            $saveRedirect .= api_get_cidreq().'&';

            if (isset($_REQUEST['add_item'])) {
                $thematicManager->thematicPlanSave(
                    $thematicEntity,
                    '',
                    '',
                    $i
                );
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

        // display form
        $form = new FormValidator(
            'thematic_plan_add',
            'POST',
            'index.php?action=thematic_plan_edit&thematic_id='.$thematicId.'&'.api_get_cidreq(),
            '',
            'style="width: 100%;"'
        );
        $form->addHidden('action', $action);
        $form->addHidden('thematic_plan_token', $token);

        if (!empty($thematicId)) {
            $form->addHidden('thematic_id', $thematicId);
        }
        if (!empty($description_type)) {
            $form->addHidden('description_type', $description_type);
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

        $content = $form->returnForm();
        break;
    case 'thematic_plan_delete':
        if ($readOnly || !api_is_allowed_to_edit(null, true)) {
            api_not_allowed();
        }
        if (api_is_allowed_to_edit(null, true)) {
            $thematicManager->thematic_plan_destroy(
                $thematicId,
                $description_type
            );
        }

        header('Location: '.$currentUrl);
        exit;
        break;
    case 'thematic_plan_list':
        if (!empty($thematicEntity)) {
            $interbreadcrumb[] = [
                'url'  => '#',
                'name' => get_lang('Thematic plan').' ('.$cleanThematicTitle.')',
            ];
        }

        $htmlHeadXtra[] = "
                <script>
                    $(function () {
                        $('.btn-delete').on('click', function (e) {
                            e.preventDefault();
                            var id = $(this).data('id') || 0;
                            if (!id) {
                                return;
                            }
                            CKEDITOR.instances['description[' + id + ']'].setData('');
                        });
                    });
                </script>
            ";

        $thematic_plan_data = $thematicEntity->getPlans();
        $description_type = isset($_GET['description_type']) ? (int) $_GET['description_type'] : null;

        $default_thematic_plan_title = $thematicManager->get_default_thematic_plan_title();
        $default_thematic_plan_icon = $thematicManager->get_default_thematic_plan_icon();
        $default_thematic_plan_question = $thematicManager->get_default_question();

        // actions menu
        $new_thematic_plan_data = [];
        if (!empty($thematic_plan_data)) {
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

        $content = Display::tag('h2', $thematicEntity->getTitle());
        $content .= $thematicEntity->getContent();

        $token = Security::get_token();

        Session::write('thematic_plan_token', $token);

        $form = new FormValidator(
            'thematic_plan_add',
            'POST',
            'index.php?action=thematic_plan_list&thematic_id='.$thematicId.'&'.api_get_cidreq()
        );
        $form->addHidden('action', 'thematic_plan_add');
        $form->addHidden('thematic_plan_token', $token);
        $form->addHidden('thematic_id', $thematicId);

        foreach ($default_thematic_plan_title as $id => $title) {
            $btnDelete = Display::toolbarButton(
                get_lang('Delete'),
                '#',
                'times',
                'danger',
                [
                    'role'   => 'button',
                    'data-id'=> (string) $id,
                    'class'  => 'btn-delete',
                    'title'  => get_lang('Delete'),
                    'aria-label' => get_lang('Delete'),
                ]
            );

            $form->addHidden('description_type['.$id.']', $id);
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

            if (!empty($thematic_simple_list) && in_array($id, $thematic_simple_list ?? [])) {
                /** @var CThematicPlan $thematic_plan */
                $thematic_plan = $new_thematic_plan_data[$id] ?? null;
                // set default values
                if ($thematic_plan) {
                    $default['title['.$id.']'] = $thematic_plan->getTitle();
                    $default['description['.$id.']'] = $thematic_plan->getDescription();
                }
                $thematic_plan = null;
            } else {
                $thematic_plan = null;
                $default['title['.$id.']'] = $title;
                $default['description['.$id.']'] = '';
            }
            $form->setDefaults($default);
        }
        $form->addGroup(
            [
                $form->addButtonSave(get_lang('Save'), 'submit', true),
            ]
        );

        $content = $form->returnForm();

        break;
    case 'thematic_advance_add':
    case 'thematic_advance_edit':
        if ($readOnly) {
            api_not_allowed();
        }
        if (!api_is_allowed_to_edit(null, true)) {
            api_not_allowed();
        }

        /** @var CThematicAdvance $advance */
        $advance = null;
        if (!empty($thematicEntity)) {
            $interbreadcrumb[] = [
                'url' => 'index.php?'.api_get_cidreq().'&action=thematic_advance_list&thematic_id='.$thematicId,
                'name' => get_lang('Thematic advance').' ('.$cleanThematicTitle.')',
            ];
            foreach ($thematicEntity->getAdvances() as $advanceItem) {
                if ($thematicAdvanceId === $advanceItem->getIid()) {
                    $advance = $advanceItem;
                    break;
                }
            }
        } else {
            $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('New thematic advance')];
        }

        $header = get_lang('New thematic advance');
        if ('thematic_advance_edit' === $action) {
            $header = get_lang('Edit thematic advance');
        }
        // display form
        $form = new FormValidator(
            'thematic_advance',
            'POST',
            api_get_self().'?'.api_get_cidreq()
        );
        $form->addHeader($header);
        $form->addHidden('action', $action);

        if ($advance) {
            $form->addHidden('thematic_advance_id', $advance->getIid());
        }
        if (!empty($thematicId)) {
            $form->addHidden('thematic_id', $thematicId);
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

        // Custom date.
        if ($advance && $advance->getAttendance()) {
            $form->addHtml('<div id="div_custom_datetime" style="display:none">');
        } else {
            $form->addHtml('<div id="div_custom_datetime" style="display:block">');
        }
        $form->addElement('DateTimePicker', 'custom_start_date', get_lang('Start Date'));
        $form->addHtml('</div>');

        // Date by attendance.
        if ($advance && $advance->getAttendance()) {
            $form->addHtml('<div id="div_datetime_by_attendance" style="display:block">');
        } else {
            $form->addHtml('<div id="div_datetime_by_attendance" style="display:none">');
        }

        if (count($attendance_select) > 1) {
            $form->addSelect(
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

        $calendar_select = [];
        if ($advance) {
            $calendars = $advance->getAttendance()->getCalendars();
            if (!empty($calendars)) {
                foreach ($calendars as $calendar) {
                    $dateTime = $calendar->getDateTime()->format('Y-m-d H:i:s');
                    $calendar_select[$dateTime] = $dateTime;
                }
            }
        }

        $form->addHtml('<div id="div_datetime_attendance">');
        if (!empty($calendar_select)) {
            $form->addSelect(
                'start_date_by_attendance',
                get_lang('Start Date'),
                $calendar_select,
                ['id' => 'start_date_select_calendar']
            );
        }
        $form->addHtml('</div>');
        $form->addHtml('</div>');

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

        if ('thematic_advance_add' === $action) {
            $form->addButtonSave(get_lang('Save'));
        } else {
            $form->addButtonUpdate(get_lang('Save'));
        }
        $js = '';
        $attendance_select_item_id = null;
        if (count($attendance_select) > 1) {
            $i = 1;
            foreach ($attendance_select as $key => $attendance_select_item) {
                if (2 == $i) {
                    $attendance_select_item_id = $key;
                    break;
                }
                $i++;
            }
            if (!empty($attendance_select_item_id)) {
                $default['attendance_select'] = $attendance_select_item_id;
                if ($thematicAdvanceId) {
                    $js .= '<script>datetime_by_attendance("'.$attendance_select_item_id.'", "'.$thematicAdvanceId.'"); </script>';
                } else {
                    $js .= '<script>datetime_by_attendance("'.$attendance_select_item_id.'", 0); </script>';
                }
            }
        }

        $default['start_date_type'] = 1;
        $default['custom_start_date'] = date('Y-m-d H:i:s', api_strtotime(api_get_local_time()));
        $default['duration_in_hours'] = 1;

        if (!empty($advance)) {
            // set default values
            $default['content'] = $advance->getContent();
            $default['duration_in_hours'] = $advance->getDuration();

            if ($advance->getAttendance()) {
                $default['start_date_type'] = 1;
                if (!empty($thematic_advance_data['start_date'])) {
                    $default['start_date_by_attendance'] = api_get_local_time($advance->getStartDate());
                }
                $default['attendance_select'] = $advance->getAttendance()->getIid();
            } else {
                $default['custom_start_date'] = date(
                    'Y-m-d H:i:s',
                    api_strtotime(api_get_local_time($advance->getStartDate()))
                );
            }
        }

        $form->setDefaults($default);

        if ($form->validate()) {
            $values = $form->getSubmitValues();
            if (isset($_POST['start_date_by_attendance'])) {
                $values['start_date_by_attendance'] = $_POST['start_date_by_attendance'];
            }
            $attendanceId = 1 == $values['start_date_type'] && isset($values['attendance_select']) ? $values['attendance_select'] : 0;
            $attendance = Container::getAttendanceRepository()->find($attendanceId);

            $startDate = $values['custom_start_date'];
            if (1 == $values['start_date_type'] && isset($values['attendance_select']) &&
                isset($values['start_date_by_attendance'])
            ) {
                $startDate = $values['start_date_by_attendance'];
            }

            $advanceId = $values['thematic_advance_id'] ?? null;
            $advance = null;
            if (!empty($advanceId)) {
                $advance = Container::getThematicAdvanceRepository()->find($advanceId);
            }

            $newAdvance = $thematicManager->thematicAdvanceSave(
                $thematicEntity,
                $attendance,
                $advance,
                $values['content'],
                $startDate,
                $values['duration_in_hours']
            );

            if ($newAdvance) {
                // get last done thematic advance before move thematic list
                $last_done_thematic_advance = $thematicManager->get_last_done_thematic_advance($course, $session);
                // update done advances with de current thematic list
                if (!empty($last_done_thematic_advance)) {
                    $thematicManager->updateDoneThematicAdvance($last_done_thematic_advance, $course, $session);
                }
            }

            $redirectUrlParams = 'course_progress/index.php?'.api_get_cidreq().'&'.
                http_build_query(
                    [
                        'action' => 'thematic_advance_list',
                        'thematic_id' => $values['thematic_id'],
                    ]
                );

            Display::addFlash(Display::return_message(get_lang('Update successful')));
            header('Location: '.api_get_path(WEB_CODE_PATH).$redirectUrlParams);
            exit;
        }

        $content = $form->returnForm().$js;

        break;
    case 'thematic_advance_delete':
        if ($readOnly || !api_is_allowed_to_edit(null, true)) {
            api_not_allowed();
        }

        if (!empty($thematicAdvanceId)) {
            if (api_is_allowed_to_edit(null, true)) {
                $repo = Container::getThematicAdvanceRepository();
                $advance = $repo->find($thematicAdvanceId);
                if ($advance) {
                    $repo->delete($advance);
                }
            }
            Display::addFlash(Display::return_message(get_lang('Deleted')));
            header('Location: '.$currentUrl);
            exit;
        }
        break;
    case 'thematic_advance_list':
        $interbreadcrumb[] = [
            'url'  => '#',
            'name' => get_lang('Thematic advance').' ('.$cleanThematicTitle.')',
        ];

        // thematic advance list toolbar (local)
        $actions = '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=thematic_details">'.
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to')).'</a>';
        if (!$readOnly && api_is_allowed_to_edit(false, true)) {
            $actions .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=thematic_advance_add&thematic_id='.$thematicId.'"> '.
                Display::getMdiIcon('progress-clock', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('New thematic advance')).'</a>';
        }
        $content = Display::toolbarAction('thematic', [$actions]);

        $table = new SortableTable(
            'thematic_advance_list',
            ['Thematic', 'get_number_of_thematic_advances'],
            ['Thematic', 'get_thematic_advance_data']
        );

        $table->setDataFunctionParams(['thematic_id' => $thematicEntity->getIid()]);
        $table->set_header(0, '', false, ['style' => 'width:20px;']);
        $table->set_header(1, get_lang('Start Date'), false);
        $table->set_header(2, get_lang('Duration in hours'), false, ['style' => 'width:80px;']);
        $table->set_header(3, get_lang('Content'), false);

        if (!$readOnly && api_is_allowed_to_edit(null, true)) {
            $table->set_header(
                4,
                get_lang('Detail'),
                false,
                ['style' => 'text-align:center']
            );
        }
        $content .= $table->return_table();
        break;
}

$toggleUrl = api_get_self() . '?' . api_get_cidreq() . '&switch_student_view=' . ($isStudentView ? '0' : '1');
$toggleLabel = $isStudentView ? get_lang('Switch to teacher view') : get_lang('Switch to student view');
$toggleIcon  = $isStudentView ? 'account-tie' : 'account-school';

$studentViewBtn = '';
if (api_is_allowed_to_edit(null, true)) {
    $studentViewBtn = Display::toolbarButton(
        $toggleLabel,
        $toggleUrl,
        $toggleIcon,
        'default',
        []
    );
}

$toolbar = Display::toolbarAction('thematic-bar', [$actionLeft, $studentViewBtn]);
$tpl->assign('content', $content);
$tpl->assign('actions', $toolbar);
$tpl->display_one_col_template();
