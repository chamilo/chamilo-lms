<?php /*                                      <!-- Dokeos metadata/index.php -->
                                                             <!-- 2005/05/19 -->

<!-- Copyright (C) 2005 rene.haentjens@UGent.be -  see metadata/md_funcs.php -->

*/

/**
==============================================================================
*	Dokeos Metadata: view/edit metadata of a Dokeos course object
*
*   URL parameters:
*   - eid=  entry-id = object-id = type.identifier, e.g. 'Document.12';
*   - lfn=  filename of a language file, default= 'md_' + type, e.g. 'md_doc';
*   - htt=  HTML template file (same dir as script), default= same as lfn;
*   - dbg=  debuginfo start number, e.g. 10000
*
*	@package dokeos.metadata
==============================================================================
*/


// PRELIMS -------------------------------------------------------------------->

require_once '../inc/global.inc.php';

require("md_funcs.php");

getpar('EID', 'Entry IDentifier');           // e.g. 'Document.12' or 'Scorm.xx'
if (!($dotpos = strpos(EID, '.'))) give_up('No . in ' . EID);

define('EID_TYPE', substr(EID, 0, $dotpos)); // e.g. 'Document' or 'Scorm'
require('md_' . strtolower(EID_TYPE) . '.php');

define('EID_ID', substr(EID, $dotpos + 1));  // e.g. '12'

getpar('LFN', 'LanguageFileName', 'md_' . strtolower(EID_TYPE));
getpar('HTT', 'HTML Template Text filename', LFN);

getpar('DBG', 'Debug number', '0');

$urlp =                                              '?eid=' . urlencode(EID);
if (LFN != 'md_' . strtolower(EID_TYPE))    $urlp .= '&lfn=' . urlencode(LFN);
if (HTT != LFN)                             $urlp .= '&htt=' . urlencode(HTT);
if (DBG)                                    $urlp .= '&dbg=' . urlencode(DBG);

// name of the language file that needs to be included
$language_file = LFN;
require("../inc/global.inc.php");
$this_section=SECTION_COURSES;

$nameTools = get_lang('Tool');

($nameTools && get_lang('Sorry'))
    or give_up('Language file ' . LFN . " doesn't define 'Tool' and 'Sorry'");

$_course = api_get_course_info(); isset($_course) or give_up(get_lang('Sorry'));

require(api_get_path(LIBRARY_PATH) . 'xmd.lib.php');
require(api_get_path(LIBRARY_PATH) . 'xht.lib.php');

$mdObj = new mdobject($_course, EID_ID);  // see 'md_' . EID_TYPE . '.php'


// Construct assoclist $langLangs from language table ------------------------->

$result = Database::query("SELECT isocode FROM " .
    Database :: get_main_table(TABLE_MAIN_LANGUAGE) .
    " WHERE available='1' ORDER BY isocode ASC", __FILE__, __LINE__);

$sep = ":"; $langLangs = $sep . "xx" . $sep . "xx";

while ($row = Database::fetch_array($result))
    if (($isocode = $row['isocode']))
    	$langLangs .= ",, " . $isocode . $sep . $isocode;


// XML and DB STUFF ----------------------------------------------------------->

$is_allowed_to_edit = isset($_user['user_id']) && $is_courseMember && is_allowed_to_edit();

$mdStore = new mdstore($is_allowed_to_edit);

if (($mdt_rec = $mdStore->mds_get(EID)) === FALSE)  // no record, default XML
     $mdt = $mdObj->mdo_generate_default_xml_metadata();
else $mdt = $mdt_rec;

$xhtxmldoc = new xmddoc(explode("\n", $mdt));

$httfile = ($xhtxmldoc->error) ? 'md_editxml.htt' : HTT . '.htt';

if (!$xhtxmldoc->error && $mdt_rec !== FALSE &&
        method_exists($mdObj, 'mdo_override'))
    $mdt = $mdObj->mdo_override($xhtxmldoc);

$xhtDoc = define_htt($httfile, $urlp, $_course['path']);

define('HSH', md5($mdt . LFN . $nameTools . get_lang('Sorry') . $httfile .
    implode('{}', $xhtDoc->htt_array)));  // cached HTML depends on LFN+HTT

