<?php
/**
 * Chamilo metadata/search.php
 * @date 2005/09/20
 * @copyright 2005 rene.haentjens@UGent.be -  see metadata/md_funcs.php
 * @package dokeos.metadata
 */
/**
 *	Chamilo Metadata: search Chamilo course objects via their metadata
 *   URL parameters:
 *   - type= type, must be 'Mix' (currently: Document + Scorm + Link)
 *   - lfn=  filename of a language file, e.g. 'md_doc', default= 'md_' + type;
 *   - htt=  HTML template file (same dir as script), default= 'mds_' + type.
 */
// PRELIMS -------------------------------------------------------------------->

require("md_funcs.php");

getpar('TYPE', 'e.g. Mix', 'Mix');  // note: only 'Mix' is currently working
require('md_' . strtolower(TYPE) . '.php');

getpar('LFN', 'LanguageFileName', 'md_' . strtolower(TYPE));
getpar('HTT', 'HTML Template Text filename', 'mds_' . strtolower(TYPE));

getpar('DBG', 'Debug number', '0');  // set to e.g. 10000 for debuginfo

$urlp =                                           '?type='. urlencode(TYPE);
if (LFN != 'md_' . strtolower(TYPE))     $urlp .= '&lfn=' . urlencode(LFN);
if (HTT != 'mds_' . strtolower(TYPE))    $urlp .= '&htt=' . urlencode(HTT);
if (DBG)                     $urlp .= '&dbg=' . urlencode(DBG);

// name of the language file that needs to be included
$language_file = LFN; require("../inc/global.inc.php");
$this_section=SECTION_COURSES;

$nameTools = get_lang('Tool');

($nameTools && get_lang('Sorry'))
    or give_up('Language file ' . LFN . " doesn't define 'Tool' and 'Sorry'");

$_course = api_get_course_info(); isset($_course) or give_up(get_lang('Sorry'));

require(api_get_path(LIBRARY_PATH) . 'xmd.lib.php');
require(api_get_path(LIBRARY_PATH) . 'xht.lib.php');

$xhtDoc = define_htt(HTT . '.htt', $urlp, $_course['path']);

$xhtDoc->xht_param['type'] = TYPE;

$xhtDoc->xht_param['index'] =
    str_replace('/search.php', '/index.php', api_get_self());


// XML and DB STUFF ----------------------------------------------------------->

$mdStore = new mdstore(FALSE);  // no create DB table from search

$xhtDoc->xht_get_lang = 'get_lang'; $xhtDoc->xht_xmldoc = new xmddoc('');
if ($xhtDoc->xht_xmldoc->error) give_up($xhtDoc->xht_xmldoc->error);

($mdt = $xhtDoc->xht_fill_template('DEFAULT'.TYPE))
    or give_up('No template DEFAULT' . TYPE);

$xhtDoc->xht_xmldoc = new xmddoc(explode("\n", $mdt));
if ($xhtDoc->xht_xmldoc->error) give_up($xhtDoc->xht_xmldoc->error);

$xmlDoc = new xmddoc(''); if ($xmlDoc->error) give_up($xmlDoc->error);

if (isset($_POST['mdsc']))  // Search criteria
{
    $mdsc = str_replace("\r", "\n", str_replace("\r\n", "\n",
        get_magic_quotes_gpc() ? stripslashes($_POST['mdsc']) : $_POST['mdsc']));

    foreach (explode("\n", $mdsc) as $word) if (($word = trim($word)))
    {
        $words .= ", " . $word;

        $where .= " AND indexabletext " . ($word{0} != '-' ?
             ("LIKE '%".addslashes($word)."%'") :
             ("NOT LIKE '%".addslashes(substr($word, 1))."%'"));
    }

    if ($where)
    {
        $whereclause = substr($where, 5);  // remove first " AND "

        $xhtDoc->xht_xmldoc->xmd_add_text_element('query', $whereclause);
        $xhtDoc->xht_param['traceinfo'] = substr($words, 2);

        $result = $mdStore->mds_get_many('eid,mdxmltext', $whereclause);

        while (($myrow = @Database::fetch_array($result)))
        {
            // not quite a real manifest, but very much like one...

            $eid = $myrow['eid']; $xmlDoc = new xmddoc($myrow['mdxmltext']);
            if ($xmlDoc->error) give_up('Entry '.$eid . ': ' . $xmlDoc->error);

            $mdObj = new mdobject($_course, $eid);  // md_mix.php

            $xhtDoc->xht_xmldoc->xmd_copy_foreign_child($xmlDoc);
            $newItem = $xhtDoc->xht_xmldoc->
                xmd_select_single_element('item[-1]');
            $xhtDoc->xht_xmldoc->xmd_set_attribute($newItem, 'eid', $eid);
            $xhtDoc->xht_xmldoc->xmd_set_attribute($newItem, 'url',
                $mdObj->mdo_url);

            if ($mdObj->mdo_type == 'Scorm')
                $xhtDoc->xht_xmldoc->xmd_set_attribute($newItem, 'brl',
                    $mdObj->mdo_base_url);
        }
    }
}


function check_is_thumb($p)  // escape function, see mds_mix.htt
{
    global $xhtDoc; if ($p !== FALSE) return '';  // should not happen

    if (!ereg('^pptsl[0-9]+_t\.jpg$', $xhtDoc->xht_param['thumb']))
        $xhtDoc->xht_param['thumb'] = '';

    return '';
}


// GENERATE OUTPUT ------------------------------------------------------------>

foreach (explode("\n", $xhtDoc->htt_array['HTTP']) as $httpXtra)
    if ($httpXtra) $httpHeadXtra[] = $httpXtra;

$xhtDoc->xht_get_lang = 'get_lang';

function resource_for($e) {return $e;}  // dummy, '=/' not used here
$xhtDoc->xht_resource = 'resource_for';

$xhtDoc->xht_param['kwdswere_string'] = $_POST['kwdswere_string'];
$htmlHeadXtra[] = $xhtDoc->xht_fill_template('HEAD');

// $noPHP_SELF = TRUE;  // in breadcrumps

Display::display_header($nameTools);

$xhtDoc->xht_dbgn = DBG;  // for template debug info, set to e.g. 10000
if (($ti = $xhtDoc->xht_param['traceinfo'])) $xhtDoc->xht_param['traceinfo'] =
    '<b>' . get_lang('Search') . '</b>: ' . htmlspecialchars($ti, ENT_QUOTES, $charset);

echo $xhtDoc->xht_fill_template('MDSEARCH'), "\n";

if ($xhtDoc->xht_dbgn) echo $xhtDoc->xht_dbgo;

Display::display_footer();
?>
