<?php /*                             <!-- Dokeos metadata/importmanifest.php -->
                                                             <!-- 2006/12/15 -->

<!-- Copyright (C) 2006 rene.haentjens@UGent.be -  see metadata/md_funcs.php -->

*/

/**
==============================================================================
*	Dokeos Metadata: create and manage table entries for SCORM package
*
*	@package dokeos.metadata
==============================================================================
*/


// PRELIMS -------------------------------------------------------------------->

$getpostvars = array('sdisub','workWith','sdi','smo'); require('md_funcs.php');

define('EID_TYPE', 'Scorm'); define('TPLEN', strlen(EID_TYPE) + 1);

require('md_' . strtolower(EID_TYPE) . '.php');

// name of the language file that needs to be included
$language_file = 'md_' . strtolower(EID_TYPE);
include('../inc/global.inc.php');
$nameTools = get_lang('Tool');

if (!isset($sdisub)) $sdisub = '';
$sdisub = substr(ereg_replace("[^0-9A-Za-z]", "", $sdisub), 0, 4);
// $sdisub is for split manifests - Scorm.NNN.$sdisub_xxx e.g. Scorm.3.1979_12

define('MFFNAME', 'imsmanifest'); define('MFFDEXT', '.xml');
define('HTF', 'mdp_scorm.htt');

$regs = array();

($nameTools && get_lang('Sorry')) or give_up(
    'Language file ' . $language_file . " doesn't define 'Tool' and 'Sorry'");

$_course = api_get_course_info(); isset($_course) or give_up(get_lang('Sorry'));

$is_allowed_to_edit = isset($_user['user_id']) && $is_courseMember && is_allowed_to_edit();
if (!$is_allowed_to_edit) give_up(get_lang('Denied'));

$baseWorkDir = get_course_path() . ($courseDir = $_course['path'] . '/scorm');

$mdStore = new mdstore($is_allowed_to_edit);  // create table if needed

require(api_get_path(LIBRARY_PATH) . 'xmd.lib.php');
require(api_get_path(LIBRARY_PATH) . 'xht.lib.php');
require(api_get_path(LIBRARY_PATH) . 'fileManage.lib.php');

require('md_phpdig.php');


// SET CURRENT SCORM DIRECTORY - HEADER --------------------------------------->

if (isset($workWith))  // explicit in URL, or selected at bottom of screen
{
    $scormdocument = Database::get_course_table(TABLE_LP_MAIN);
    $sql = "SELECT id FROM $scormdocument WHERE path='". Database::escape_string(api_substr($workWith,1)) . "' OR path='". Database::escape_string(substr($workWith,1)) . "/.'";
    $result = Database::query($sql, __FILE__, __LINE__);

    if (Database::num_rows($result) == 1)
    {
        if (($row = Database::fetch_array($result)))
        {
        	$sdi = $row['id'];
        }
    }
}

if (isset($sdi) && is_numeric($sdi) && $sdi > 0 && $sdi == (int) $sdi)
{
    $mdObj = new mdobject($_course, $sdi); $workWith = $mdObj->mdo_path;
    $hdrInfo = ' ' . get_lang('WorkOn') . ' ' .
        ($workWith ? htmlspecialchars($workWith, ENT_QUOTES, $charset) . ', ' : '') .
        'SD-id= ' . htmlspecialchars($sdi, ENT_QUOTES, $charset) .
        ($sdisub ? ' (' . htmlspecialchars($sdisub, ENT_QUOTES, $charset) . ')' : '');
}
else
{
    unset($sdi); $mdObj = new mdobject($_course, 0);
    if ($workWith) $hdrInfo = ' (' . htmlspecialchars($workWith, ENT_QUOTES, $charset) .
        ': ' . get_lang('NotInDB') . ')'; unset($workWith);
}

define('UZYX', 'UZYX');  // magic word to repeat for all $sdisub

if (($sdiall = ($sdisub == UZYX)))
{
	$sdisub = ''; $sdiall = array();
	if (($dh = opendir($baseWorkDir . $workWith)))
    {
        while (FALSE !== ($file = readdir($dh)))
            if (ereg('^'.MFFNAME.'(.+)\\'.MFFDEXT .'$', $file, $regs))
            	$sdiall[] = $regs[1];
        closedir($dh);
    }
    sort($sdiall);
}

