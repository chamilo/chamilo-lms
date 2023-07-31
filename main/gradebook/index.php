<?php

/* For licensing terms, see /license.txt */

// $cidReset : This is the main difference with gradebook.php, here we say,
// basically, that we are inside a course, and many things depend from that
//$cidReset = false;
$_in_course = true;
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_GRADEBOOK;

api_block_anonymous_users();
api_protect_course_script(true);

$course_code = api_get_course_id();
$stud_id = api_get_user_id();
$session_id = api_get_session_id();
$course_id = api_get_course_int_id();
$courseInfo = api_get_course_info();

$action = isset($_GET['action']) ? $_GET['action'] : null;
$itemId = isset($_GET['itemId']) ? $_GET['itemId'] : 0;

switch ($action) {
    case 'generate_eval_stats':
        if (!empty($itemId)) {
            Evaluation::generateStats($itemId);
            Display::addFlash(Display::return_message(get_lang('Updated')));
        }
        header('Location: '.api_get_self().'?'.api_get_cidreq());
        exit;
        break;
    case 'generate_link_stats':
        if (!empty($itemId)) {
            $link = LinkFactory::create(LINK_EXERCISE);
            $links = $link::load($itemId);

            $exercise = new Exercise(api_get_course_int_id());
            /** @var ExerciseLink $link */
            foreach ($links as $link) {
                $exerciseId = $link->get_ref_id();
                $data = $link->get_exercise_data();
                if (empty($data)) {
                    continue;
                }

                $exerciseId = $data['id'];
                $result = $exercise->read($exerciseId);
                if ($result) {
                    $exercise->generateStats($exerciseId, api_get_course_info(), api_get_session_id());
                }
            }
            Display::addFlash(Display::return_message(get_lang('Updated')));
        }
        header('Location: '.api_get_self().'?'.api_get_cidreq());
        exit;
        break;
    case 'lock':
        $category_to_lock = Category::load($_GET['category_id']);
        $category_to_lock[0]->lockAllItems(1);
        $confirmation_message = get_lang('GradebookLockedAlert');
        break;
    case 'unlock':
        if (api_is_platform_admin()) {
            $category_to_lock = Category::load($_GET['category_id']);
            $category_to_lock[0]->lockAllItems(0);
            $confirmation_message = get_lang('EvaluationHasBeenUnLocked');
        }
        break;
    case 'export_table':
        $hidePdfReport = api_get_configuration_value('gradebook_hide_pdf_report_button');
        if ($hidePdfReport) {
            api_not_allowed(true);
        }
        if (isset($_GET['category_id'])) {
            $cats = Category::load($_GET['category_id'], null, null, null, null, null, false);
            GradebookUtils::generateTable($courseInfo, api_get_user_id(), $cats);
            exit;
        }
        break;
}

ob_start();

// Make sure the destination for scripts is index.php instead of gradebook.php
Category::setUrl('index.php');

$this_section = SECTION_COURSES;

$htmlHeadXtra[] = '<script>
var show_icon = "'.Display::returnIconPath('view_more_stats.gif').'";
var hide_icon = "'.Display::returnIconPath('view_less_stats.gif').'";

function confirmation() {
	if (confirm("'.get_lang('DeleteAll').'?")) {
		return true;
	} else {
		return false;
	}
}

$(function() {
    $("body").on("click", ".view_children", function() {
        var id = $(this).attr("data-cat-id");
        $(".hidden_"+id).removeClass("hidden");
        $(this).removeClass("view_children");
        $(this).find("img").attr("src", hide_icon);
        $(this).attr("class", "hide_children");
    });

    $("body").on("click", ".hide_children", function(event) {
        var id = $(this).attr("data-cat-id");
        $(".hidden_"+id).addClass("hidden");
        $(this).removeClass("hide_children");
        $(this).addClass("view_children");
        $(this).find("img").attr("src", show_icon);
    });

	for (i=0;i<$(".actions").length;i++) {
		if ($(".actions:eq("+i+")").html()=="<table border=\"0\"></table>" || $(".actions:eq("+i+")").html()=="" || $(".actions:eq("+i+")").html()==null || $(".actions:eq("+i+")").html().split("<TBODY></TBODY>").length==2) {
			$(".actions:eq("+i+")").hide();
		}
	}
});
</script>';

