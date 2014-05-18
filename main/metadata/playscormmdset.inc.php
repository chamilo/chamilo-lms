<?php 
/** 
 * Chamilo metadata/playscormmdset.inc.php
 * 2005/11/16
 * Copyright (C) 2005 rene.haentjens@UGent.be -  see metadata/md_funcs.php
 * @package chamilo.metadata
 */
/**
 * Chamilo Metadata: include file for accessing Scorm metadata
 * This script is to be included from /coursedir/scorm/dir.../index.php,
 * after setting $scormid (Chamilo document root).
 */
// PRELIMS -------------------------------------------------------------------->

if (!isset($scormid)) exit();

define('EID_TYPE', 'Scorm');
define('BID', EID_TYPE . '.' . $scormid);
getpar('SID', 'Scorm sub-id', '*');
define('EID_ID', (SID == '*') ? $scormid : $scormid . '.' . SID);
define('EID', EID_TYPE . '.' . EID_ID);
getpar('LFN', 'LanguageFileName', 'md_' . strtolower(EID_TYPE));
getpar('HTT', 'HTML Template Text filename', 'mdp_' . strtolower(EID_TYPE));
getpar('WHF', 'With Header and Footer', '0');
define('DBG', 0);  // for template debug info, set to e.g. 10000
getpar('RNG', 'Slide range', '*');

if (RNG == '*' || ($dotdot = strpos(RNG, '..')) === FALSE)
    $id_range_first = $id_range_last = '';
else
{
    $id_range_first = trim(substr(RNG, 0, $dotdot));
    $id_range_last =  trim(substr(RNG, $dotdot + 2));
}

$urlp = '?dbg=' .                                              urlencode(DBG);
if (LFN != 'md_' .  strtolower(EID_TYPE))   $urlp .= '&lfn=' . urlencode(LFN);
if (HTT != 'mdp_' . strtolower(EID_TYPE))   $urlp .= '&lfn=' . urlencode(HTT);
if (WHF != '0')                             $urlp .= '&whf=' . urlencode(WHF);
if (RNG != '*')                             $urlp .= '&rng=' . urlencode(RNG);

// name of the language file that needs to be included
$language_file = LFN;
require('../inc/global.inc.php');
$nameTools = get_lang('Tool');

require(api_get_path(SYS_CODE_PATH) . 'metadata/md_funcs.php');

($nameTools && get_lang('Sorry'))
    or give_up('Language file ' . LFN . " doesn't define 'Tool' and 'Sorry'");

$_course = api_get_course_info(); isset($_course) or give_up(get_lang('Sorry'));

require(api_get_path(LIBRARY_PATH) . 'xmd.lib.php');
require(api_get_path(LIBRARY_PATH) . 'xht.lib.php');

require(api_get_path(SYS_CODE_PATH) . 'metadata/md_' . strtolower(EID_TYPE) . '.php');
$mdObj = new mdobject($_course, EID_ID);

define('DR', $_SERVER['DOCUMENT_ROOT']);
define('SELF', api_get_self());
define('DIRECTORY', DR . $self = substr(SELF, 0, strrpos(SELF, '/')));
if (!file_exists(DIRECTORY)) give_up('No such directory: ' . DIRECTORY);


// TEMPLATES FILE ------------------------------------------------------------->

$topdir = strtolower(realpath(DR));  // to stop search for .htt file

if (strpos(strtolower(realpath(DIRECTORY)), $topdir) !== 0)
    give_up('Invalid directory: ' . DIRECTORY);

chdir(DIRECTORY);

for ($i = 0; $i < 10; $i++)
    if(!file_exists(HTT . '.htt'))
        if (strtolower(realpath(getcwd())) == $topdir) {break;}
        else chdir('..');


// XML and DB STUFF ----------------------------------------------------------->

$is_allowed_to_edit = isset($_user['user_id']) && $is_courseMember && api_is_allowed_to_edit();

$mdStore = new mdstore($is_allowed_to_edit);

if (($mdt_rec = $mdStore->mds_get(EID)) === FALSE)  // no record, default XML
     $mdt = $mdObj->mdo_generate_default_xml_metadata();
else $mdt = $mdt_rec;

$xhtxmldoc = new xmddoc(explode("\n", $mdt));

(!$xhtxmldoc->error) or give_up($xhtxmldoc->error);

if (SID == $id_range_first &&
        ($prv = $xhtxmldoc->xmd_select_single_element('previous')) != -1)
    $xhtxmldoc->xmd_remove_element($prv);