$originalHdrInfo = $hdrInfo;

function slurpmanifest()
{
    global $baseWorkDir, $workWith, $sdisub, $mfContents, $xht_doc;
    $fmff = $baseWorkDir .'/'. $workWith . '/' . MFFNAME . $sdisub . MFFDEXT;
    if (file_exists($fmff))
    {
        if (($mfContents = @fgc($fmff)))
        {
            set_time_limit(120);  // for analyzing the manifest file
            $xht_doc = new xmddoc(explode("\n", $mfContents));
            if (!$xht_doc->error) return '';  // keeping $mfContents and $xht_doc

            unset($mfContents);
            return get_lang('ManifestSyntax') . ' ' . htmlspecialchars($xht_doc->error, ENT_QUOTES, $charset);
        }
        else
        {
        	return get_lang('EmptyManifest');
        }
    }
    else
    {
    	return get_lang('NoManifest');
    }
}

if (isset($workWith))  // now checked to be a valid path in scormdocument
{
    if ($mdObj->mdo_filetype == 'folder')  // a folder with a manifest?
    {
    	if (($errmsg = slurpmanifest())) $hdrInfo .= ' ' . $errmsg;
    }
    else
    {
        $hdrInfo .= ' ' . get_lang('NotFolder'); unset($sdi);
    }
}

$mdObj->mdo_add_breadcrump_nav();  // see 'md_' . EID_TYPE . '.php'
if (isset($sdi)) $interbreadcrumb[]= array(
    'url' => api_get_self() . '?sdi=' . urlencode($sdi) .
        ($sdisub ? '&sdisub=' . urlencode($sdisub) :
        	($sdiall ? '&sdisub='.UZYX : '')),
    'name'=> get_lang('Continue') . ' ' . $sdi .
        ($sdisub ? ' (' . $sdisub . ')' : ($sdiall ? ' ('.UZYX.')' : '')));

$htmlHeadXtra[] = '
<link rel="stylesheet" type="text/css" href="md_styles.css">
<script type="text/javascript" src="md_script.js"></script>
';
Display::display_header($nameTools);

// OPERATIONS ----------------------------------------------------------------->

if (isset($smo)) echo '<h3>', $smo, '</h3>', "\n";  // selected manifest op

