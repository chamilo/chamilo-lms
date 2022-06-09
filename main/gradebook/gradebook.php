<?php
/* For licensing terms, see /license.txt */

// $cidReset : This is the main difference with gradebook.php, here we say,
// basically, that we are inside a course, and many things depend from that
$cidReset = true;
$_in_course = false;
//make sure the destination for scripts is index.php instead of gradebook.php
require_once __DIR__.'/../inc/global.inc.php';
if (!empty($_GET['course'])) {
    Category::setUrl('index.php');
    $this_section = SECTION_COURSES;
} else {
    Category::setUrl('gradebook.php');
    $this_section = SECTION_MYGRADEBOOK;
    unset($_GET['course']);
}

$selectcat = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;

$htmlHeadXtra[] = '<script>
$(function() {
	for (i=0;i<$(".actions").length;i++) {
		if ($(".actions:eq("+i+")").html()=="<table border=\"0\"></table>" || $(".actions:eq("+i+")").html()=="" || $(".actions:eq("+i+")").html()==null) {
			$(".actions:eq("+i+")").hide();
		}
	}
});
</script>';
api_block_anonymous_users();

$htmlHeadXtra[] = '<script>
function confirmation () {
	if (confirm("'.get_lang('DeleteAll').'?")) {
	    return true;
	} else {
	    return false;
	}
}
</script>';
$filter_confirm_msg = true;
$filter_warning_msg = true;
// ACTIONS