$xhtDoc->xht_param['traceinfo'] = $xhtxmldoc->error;
$xhtDoc->xht_param['dbrecord'] = $mdt_rec !== FALSE ? 'TRUE' : '';

$xhtDoc->xht_xmldoc = $xhtxmldoc;

if ($is_allowed_to_edit) $xhtDoc->xht_param['isallowedtoedit'] = 'TRUE';

if ($is_allowed_to_edit && isset($_POST['mda']))  // MD updates to Doc and DB
{
    $mdt = $mdStore->mds_update_xml_and_mdt($mdObj, $xhtDoc->xht_xmldoc,
        get_magic_quotes_gpc() ? stripslashes($_POST['mda']) : $_POST['mda'],
        EID, $xhtDoc->xht_param['traceinfo'], $mdt_rec !== FALSE);

    if ($mdt_rec !== FALSE)
    {
         if (strpos($xhtDoc->xht_param['traceinfo'], 'DELETE') !== FALSE)
            $xhtDoc->xht_param['dbrecord'] = '';
    }
    else if (strpos($xhtDoc->xht_param['traceinfo'], 'INSERT') !== FALSE)
            $xhtDoc->xht_param['dbrecord'] = 'TRUE';

    if (method_exists($mdObj, 'mdo_storeback'))
        $mdObj->mdo_storeback($xhtDoc->xht_xmldoc);

    $mdt_rec = FALSE;  // cached HTML obsolete, must re-apply templates
}
elseif ($is_allowed_to_edit && $_POST['mdt'])  // md_editxml.htt
{
    $mdStore->mds_put(EID,
        get_magic_quotes_gpc() ? stripslashes($_POST['mdt']) : $_POST['mdt'],
        'mdxmltext', '?');
    $mdStore->mds_put(EID, HSH, 'md5');

    $xhtDoc->xht_param['dbrecord'] = 'TRUE';

    $mdt = ''; $xhtDoc->xht_param['traceinfo'] = get_lang('PressAgain');

    $mdt_rec = FALSE;  // cached HTML obsolete, must re-apply templates
}

$xhtDoc->xht_param['mdt'] = $mdt;

define('CACHE_IS_VALID', isset($mdt_rec) && $mdt_rec !== FALSE &&
    HSH && HSH == $mdStore->mds_get(EID, 'md5'));


function md_part($part, $newtext)  // callback from template (HTML cache in DB)
{
    global $mdStore;

    if ($newtext === FALSE)
    {
        if (!CACHE_IS_VALID) return FALSE;
        return '<!-- ' . $part . ' -->' . $mdStore->mds_get(EID, $part);
    }
    else
    {
        $mdStore->mds_put(EID, HSH, 'md5');
        $mdStore->mds_put(EID, $newtext, $part);

        return $newtext;
    }
}

function md_part1($newtext)         { return md_part('htmlcache1',    $newtext); }
function md_part2($newtext)         { return md_part('htmlcache2',    $newtext); }
function md_indexabletext($newtext) { return md_part('indexabletext', $newtext); }


// GENERATE OUTPUT ------------------------------------------------------------>

foreach (explode("\n", $xhtDoc->htt_array['HTTP']) as $httpXtra)
    if ($httpXtra) $httpHeadXtra[] = $httpXtra;

$xhtDoc->xht_get_lang = 'get_lang';

function resource_for($e) {return $e;}  // dummy, '=/' not used here
$xhtDoc->xht_resource = 'resource_for';

$htmlHeadXtra[] = $xhtDoc->xht_fill_template('HEAD');

$mdObj->mdo_add_breadcrump_nav();  // see 'md_' . EID_TYPE . '.php'
$noPHP_SELF = TRUE;  // in breadcrumps

Display::display_header($nameTools); echo "\n";

$xhtDoc->xht_dbgn = DBG;  // for template debug info, set to e.g. 10000
if (($ti = $xhtDoc->xht_param['traceinfo'])) $xhtDoc->xht_param['traceinfo'] =
    '<h5>Trace information</h5>' . htmlspecialchars($ti, ENT_QUOTES, $charset);

echo $xhtDoc->xht_fill_template('METADATA'), "\n";

if ($xhtDoc->xht_dbgn) echo $xhtDoc->xht_dbgo;

Display::display_footer();
?>