$list_actions = [];
$list_values = [];
if (isset($_GET['movecat'])) {
    $list_actions[] = 'movecat';
    $list_values[] = $_GET['movecat'];
}
if (isset($_GET['moveeval'])) {
    $list_actions[] = 'moveeval';
    $list_values[] = $_GET['moveeval'];
}
if (isset($_GET['movelink'])) {
    $list_actions[] = 'movelink';
    $list_values[] = $_GET['movelink'];
}
if (isset($_GET['visiblecat'])) {
    $list_actions[] = 'visiblecat';
    $list_values[] = $_GET['visiblecat'];
}
if (isset($_GET['deletecat'])) {
    $list_actions[] = 'deletecat';
    $list_values[] = $_GET['deletecat'];
}
if (isset($_GET['visibleeval'])) {
    $list_actions[] = 'visibleeval';
    $list_values[] = $_GET['visibleeval'];
}
if (isset($_GET['lockedeval'])) {
    $list_actions[] = 'lockedeval';
    $list_values[] = $_GET['lockedeval'];
}
if (isset($_GET['deleteeval'])) {
    $list_actions[] = 'deleteeval';
    $list_values[] = $_GET['deleteeval'];
}
if (isset($_GET['visiblelink'])) {
    $list_actions[] = 'visiblelink';
    $list_values[] = $_GET['visiblelink'];
}
if (isset($_GET['deletelink'])) {
    $list_actions[] = 'deletelink';
    $list_values[] = $_GET['deletelink'];
}
if (isset($_GET['action'])) {
    $list_actions[] = $_GET['action'];
}
$my_actions = implode(';', $list_actions);
$my_actions_values = implode(';', $list_values);
$logInfo = [
    'tool' => TOOL_GRADEBOOK,
    'action' => $my_actions,
    'action_details' => $my_actions_values,
];
Event::registerLog($logInfo);

$tbl_forum_thread = Database::get_course_table(TABLE_FORUM_THREAD);
$tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
$tbl_grade_links = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
$filter_confirm_msg = true;
$filter_warning_msg = true;
$courseInfo = api_get_course_info();

$cats = Category::load(
    null,
    null,
    $course_code,
    null,
    null,
    $session_id,
    'ORDER By id'
);
$first_time = null;

if (empty($cats)) {
    // first time
    $cats = Category::load(
        0,
        null,
        $course_code,
        null,
        null,
        $session_id,
        'ORDER By id'
    );
    $first_time = 1;
}

$selectCat = (int) $cats[0]->get_id();
$_GET['selectcat'] = $selectCat;

$isStudentView = api_is_student_view_active();
if ($selectCat > 0 && $isStudentView) {
    $interbreadcrumb[] = [
        'url' => 'index.php?selectcat=0&isStudentView=true',
        'name' => get_lang('ToolGradebook'),
    ];
}

// ACTIONS
//this is called when there is no data for the course admin
if (isset($_GET['createallcategories'])) {
    GradebookUtils::block_students();
    $coursecat = Category::get_not_created_course_categories($stud_id);
    if (0 == !count($coursecat)) {
        foreach ($coursecat as $row) {
            $cat = new Category();
            $cat->set_name($row[1]);
            $cat->set_course_code($row[0]);
            $cat->set_description(null);
            $cat->set_user_id($stud_id);
            $cat->set_parent_id(0);
            $cat->set_weight(0);
            $cat->set_visible(0);
            $cat->add();
            unset($cat);
        }
    }
    header('Location: '.Category::getUrl().'addallcat=&selectcat=0');
    exit;
}

//show logs evaluations
if (isset($_GET['visiblelog'])) {
    header('Location: '.api_get_self().'/gradebook_showlog_eval.php');
    exit;
}

//move a category
if (isset($_GET['movecat'])) {
    GradebookUtils::block_students();
    $moveCategoryId = isset($_GET['movecat']) ? (int) $_GET['movecat'] : 0;
    $cats = Category::load($moveCategoryId);
    if (!isset($_GET['targetcat'])) {
        $move_form = new CatForm(
            CatForm::TYPE_MOVE,
            $cats[0],
            'move_cat_form',
            null,
            api_get_self().'?movecat='.$moveCategoryId.'&selectcat='.$selectCat
        );
        if ($move_form->validate()) {
            header('Location: '.api_get_self().'?selectcat='.$selectCat
                .'&movecat='.$moveCategoryId.'&targetcat='.$move_form->exportValue('move_cat'));
            exit;
        }
    } else {
        $targetcat = Category::load($_GET['targetcat']);
        $course_to_crsind = ($cats[0]->get_course_code() != null && $targetcat[0]->get_course_code() == null);

        if (!($course_to_crsind && !isset($_GET['confirm']))) {
            $cats[0]->move_to_cat($targetcat[0]);
            header('Location: '.api_get_self().'?categorymoved=&selectcat='.$selectCat);
            exit;
        }
        unset($targetcat);
    }
    unset($cats);
}

