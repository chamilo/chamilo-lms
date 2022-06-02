<?php

/* For licensing terms, see /license.txt */

/**
 * Script.
 *
 * @package chamilo.gradebook
 *
 * @author Julio Montoya - fixes in order to use gradebook models + some code cleaning
 */
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;
$current_course_tool = TOOL_GRADEBOOK;

api_protect_course_script(true);
api_block_anonymous_users();
GradebookUtils::block_students();
$my_selectcat = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;

if (empty($my_selectcat)) {
    api_not_allowed(true);
}

$action_details = '';
if (isset($_GET['selectcat'])) {
    $action_details = 'selectcat';
}

$logInfo = [
    'tool' => TOOL_GRADEBOOK,
    'action' => 'edit-weight',
    'action_details' => $action_details,
];
Event::registerLog($logInfo);

$course_id = GradebookUtils::get_course_id_by_link_id($my_selectcat);
$table_link = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
$table_evaluation = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
$tbl_forum_thread = Database::get_course_table(TABLE_FORUM_THREAD);
$tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);

$table_evaluated[LINK_EXERCISE] = [
    TABLE_QUIZ_TEST,
    'title',
    'iid',
    get_lang('Exercise'),
];
$table_evaluated[LINK_DROPBOX] = [
    TABLE_DROPBOX_FILE,
    'name',
    'id',
    get_lang('Dropbox'),
];
$table_evaluated[LINK_STUDENTPUBLICATION] = [
    TABLE_STUDENT_PUBLICATION,
    'url',
    'id',
    get_lang('Student_publication'),
];
$table_evaluated[LINK_LEARNPATH] = [
    TABLE_LP_MAIN,
    'name',
    'id',
    get_lang('Learnpath'),
];
$table_evaluated[LINK_FORUM_THREAD] = [
    TABLE_FORUM_THREAD,
    'thread_title_qualify',
    'thread_id',
    get_lang('Forum'),
];
$table_evaluated[LINK_ATTENDANCE] = [
    TABLE_ATTENDANCE,
    'attendance_title_qualify',
    'id',
    get_lang('Attendance'),
];
$table_evaluated[LINK_SURVEY] = [
    TABLE_SURVEY,
    'code',
    'survey_id',
    get_lang('Survey'),
];

$submitted = isset($_POST['submitted']) ? $_POST['submitted'] : '';
if ($submitted == 1) {
    Display::addFlash(Display::return_message(get_lang('GradebookWeightUpdated')));
    if (isset($_POST['evaluation'])) {
        $eval_log = new Evaluation();
    }
}

$output = '';
$my_cat = Category::load($my_selectcat);
$my_cat = $my_cat[0];

$parent_id = $my_cat->get_parent_id();
$parent_cat = Category::load($parent_id);

$my_category = [];
$cat = new Category();
$my_category = $cat->showAllCategoryInfo($my_selectcat);

$original_total = $my_category['weight'];
$masked_total = $parent_cat[0]->get_weight();

$sql = 'SELECT * FROM '.$table_link.'
        WHERE category_id = '.$my_selectcat;
$result = Database::query($sql);
$links = Database::store_result($result, 'ASSOC');

foreach ($links as &$row) {
    $item_weight = $row['weight'];
    $sql = 'SELECT * FROM '.GradebookUtils::get_table_type_course($row['type']).'
            WHERE c_id = '.$course_id.' AND '.$table_evaluated[$row['type']][2].' = '.$row['ref_id'];

    $result = Database::query($sql);
    $resource_name = Database::fetch_array($result);

    if (isset($resource_name['lp_type'])) {
        $resource_name = $resource_name[4];
    } else {
        switch ($row['type']) {
            case LINK_EXERCISE:
                $resource_name = $resource_name['title'];
                break;
            default:
                $resource_name = $resource_name[3];
                break;
        }
    }

    $row['resource_name'] = $resource_name;

    // Update only if value changed
    if (isset($_POST['link'][$row['id']])) {
        $new_weight = trim($_POST['link'][$row['id']]);
        GradebookUtils::updateLinkWeight(
            $row['id'],
            $resource_name,
            $new_weight
        );
        $item_weight = $new_weight;
    }

    $output .= '<tr><td>'.GradebookUtils::build_type_icon_tag($row['type']).'</td>
               <td> '.$resource_name.' '.
        Display::label(
            $table_evaluated[$row['type']][3],
            'info'
        ).' </td>';
    $output .= '<td>
                    <input type="hidden" name="link_'.$row['id'].'" value="1" />
                    <input size="10" type="text" name="link['.$row['id'].']" value="'.$item_weight.'"/>
               </td></tr>';
}

$sql = "SELECT * FROM $table_evaluation
        WHERE category_id = $my_selectcat
        ORDER BY name";