if (isset($smo))
if ($smo == get_lang('UploadMff'))
{
    if (is_uploaded_file($filespec = $_FILES['import_file']['tmp_name']) &&
            filesize($filespec) && ($myFile = @fopen($filespec, 'r')))
    {
        fclose($myFile);

        if (move_uploaded_file($filespec,
                $baseWorkDir . $workWith . '/' . MFFNAME . $sdisub . MFFDEXT))
        {
            echo get_lang('MffOk'); $hdrInfo = $originalHdrInfo;

            if (($errmsg = slurpmanifest())) $hdrInfo .= ' ' . $errmsg;
        }
        else echo get_lang('MffNotOk');
    }
    else echo get_lang('MffFileNotFound');
}
elseif ($smo == get_lang('UploadHtt'))
{
	$filespec = $_FILES['import_file']['tmp_name'];
    if (is_uploaded_file($filespec) && filesize($filespec) && ($myFile = @fopen($filespec, 'r')))
    {
        fclose($myFile);
        $htt_file = $baseWorkDir .'/'. $workWith . '/' . HTF;
        if (move_uploaded_file($filespec,$htt_file))
        {
        	echo get_lang('HttOk');
        }
        else
        {
       		echo get_lang('HttNotOk');
        }
    }
    else
    {
    	echo get_lang('HttFileNotFound');
    }
}
elseif ($smo == get_lang('RemoveHtt'))
{
    @unlink($fhtf = $baseWorkDir . $workWith . '/' . HTF);
    if (file_exists($fhtf))
         echo get_lang('HttRmvNotOk');
    else echo get_lang('HttRmvOk');
}
elseif ($smo == get_lang('Import'))
{
    define('TREETOP',   'organizations/organization');
    define('TITLE',     'title');
    define('SUBITEM',   'item');
    define('IDENTIF',   'identifier');
    define('ITEMID',    '@'.IDENTIF);
    define('SUBIT',     SUBITEM.'/'.ITEMID);
    define('RESOURCE',  'resources/resource');
    define('WHERE',     ITEMID);
    define('ISITEM',    '@identifierref');
    define('HREF',      'href');
    define('WEBF',      '@'.HREF);
    define('FILE',      'file');
    define('THUMB',     FILE.'[1]/'.WEBF);

    function resource_for($elem)
    {
        global $xht_doc;

        $resForItem = $xht_doc->xmd_select_elements_where(RESOURCE,
            WHERE, $xht_doc->xmd_value(ISITEM, $elem));

        return (count($resForItem) == 0) ? -1 : $resForItem[0];
    }

    function store_md_and_traverse_subitems($mfdocId, $level, $counter,
            $contextElem, $treeElem, $parentElem)
    {
        global $_user, $xht_doc, $mdStore, $mdObj, $sdisub;

        //  $contextElem -> @identifier, metadata/lom
        //  $treeElem ->    title, items

        $itemId = $xht_doc->xmd_value(ITEMID, $contextElem);
        if ($sdisub && $level == 1 && $sdisub != $itemId) return;

        //  <item level=... number=... identifier=...>:
        //      <title>...</title>
        //      <parent identifier=... /> <previous ... /> <next ... />
        //      <child identifier=... /> <child identifier=... /> ...
        //      <resource href=...>
        //          <file href=... /> <file href=... /> ...
        //      </resource>
        //      <metadata>...</metadata>
        //  </item>

        set_time_limit(30);  // again 30 seconds from here on...

        $mddoc = new xmddoc('<item/>');  // version, name ?
        $mddoc->xmd_set_attribute(0, 'level', $level, FALSE);
        $mddoc->xmd_set_attribute(0, 'number', $counter, FALSE);
        $mddoc->xmd_set_attribute(0, IDENTIF, $itemId, FALSE);

        if ($level == 0)
        {
            $mddoc->xmd_set_attribute(0, 'created', date('Y/m/d H:i:s'), FALSE);
            $mddoc->xmd_set_attribute(0, 'by', $_user['user_id'], FALSE);
        }


        $mddoc->xmd_add_text_element(TITLE,
            $xht_doc->xmd_value(TITLE, $treeElem));

        if (($ppnId = $xht_doc->xmd_value(ITEMID, $parentElem))) $mddoc->
            xmd_add_element('parent', 0, array(IDENTIF => $ppnId));
        if (($ppnId = $xht_doc->xmd_value('-'.SUBIT, $treeElem))) $mddoc->
            xmd_add_element('previous', 0, array(IDENTIF => $ppnId));
        if (($ppnId = $xht_doc->xmd_value('+'.SUBIT, $treeElem))) $mddoc->
            xmd_add_element('next', 0, array(IDENTIF => $ppnId));

        if (($srcElem = resource_for($treeElem)) > 0)
        {
            // change stuff below to xmd_copy_foreign_child ?
            $resElem = $mddoc->xmd_add_element('resource', 0,
                array(HREF => $xht_doc->xmd_value(WEBF, $srcElem)));
            foreach ($xht_doc->xmd_select_elements(FILE, $srcElem) as $fileElem)
                $mddoc->xmd_add_element(FILE, $resElem,
                    array(HREF => $xht_doc->xmd_value(WEBF, $fileElem)));
        }

        $mddoc->xmd_copy_foreign_child($xht_doc,
            $xht_doc->xmd_select_single_element('metadata', $contextElem));

        foreach ($xht_doc->xmd_select_elements(SUBITEM, $treeElem) as $subElem)
            $mddoc->xmd_add_element('child', 0,
                array(IDENTIF => $xht_doc->xmd_value(ITEMID, $subElem)));

        $mdt = $mddoc->xmd_xml();

        $xhtDoc = $mdObj->mdo_define_htt();
        $xhtDoc->xht_xmldoc = $mddoc;  // $xhtDoc->xht_param['xxx'] = 'yyy';

        $mdStore->mds_put($eid = EID_TYPE . '.' . $mfdocId . '.' . $itemId,
            $mdt, 'mdxmltext', '?');
        $mdStore->mds_put($eid, $ixt =
            $xhtDoc->xht_fill_template('INDEXABLETEXT'), 'indexabletext');

        if ($level == 0)  // store a copy as 'Scorm.nnn'
        {
            $mdStore->mds_put(EID_TYPE . '.' . $mfdocId, $mdt, 'mdxmltext', '?');
            $mdStore->mds_put(EID_TYPE . '.' . $mfdocId, $ixt, 'indexabletext');
        }

        echo $level <= 1 ? '<br />'.$level.'/ ' : ' ', htmlspecialchars($itemId, ENT_QUOTES, $charset);
        flush(); $loopctr = 0;

        foreach ($xht_doc->xmd_select_elements(SUBITEM, $treeElem) as $subElem)
        {
            store_md_and_traverse_subitems($mfdocId, $level + 1, ++$loopctr,
                $subElem, $subElem, $contextElem);
            // note: replacing this recursion by queue+loop makes it slower!
        }
    }

    function content_for_index_php($scid)
    {
        // 'if {}' and 'else {}' are string literals spanning several lines

        return '<?php' .
'
        // This PHP file has been generated by metadata/importmanifest.php

        if (isset($_GET["th"]) && ($th = str_replace("..", "",
            get_magic_quotes_gpc() ? stripslashes($_GET["th"]) : $_GET["th"])))
        {
            if (strtolower(substr($th, -4)) != ".jpg") exit;  // other ext?

            $thf = $_SERVER["PHP_SELF"];
            if(!is_file($thf = $_SERVER["DOCUMENT_ROOT"] .
                substr($thf, 0, strrpos($thf, "/")) . "/" . $th)) exit;

            header("Content-disposition: filename=".basename($th));
            header("Content-Type: image/jpeg");
            header("Expires: ".gmdate("D, d M Y H:i:s",time()+10)." GMT");
            header("Last-Modified: ".gmdate("D, d M Y H:i:s",time()+10)." GMT");

            $fp = fopen($thf, "rb"); fpassthru($fp); fclose($fp);
        }
'
            . str_replace('<SYS_PATH-placeholder>', api_get_path(SYS_PATH),
                str_replace('<scid>', $scid,  // 2 * replace in $drs-line below
'
        else
        {
            $drs = "<SYS_PATH-placeholder>"; $scormid = "<scid>";
            require($drs. "main/metadata/playscormmdset.inc.php");
        }
'           )) . '?' . '>';
    }

    if ($mfContents)
    {
        if ($sdiall)
        {
        	foreach ($sdiall as $sdisub)
        	{
		        if (($errmsg = slurpmanifest()))
		        	echo '? ', $sdisub, ': ', $errmsg, '<br>';
		        else
			        store_md_and_traverse_subitems($sdi, 0, 1, 0,
			            $xht_doc->xmd_select_single_element(TREETOP), -1);
		    }
		    $sdisub = '';
		}
        else  // just once, slurpmanifest() has already been done
	        store_md_and_traverse_subitems($sdi, 0, 1, 0,
	            $xht_doc->xmd_select_single_element(TREETOP), -1);

        $playIt = $baseWorkDir .'/'. $workWith . '/index.php';
        $fileHandler = @fopen($playIt, 'w');
        @fwrite($fileHandler, content_for_index_php($sdi));
        @fclose($fileHandler);

    	echo '<br>', htmlspecialchars($workWith, ENT_QUOTES, $charset);
    	if (file_exists($playIt)) echo '/index.php ',
    	    htmlspecialchars(date('Y/m/d H:i:s', filemtime($playIt)), ENT_QUOTES, $charset);
	}
}
elseif ($smo == get_lang('Remove') && $sdisub)
{
    $screm = EID_TYPE . '.' . $sdi . '.' . $sdisub;
    $mdStore->mds_delete_offspring($screm, '\_');  // SQL LIKE underscore
    echo htmlspecialchars($screm . '_*: ' . mysql_affected_rows(), ENT_QUOTES, $charset), '<br />';
}
elseif ($smo == get_lang('Remove'))  // remove all, regardless of $sdiall
{
    $mdStore->mds_delete($screm = EID_TYPE . '.' . $sdi);
    echo htmlspecialchars($screm . ': ' . mysql_affected_rows(), ENT_QUOTES, $charset), '<br />';
    $mdStore->mds_delete_offspring($screm);
    echo htmlspecialchars($screm . '.*: ' . mysql_affected_rows(), ENT_QUOTES, $charset), '<br /><br />',
	'<b>' . get_lang('AllRemovedFor') . ' ' . $screm . '</b><br />';
}
elseif ($smo == get_lang('Index') && file_exists($phpDigIncCn) &&
        ereg('^http://([^/]+)/(.+)/index\.php$', $mdObj->mdo_url, $regs))
{
    $result = $mdStore->mds_get_many('eid,mdxmltext,indexabletext',
        "eid LIKE '" . EID_TYPE . "." . $sdi .
        ($sdisub ? "." . $sdisub . "\_%'" : ".%'") .
        ($sdiall ? "" : " AND NOT INSTR(eid,'_')"));  // SQL LIKE underscore

    while ($row = Database::fetch_array($result))  // load indexabletexts in memory
    {
        // URL: index.php[?sid=xxx[&thumb=yyy]] (file[1]/@href: pptslnnn_t.jpg)

        $th = ''; $indtxt = $row['indexabletext'];

        if (($fh = strpos($rx = $row['mdxmltext'], 'file href="')) !== FALSE)
        if (($cq = strpos($rx, '"', $fh += 11)) !== FALSE)
        if (ereg('^pptsl[0-9]+_t\.jpg$', $thwf = substr($rx, $fh, $cq - $fh)))
            $th = '&thumb=' . urlencode($thwf);

        if ($th == '' && ($sclvl = strpos($indtxt, 'scorm-level-')) !== FALSE)
            $th = '&thumb=scorm-level-' . $indtxt{$sclvl + 12} . '.jpg';

        $idt[($dotpos = strpos($ri = $row['eid'], '.', TPLEN)) !== FALSE ?
            ('index.php?sid=' . urlencode(substr($ri, $dotpos+1)) . $th) :
            'index.php'] = $indtxt;
    }

    require($phpDigIncCn);  // switch to PhpDig DB

    if (($site_id = remove_engine_entries('http://' . $regs[1] .'/', $path =
            $regs[2] . '/', $sdisub ? 'index.php?sid=' . $sdisub . '_' : '')))
    {
        echo '<table>', "\n";
        foreach ($idt as $url => $text)
        {
            set_time_limit(30);  // again 30 seconds from here on...
            index_words($site_id, $path, $url,
                get_first_words($text, $path, $url), get_keywords($text));
        }
        echo '</table>', "\n";
    }
    // possible enhancement: UPDATE spider record for still existing pages

    if(isset($db)) mysql_select_db($_configuration['main_database'], $db);  // back to Dokeos
}
elseif ($smo == get_lang('Index'))
{
    echo 'Problem! PhpDig connect.php has gone or else URL "' .
        htmlspecialchars($mdObj->mdo_url, ENT_QUOTES, $charset) .
        '" is not like "http://xxxx/yyy.../zzz/index.php"';
}


// STATISTICS ----------------------------------------------------------------->

echo '<h3>', get_lang('Statistics'), '</h3>', "\n";

$result = $mdStore->mds_get_many('eid', "eid LIKE '" . EID_TYPE . ".%'");
echo get_lang('TotalMDEs'), Database::num_rows($result), "\n";

while ($row = Database::fetch_array($result))
{
    $eid_id = substr($eid = $row['eid'], TPLEN);

    if (($dotpos = strpos($eid_id, '.')))
        $eid_id = substr($eid_id, 0, $dotpos);
    else
        $mdtmain[$eid_id] = $mdStore->mds_get($eid);

    $perId[$eid_id] = ($pi = $perId[$eid_id]) ? $pi + 1 : 1;
}


if (isset($sdi))
{
    $mdo = new mdobject($_course, $sdi);
    echo '<br />', htmlspecialchars($mdo->mdo_path, ENT_QUOTES, $charset), ', SD-id ', $sdi, ': ',
        ($perId[$sdi] ? $perId[$sdi] : '0'), ' ',
        ($mdtmain[$sdi] ? '- <span class="lbs" onClick="' .
            "makeWindow('index.php?eid=" . EID_TYPE . '.' .$sdi . "', '', '')\">" .
            get_lang('MainMD') . '</span>' : ''), "\n";
}

if (count($perId))
{
    foreach ($perId as $id => $number)
    {
        $mdo = new mdobject($_course, $id);
        if (!($pth = $mdo->mdo_path))
        {
            $pth = $mdtmain[$id];  // fetch something simple without parsing
            if ($ttopen = strpos($pth, '<title>'))
                if ($ttclose = strpos($pth, '</title>', $ttopen))
                     $pth = ' ' . api_html_entity_decode
                        (substr($pth, $ttopen+7, $ttclose-$ttopen-7), ENT_QUOTES, $charset);
                else $pth = ' ' . substr($pth, $ttopen+7, 30);
            else     $pth = ' ' . substr($pth, 0, 30);
        }

        $pathId[$pth] = $id;
    }

    echo '<br><br><table>', "\n"; ksort($pathId); $wwl = strlen($workWith);

    foreach ($pathId as $pth => $id) if ($wwl == 0 ||
            ($wwl < strlen($pth) && substr($pth, 0, $wwl) == $workWith))
    {
        $tmfdt = file_exists($tfmff = $baseWorkDir . $pth . '/' . MFFNAME . $sdisub . MFFDEXT) ?
            date('Y/m/d H:i:s', filemtime($tfmff)) : '-';
        echo '<tr><td>', htmlspecialchars($tmfdt, ENT_QUOTES, $charset), '</td>',
            '<td>', htmlspecialchars($pth, ENT_QUOTES, $charset),
            '</td><td align="right">(SD-id ', $id,
            '):</td><td align="right">', $perId[$id], '</td></tr>', "\n";
    }
    echo '</table>', "\n";
}

if ($mfContents)
{
    echo $workWith, '/', MFFNAME . $sdisub . MFFDEXT, ': ',
        htmlspecialchars(date('Y/m/d H:i:s', filemtime($fmff)), ENT_QUOTES, $charset) , ", \n",
        substr_count($mfContents, "\n") + 1,
        ' ' . get_lang('Lines') . '.', "\n";

    if (!$sdisub && ($dh = opendir($baseWorkDir . $workWith)))
    {
        $nsplit = array();
        while (FALSE !== ($file = readdir($dh)))
            if (ereg('^'.MFFNAME.'(.+)\\'.MFFDEXT .'$', $file, $regs))
            {
                $nsplit []= $regs[1];
            }
        closedir($dh);

        if (count($nsplit))
        {
            echo '<br>', get_lang('SplitData'); sort($nsplit);
            foreach ($nsplit as $ns)
            {
                $result = $mdStore->mds_get_many('eid', "eid LIKE '" .
                    EID_TYPE . "." . $sdi . "." . $ns . "\_%'");
                $nns = Database::num_rows($result);
                echo $ns, $nns ? '_ ' . $nns : '', '; ';
            }
            echo '<br>';
        }
    }
}

if (file_exists($baseWorkDir . $workWith . '/index.php'))
    echo "<span class=\"lbs\" onClick=\"makeWindow('" .
        $mdObj->mdo_url . "', '', '')\">" . get_lang('Play'), '</span>', "\n";

if (file_exists($fhtf = $baseWorkDir . $workWith . '/' . HTF))
    echo '<br>', $workWith, '/', HTF, ': ',
        htmlspecialchars(date('Y/m/d H:i:s', filemtime($fhtf)), ENT_QUOTES, $charset) , "\n";



// SELECT & FOOTER ------------------------------------------------------------>

if ($mfContents || $xht_doc->error)
{
    echo '<h3>', get_lang('UploadMff'), "</h3>\n\n",
        '<form action="' .api_get_self(). '?sdi=' . urlencode($sdi) .
                ($sdisub ? '&sdisub=' . urlencode($sdisub) : ($sdiall ? '&sdisub='.UZYX : '')) .
                '" enctype="multipart/form-data" method="post">', "\n",
            '<input type="hidden" name="MAX_FILE_SIZE" value="32768">', "\n",
            '<input type="file" name="import_file" size="30">', "\n",
            '<input type="submit" name="smo" value="', get_lang('UploadMff'),
            '">' . "\n</form>\n";
}

echo '<h3>', get_lang('UploadHtt'), file_exists($fhtf) ?
    (' + ' . get_lang('RemoveHtt')) : '', "</h3>\n\n",

    '<form action="' .api_get_self(). '?sdi=' . urlencode($sdi) .
            ($sdisub ? '&sdisub=' . urlencode($sdisub) : ($sdiall ? '&sdisub='.UZYX : '')) .
            '" enctype="multipart/form-data" method="post">', "\n",
        '<input type="hidden" name="MAX_FILE_SIZE" value="32768">', "\n",
        '<input type="file" name="import_file" size="30">', "\n",
        '<input type="submit" name="smo" value="', get_lang('UploadHtt'), '">';
if  (file_exists($fhtf)) echo
        '<input type="submit" name="smo" value="', get_lang('RemoveHtt'), '">';
echo "\n</form>\n";

echo '<h3>', $nameTools, $hdrInfo, '</h3>', "\n";

if ($mfContents || $perId[$sdi])  // buttons for manifest operations
{
    echo '<form action="' .api_get_self(). '?sdi=' . urlencode($sdi) .
        ($sdisub ? '&sdisub=' . urlencode($sdisub) : ($sdiall ? '&sdisub='.UZYX : '')) .
        '" method="post">', "\n";
    if ($mfContents) echo
    '<input type="submit" name="smo" value="', get_lang('Import'), '">', "\n";
    if ($perId[$sdi]) echo
    '<input type="submit" name="smo" value="', get_lang('Remove'), '">', "\n";
    if ($mfContents && $perId[$sdi] && file_exists($phpDigIncCn)) echo
    '<input type="submit" name="smo" value="', get_lang('Index'), '">', "\n";
    echo
    '</form>', "\n";
}
else
{
    echo '(', get_lang('NonePossible'), '...)';
}


function showSelectForm($label, $specifics)
{
    echo    '<tr><td align="right" class="alternativeBgDark">', "\n",
        	'<form action="', api_get_self(), '" method="post">', "\n",
            	get_lang($label), ' :', "\n", $specifics, "\n",
                '<input type="submit" value="', '  '.get_lang('Ok').'  ', '" />', "\n",
            '</form></td></tr>', "\n";
}

echo '<h3>', get_lang('OrElse'), '</h3>', "\n<table>\n";

$specifics = '<select name="workWith">' . "\n" .
	'<option value="" style="color:#999999">' . get_lang('Root') . "</option>\n";

if (($dirList = index_and_sort_dir($baseWorkDir)))  // fileManage.lib
{
    $someDirs = array();

    foreach ($dirList as $pathValue)
        $someDirs[$pathValue] = file_exists($pathValue. '/'. MFFNAME. MFFDEXT);

    foreach ($someDirs as $pathValue => $mfExists) if ($mfExists)
    {
        while (($i = strrpos($pathValue, '/')))
        {
            $pathValue = substr($pathValue, 0, $i);
            if (!array_key_exists($pathValue, $someDirs)) break;
            $someDirs[$pathValue] = TRUE;
        }
    }

    $bwdL = strlen($baseWorkDir);

    foreach ($someDirs as $pathValue => $mfExists) if ($mfExists)
    {
		$pathValue = substr($pathValue, $bwdL);
		$specifics .= '<option value="' . $pathValue . '"' .
		    ($pathValue == $workWith ? ' selected' : '') . '>' .
		    str_repeat('&nbsp;&nbsp;', substr_count($pathValue, '/')) . '>' .
		    basename($pathValue) . '</option>' . "\n";
	}
}

showSelectForm('WorkWith', $specifics . '</select>');

showSelectForm('SDI',
    '<input type="text" size="5" name="sdi" value="' .
        htmlspecialchars($sdi) . '" />' .
    '(<input type="text" size="4" name="sdisub" value="' .
        ($sdiall ? UZYX : htmlspecialchars($sdisub, ENT_QUOTES, $charset)) . '" />)' . "\n");

echo '</table>', "\n";


Display::display_footer();
?>
