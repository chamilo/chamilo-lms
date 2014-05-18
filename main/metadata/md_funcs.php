<?php 
/**
 * Chamilo metadata/md_funcs.php -->
 * 2006/12/15
 * Copyright (C) 2006 rene.haentjens@UGent.be - see note at end of text    -->
 * @package chamilo.metadata
*/

/**
*   Chamilo Metadata: common functions and mdstore class
*
*   This script requires xmd.lib.php and xht.lib.php (Chamilo inc/lib).
*
*   Note on the funny characters used in mds_update_xml_and_mdt:
*
*   ! and ~ and ,   are handled by xmd_update, see xmd.lib.php
*   ~~ !! ; and =   are handled here; note that = excludes newlines in value
*
*   path!elem       create new element (not for attributes!)
*   path=value      assign new value to existing element or value to attribute
*   path~           delete element (you cannot delete an attribute)
*   ~~              delete the whole xmldoc and the DB entry
*   !!              this (xml) document contains the course keywords
*
*   path1,path2,...;subpath=value   for all elements in path1, path2, ...
*                   assign value to subpath (see also xmd_update_many)
*
*/

// FETCH GET/POST-DATA; GENERAL FUNCTIONS

define('MDS_TABLE', Database::get_course_table(TABLE_METADATA));


if (isset($getpostvars) && is_array($getpostvars))
  foreach ($getpostvars as $gpvar)
    if (is_string($gpvar) && (isset($_POST[$gpvar]) || isset($_GET[$gpvar]))) {
        $val = isset($_POST[$gpvar]) ? $_POST[$gpvar] : $_GET[$gpvar];
        $GLOBALS[$gpvar] = get_magic_quotes_gpc() ? stripslashes($val) : $val;
    }

function fgc($filename) {
    $fp = fopen($filename, 'rb'); $buffer = fread($fp, filesize($filename));
    fclose($fp); return $buffer; // file_get_contents: PHP >= 4.3.0
}


function give_up($msg) {
	global $charset;
    echo '<p align="center">MetaData:<br /><b>? ',
        htmlspecialchars($msg, ENT_QUOTES, $charset), '</b></p>'; exit;
}


function getpar($name, $description, $default = '')
{
    $value = isset($_GET[$value = api_strtolower($name)]) ? $_GET[$value] : '';
    $value = get_magic_quotes_gpc() ? stripslashes($value) : $value;
    if (!$value) $value = $default;
    if ($value == '') give_up('URL parameter ' . api_strtoupper($name) . ' - ' .
                $description . ' - is required');

    define(api_strtoupper($name), $value);
}


function get_course_path() {
    return api_get_path(SYS_COURSE_PATH);
}

function get_course_web() {
    return api_get_path(WEB_COURSE_PATH);
}


function define_htt($htt_file, $urlp, $course_path) {
	global $charset;

    ($htt_file_contents = @fgc($htt_file))
        or give_up('Templates file "' . $htt_file . '" is missing...');

    $xhtDoc = new xhtdoc($htt_file_contents);
    if ($xhtDoc->htt_error)
        give_up('Templates file "' . $htt_file . '": ' . $xhtDoc->htt_error);

    $xhtDoc->xht_param['self'] = api_get_self() . $urlp;

    $xhtDoc->xht_param['dateTime'] = date('Y-m-d');

    $ckw = $course_path . '/CourseKwds.js';
    define('KEYWORDS_CACHE', get_course_path() . $ckw);

    if (file_exists(KEYWORDS_CACHE)) $kcdt =
        htmlspecialchars(date('Y/m/d H:i:s', filemtime(KEYWORDS_CACHE)), ENT_QUOTES, $charset);

    $xhtDoc->xht_param['keywordscache'] = $kcdt ?
        '<script type="text/javascript" src="' . get_course_web() . $ckw . '"></script>' .
        '<br /><small><i>(CourseKwds cache: ' . $kcdt . ')</i></small>' : '';

    return $xhtDoc;
}


