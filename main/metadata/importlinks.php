<?php /*                                <!-- Dokeos metadata/importlinks.php -->
                                                             <!-- 2006/12/15 -->

<!-- Copyright (C) 2006 rene.haentjens@UGent.be -  see metadata/md_funcs.php -->

*/

/**
==============================================================================
*	Dokeos Metadata: create table entries for a category of Link-type items
*
*	@package dokeos.metadata
==============================================================================
*/


// PRELIMS -------------------------------------------------------------------->

$getpostvars = array('lcn', 'slo'); require('md_funcs.php');

define('EID_TYPE', 'Link');
define('OF_EID_TYPE', "eid LIKE '" . EID_TYPE . ".%'");

require('md_' . strtolower(EID_TYPE) . '.php');

// name of the language file that needs to be included
$language_file = 'md_' . strtolower(EID_TYPE);
include('../inc/global.inc.php');
$nameTools = get_lang('Tool');

($nameTools && get_lang('Sorry')) or give_up(
    'Language file ' . $language_file . " doesn't define 'Tool' and 'Sorry'");

$_course = api_get_course_info(); isset($_course) or give_up(get_lang('Sorry'));

$is_allowed_to_edit = isset($_user['user_id']) && $is_courseMember && api_is_allowed_to_edit();
if (!$is_allowed_to_edit) give_up(get_lang('Denied'));

$mdStore = new mdstore($is_allowed_to_edit);  // create table if needed

require(api_get_path(LIBRARY_PATH) . 'xmd.lib.php');
require(api_get_path(LIBRARY_PATH) . 'xht.lib.php');

require('md_phpdig.php');

$mdObj = new mdobject($_course, 0);
$mdCat = $mdObj->mdo_dcmap_v['Coverage'];
$mdUrl = 'metadata/lom/technical/location[1]';

$mdObj->mdo_add_breadcrump_nav();  // see 'md_' . EID_TYPE . '.php'

$htmldecode = array_flip(get_html_translation_table(HTML_SPECIALCHARS));


function check_andor_get($row, $get = '', $check = '', $tobe = '')
{
	global $mdCat, $htmldecode;

	if (!$check && !$get) return FALSE;

	$regs = array(); // for use with ereg()

	if ($get == $mdCat && !$check)  // cheat to be quicker
		if (ereg('<coverage>[^<]*<string language="..">([^<]+)<\/string>',
			$row['mdxmltext'], $regs)) return strtr($regs[1], $htmldecode);

	if ($check == $mdCat && !$get)  // cheat to be quicker
		if (ereg('<coverage>[^<]*<string language="..">([^<]+)<\/string>',
			$row['mdxmltext'], $regs))
				return (strtr($regs[1], $htmldecode) == $tobe);

	$xmlDoc = new xmddoc(explode("\n", $row['mdxmltext']));
	if ($xmlDoc->error) return FALSE;

	if (!$check) return $xmlDoc->xmd_value($get);

	if ($xmlDoc->xmd_value($check) == $tobe)
		return $get ? $xmlDoc->xmd_value($get) : TRUE;

	return FALSE;
}


function get_cat($catname)
{
    global $_course; $cateq = "category_title='". addslashes($catname) . "'";

    $linkcat_table = Database::get_course_table(TABLE_LINK_CATEGORY);
    $result = Database::query("SELECT id FROM $linkcat_table WHERE " . $cateq);

    if (Database::num_rows($result) >= 1 && ($row = Database::fetch_array($result)))
        return $row['id'];  // several categories with same name: take first

    return FALSE;
}


// SET CURRENT LINKS CATEGORY - HEADER ---------------------------------------->

unset($lci);  // category-id

if (isset($lcn))  // category_title
{
    $lcn = substr(ereg_replace("[^\x20-\x7E\xA1-\xFF]", "", $lcn), 0, 255);

    $uceids = array(); $mceids = array();

    $result = $mdStore->mds_get_many('eid,mdxmltext', OF_EID_TYPE);

    while ($row = Database::fetch_array($result))
        if (check_andor_get($row, '', $mdCat, $lcn)) $uceids[] = $row['eid'];

    if (($lci = get_cat($lcn)) !== FALSE)
    {
        $link_table = Database::get_course_table(TABLE_LINK);
        $result = Database::query("SELECT id FROM $link_table WHERE category_id=" . $lci);

        while ($row = Database::fetch_array($result))
        {
            $lceids[$id = (int) $row['id']] = ($eid = EID_TYPE . '.' . $id);

            if (in_array($eid, $uceids)) $mceids[] = $eid;
        }

        $hdrInfo = ' ' . get_lang('WorkOn') . ' ' . htmlspecialchars($lcn, ENT_QUOTES, $charset) .
            ', LC-id=&nbsp;' . htmlspecialchars($lci, ENT_QUOTES, $charset);
    }
    elseif ($lcn)
    {
        $hdrInfo = ' (' . htmlspecialchars($lcn, ENT_QUOTES, $charset) .
            ': ' . get_lang('NotInDB') . ')';
    }
    else
        unset($lcn);

    $uceids = array_diff($uceids, $mceids);  // old entries with no link

    if (count($lceids) && count($uceids))
    {
        $mdStore->mds_delete_many($uceids); $ufos = Database::affected_rows();
    }

    $interbreadcrumb[]= array(
        'url' => api_get_self() . '?lcn=' . urlencode($lcn),
        'name'=> get_lang('Continue') . ' ' . htmlspecialchars($lcn, ENT_QUOTES, $charset));
}