//move an evaluation
if (isset($_GET['moveeval'])) {
    GradebookUtils::block_students();
    $evals = Evaluation::load($_GET['moveeval']);
    if (!isset($_GET['targetcat'])) {
        $move_form = new EvalForm(
            EvalForm::TYPE_MOVE,
            $evals[0],
            null,
            'move_eval_form',
            null,
            api_get_self().'?moveeval='.Security::remove_XSS($_GET['moveeval']).'&selectcat='.$selectCat
        );

        if ($move_form->validate()) {
            header('Location: '.api_get_self().'?selectcat='.$selectCat
                .'&moveeval='.Security::remove_XSS($_GET['moveeval'])
                .'&targetcat='.$move_form->exportValue('move_cat'));
            exit;
        }
    } else {
        $targetcat = Category::load($_GET['targetcat']);
        $course_to_crsind = $evals[0]->get_course_code() != null && $targetcat[0]->get_course_code() == null;

        if (!($course_to_crsind && !isset($_GET['confirm']))) {
            $evals[0]->move_to_cat($targetcat[0]);
            header('Location: '.api_get_self().'?evaluationmoved=&selectcat='.$selectCat);
            exit;
        }
        unset($targetcat);
    }
    unset($evals);
}

//move a link
if (isset($_GET['movelink'])) {
    $moveLink = (int) $_GET['movelink'];
    GradebookUtils::block_students();
    $link = LinkFactory::load($moveLink);
    $move_form = new LinkForm(
        LinkForm::TYPE_MOVE,
        null,
        $link[0],
        'move_link_form',
        null,
        api_get_self().'?movelink='.$moveLink.'&selectcat='.$selectCat.'&'.api_get_cidreq()
    );

    if ($move_form->validate()) {
        $targetcat = Category::load($move_form->exportValue('move_cat'));
        $link[0]->move_to_cat($targetcat[0]);
        header('Location: '.api_get_self().'?linkmoved=&selectcat='.$selectCat.'&'.api_get_cidreq());
        exit;
    }
}

// Parameters for categories.
if (isset($_GET['visiblecat'])) {
    GradebookUtils::block_students();
    $visibility_command = 0;
    if (isset($_GET['set_visible'])) {
        $visibility_command = 1;
    }
    $cats = Category::load($_GET['visiblecat']);
    $cats[0]->set_visible($visibility_command);
    $cats[0]->save();
    $cats[0]->apply_visibility_to_children();
    unset($cats);
    if ($visibility_command) {
        $confirmation_message = get_lang('ViMod');
        $filter_confirm_msg = false;
    } else {
        $confirmation_message = get_lang('InViMod');
        $filter_confirm_msg = false;
    }
}

if (isset($_GET['deletecat'])) {
    GradebookUtils::block_students();
    $cats = Category::load($_GET['deletecat']);
    if (isset($cats[0])) {
        // Delete all categories,subcategories and results
        if ($cats[0] != null) {
            if ($cats[0]->get_id() != 0) {
                // better don't try to delete the root...
                $cats[0]->delete_all();
            }
        }
    }
    $confirmation_message = get_lang('CategoryDeleted');
    $filter_confirm_msg = false;
}

// Parameters for evaluations.
if (isset($_GET['visibleeval'])) {
    GradebookUtils::block_students();
    $visibility_command = 0;
    if (isset($_GET['set_visible'])) {
        $visibility_command = 1;
    }
    $eval = Evaluation::load($_GET['visibleeval']);
    $eval[0]->set_visible($visibility_command);
    $eval[0]->save();
    unset($eval);
    if ($visibility_command) {
        $confirmation_message = get_lang('ViMod');
        $filter_confirm_msg = false;
    } else {
        $confirmation_message = get_lang('InViMod');
        $filter_confirm_msg = false;
    }
}

// Parameters for evaluations.
if (isset($_GET['lockedeval'])) {
    GradebookUtils::block_students();
    $locked = (int) $_GET['lockedeval'];
    $type_locked = 1;
    $confirmation_message = get_lang('EvaluationHasBeenLocked');
    if (isset($_GET['typelocked']) && api_is_platform_admin()) {
        $type_locked = 0;
        $confirmation_message = get_lang('EvaluationHasBeenUnLocked');
    }
    $eval = Evaluation::load($locked);
    if ($eval[0] != null) {
        $eval[0]->lock($type_locked);
    }

    $filter_confirm_msg = false;
}