function make_uri() {
    $regs = array(); // for use with ereg()

    $uri = strtr(ereg_replace(
        "[^0-9A-Za-z\xC0-\xD6\xD8-\xF6\xF8-\xFF\*\(\('!_.-]", "_",
        api_get_setting('siteName')), "\\", "_");  // allow letdigs, and _-.()'!*

    if (($p = strpos($uri, '.')) !== FALSE)
        $uri = substr($uri, 0, $p);
    if (ereg('^([^/]+//)?([^/\?]+)[/\?]',api_get_path(WEB_PATH).'/',$regs))
        if (ereg('([^\.]+)(\.ca)?(\.[^\.]+)?', strrev($regs[2]), $regs))
            $uri = str_replace('.', '-',
                strrev($regs[1].$regs[2].$regs[3])) . ':' . $uri;
    $uri = 'urn:' . strtolower($uri);
    while (substr($uri, -1)=='.') $uri = substr($uri,0,-1);

    return $uri;
}


// IEEE LOM: DEFAULT XML AND DUBLIN CORE MAPPING ------------------------------>

$ieee_xml = <<<EOD

<!-- {-XML-} -->

<item>
  <metadata>
    <lom xmlns="http://ltsc.ieee.org/xsd/LOM">
      <general>
        <identifier><catalog>{-H {-P siteUri-}-}.</catalog><entry>{-H {-P entry-}-}</entry></identifier>
        <title><string language="{-H {-P mdlang-}-}">{-H {-P title-}-}</string></title>
        <language>{-H {-P lang-}-}</language>
        <description><string language="{-H {-P mdlang-}-}">{-H {-P description-}-}</string></description>
        <coverage><string language="{-H {-P mdlang-}-}">{-H {-P coverage-}-}</string></coverage>
      </general>
      <lifeCycle>
        <version><string language="xx">0.5</string></version>
        <status><source>LOMv1.0</source><value>draft</value></status>
        <contribute>
          <role><source>LOMv1.0</source><value>author</value></role>
          <entity>{-H {-P author-}-}</entity>
          <date><dateTime>{-H {-P dateTime-}-}</dateTime></date>
        </contribute>
      </lifeCycle>
      <metaMetadata>
        <metadataSchema>ADLv1.3</metadataSchema>
      </metaMetadata>
      <technical>
        <format>{-H {-P format-}-}</format>
        <size>{-H {-P size-}-}</size>
        <location>{-H {-P location-}-}</location>
      </technical>
      <educational>
        <learningResourceType><source>LOMv1.0</source><value>narrative text</value></learningResourceType>
      </educational>
      <rights>
        <cost><source>LOMv1.0</source><value>yes</value></cost>
        <copyrightAndOtherRestrictions><source>LOMv1.0</source><value>yes</value></copyrightAndOtherRestrictions>
        <description><string language="{-H {-P mdlang-}-}">{-L MdCopyright-}</string></description>
      </rights>
      <classification>
        <purpose><source>LOMv1.0</source><value>educational objective</value></purpose>
      </classification>
    </lom>
  </metadata>
</item>
<!-- {--} -->
EOD;

$ieee_dcmap_e = array(
'Identifier'=>      'metadata/lom/general/identifier[1]',
'Title'=>           'metadata/lom/general/title[1]',
'Language'=>        'metadata/lom/general/language[1]',
'Description'=>     'metadata/lom/general/description[1]',
'Coverage'=>        'metadata/lom/general/coverage[1]',
'Type'=>            'metadata/lom/educational/learningResourceType[1]',
'Date'=>            'metadata/lom/lifeCycle/contribute[1]/date',
'Creator'=>         'metadata/lom/lifeCycle/contribute[1]/entity',
'Format'=>          'metadata/lom/technical/format[1]',
'Rights'=>          'metadata/lom/rights/description[1]');
// maps Dublin Core elements to xmd paths for elements (not yet complete)

$ieee_dcmap_v = array(
'Identifier'=>      'metadata/lom/general/identifier[1]/entry',
'Title'=>           'metadata/lom/general/title[1]/string',
'Language'=>        'metadata/lom/general/language[1]',
'Description'=>     'metadata/lom/general/description[1]/string',
'Coverage'=>        'metadata/lom/general/coverage[1]/string',
'Type'=>            'metadata/lom/educational/learningResourceType[1]/value',
'Date'=>            'metadata/lom/lifeCycle/contribute[1]/date/dateTime',
'Creator'=>         'metadata/lom/lifeCycle/contribute[1]/entity',
'Format'=>          'metadata/lom/technical/format[1]',
'Rights'=>          'metadata/lom/rights/description[1]/string');
// maps Dublin Core elements to xmd paths for values (not yet complete)