$htmlHeadXtra[] = '
<link rel="stylesheet" type="text/css" href="md_styles.css">
<script type="text/javascript" src="md_script.js"></script>
';
Display::display_header($nameTools);

// OPERATIONS ----------------------------------------------------------------->

if ($ufos) echo '<h3>', $ufos, ' ', get_lang('RemainingFor'), ' ',
        htmlspecialchars($lcn, ENT_QUOTES, $charset), '</h3>', "\n";

if (isset($slo)) echo '<h3>', $slo, '</h3>', "\n";  // selected links op

if (isset($slo))
if ($slo == get_lang('Create') && count($lceids))
{
    foreach ($lceids as $id => $eid)
    {
        $mdObj = new mdobject($_course, $id); $xht = $mdObj->mdo_define_htt();
        $mdStore->mds_put($eid, $mdt = $mdObj->mdo_generate_default_xml_metadata(),
            'mdxmltext', '?');
        $xht->xht_xmldoc = new xmddoc(explode("\n", $mdt));
        $mdStore->mds_put($eid, $xht->xht_fill_template('INDEXABLETEXT'),
            'indexabletext');
        echo '<span class="lbs" onClick="', "javascript: makeWindow('index.php?eid=",
            urlencode($eid), "', '', '')\">", htmlspecialchars($eid, ENT_QUOTES, $charset), '</span> ';
    }
    echo '<br>';
}
elseif ($slo == get_lang('Remove') && count($lceids))
{
    $mdStore->mds_delete_many($mceids); $aff = Database::affected_rows();

    echo $aff, ' MDEs/ ', count($lceids), ' ', get_lang('MdCallingTool'),
        '<br><br><b>', get_lang('AllRemovedFor'),
        ' ', htmlspecialchars($lcn, ENT_QUOTES, $charset), '</b><br />';
}
elseif ($slo == get_lang('Remove') && count($mceids))  // obsolete category
{
    $mdStore->mds_delete_many($mceids);

    echo get_lang('AllRemovedFor'), ' ', htmlspecialchars($lcn, ENT_QUOTES, $charset), '<br />';
}
elseif ($slo == get_lang('Index') && file_exists($phpDigIncCn) && count($mceids))
{
    $result = $mdStore->mds_get_many('eid,mdxmltext,indexabletext',
        OF_EID_TYPE . " AND eid IN ('" .
        implode("','", array_map('addslashes', $mceids)) . "')");

    while ($row = Database::fetch_array($result))  // load indexabletexts in memory
        $idt[check_andor_get($row, $mdUrl)] = $row['indexabletext'];

    require($phpDigIncCn);  // switch to PhpDig DB

    foreach ($idt as $url => $text)
    {
        $pu = parse_url($url);
        if (!isset($pu['scheme'])) $pu['scheme'] = "http";

        if (isset($pu['host']))
        {
            $url = $pu['scheme'] . "://" . $pu['host'] . "/"; $file = '';

            if (($path = $pu['path']))
            if (substr($path, -1) == '/') $path = substr($path, 1);
            else
            {
                $pi = pathinfo($path); $path = $pi['dirname'];
                if ($path{0} == '\\') $path = substr($path, 1);
                if ($path{0} == '/')  $path = substr($path, 1) . '/';

                $file = $pi['basename'];
            }

            $file .= ($pu['query'] ? '?'.$pu['query'] : '') .
                    ($pu['fragment'] ? '#'.$pu['fragment'] : '');


            if ($site_id = remove_engine_entries($url, $path, $file))
            {
                echo '<table>', "\n";
                index_words($site_id, $path, $file,
                    get_first_words($text, $url . $path, $file),
                    get_keywords($text));
                echo '</table>', "\n";
            }
            else
            {
                echo '<table>', "\n";
                echo '<tr><td>', htmlspecialchars($url, ENT_QUOTES, $charset),
                    '</td><td>', htmlspecialchars($path, ENT_QUOTES, $charset),
                    '</td><td>', htmlspecialchars($file, ENT_QUOTES, $charset), '</td></tr>';
                echo '</table>', "\n";
            }
        }
        elseif (isset($pu['scheme']) && $pu['scheme'] == 'mailto' && isset($pu['path']))
        {
            if ($site_id = remove_engine_entries($url = 'mailto:' . $pu['path'], ''))
            {
                echo '<table>', "\n";
                index_words($site_id, '', '',
                    get_first_words($text, $url, ''),
                    get_keywords($text));
                echo '</table>', "\n";
            }
            else
            {
                echo '<table>', "\n";
                echo '<tr><td>', htmlspecialchars($url, ENT_QUOTES, $charset),
                    '</td><td>', htmlspecialchars($path, ENT_QUOTES, $charset),
                    '</td><td>', htmlspecialchars($file, ENT_QUOTES, $charset), '</td></tr>';
                echo '</table>', "\n";
            }
        }
    }

    if(isset($db))
    {
    	//mysql_select_db($_configuration['main_database'], $db);
    	Database::select_db($_configuration['main_database'], $db);
    }
}
elseif ($slo == get_lang('Index'))
{
    echo 'Problem! PhpDig connect.php has gone ...';
}