if (isset($_GET['deleteeval'])) {
    GradebookUtils::block_students();
    $eval = Evaluation::load($_GET['deleteeval']);
    if ($eval[0] != null) {
        $eval[0]->delete_with_results();
    }
    $confirmation_message = get_lang('GradebookEvaluationDeleted');
    $filter_confirm_msg = false;
}

// Parameters for links.
if (isset($_GET['visiblelink'])) {
    GradebookUtils::block_students();
    $visibility_command = 0;
    if (isset($_GET['set_visible'])) {
        $visibility_command = 1;
    }
    $link = LinkFactory::load($_GET['visiblelink']);
    if (isset($link) && isset($link[0])) {
        $link[0]->set_visible($visibility_command);
        $link[0]->save();
    }
    unset($link);
    if ($visibility_command) {
        $confirmation_message = get_lang('ViMod');
        $filter_confirm_msg = false;
    } else {
        $confirmation_message = get_lang('InViMod');
        $filter_confirm_msg = false;
    }
}

if (isset($_GET['deletelink'])) {
    GradebookUtils::block_students();
    $get_delete_link = (int) $_GET['deletelink'];
    //fixing #5229
    if (!empty($get_delete_link)) {
        $link = LinkFactory::load($get_delete_link);
        if ($link[0] != null) {
            // Clean forum qualify
            $sql = 'UPDATE '.$tbl_forum_thread.' SET
                        thread_qualify_max = 0,
                        thread_weight = 0,
                        thread_title_qualify = ""
					WHERE c_id = '.$course_id.' AND thread_id = (
					    SELECT ref_id FROM '.$tbl_grade_links.'
					    WHERE id='.$get_delete_link.' AND type = '.LINK_FORUM_THREAD.'
                    )';
            Database::query($sql);
            // clean attendance
            $sql = 'UPDATE '.$tbl_attendance.' SET
                        attendance_weight = 0,
                        attendance_qualify_title = ""
				 	WHERE c_id = '.$course_id.' AND id = (
				 	    SELECT ref_id FROM '.$tbl_grade_links.'
				 	    WHERE id='.$get_delete_link.' AND type = '.LINK_ATTENDANCE.'
                    )';
            Database::query($sql);
            $link[0]->delete();
        }
        unset($link);
        $confirmation_message = get_lang('LinkDeleted');
        $filter_confirm_msg = false;
    }
}

if (!empty($course_to_crsind) && !isset($_GET['confirm'])) {
    GradebookUtils::block_students();
    if (!isset($_GET['movecat']) && !isset($_GET['moveeval'])) {
        exit('Error: movecat or moveeval not defined');
    }
    $button = '<form name="confirm" method="post" action="'.api_get_self().'?confirm='
        .(isset($_GET['movecat']) ? '&movecat='.$moveCategoryId
            : '&moveeval='.intval($_GET['moveeval'])).'&selectcat='.$selectCat.'&targetcat='.intval($_GET['targetcat']).'">
			   <input type="submit" value="'.get_lang('Ok').'">
			   </form>';
    $warning_message = get_lang('MoveWarning').'<br><br>'.$button;
    $filter_warning_msg = false;
}