//this is called when there is no data for the course admin
if (isset($_GET['createallcategories'])) {
    GradebookUtils::block_students();
    $coursecat = Category::get_not_created_course_categories(api_get_user_id());
    if (!count($coursecat) == 0) {
        foreach ($coursecat as $row) {
            $cat = new Category();
            $cat->set_name($row[1]);
            $cat->set_course_code($row[0]);
            $cat->set_description(null);
            $cat->set_user_id(api_get_user_id());
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

//move a category
if (isset($_GET['movecat'])) {
    $move_cat = (int) $_GET['movecat'];
    GradebookUtils::block_students();
    $cats = Category::load($move_cat);
    if (!isset($_GET['targetcat'])) {
        $move_form = new CatForm(
            CatForm::TYPE_MOVE,
            $cats[0],
            'move_cat_form',
            null,
            api_get_self().'?movecat='.$move_cat.'&selectcat='.$selectcat
        );
        if ($move_form->validate()) {
            header('Location: '.api_get_self().'?selectcat='.$selectcat
                .'&movecat='.$move_cat
                .'&targetcat='.$move_form->exportValue('move_cat'));
            exit;
        }
    } else {
        $get_target_cat = Security::remove_XSS($_GET['targetcat']);
        $targetcat = Category::load($get_target_cat);
        $course_to_crsind = ($cats[0]->get_course_code() != null && $targetcat[0]->get_course_code() == null);

        if (!($course_to_crsind && !isset($_GET['confirm']))) {
            $cats[0]->move_to_cat($targetcat[0]);
            header('Location: '.api_get_self().'?categorymoved=&selectcat='.$selectcat);
            exit;
        }
        unset($targetcat);
    }
    unset($cats);
}

//move an evaluation
if (isset($_GET['moveeval'])) {
    GradebookUtils::block_students();
    $get_move_eval = Security::remove_XSS($_GET['moveeval']);
    $evals = Evaluation::load($get_move_eval);
    if (!isset($_GET['targetcat'])) {
        $move_form = new EvalForm(
            EvalForm::TYPE_MOVE,
            $evals[0],
            null,
            'move_eval_form',
            null,
            api_get_self().'?moveeval='.$get_move_eval.'&selectcat='.$selectcat
        );

        if ($move_form->validate()) {
            header('Location: '.api_get_self().'?selectcat='.$selectcat
                .'&moveeval='.$get_move_eval
                .'&targetcat='.$move_form->exportValue('move_cat'));
            exit;
        }
    } else {
        $get_target_cat = Security::remove_XSS($_GET['targetcat']);
        $targetcat = Category::load($get_target_cat);
        $course_to_crsind = ($evals[0]->get_course_code() != null && $targetcat[0]->get_course_code() == null);

        if (!($course_to_crsind && !isset($_GET['confirm']))) {
            $evals[0]->move_to_cat($targetcat[0]);
            header('Location: '.api_get_self().'?evaluationmoved=&selectcat='.$selectcat);
            exit;
        }
        unset($targetcat);
    }
    unset($evals);
}

// Move a link
if (isset($_GET['movelink'])) {
    GradebookUtils::block_students();
    $get_move_link = Security::remove_XSS($_GET['movelink']);
    $link = LinkFactory::load($get_move_link);
    $move_form = new LinkForm(
        LinkForm::TYPE_MOVE,
        null,
        $link[0],
        'move_link_form',
        null,
        api_get_self().'?movelink='.$get_move_link.'&selectcat='.$selectcat
    );
    if ($move_form->validate()) {
        $targetcat = Category::load($move_form->exportValue('move_cat'));
        $link[0]->move_to_cat($targetcat[0]);
        unset($link);
        header('Location: '.api_get_self().'?linkmoved=&selectcat='.$selectcat);
        exit;
    }
}

//parameters for categories
if (isset($_GET['visiblecat'])) {
    GradebookUtils::block_students();
    if (isset($_GET['set_visible'])) {
        $visibility_command = 1;
    } else {
        $visibility_command = 0;
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
    //delete all categories,subcategories and results
    if ($cats[0] != null) {
        if ($cats[0]->get_id() != 0) {
            // better don't try to delete the root...
            $cats[0]->delete_all();
        }
    }
    $confirmation_message = get_lang('CategoryDeleted');
    $filter_confirm_msg = false;
}

//parameters for evaluations
if (isset($_GET['visibleeval'])) {
    GradebookUtils::block_students();
    if (isset($_GET['set_visible'])) {
        $visibility_command = 1;
    } else {
        $visibility_command = 0;
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

if (isset($_GET['deleteeval'])) {
    GradebookUtils::block_students();
    $eval = Evaluation::load($_GET['deleteeval']);
    if ($eval[0] != null) {
        $eval[0]->delete_with_results();
    }
    $confirmation_message = get_lang('GradebookEvaluationDeleted');
    $filter_confirm_msg = false;
}
//parameters for links
if (isset($_GET['visiblelink'])) {
    GradebookUtils::block_students();
    if (isset($_GET['set_visible'])) {
        $visibility_command = 1;
    } else {
        $visibility_command = 0;
    }
    $link = LinkFactory::load($_GET['visiblelink']);
    $link[0]->set_visible($visibility_command);
    $link[0]->save();
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
    //fixing #5229
    if (!empty($_GET['deletelink'])) {
        $link = LinkFactory::load($_GET['deletelink']);
        if ($link[0] != null) {
            $link[0]->delete();
        }
        unset($link);
        $confirmation_message = get_lang('LinkDeleted');
        $filter_confirm_msg = false;
    }
}
$course_to_crsind = isset($course_to_crsind) ? $course_to_crsind : '';
if ($course_to_crsind && !isset($_GET['confirm'])) {
    GradebookUtils::block_students();
    if (!isset($_GET['movecat']) && !isset($_GET['moveeval'])) {
        api_not_allowed(true);
    }
    $button = '<form name="confirm"
                 method="post"
                 action="'.api_get_self().'?confirm='
        .(isset($_GET['movecat']) ? '&movecat='.Security::remove_XSS($_GET['movecat'])
            : '&moveeval='.intval($_GET['moveeval'])).'&selectcat='.$selectcat.'&targetcat='.Security::remove_XSS($_GET['targetcat']).'">
			   <input type="submit" value="'.'  '.get_lang('Ok').'  '.'">
			   </form>';

    $warning_message = get_lang('MoveWarning').'<br><br>'.$button;
    $filter_warning_msg = false;
}

//actions on the sortabletable
if (isset($_POST['action'])) {
    GradebookUtils::block_students();
    $number_of_selected_items = count($_POST['id']);
    if ($number_of_selected_items == '0') {
        $warning_message = get_lang('NoItemsSelected');
        $filter_warning_msg = false;
    } else {
        switch ($_POST['action']) {
            case 'deleted':
                $number_of_deleted_categories = 0;
                $number_of_deleted_evaluations = 0;
                $number_of_deleted_links = 0;
                foreach ($_POST['id'] as $indexstr) {
                    if (api_substr($indexstr, 0, 4) == 'CATE') {
                        $cats = Category::load(api_substr($indexstr, 4));
                        if ($cats[0] != null) {
                            $cats[0]->delete_all();
                        }
                        $number_of_deleted_categories++;
                    }
                    if (api_substr($indexstr, 0, 4) == 'EVAL') {
                        $eval = Evaluation::load(api_substr($indexstr, 4));
                        if ($eval[0] != null) {
                            $eval[0]->delete_with_results();
                        }
                        $number_of_deleted_evaluations++;
                    }
                    if (api_substr($indexstr, 0, 4) == 'LINK') {
                        $id = api_substr($indexstr, 4);
                        if (!empty($id)) {
                            $link = LinkFactory::load();
                            if ($link[0] != null) {
                                $link[0]->delete();
                            }
                            $number_of_deleted_links++;
                        }
                    }
                }
                $confirmation_message = get_lang('DeletedCategories').' : <b>'.$number_of_deleted_categories.'</b><br />'.get_lang('DeletedEvaluations').' : <b>'.$number_of_deleted_evaluations.'</b><br />'.get_lang('DeletedLinks').' : <b>'.$number_of_deleted_links.'</b><br /><br />'.get_lang('TotalItems').' : <b>'.$number_of_selected_items.'</b>';
                $filter_confirm_msg = false;
                break;
            case 'setvisible':
                foreach ($_POST['id'] as $indexstr) {
                    if (api_substr($indexstr, 0, 4) == 'CATE') {
                        $cats = Category::load(api_substr($indexstr, 4));
                        $cats[0]->set_visible(1);
                        $cats[0]->save();
                        $cats[0]->apply_visibility_to_children();
                    }
                    if (api_substr($indexstr, 0, 4) == 'EVAL') {
                        $eval = Evaluation::load(api_substr($indexstr, 4));
                        $eval[0]->set_visible(1);
                        $eval[0]->save();
                    }
                    if (api_substr($indexstr, 0, 4) == 'LINK') {
                        $link = LinkFactory::load(api_substr($indexstr, 4));
                        $link[0]->set_visible(1);
                        $link[0]->save();
                    }
                }
                $confirmation_message = get_lang('ItemsVisible');
                $filter_confirm_msg = false;
                break;
            case 'setinvisible':
                foreach ($_POST['id'] as $indexstr) {
                    if (api_substr($indexstr, 0, 4) == 'CATE') {
                        $cats = Category::load(api_substr($indexstr, 4));
                        $cats[0]->set_visible(0);
                        $cats[0]->save();
                        $cats[0]->apply_visibility_to_children();
                    }
                    if (api_substr($indexstr, 0, 4) == 'EVAL') {
                        $eval = Evaluation::load(api_substr($indexstr, 4));
                        $eval[0]->set_visible(0);
                        $eval[0]->save();
                    }
                    if (api_substr($indexstr, 0, 4) == 'LINK') {
                        $link = LinkFactory::load(api_substr($indexstr, 4));
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
    header('Location: '.api_get_self().'?selectcat='.$selectcat
        .'&search='.Security::remove_XSS($_POST['keyword']));
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

// DISPLAY HEADERS AND MESSAGES                           -
if (!isset($_GET['exportpdf']) && !isset($_GET['export_certificate'])) {
    if (isset($_GET['studentoverview'])) {
        $interbreadcrumb[] = [
            'url' => Category::getUrl().'selectcat='.$selectcat,
            'name' => get_lang('ToolGradebook'),
        ];
        Display::display_header(get_lang('FlatView'));
    } elseif (isset($_GET['search'])) {
        $interbreadcrumb[] = [
            'url' => Category::getUrl(),
            'name' => get_lang('Gradebook'),
        ];

        if ((isset($_GET['selectcat']) && $_GET['selectcat'] > 0)) {
            if (!empty($_GET['course'])) {
                $interbreadcrumb[] = [
                    'url' => Category::getUrl().'selectcat='.$selectcat,
                    'name' => get_lang('Details'),
                ];
            } else {
                $interbreadcrumb[] = [
                    'url' => Category::getUrl().'selectcat=0',
                    'name' => get_lang('Details'),
                ];
            }
        }
        Display::display_header('');
    } else {
        Display::display_header('');
    }
}

// LOAD DATA & DISPLAY TABLE                             -
$is_platform_admin = api_is_platform_admin();
$is_course_admin = api_is_allowed_to_edit();

//load data for category, evaluation and links
if (empty($selectcat)) {
    $category = 0;
} else {
    $category = $selectcat;
}
// search form

$simple_search_form = new UserForm(
    UserForm::TYPE_SIMPLE_SEARCH,
    null,
    'simple_search_form',
    null,
    api_get_self().'?selectcat='.$selectcat
);
$values = $simple_search_form->exportValues();
$keyword = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $keyword = Security::remove_XSS($_GET['search']);
}
if ($simple_search_form->validate() && (empty($keyword))) {
    $keyword = $values['keyword'];
}

if (!empty($keyword)) {
    $cats = Category::load($category);
    $allcat = [];
    if ((isset($_GET['selectcat']) && $_GET['selectcat'] == 0) && isset($_GET['search'])) {
        $allcat = $cats[0]->get_subcategories(null);
        $allcat_info = Category::find_category($keyword, $allcat);
        $alleval = [];
        $alllink = [];
    } else {
        $alleval = Evaluation::findEvaluations($keyword, $cats[0]->get_id());
        $alllink = LinkFactory::find_links($keyword, $cats[0]->get_id());
    }
} elseif (isset($_GET['studentoverview'])) {
    //@todo this code seems to be deprecated because the gradebook tab is off
    $cats = Category::load($category);
    $stud_id = (api_is_allowed_to_edit() ? null : api_get_user_id());
    $allcat = [];
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
        $data_array = $datagen->get_data(GradebookDataGenerator::GDG_SORT_NAME, 0, null, true);
        $newarray = [];
        foreach ($data_array as $data) {
            $newarray[] = array_slice($data, 1);
        }
        $pdf = new Cezpdf();
        $pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
        $pdf->ezSetMargins(30, 30, 50, 30);
        $pdf->ezSetY(810);
        $pdf->ezText(get_lang('FlatView').' ('.api_convert_and_format_date(null, DATE_FORMAT_SHORT).' '.api_convert_and_format_date(null, TIME_NO_SEC_FORMAT).')', 12, ['justification' => 'center']);
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
} elseif (!empty($_GET['export_certificate'])) {
    //@todo this code seems not to be used
    $user_id = strval(intval($_GET['user']));
    if (!api_is_allowed_to_edit(true, true)) {
        $user_id = api_get_user_id();
    }
    $category = Category::load($_GET['cat_id']);
    if ($category[0]->is_certificate_available($user_id)) {
        $user = api_get_user_info($user_id);
        $scoredisplay = ScoreDisplay::instance();
        $scorecourse = $category[0]->calc_score($user_id);
        $scorecourse_display = (isset($scorecourse) ? $scoredisplay->display_score($scorecourse, SCORE_AVERAGE) : get_lang('NoResultsAvailable'));

        $cattotal = Category::load(0);
        $scoretotal = $cattotal[0]->calc_score($user_id);
        $scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal, SCORE_PERCENT) : get_lang('NoResultsAvailable'));

        //prepare all necessary variables:
        $organization_name = api_get_setting('Institution');
        $portal_name = api_get_setting('siteName');
        $stud_fn = $user['firstname'];
        $stud_ln = $user['lastname'];
        $certif_text = sprintf(get_lang('CertificateWCertifiesStudentXFinishedCourseYWithGradeZ'), $organization_name, $stud_fn.' '.$stud_ln, $category[0]->get_name(), $scorecourse_display);
        $certif_text = str_replace("\\n", "\n", $certif_text);
        $date = api_convert_and_format_date(null, DATE_FORMAT_SHORT);

        $pdf = new Cezpdf('a4', 'landscape');
        $pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
        $pdf->ezSetMargins(30, 30, 50, 50);
        //line Y coordinates in landscape mode are upside down (500 is on top, 10 is on the bottom)
        $pdf->line(50, 50, 790, 50);
        $pdf->line(50, 550, 790, 550);
        $pdf->ezSetY(450);
        $pdf->ezSetY(480);
        $pdf->ezText($certif_text, 28, ['justification' => 'center']);
        //$pdf->ezSetY(750);
        $pdf->ezSetY(50);
        $pdf->ezText($date, 18, ['justification' => 'center']);
        $pdf->ezSetY(580);
        $pdf->ezText($organization_name, 22, ['justification' => 'left']);
        $pdf->ezSetY(580);
        $pdf->ezText($portal_name, 22, ['justification' => 'right']);
        $pdf->ezStream();
    }
    exit;
} else {
    $cats = Category::load($category);
    $stud_id = (api_is_allowed_to_edit() ? null : api_get_user_id());
    $allcat = $cats[0]->get_subcategories($stud_id);
    $alleval = $cats[0]->get_evaluations($stud_id);
    $alllink = $cats[0]->get_links($stud_id);
}
$addparams = ['selectcat' => $cats[0]->get_id()];
if (isset($_GET['search'])) {
    $addparams['search'] = $keyword;
}
if (isset($_GET['studentoverview'])) {
    $addparams['studentoverview'] = '';
}
if (isset($allcat_info) && count($allcat_info) >= 0 &&
    (isset($_GET['selectcat']) && $_GET['selectcat'] == 0) &&
    isset($_GET['search']) && strlen(trim($_GET['search'])) > 0
) {
    $allcat = $allcat_info;
} else {
    $allcat = $allcat;
}
$gradebooktable = new GradebookTable(
    $cats[0],
    $allcat,
    $alleval,
    $alllink,
    $addparams
);

if (empty($allcat) && empty($alleval) && empty($alllink) &&
    !$is_platform_admin && $is_course_admin && !isset($_GET['selectcat']) && api_is_course_tutor()
) {
    echo Display::return_message(
        get_lang('GradebookWelcomeMessage').
        '<br /><br />
        <form name="createcat" method="post" action="'.api_get_self().'?createallcategories=1">
        <input type="submit" value="'.get_lang('CreateAllCat').'"></form>',
        'normal',
        false
    );
}
// Here we are in a sub category
if ($category != '0') {
    DisplayGradebook::header(
        $cats[0],
        1,
        $_GET['selectcat'],
        $is_course_admin,
        $is_platform_admin,
        $simple_search_form
    );
} else {
    // This is the root category
    DisplayGradebook::header(
        $cats[0],
        count($allcat) == '0' && !isset($_GET['search']) ? 0 : 1,
        0,
        $is_course_admin,
        $is_platform_admin,
        $simple_search_form
    );
}
$gradebooktable->display();
Display::display_footer();