// STATISTICS ----------------------------------------------------------------->

echo '<h3>', get_lang('Statistics'), '</h3>', "\n";

$result = $mdStore->mds_get_many('eid,mdxmltext', OF_EID_TYPE);
echo get_lang('TotalMDEs'), Database::num_rows($result), "\n";

while ($row = Database::fetch_array($result))
{
    $cat = check_andor_get($row, $mdCat);
    $perCat[$cat] = ($pc = $perCat[$cat]) ? $pc + 1 : 1;
}

if (count($perCat))
{
    echo '<table>', "\n";
    foreach ($perCat as $cat => $number)
    {
        echo '<tr><td>', $cat == $lcn ? '' : '(', htmlspecialchars($cat, ENT_QUOTES, $charset),
            $cat == $lcn ? '' : ')', ':</td><td align="right">',
            $number, '</td></tr>', "\n";
    }
    echo '</table>', "\n";
}

if (isset($lci))
{
    echo '<br><br>', htmlspecialchars($lcn, ENT_QUOTES, $charset), ' ', get_lang('MdCallingTool'),
        ': ', count($lceids), '<br />', "\n";
}



// SELECT & FOOTER ------------------------------------------------------------>

echo '<h3>', $nameTools, $hdrInfo, '</h3>', "\n";

echo '<form action="' .api_get_self(). '?lcn=' . urlencode($lcn) .
    '" method="post">', "\n";

if (count($lceids)) echo
    '<input type="submit" name="slo" value="', get_lang('Create'), '">', "\n";
if ($perCat[$lcn]) echo
    '<input type="submit" name="slo" value="', get_lang('Remove'), '">', "\n";
if ($perCat[$lcn] && file_exists($phpDigIncCn)) echo
    '<input type="submit" name="slo" value="', get_lang('Index'), '">', "\n";

echo '</form>', "\n";

if (count($perCat)) foreach ($perCat as $cat => $number)
    $perCat[$cat] = '(' . htmlspecialchars($cat, ENT_QUOTES, $charset) . ')';

$linkcat_table = Database::get_course_table(TABLE_LINK_CATEGORY);
$result = Database::query("SELECT category_title FROM $linkcat_table");

while ($row = Database::fetch_array($result))
{
    $cat = $row['category_title']; $hcat = htmlspecialchars($cat, ENT_QUOTES, $charset);
    if ($perCat[$cat] == $hcat) $dups[] = $cat;
    else $perCat[$cat] = $hcat;
}

if (count($dups))
{
    $warning = get_lang('WarningDups');;

    foreach ($dups as $cat) unset($perCat[$cat]);
}

echo '<h3>', get_lang('OrElse'), $warning, '</h3>', "\n",  // select new target
    '<table><tr><td align="right" class="alternativeBgDark">', "\n",
    '<form action="'.api_get_self().'" method="post">', "\n",
    get_lang('SLC'), ' :', "\n", '<select name="lcn">', "\n",
    '<option value=""></option>', "\n";

    foreach ($perCat as $cat => $text) echo '<option value="' .
        htmlspecialchars($cat, ENT_QUOTES, $charset) . '"' .
        ($cat == $lcn ? ' selected' : '') . '>' . $text . '</option>', "\n";

echo '</select><input type="submit" value="', '  '.get_lang('Ok').'  ', '">', "\n",
    '</form>', "\n", '</td></tr></table>', "\n";

Display::display_footer();
?>