// Actions on the sortabletable.
if (isset($_POST['action'])) {
    GradebookUtils::block_students();
    $number_of_selected_items = count($_POST['id']);

    if ($number_of_selected_items == 0) {
        $warning_message = get_lang('NoItemsSelected');
        $filter_warning_msg = false;
    } else {
        switch ($_POST['action']) {
            case 'deleted':
                $number_of_deleted_categories = 0;
                $number_of_deleted_evaluations = 0;
                $number_of_deleted_links = 0;
                foreach ($_POST['id'] as $indexstr) {
                    if (substr($indexstr, 0, 4) == 'CATE') {
                        $cats = Category::load(substr($indexstr, 4));
                        if ($cats[0] != null) {
                            $cats[0]->delete_all();
                        }
                        $number_of_deleted_categories++;
                    }
                    if (substr($indexstr, 0, 4) == 'EVAL') {
                        $eval = Evaluation::load(substr($indexstr, 4));
                        if ($eval[0] != null) {
                            $eval[0]->delete_with_results();
                        }

                        $number_of_deleted_evaluations++;
                    }
                    if (substr($indexstr, 0, 4) == 'LINK') {
                        //fixing #5229
                        $id = substr($indexstr, 4);
                        if (!empty($id)) {
                            $link = LinkFactory::load($id);
                            if ($link[0] != null) {
                                $link[0]->delete();
                            }
                            $number_of_deleted_links++;
                        }
                    }
                }

                $confirmation_message =
                    get_lang('DeletedCategories').' : <b>'.$number_of_deleted_categories.'</b><br />'.
                    get_lang('DeletedEvaluations').' : <b>'.$number_of_deleted_evaluations.'</b><br />'.
                    get_lang('DeletedLinks').' : <b>'.$number_of_deleted_links.'</b><br /><br />'.
                    get_lang('TotalItems').' : <b>'.$number_of_selected_items.'</b>';
                $filter_confirm_msg = false;
                break;
            case 'setvisible':
                foreach ($_POST['id'] as $indexstr) {
                    if (substr($indexstr, 0, 4) == 'CATE') {
                        $cats = Category::load(substr($indexstr, 4));
                        $cats[0]->set_visible(1);
                        $cats[0]->save();
                        $cats[0]->apply_visibility_to_children();
                    }
                    if (substr($indexstr, 0, 4) == 'EVAL') {
                        $eval = Evaluation::load(substr($indexstr, 4));
                        $eval[0]->set_visible(1);
                        $eval[0]->save();
                    }
                    if (substr($indexstr, 0, 4) == 'LINK') {
                        $link = LinkFactory::load(substr($indexstr, 4));
                        $link[0]->set_visible(1);
                        $link[0]->save();
                    }
                }
                $confirmation_message = get_lang('ItemsVisible');
                $filter_confirm_msg = false;
                break;
            case 'setinvisible':
                foreach ($_POST['id'] as $indexstr) {
                    if (substr($indexstr, 0, 4) == 'CATE') {
                        $cats = Category::load(substr($indexstr, 4));
                        $cats[0]->set_visible(0);
                        $cats[0]->save();
                        $cats[0]->apply_visibility_to_children();
                    }
                    if (substr($indexstr, 0, 4) == 'EVAL') {
                        $eval = Evaluation::load(substr($indexstr, 4));
                        $eval[0]->set_visible(0);
                        $eval[0]->save();
                    }
                    if (substr($indexstr, 0, 4) == 'LINK') {
                        $link = LinkFactory::load(substr($indexstr, 4));
                        $link[0]->set_visible(0);
                        $link[0]->save();
                    }
                }
                $confirmation_message = get_lang('ItemsInVisible');
                $filter_confirm_msg = false;
                break;
        }
    }
}

if (isset($_POST['submit']) && isset($_POST['keyword'])) {
    header('Location: '.api_get_self().'?selectcat='.$selectCat.'&search='.Security::remove_XSS($_POST['keyword']));
    exit;
}

if (isset($_GET['categorymoved'])) {
    Display::addFlash(Display::return_message(get_lang('CategoryMoved'), 'confirmation', false));
}
if (isset($_GET['evaluationmoved'])) {
    Display::addFlash(Display::return_message(get_lang('EvaluationMoved'), 'confirmation', false));
}
if (isset($_GET['linkmoved'])) {
    Display::addFlash(Display::return_message(get_lang('LinkMoved'), 'confirmation', false));
}
if (isset($_GET['addcat'])) {
    Display::addFlash(Display::return_message(get_lang('CategoryAdded'), 'confirmation', false));
}
if (isset($_GET['linkadded'])) {
    Display::addFlash(Display::return_message(get_lang('LinkAdded'), 'confirmation', false));
}
if (isset($_GET['addresult'])) {
    Display::addFlash(Display::return_message(get_lang('ResultAdded'), 'confirmation', false));
}
if (isset($_GET['editcat'])) {
    Display::addFlash(Display::return_message(get_lang('CategoryEdited'), 'confirmation', false));
}
if (isset($_GET['editeval'])) {
    Display::addFlash(Display::return_message(get_lang('EvaluationEdited'), 'confirmation', false));
}
if (isset($_GET['linkedited'])) {
    Display::addFlash(Display::return_message(get_lang('LinkEdited'), 'confirmation', false));
}
if (isset($_GET['nolinkitems'])) {
    Display::addFlash(Display::return_message(get_lang('NoLinkItems'), 'warning', false));
}
if (isset($_GET['addallcat'])) {
    Display::addFlash(Display::return_message(get_lang('AddAllCat'), 'normal', false));
}
if (isset($confirmation_message)) {
    Display::addFlash(Display::return_message($confirmation_message, 'confirmation', $filter_confirm_msg));
}
if (isset($warning_message)) {
    Display::addFlash(Display::return_message($warning_message, 'warning', $filter_warning_msg));
}
if (isset($move_form)) {
    Display::addFlash(Display::return_message($move_form->toHtml(), 'normal', false));
}