// KEYWORD TREE 
function define_kwds($mdo) {
    if (!($newtext = trim(@fgc(get_course_path() . $mdo->mdo_course['path'] .
            '/document' . $mdo->mdo_path ))))
    {
        unlink(KEYWORDS_CACHE); return;
    }
                                    // templates to define the tree as JScript object
    $xhtDocKw = new xhtdoc(<<<EOD

<!-- {-KWTREE_OBJECT-} -->

KWTREE_OBJECT = {n:"", ti:"{-X @title-}"
, c:[{-R * C DOWN_THE_KWTREE-}]};

document.write(traverseKwObj(KWTREE_OBJECT, '', 0)); KWDS_ARRAY.sort();

<!-- {-DOWN_THE_KWTREE-} -->
{-T number > 1 , -}{n:"{-V @.-}"{-D cm {-X @comment-}-}{-T cm != empty , cm:"{-P cm-}"-}{-D pt {-X @postit-}-}{-T pt != empty , pt:"{-P pt-}"-}{-R * P empty-}{-T number >= 1
, c:[-}{-T number >= 1 R * C DOWN_THE_KWTREE-}{-R * P empty-}{-T number >= 1 ]-}}

<!-- {--} -->
EOD
    );  // traverseKwObj (md_script) generates clickable tree and populates KWDS_ARRAY


    if ($xhtDocKw->htt_error)
        give_up('KwdTree template (metadata/md_funcs): ' . $xhtDocKw->htt_error);

    $xhtDocKw->xht_xmldoc = new xmddoc(explode("\n", $newtext));
    if ($xhtDocKw->xht_xmldoc->error)
        give_up('CourseKwds (metadata/md_funcs): XML error: ' .
        $xhtDocKw->xht_xmldoc->error);

    if (count($xhtDocKw->xht_xmldoc->children[0]) < 2)
    {
        unlink(KEYWORDS_CACHE); return;
    }

    $fileHandler = @fopen(KEYWORDS_CACHE, 'w');
    @fwrite($fileHandler, $xhtDocKw->xht_fill_template('KWTREE_OBJECT'));
    @fclose($fileHandler);
}


// METADATA STORE 
/**
 * mdstore class
 * @package chamilo.metadata
 */
class mdstore {
        
    var $mds_something;    
    
    function __construct($allow_create) {
        global $_course; 
        $this->course_id = api_get_course_int_id();
        if (!isset($_course)) return;      
    }
        
    function mds_get($eid, $column = 'mdxmltext', $must_exist = '') {
        // none: FALSE
        if (($mdt = Database::fetch_array(Database::query("SELECT " . $column ." FROM ".MDS_TABLE." WHERE eid = '$eid' AND c_id = {$this->course_id} ")))) 
            return $mdt[$column];    
        if ($must_exist) give_up($must_exist . $this->_coldat('eid', $eid));
    
        return FALSE;
    }
    
    function mds_get_dc_elements($mdo)  // no record: FALSE
    {
        if (!($mdt = $this->mds_get($mdo->mdo_eid))) return FALSE;    
        $xmlDoc = new xmddoc(explode("\n", $mdt)); if ($xmlDoc->error) return FALSE;    
        $result = array();
        foreach ($mdo->mdo_dcmap_v as $dce => $xp) {
            $result[$dce] = $xmlDoc->xmd_value($xp);
        }    
        return $result;
    }
    
    function mds_get_many($columns, $where_clause) {
        $cols = '';
        foreach (explode(',', $columns) as $col) $cols .= "," . trim($col);
        if (!$cols) return;    
        return $this->_query("SELECT " . api_substr($cols, 1) ." FROM " . MDS_TABLE . " WHERE c_id = {$this->course_id} AND ". $where_clause);
    }
    
    function mds_put($eid, $data, $column = 'mdxmltext', $exists = TRUE) {
        if ($exists === TRUE)
            return $this->_query("UPDATE " . MDS_TABLE . " SET " .$this->_coldat($column, $data) . " WHERE c_id = {$this->course_id} AND ", $eid);
        elseif ($exists === FALSE)
            return $this->_query("INSERT INTO " . MDS_TABLE . " SET c_id = {$this->course_id} , ".$this->_coldat($column, $data).", ", $eid);
        else  // user doesn't know, check first whether the record exists
            return $this->mds_put($eid, $data, $column, !($this->mds_get($eid) === FALSE));
    }
    
