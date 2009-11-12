<?php /*                        <!-- Dokeos metadata/udate_indexableText.php -->
                                                             <!-- 2005/03/16 -->

<!-- Copyright (C) 2005 rene.haentjens@UGent.be -  see metadata/md_funcs.php -->

*/

/**
==============================================================================
*	Dokeos Metadata: update indexabletext for all eid_type records
*
*	@package dokeos.metadata
==============================================================================
*/


// PRELIMS -------------------------------------------------------------------->

require('md_funcs.php');

getpar('EID_TYPE', 'Entry Type');  // e.g. 'Document' or 'Scorm'
define('TPLEN', strlen(EID_TYPE) + 1);

require('md_' . strtolower(EID_TYPE) . '.php');

// name of the language file that needs to be included
$language_file = 'md_' . strtolower(EID_TYPE);
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

$nameTools = get_lang('Tool');

($nameTools && get_lang('Sorry')) or give_up(
    'Language file ' . $language_file . " doesn't define 'Tool' and 'Sorry'");

$_course = api_get_course_info(); isset($_course) or give_up(get_lang('Sorry'));

$is_allowed_to_edit = isset($_user['user_id']) && $is_courseMember && is_allowed_to_edit();
if (!$is_allowed_to_edit) give_up(get_lang('Denied'));

$mdStore = new mdstore($is_allowed_to_edit);  // create table if needed
$mdObj = new mdobject($_course, 0);

require(api_get_path(LIBRARY_PATH) . 'xmd.lib.php');
require(api_get_path(LIBRARY_PATH) . 'xht.lib.php');

$xhtDoc = $mdObj->mdo_define_htt();

$mdObj->mdo_add_breadcrump_nav();  // see 'md_' . EID_TYPE . '.php'
Display::display_header($nameTools);

// OPERATIONS ----------------------------------------------------------------->

echo '<h3>', htmlspecialchars(EID_TYPE, ENT_QUOTES, $charset), '</h3>', "\n";

$result = $mdStore->mds_get_many('eid,mdxmltext', "eid LIKE '" . EID_TYPE . ".%'");
echo get_lang('TotalMDEs'), $total = Database::num_rows($result), "<br><br>\n";

if ($total > 100) set_time_limit((int) ($total / 10));

while ($row = Database::fetch_array($result))
{
    $eid = $row['eid']; $xmltext = $row['mdxmltext'];
    $xhtDoc->xht_xmldoc = new xmddoc(explode("\n", $xmltext));

    $mdStore->mds_put($eid,
        $xhtDoc->xht_fill_template('INDEXABLETEXT'), 'indexabletext');

    echo htmlspecialchars($eid, ENT_QUOTES, $charset), ' ';
}

echo '<h5>', htmlspecialchars(EID_TYPE, ENT_QUOTES, $charset), '</h5>', "\n";

Display::display_footer();
?>