$viewTitle = '';
// DISPLAY HEADERS AND MESSAGES
if (!isset($_GET['exportpdf'])) {
    if (isset($_GET['studentoverview'])) {
        $interbreadcrumb[] = [
            'url' => Category::getUrl().'selectcat='.$selectCat,
            'name' => get_lang('ToolGradebook'),
        ];
        $viewTitle = get_lang('FlatView');
    } elseif (isset($_GET['search'])) {
        $interbreadcrumb[] = [
            'url' => Category::getUrl().'selectcat='.$selectCat,
            'name' => get_lang('ToolGradebook'),
        ];
        $viewTitle = get_lang('SearchResults');
    } elseif (!empty($selectCat)) {
        $interbreadcrumb[] = [
            'url' => '#',
            'name' => get_lang('ToolGradebook'),
        ];
    } else {
        $viewTitle = get_lang('ToolGradebook');
    }
}

if (api_get_configuration_value('allow_skill_rel_items') == true) {
    $htmlContentExtraClass[] = 'feature-item-user-skill-on';
}

// LOAD DATA & DISPLAY TABLE
$is_platform_admin = api_is_platform_admin();
$is_course_admin = api_is_allowed_to_edit(null, true);
$simple_search_form = '';

if (isset($_GET['studentoverview'])) {
    //@todo this code also seems to be deprecated ...
    $cats = Category::load($selectCat);
    $stud_id = api_is_allowed_to_edit() ? null : $stud_id;
    $allcat = $cats[0]->get_subcategories($stud_id, $course_code, $session_id);
    $alleval = $cats[0]->get_evaluations($stud_id, true);
    $alllink = $cats[0]->get_links($stud_id, true);
    if (isset($_GET['exportpdf'])) {
        $datagen = new GradebookDataGenerator($allcat, $alleval, $alllink);
        $header_names = [
            get_lang('Name'),
            get_lang('Description'),
            get_lang('Weight'),
            get_lang('Date'),
            get_lang('Results'),
        ];
        $data_array = $datagen->get_data(
            GradebookDataGenerator::GDG_SORT_NAME,
            0,
            null,
            true
        );
        $newarray = [];
        foreach ($data_array as $data) {
            $newarray[] = array_slice($data, 1);
        }
        $pdf = new Cezpdf();
        $pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
        $pdf->ezSetMargins(30, 30, 50, 30);
        $pdf->ezSetY(810);
        $pdf->ezText(
            get_lang('FlatView').' ('.api_convert_and_format_date(
                null,
                DATE_FORMAT_SHORT
            ).' '.api_convert_and_format_date(null, TIME_NO_SEC_FORMAT).')',
            12,
            ['justification' => 'center']
        );
        $pdf->line(50, 790, 550, 790);
        $pdf->line(50, 40, 550, 40);
        $pdf->ezSetY(750);
        $pdf->ezTable(
            $newarray,
            $header_names,
            '',
            [
                'showHeadings' => 1,
                'shaded' => 1,
                'showLines' => 1,
                'rowGap' => 3,
                'width' => 500,
            ]
        );
        $pdf->ezStream();
        exit;
    }
} else {
    // Student view

    // In any other case (no search, no pdf), print the available gradebooks
    // Important note: loading a category will actually load the *contents* of
    // this category. This means that, to show the categories of a course,
    // we have to show the root category and show its subcategories that
    // are inside this course. This is done at the time of calling
    // $cats[0]->get_subcategories(), not at the time of doing Category::load()
    // $category comes from GET['selectcat']

    // if $category = 0 (which happens when GET['selectcat'] is undefined)
    // then Category::load() will create a new 'root' category with empty
    // course and session fields in memory (Category::create_root_category())

    $cats = Category::load(
        null,
        null,
        $course_code,
        null,
        null,
        $session_id,
        false
    );

    if (empty($cats)) {
        // There is no category for this course+session, so create one
        $cat = new Category();
        if (!empty($session_id)) {
            $sessionName = api_get_session_name($session_id);
            $cat->set_name($course_code.' - '.get_lang('Session').' '.$sessionName);
            $cat->set_session_id($session_id);
        } else {
            $cat->set_name($course_code);
            $cat->setIsRequirement(1);
        }
        $cat->set_course_code($course_code);
        $cat->set_description(null);
        $cat->set_user_id($stud_id);
        $cat->set_parent_id(0);
        $cat->set_weight(100);
        $cat->set_visible(0);
        $cat->set_certificate_min_score(75);
        $can_edit = api_is_allowed_to_edit(true, true);
        if ($can_edit) {
            $cat->add();
        }
        unset($cat);
    }

    $cats = Category::load($selectCat, null, null, null, null, null, false);
    // With this fix the teacher only can view 1 gradebook
    if (api_is_platform_admin()) {
        $stud_id = api_is_allowed_to_edit() ? null : api_get_user_id();
    }

    $allcat = $cats[0]->get_subcategories($stud_id, $course_code, $session_id);
    $alleval = $cats[0]->get_evaluations($stud_id);
    $alllink = $cats[0]->get_links($stud_id);
}

