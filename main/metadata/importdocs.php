<?php /*                                 <!-- Dokeos metadata/importdocs.php -->
                                                             <!-- 2005/09/20 -->

<!-- Copyright (C) 2005 rene.haentjens@UGent.be -  see metadata/md_funcs.php -->

*/

/**
==============================================================================
*	Dokeos Metadata: index all course documents with PhpDig
*
*	@package dokeos.metadata
==============================================================================
*/


// PRELIMS -------------------------------------------------------------------->

$getpostvars = array('dmo'); require('md_funcs.php');

define('EID_TYPE', 'Document'); define('AFTER_DOT', strlen(EID_TYPE) + 1);
define('OF_EID_TYPE', "eid LIKE '" . EID_TYPE . ".%'");

require('md_' . strtolower(EID_TYPE) . '.php');

// name of the language file that needs to be included
$language_file = 'md_' . strtolower(EID_TYPE);
include('../inc/global.inc.php');
$nameTools = get_lang('Tool');

($nameTools && get_lang('Sorry')) or give_up(
    'Language file ' . $language_file . " doesn't define 'Tool' and 'Sorry'");

$_course = api_get_course_info(); isset($_course) or give_up(get_lang('Sorry'));

$is_allowed_to_edit = isset($_user['user_id']) && $is_courseMember && is_allowed_to_edit();
if (!$is_allowed_to_edit) give_up(get_lang('Denied'));

$mdObj = new mdobject($_course, 0);
$mdStore = new mdstore($is_allowed_to_edit);  // create table if needed

require(api_get_path(LIBRARY_PATH) . 'xmd.lib.php');
require(api_get_path(LIBRARY_PATH) . 'xht.lib.php');

require('md_phpdig.php');

$mdObj->mdo_add_breadcrump_nav();  // see 'md_' . EID_TYPE . '.php'

$htmlHeadXtra[] = '
<link rel="stylesheet" type="text/css" href="md_styles.css">
<script type="text/javascript" src="md_script.js"></script>
';
Display::display_header($nameTools);


if (isset($dmo))  // for future use
{
    echo '<h3>', $dmo, '</h3>', "\n";  // document metadata op

    // if ($dmo == get_lang('Index')) $dmo = $dmo;
}

$result = $mdStore->mds_get_many('eid,indexabletext', OF_EID_TYPE);
echo get_lang('Tool'), ': ', mysql_num_rows($result), "<br><br>\n";

$idt = array(); $cidpar = '?cidReq=' . $_course['sysCode'];

while ($row = Database::fetch_array($result))  // load indexabletexts in memory
{
    $mdObj = new mdobject($_course, substr($row['eid'], AFTER_DOT));
    $idt[$mdObj->mdo_url . $cidpar] = $row['indexabletext'];
}

if (count($idt) && file_exists($phpDigIncCn))
{
    require($phpDigIncCn);  // switch to PhpDig DB

    foreach ($idt as $url => $text)
    if (ereg('^http://([^/]+)/(.+)/([^/]+)\?cidReq=(.+)$', $url, $regs))
    {
        $path = $regs[2] .'/'; $file = $regs[3] . '?cidReq=' . $regs[4];
        if ($site_id = remove_engine_entries('http://' . $regs[1] .'/',
                $path, $file))
        {
            echo '<table>', "\n";
            index_words($site_id, $path, $file,
                get_first_words($text, $path, $file),
                get_keywords($text));
            echo '</table>', "\n";
        }
    }

    if(isset($db))
    {
    	mysql_select_db($_configuration['main_database'], $db);  // back to Dokeos
    }
}
else
{
	echo 'No documents with metadata or no PhpDig in this course...<br />';
}

if (false && file_exists($phpDigIncCn))  // future: buttons for operations
{
    echo '<form action="' .api_get_self(). '" method="post">', "\n",
        '<input type="submit" name="dmo" value="', get_lang('Import', 'noDLTT'), '">', "\n",
        '<input type="submit" name="dmo" value="', get_lang('Remove', 'noDLTT'), '">', "\n",
        '</form>', "\n";
}

Display::display_footer();
?>
