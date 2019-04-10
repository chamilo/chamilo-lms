<?php
/* For licensing terms, see /license.txt */

/**
 * Script.
 *
 * @package chamilo.gradebook
 */
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$categoryId = (int) $_GET['selecteval'];
$eval = Evaluation::load($categoryId);
if (!isset($eval[0])) {
    api_not_allowed(true);
}
/** @var Evaluation $eval */
$eval = $eval[0];

if ($eval->get_category_id() < 0) {
    // if category id is negative, then the evaluation's origin is a link
    $link = LinkFactory::get_evaluation_link($eval->get_id());
    $currentcat = Category::load($link->get_category_id());
} else {
    $currentcat = Category::load($eval->get_category_id());
}

$interbreadcrumb[] = [
    'url' => Category::getUrl().'selectcat='.$currentcat[0]->get_id(),
    'name' => get_lang('ToolGradebook'),
];

if (api_is_allowed_to_edit()) {
    $interbreadcrumb[] = [
        'url' => 'gradebook_view_result.php?selecteval='.$categoryId.'&'.api_get_cidreq(),
        'name' => get_lang('ViewResult'),
    ];
}
$displayScore = ScoreDisplay::instance();

Display::display_header(get_lang('EvaluationStatistics'));
DisplayGradebook::display_header_result(
    $eval,
    $currentcat[0]->get_id(),
    0,
    'statistics'
);

//Bad, Regular, Good  - User definitions
$displays = $displayScore->get_custom_score_display_settings();

if (!$displayScore->is_custom() || empty($displays)) {
    if (api_is_platform_admin() || api_is_course_admin()) {
        echo Display::return_message(get_lang('PleaseEnableScoringSystem'), 'error', false);
    }
} else {
    $allresults = Result::load(null, null, $eval->get_id());
    $nr_items = [];
    foreach ($displays as $itemsdisplay) {
        $nr_items[$itemsdisplay['display']] = 0;
    }

    $resultcount = 0;
    foreach ($allresults as $result) {
        $score = $result->get_score();
        if (isset($score)) {
            $display = $displayScore->display_score(
                [$score, $eval->get_max()],
                SCORE_CUSTOM,
                SCORE_ONLY_CUSTOM,
                true
            );
            $nr_items[$display]++;
            $resultcount++;
        }
    }

    $keys = array_keys($nr_items);
    // find the region with the most scores, this is 100% of the bar
    $highest_ratio = 0;
    foreach ($keys as $key) {
        if ($nr_items[$key] > $highest_ratio) {
            $highest_ratio = $nr_items[$key];
        }
    }

    // Generate table
    $stattable = '<table class="data_table" cellspacing="0" cellpadding="3">';
    $stattable .= '<tr><th>'.get_lang('ScoringSystem').'</th>';
    $stattable .= '<th>'.get_lang('Percentage').'</th>';
    $stattable .= '<th>'.get_lang('CountUsers').'</th></tr>';
    $counter = 0;
    foreach ($keys as $key) {
        $bar = ($highest_ratio > 0 ? ($nr_items[$key] / $highest_ratio) * 100 : 0);
        $stattable .= '<tr class="row_'.($counter % 2 == 0 ? 'odd' : 'even').'">';
        $stattable .= '<td width="150">'.$key.'</td>';
        $stattable .= '<td width="550">'.Display::bar_progress($bar).'</td>';
        $stattable .= '<td align="right">'.$nr_items[$key].'</td>';
        $stattable .= '</tr>';
        $counter++;
    }
    $stattable .= '</table>';
    echo $stattable;
}
Display :: display_footer();