    function mds_put_dc_elements($mdo, $dcelem) {
        if (($mdt = $this->mds_get($mdo->mdo_eid)) === FALSE) {
            $mdt = $mdo->mdo_generate_default_xml_metadata(); $exists = FALSE;
        } else
            $exists = TRUE;
    
        $xmlDoc = new xmddoc(explode("\n", $mdt)); if ($xmlDoc->error) return FALSE;    
        foreach ($dcelem as $dce => $value) {
            $xmlDoc->xmd_update($mdo->mdo_dcmap_v[$dce], (string) $value);
        }    
        $this->mds_put($mdo->mdo_eid, '', 'md5', $exists);    
        return $this->mds_put($mdo->mdo_eid, $xmlDoc->xmd_xml());
    }
    
    function mds_append($eid, $moredata, $column = 'indexabletext') {
        if (($olddata = $this->mds_get($eid, $column)) === FALSE) return FALSE;
        $this->mds_put($eid, $olddata . $moredata, $column); return $olddata;
    }
    
    function mds_delete($eid) {
        return $this->_query("DELETE FROM " . MDS_TABLE . " WHERE c_id = {$this->course_id} AND ", $eid);
    }
    
    function mds_delete_offspring($eid, $sep = '.') {
        return $this->_query("DELETE FROM " . MDS_TABLE . " WHERE c_id = {$this->course_id} AND ", $eid, $sep);
    }
    
    function mds_delete_many($idarray) {
        if (!is_array($idarray) || count($idarray) == 0) return FALSE;
    
        return $this->_query("DELETE FROM " . MDS_TABLE . " WHERE c_id = {$this->course_id} AND eid IN ('" .
            implode("','", array_map('addslashes', $idarray)) . "')");
    }
    
    function mds_update_xml_and_mdt($mdo, &$xmlDoc, $mda, $eid, &$traceinfo,
            $exists = TRUE)  // note: $xmlDoc and $traceinfo passed by reference
    {
        foreach (explode("\n",
               str_replace("\r", "\n", str_replace("\r\n", "\n", $mda))) as $update)
        {
            if (!$update) continue;
    
            if (($nameLth = strpos($update, '=')))  // e.g. 'gen/tit/str=new'
            {
                if (($text = api_substr($update, $nameLth + 1)) === FALSE) $text = '';
    
                if (!($path = trim(api_substr($update, 0, $nameLth)))) continue;
    
                if (($sc = api_strpos($path, ';')))  // e.g. 'gen/tit,gen/des;str@lang'
                    $xmlDoc->xmd_update_many(api_substr($path, 0, $sc),
                        api_substr($path, $sc + 1), $text);
                else
                    $xmlDoc->xmd_update($path, $text);
            }
            elseif ($nameLth === FALSE)  // e.g. 'gen/tit/str[-1]~'
            {
                if ($update == '~~')
                {
                    $update = 'DELETE ' . $eid;
                    if ($exists === FALSE) $update = '';
                    else $this->mds_delete($eid);
                    $mda = ''; $exists = TRUE;
                    foreach ($xmlDoc->children[0] as $key => $child)
                        unset($xmlDoc->children[0][$key]);
                }
                elseif ($update == '!!')
                {
                    define_kwds($mdo);
                    $update = ''; $mda = ''; $exists = TRUE;
                }
                else
                {
                    $x = $xmlDoc->xmd_update(trim($update), '');
                }
            }    
            if ($update) $traceinfo .= $update . '- ';
        }
    
        $mdt = $xmlDoc->xmd_xml();
    
        if ($exists === FALSE)
        {
            $this->mds_put($eid, $mdt, 'mdxmltext', FALSE);
            $traceinfo .= 'INSERT ' . $eid . '- ';
        }
        elseif($mda)
        {
            $this->mds_put($eid, $mdt, 'mdxmltext');
            $traceinfo .= 'UPDATE ' . $eid . '- ';
        }
    
        return $mdt;
    }
    
    
    function _coldatstart($column, $data) {
        return $column . " LIKE '" . Database::escape_string($data) . "%'";
    }
    
    function _coldat($column, $data) {
        return $column . "='" . Database::escape_string($data) . "'";
    }
    
    function _query($sql, $eid = '', $sep = '') {        
        if ($eid) $sql .= $sep ? $this->_coldatstart('eid', $eid . $sep) :
            $this->_coldat('eid', $eid);
        return Database::query($sql);
    }

}