// add params to the future links (in the table shown)
$addparams = ['selectcat' => $selectCat];
if (isset($_GET['studentoverview'])) {
    $addparams['studentoverview'] = '';
}

if (isset($_GET['cidReq']) && $_GET['cidReq'] != '') {
    $addparams['cidReq'] = Security::remove_XSS($_GET['cidReq']);
} else {
    $addparams['cidReq'] = '';
}

$no_qualification = false;

// Show certificate link.
$certificate = [];
$actionsLeft = '';
$hideCertificateExport = api_get_setting('hide_certificate_export_link') === 'true';
$hideCertificateExportStudent = api_is_student() && api_get_setting('hide_certificate_export_link_students') === 'true';
if (!empty($selectCat)) {
    $cat = new Category();
    $course_id = CourseManager::get_course_by_category($selectCat);
    $show_message = $cat->show_message_resource_delete($course_id);
    if ('' == $show_message) {
        // Student
        if (!api_is_allowed_to_edit() && !api_is_excluded_user_type()) {
            $certificate = Category::generateUserCertificate(
                $selectCat,
                $stud_id
            );

            if (isset($certificate['pdf_url'])) {
                if (!$hideCertificateExport && !$hideCertificateExportStudent) {
                    $actionsLeft .= Display::url(
                        Display::returnFontAwesomeIcon('file-pdf-o').get_lang('DownloadCertificatePdf'),
                        $certificate['pdf_url'],
                        ['class' => 'btn btn-default']
                    );
                }
            }

            $currentScore = Category::getCurrentScore(
                $stud_id,
                $cats[0],
                true
            );
            Category::registerCurrentScore($currentScore, $stud_id, $selectCat);
        }
    }
}

if (!api_is_allowed_to_edit(null, true)) {
    $allowButton = api_get_configuration_value('gradebook_hide_pdf_report_button') === false;
    if ($allowButton) {
        $actionsLeft .= Display::url(
            Display::returnFontAwesomeIcon('file-pdf-o').get_lang('DownloadReportPdf'),
            api_get_self().'?action=export_table&'.api_get_cidreq().'&category_id='.$selectCat,
            ['class' => 'btn btn-default']
        );
    }
}