if (SID == $id_range_last &&
        ($nxt = $xhtxmldoc->xmd_select_single_element('next')) != -1)
    $xhtxmldoc->xmd_remove_element($nxt);

$before_first = $id_range_first ? TRUE : FALSE; $after_last = FALSE;

foreach ($xhtxmldoc->xmd_select_elements('child') as $chEl)
{
    $chId = $xhtxmldoc->attributes[$chEl]['identifier'];  // no get_att yet...

    if ($after_last ||
        ($before_first = $before_first && $chId != $id_range_first))
    {
        $xhtxmldoc->xmd_remove_element($chEl); continue;
    }

    if (($mdt_rec = $mdStore->mds_get(BID . '.' . $chId)) === FALSE)
         $mdt = $mdObj->mdo_generate_default_xml_metadata();
    else $mdt = $mdt_rec;

    $xhtxmldocchild = new xmddoc(explode("\n", $mdt));

    (!$xhtxmldocchild->error) or give_up($chId . ': ' . $xhtxmldocchild->error);

    // make stuff below a parameter? copy some already in importmanifest?
    $xhtxmldoc->xmd_copy_foreign_child($xhtxmldocchild,
        $xhtxmldocchild->xmd_select_single_element('title'), $chEl);
    $xhtxmldoc->xmd_copy_foreign_child($xhtxmldocchild,
        $xhtxmldocchild->xmd_select_single_element('resource'), $chEl);

    $after_last = $after_last || $chId == $id_range_last;
}

$xhtDoc = define_htt(HTT . '.htt', $urlp, $_course['path']);
$xhtDoc->xht_xmldoc = $xhtxmldoc;

$xhtDoc->xht_param['mdt'] = $xhtxmldoc->xmd_xml();


// GENERATE OUTPUT ------------------------------------------------------------>

foreach (explode("\n", $xhtDoc->htt_array['HTTP']) as $httpXtra)
    if ($httpXtra) $httpHeadXtra[] = $httpXtra;

$xhtDoc->xht_get_lang = 'get_lang';

function resource_for($e) {return $e;}  // dummy, '=/' not used here
$xhtDoc->xht_resource = 'resource_for';

$htmlHeadXtra[] = $xhtDoc->xht_fill_template('HEAD');

// $mdObj->mdo_add_breadcrump_nav();  // see 'md_' . EID_TYPE . '.php'
$noPHP_SELF = TRUE;  // in breadcrumps

if (WHF != '0') Display::display_header($nameTools);
else
{
    header('Content-Type: text/html; charset='. $charset); $document_language = 'en';
    if ( isset($httpHeadXtra) && $httpHeadXtra )
    {
    	foreach($httpHeadXtra as $thisHttpHead)
    	{
    		header($thisHttpHead);
    	}
    }
    ?>
    <!DOCTYPE html
         PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $document_language; ?>" lang="<?php echo $document_language; ?>">
    <head>
    <title>Scorm package
    </title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
    <link rel="stylesheet" href="<?php echo api_get_path(WEB_CODE_PATH); ?>css/default.css" type="text/css" media="screen,projection" />
    <style type="text/css" media="screen, projection">
    /*<![CDATA[*/
    @import "<?php echo api_get_path(WEB_CODE_PATH); ?>css/default.css";
    /*]]>*/
    </style>
    <?php
    if ( isset($htmlHeadXtra) && $htmlHeadXtra )
    {
    	foreach($htmlHeadXtra as $this_html_head)
    	{
    		echo($this_html_head);
    	}
    }
    ?>
    </head>
    <body dir="<?php echo $text_dir; ?>">
    <!-- #outerframe container to control some general layout of all pages -->
    <div id="outerframe">
    <?php
}

echo "\n";

$xhtDoc->xht_dbgn = DBG;  // for template debug info, set to e.g. 10000
if (($ti = $xhtDoc->xht_param['traceinfo'])) $xhtDoc->xht_param['traceinfo'] =
    '<h5>Trace information</h5>' . htmlspecialchars($ti, ENT_QUOTES, $charset);

echo $xhtDoc->xht_fill_template('METADATA'), "\n";

if ($xhtDoc->xht_dbgn) echo $xhtDoc->xht_dbgo;

if (WHF != '0')
{
    Display::display_footer();
    exit;
}

?>
    <div class="clear">&nbsp;</div> <!-- 'clearing' div to make sure that footer stays below the main and right column sections -->
    </div> <!-- end of #outerframe opened in header.inc.php -->
    </body>
    </html>
