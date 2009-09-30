<?php /*                                 <!-- Dokeos metadata/statistics.php -->
                                                             <!-- 2005/02/02 -->

<!-- Copyright (C) 2005 rene.haentjens@UGent.be -  see metadata/md_funcs.php -->

*/

/**
==============================================================================
*	Dokeos Metadata: statistics about metadata
*
*	@package dokeos.metadata
==============================================================================
*/


// PRELIMS -------------------------------------------------------------------->

require('md_funcs.php');

define('EID_TYPE', 'Mix');
require('md_' . strtolower(EID_TYPE) . '.php');

// name of the language file that needs to be included
$language_file = 'md_mix';
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

$nameTools = get_lang('Tool');

($nameTools && get_lang('Sorry')) or give_up(
    'Language file ' . $language_file . " doesn't define 'Tool' and 'Sorry'");

$_course = api_get_course_info(); isset($_course) or give_up(get_lang('Sorry'));

$is_allowed_to_edit = isset($_user['user_id']) && $is_courseMember && is_allowed_to_edit();
if (!$is_allowed_to_edit) give_up(get_lang('Denied'));

$mdStore = new mdstore(FALSE);  // no create from statistics

require(api_get_path(LIBRARY_PATH) . 'xmd.lib.php');
require(api_get_path(LIBRARY_PATH) . 'xht.lib.php');

$htmldecode = array_flip(get_html_translation_table(HTML_SPECIALCHARS));


// STATISTICS ----------------------------------------------------------------->

$noPHP_SELF = TRUE;  // in breadcrumps
Display::display_header($nameTools);

echo '<h3>', get_lang('Statistics'), '</h3>', "\n";

$ckw = $_course['path'] . '/CourseKwds.js';
define('KEYWORDS_CACHE', get_course_path() . $ckw);

if (!file_exists(KEYWORDS_CACHE))
{
    echo get_lang('NoKeywords');
    Display::display_footer();
    exit();
}

if (!($myFile = @fopen(KEYWORDS_CACHE, 'r')))
{
    echo get_lang('KwCacheProblem');
    Display::display_footer();
    exit();
}

$kwds = array(); $kwcnt = array(); $kwrefs = array();

while (($kwline = fgets($myFile)))
{
    if (ereg('�>(.+)<�', $kwline, $regs) || ereg('">(.+)<�', $kwline, $regs))
        foreach (explode(',', $regs[1]) as $kw)
            if (!in_array($kw = strtr(trim($kw), $htmldecode), $kwds))
                $kwds []= $kw;
}
fclose($myFile);

$result = $mdStore->mds_get_many('eid,mdxmltext', '1 = 1');
echo get_lang('TotalMDEs'), mysql_num_rows($result), "<br>\n";

echo count($kwds), ' ', get_lang('CourseKwds'), '<br>', "\n";

while ($row = mysql_fetch_array($result))
{
    $eid = $row['eid']; $curr = ''; $xmltext = $row['mdxmltext']; $offset = 0;

    if (substr($eid, 0, 6) == 'Scorm.')
        if (($dotpos = strpos($eid, '.', 6)) && $dotpos + 1 < strlen($eid))
            $curr = substr($eid, 0, $dotpos);

    while (($start = strpos($xmltext, '<keyword>', $offset)))
        if (($start = strpos($xmltext, '">', $start + 9)))
        {
            if (($stop = strpos($xmltext, '</', $start += 2)) && $stop > $start)
            {
                $kw = strtr(substr($xmltext, $start, $stop-$start), $htmldecode);
                if (!in_array($kw, $kwds))
                {
                    if (!in_array($kw = '!' . $kw, $kwds)) $kwds []= $kw;
                    $kwrefs[$kw] .= ' ' . ($curr ?
                        (strpos($kwrefs[$kw], $curr) ?
                            substr($eid, $dotpos+1) : $eid) : $eid);
                }
                $kwcnt[$kw] ++;  // = $kwcnt[$kw] ? $kwcnt[$kw] + 1 : 1;
                $offset = $stop + 19;
            }
            else $offset = $start + 2;
            // <keyword><string language="en">lecture</string></keyword>
        }
        else $offset = $start + 9;

    // xmd would be nicer but this is faster...
}

echo count($kwds), ' ', get_lang('KwdsInMD'), '<br>'; sort($kwds);

$total = 0; foreach ($kwcnt as $kw => $cnt) $total += $cnt;
echo $total, ' ', get_lang('KwdRefs'), "\n";

echo '<h4>', get_lang('NonCourseKwds'), '</h4>', "\n";

foreach ($kwds as $kw)
    if ($kw{0} == '!')
        echo '<b>', htmlspecialchars(api_substr($kw, 1), ENT_QUOTES, $charset), '</b>: ', $kwcnt[$kw],
            ': <i>', htmlspecialchars($kwrefs[$kw], ENT_QUOTES, $charset), ";</i> \n";
    else break;

echo '<h4>', get_lang('KwdsUse'), '</h4>', "\n";

foreach ($kwds as $kw)
    if ($kw{0} != '!')
        if (!$kwcnt[$kw])
            echo '<b>', htmlspecialchars($kw, ENT_QUOTES, $charset), "</b>; \n";
        else echo htmlspecialchars($kw, ENT_QUOTES, $charset), ': ', $kwcnt[$kw], "; \n";

Display::display_footer();
?>