if (isset($first_time) && $first_time == 1 && api_is_allowed_to_edit(null, true)) {
    echo '<meta http-equiv="refresh" content="0;url='.api_get_self().'?'.api_get_cidreq().'" />';
} else {
    Display::display_introduction_section(
        TOOL_GRADEBOOK,
        ['ToolbarSet' => 'AssessmentsIntroduction']
    );

    if (!empty($actionsLeft)) {
        echo $toolbar = Display::toolbarAction(
            'gradebook-student-actions',
            [$actionsLeft]
        );
    }

    $cats = Category::load(
        null,
        null,
        $course_code,
        null,
        null,
        $session_id,
        false
    );

    if (!empty($cats)) {
        if ((api_get_setting('gradebook_enable_grade_model') === 'true') &&
            (
                api_is_platform_admin() || (
                    api_is_allowed_to_edit(null, true) &&
                    api_get_setting('teachers_can_change_grade_model_settings') === 'true'
                )
            )
        ) {
            // Getting grade models.
            $obj = new GradeModel();
            $grade_models = $obj->get_all();
            $grade_model_id = $cats[0]->get_grade_model_id();
            // No children.
            if ((count($cats) == 1 && empty($grade_model_id)) ||
                (count($cats) == 1 && $grade_model_id != -1)
            ) {
                if (!empty($grade_models)) {
                    $form_grade = new FormValidator('grade_model_settings');
                    $obj->fill_grade_model_select_in_form($form_grade, 'grade_model_id', $grade_model_id);
                    $form_grade->addButtonSave(get_lang('Save'));

                    if ($form_grade->validate()) {
                        $value = $form_grade->exportValue('grade_model_id');

                        $gradebook = new Gradebook();
                        $gradebook->update(['id' => $cats[0]->get_id(), 'grade_model_id' => $value], true);

                        //do something
                        $obj = new GradeModel();
                        $components = $obj->get_components($value);

                        foreach ($components as $component) {
                            $gradebook = new Gradebook();
                            $params = [];
                            $params['name'] = $component['acronym'];
                            $params['description'] = $component['title'];
                            $params['user_id'] = api_get_user_id();
                            $params['parent_id'] = $cats[0]->get_id();
                            $params['weight'] = $component['percentage'];
                            $params['session_id'] = api_get_session_id();
                            $params['course_code'] = api_get_course_id();
                            $params['grade_model_id'] = api_get_session_id();

                            $gradebook->save($params);
                        }

                        // Reloading cats
                        $cats = Category::load(
                            null,
                            null,
                            $course_code,
                            null,
                            null,
                            $session_id,
                            false
                        );
                    } else {
                        $form_grade->display();
                    }
                }
            }
        }

        $i = 0;
        $allcat = [];
        $model = ExerciseLib::getCourseScoreModel();
        $allowGraph = api_get_configuration_value('gradebook_hide_graph') === false;
        $isAllowed = api_is_allowed_to_edit(null, true);
        $settings = api_get_configuration_value('gradebook_pdf_export_settings');
        $showFeedBack = true;
        if (isset($settings['hide_feedback_textarea']) && $settings['hide_feedback_textarea']) {
            $showFeedBack = false;
        }

        $allowTable = api_get_configuration_value('gradebook_hide_table') === false;

        /** @var Category $cat */
        foreach ($cats as $cat) {
            $allcat = $cat->get_subcategories($stud_id, $course_code, $session_id);
            $alleval = $cat->get_evaluations($stud_id, false, $course_code, $session_id);
            $alllink = $cat->get_links($stud_id, true, $course_code, $session_id);

            if ($cat->get_parent_id() != 0) {
                $i++;
            } else {
                // This is the father
                // Create gradebook/add gradebook links.
                DisplayGradebook::header(
                    $cat,
                    0,
                    $cat->get_id(),
                    $is_course_admin,
                    $is_platform_admin,
                    $simple_search_form,
                    false,
                    true,
                    $certificate
                );

                if ($isAllowed && api_get_setting('gradebook_enable_grade_model') === 'true') {
                    // Showing the grading system
                    if (!empty($grade_models[$grade_model_id])) {
                        echo Display::return_message(
                            get_lang('GradeModel').': '.$grade_models[$grade_model_id]['name']
                        );
                    }
                }

                $exportToPdf = false;
                if ($action === 'export_table') {
                    $exportToPdf = true;
                }

                $loadStats = $isAllowed ? [] : GradebookTable::getExtraStatsColumnsToDisplay();

                $gradebookTable = new GradebookTable(
                    $cat,
                    $allcat,
                    $alleval,
                    $alllink,
                    $addparams,
                    $exportToPdf,
                    null,
                    api_get_user_id(),
                    [],
                    $loadStats
                );

                if ($isAllowed) {
                    $gradebookTable->td_attributes = [
                        4 => 'class="text-center"',
                    ];
                }

                $table = '';
                if ($isAllowed) {
                    $table = $gradebookTable->return_table();
                } else {
                    if ($allowTable) {
                        $table = $gradebookTable->return_table();
                    }
                }

                $graph = '';
                if ($allowGraph && empty($model)) {
                    $graph = $gradebookTable->getGraph();
                }

                if ($action === 'export_table') {
                    ob_clean();
                    $params = [
                        'pdf_title' => sprintf(get_lang('GradeFromX'), $courseInfo['name']),
                        'course_code' => api_get_course_id(),
                        'session_info' => '',
                        'course_info' => '',
                        'pdf_date' => '',
                        'student_info' => api_get_user_info(),
                        'show_grade_generated_date' => true,
                        'show_real_course_teachers' => false,
                        'show_teacher_as_myself' => false,
                        'orientation' => 'P',
                    ];
                    $feedback = '';
                    if ($showFeedBack) {
                        $feedback = '<br />'.get_lang('Feedback').'<br />
                                      <textarea rows="5" cols="100" >&nbsp;</textarea>';
                    }
                    $pdf = new PDF('A4', $params['orientation'], $params);
                    $pdf->html_to_pdf_with_template($table.$graph.$feedback);
                } else {
                    echo $table;
                    echo $graph;
                }
            }
        }
    }
}

api_set_in_gradebook();
$contents = ob_get_contents();
ob_end_clean();

$view = new Template($viewTitle);
$view->assign('content', $contents);
$view->display_one_col_template();
