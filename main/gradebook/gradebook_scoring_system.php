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

if (api_get_setting('teachers_can_change_score_settings') != 'true') {
    api_not_allowed();
}

$htmlHeadXtra[] = '<script>
function plusItem(item) {
    document.getElementById(item).style.display = "inline";
    document.getElementById("plus-"+item).style.display = "none";
    document.getElementById("min-"+(item-1)).style.display = "none";
    document.getElementById("min-"+(item)).style.display = "inline";
    document.getElementById("plus-"+(item+1)).style.display = "inline";
    //document.getElementById("txta-"+(item)).value = "100";
    //document.getElementById("txta-"+(item-1)).value = "";
}

function minItem(item) {
    if (item != 1) {
        document.getElementById(item).style.display = "none";
        document.getElementById("txta-"+item).value = "";
        document.getElementById("txtb-"+item).value = "";
        document.getElementById("plus-"+item).style.display = "inline";
        document.getElementById("min-"+(item-1)).style.display = "inline";
        document.getElementById("txta-"+(item-1)).value = "100";
    }
    if (item = 1) {
        document.getElementById("min-"+(item)).style.display = "none";
    }
}
</script>';

$interbreadcrumb[] = [
    'url' => Category::getUrl().'selectcat=1',
    'name' => get_lang('Assessments'),
];

$select_cat = intval($_GET['selectcat']);
$displayScore = ScoreDisplay :: instance();
$customdisplays = $displayScore->get_custom_score_display_settings();

$nr_items = count($customdisplays) != '0' ? count($customdisplays) : '1';
$scoreform = new ScoreDisplayForm(
    'scoring_system_form',
    api_get_self().'?selectcat='.$select_cat.'&'.api_get_cidreq()
);

if ($scoreform->validate()) {
    $value_export = $scoreform->exportValues();
    $value_export = isset($value_export) ? $scoreform->exportValues() : '';
    $values = $value_export;

    // create new array of custom display settings
    // this loop also checks if all score ranges are unique
    $scoringDisplay = [];
    $ranges_ok = true;
    $endscore = isset($values['endscore']) ? $values['endscore'] : null;
    $displaytext = isset($values['displaytext']) ? $values['displaytext'] : null;
    for ($counter = 1; $ranges_ok && $counter <= 20; $counter++) {
        $setting = [];
        $setting['score'] = $endscore[$counter];
        $setting['display'] = $displaytext[$counter];
        if (!empty($setting['score'])) {
            foreach ($scoringDisplay as $passed_entry) {
                if ($passed_entry['score'] == $setting['score']) {
                    $ranges_ok = false;
                }
            }
            $scoringDisplay[] = $setting;
        }
    }

    if (!$ranges_ok) {
        Display::addFlash(
            Display::return_message(
                get_lang('There is no unique score range possibility.'),
                'error',
                false
            )
        );
        header('Location: '.api_get_self().'?selectcat='.$select_cat.'&'.api_get_cidreq());
        exit;
    }

    $scorecolpercent = 0;
    if ($displayScore->is_coloring_enabled()) {
        $scorecolpercent = $values['scorecolpercent'];
    }

    if ($displayScore->is_custom() && !empty($scoringDisplay)) {
        $displayScore->update_custom_score_display_settings(
            $scoringDisplay,
            $scorecolpercent
        );
    }

    Display::addFlash(
        Display::return_message(get_lang('Skills ranking updated'), 'confirm', false)
    );

    header('Location:'.api_get_self().'?selectcat='.$select_cat.'&'.api_get_cidreq());
    exit;
}

$this_section = SECTION_COURSES;
Display::display_header(get_lang('Skills ranking'));
$scoreform->display();
Display::display_footer();