$result = Database::query($sql);
$evaluations = Database::store_result($result);
foreach ($evaluations as $evaluationRow) {
    $item_weight = $evaluationRow['weight'];
    // update only if value changed
    if (isset($_POST['evaluation'][$evaluationRow['id']])) {
        $new_weight = trim($_POST['evaluation'][$evaluationRow['id']]);
        GradebookUtils::updateEvaluationWeight(
            $evaluationRow['id'],
            $new_weight
        );

        $item_weight = $new_weight;
    }

    $output .= '<tr>
                <td>'.GradebookUtils::build_type_icon_tag('evalnotempty').'</td>
                <td>'.$evaluationRow['name'].' '.Display::label(get_lang('Evaluation')).'</td>';
    $output .= '<td>
                    <input type="hidden" name="eval_'.$evaluationRow['id'].'" value="1" />
                    <input type="text" size="10" name="evaluation['.$evaluationRow['id'].']" value="'.$item_weight.'"/>
                </td></tr>';
}

$currentUrl = api_get_self().'?'.api_get_cidreq().'&selectcat='.$my_selectcat;

$form = new FormValidator('auto_weight', 'post', $currentUrl);
$form->addHeader(get_lang('AutoWeight'));
$form->addLabel(null, get_lang('AutoWeightExplanation'));
$form->addButtonUpdate(get_lang('AutoWeight'));

if ($form->validate()) {
    $itemCount = count($links) + count($evaluations);
    $weight = round($original_total / $itemCount, 2);
    $total = $weight * $itemCount;

    $diff = null;
    if ($original_total !== $total) {
        if ($total > $original_total) {
            $diff = $total - $original_total;
        }
    }

    $total = 0;
    $diffApplied = false;

    foreach ($links as $link) {
        $weightToApply = $weight;
        if ($diffApplied == false) {
            if (!empty($diff)) {
                $weightToApply = $weight - $diff;
                $diffApplied = true;
            }
        }
        GradebookUtils::updateLinkWeight(
            $link['id'],
            $link['resource_name'],
            $weightToApply
        );
    }

    foreach ($evaluations as $evaluation) {
        $weightToApply = $weight;
        if ($diffApplied == false) {
            if (!empty($diff)) {
                $weightToApply = $weight - $diff;
                $diffApplied = true;
            }
        }
        GradebookUtils::updateEvaluationWeight(
            $evaluation['id'],
            $weightToApply
        );
    }
    Display::addFlash(Display::return_message(get_lang('GradebookWeightUpdated')));

    header('Location: '.$currentUrl);
    exit;
}

// 	DISPLAY HEADERS AND MESSAGES
if (!isset($_GET['exportpdf']) && !isset($_GET['export_certificate'])) {
    if (isset($_GET['studentoverview'])) {
        $interbreadcrumb[] = [
            'url' => Category::getUrl().'selectcat='.$my_selectcat,
            'name' => get_lang('Gradebook'),
        ];
        Display::display_header(get_lang('FlatView'));
    } elseif (isset($_GET['search'])) {
        $interbreadcrumb[] = [
            'url' => Category::getUrl().'selectcat='.$my_selectcat,
            'name' => get_lang('Gradebook'),
        ];
        Display::display_header(get_lang('SearchResults'));
    } else {
        $interbreadcrumb[] = [
            'url' => Category::getUrl().'selectcat=1',
            'name' => get_lang('Gradebook'),
        ];
        $interbreadcrumb[] = [
            'url' => '#',
            'name' => get_lang('EditAllWeights'),
        ];
        Display::display_header('');
    }
}
?>
    <div class="actions">
        <a href="<?php echo Category::getUrl(); ?>selectcat=<?php echo $my_selectcat; ?>">
            <?php echo Display::return_icon(
                'back.png',
                get_lang('FolderView'),
                '',
                ICON_SIZE_MEDIUM
            ); ?>
        </a>
    </div>
<?php

$form->display();

$formNormal = new FormValidator('normal_weight', 'post', $currentUrl);
$formNormal->addHeader(get_lang('EditWeight'));
$formNormal->display();

echo Display::return_message(sprintf(get_lang('TotalWeightMustBeX'), $original_total), 'warning', false);

?>
<form method="post" action="<?php echo $currentUrl; ?>">
    <table class="table table-hover table-striped data_table">
        <thead>
        <tr>
            <th><?php echo get_lang('Type'); ?></th>
            <th><?php echo get_lang('Resource'); ?></th>
            <th><?php echo get_lang('Weight'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php echo $output; ?>
        </tbody>
    </table>
    <input type="hidden" name="submitted" value="1"/>
    <br/>
    <button class="btn btn-primary" type="submit" name="name"
            value="<?php echo get_lang('Save'); ?>">
        <?php echo get_lang('SaveScoringRules'); ?>
    </button>
</form>
<?php
Display::display_footer();
