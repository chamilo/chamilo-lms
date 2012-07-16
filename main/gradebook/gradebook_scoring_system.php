<?php
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */
$language_file= 'gradebook';
//$cidReset= true;
require_once '../inc/global.inc.php';
require_once 'lib/be.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/scoredisplayform.class.php';
require_once 'lib/scoredisplay.class.php';

api_block_anonymous_users();
//api_protect_admin_script();

if (api_get_setting('teachers_can_change_score_settings') != 'true') {
    api_not_allowed();
}

$htmlHeadXtra[]= '
  <script>
  function plusItem(item) {
		document.getElementById(item).style.display = "inline";
    	document.getElementById("plus-"+item).style.display = "none";
   	 	document.getElementById("min-"+(item-1)).style.display = "none";
   	 	document.getElementById("min-"+(item)).style.display = "inline";
   	 	document.getElementById("plus-"+(item+1)).style.display = "inline";
	 	document.getElementById("txta-"+(item)).value = "100";
	 	document.getElementById("txta-"+(item-1)).value = "";
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

$interbreadcrumb[] = array ('url' => $_SESSION['gradebook_dest'].'?selectcat=1', 'name' => get_lang('ToolGradebook'));

$select_cat = intval($_GET['selectcat']);
$displayscore= ScoreDisplay :: instance();
$customdisplays = $displayscore->get_custom_score_display_settings();
$nr_items =(count($customdisplays)!='0')?count($customdisplays):'1';

$scoreform= new ScoreDisplayForm('scoring_system_form', api_get_self() . '?selectcat=' . $select_cat);
if ($scoreform->validate()) {
	$value_export='';
	$value_export=$scoreform->exportValues();
	$value_export=isset($value_export) ? $scoreform->exportValues(): '';
	$values= $value_export;

    // create new array of custom display settings
    // this loop also checks if all score ranges are unique

	$scoringdisplay= array ();
	$ranges_ok = true;
	$endscore= isset($values['endscore']) ? $values['endscore'] : null;
	$displaytext=isset($values['displaytext']) ? $values['displaytext'] : null;
	for ($counter= 1; $ranges_ok && $counter <= 20; $counter++) {
        $setting= array ();
        $setting['score']= $endscore[$counter];
        $setting['display']= $displaytext[$counter];
        if (!empty($setting['score'])) {
            foreach ($scoringdisplay as $passed_entry) {
                if ($passed_entry['score'] == $setting['score']) {
                    $ranges_ok = false;
                }
            }
            $scoringdisplay[]= $setting;
        }
    }

	if (!$ranges_ok) {
		header('Location: ' . api_get_self() . '?nouniqueranges=&selectcat=' . $select_cat);
		exit;
	}

	$scorecolpercent = 0;
    
    if ($displayscore->is_coloring_enabled()) {
        $scorecolpercent = $values['scorecolpercent'];
	}
    	    
	if ($displayscore->is_custom() && !empty($scoringdisplay)) {
		$displayscore->update_custom_score_display_settings($scoringdisplay, $scorecolpercent);
	}
	header('Location: ' . api_get_self() . '?scoringupdated=&selectcat=' . $select_cat);
	exit;
}

$this_section = SECTION_COURSES;
Display :: display_header(get_lang('ScoreEdit'));

if (((isset($_GET['isStudentView']) && $_GET['isStudentView']=='false') || (isset($_GET['selectcat']) && ($_SESSION['studentview']=='teacherview')))) {
	if (isset ($_GET['scoringupdated'])) {
		Display :: display_confirmation_message(get_lang('ScoringUpdated'),false);
	}

    if (isset ($_GET['nouniqueranges'])) {
    	Display :: display_error_message(get_lang('NoUniqueScoreRanges'),false);
    }    
    $scoreform->display();
    
}
Display :: display_footer();
