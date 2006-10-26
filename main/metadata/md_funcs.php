<?php /*                                   <!-- Dokeos metadata/md_funcs.php -->
                                                             <!-- 2005/09/20 -->

<!-- Copyright (C) 2005 rene.haentjens@UGent.be - see note at end of text    -->

*/

/**
============================================================================== 
*	Dokeos Metadata: common functions and mdstore class
*
*   This script requires xmd.lib.php and xht.lib.php (Dokeos inc/lib).
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
*	@package dokeos.metadata
============================================================================== 
*/


// FETCH GET/POST-DATA; GENERAL FUNCTIONS ------------------------------------->

if (isset($getpostvars) && is_array($getpostvars))
  foreach ($getpostvars as $gpvar)
    if (is_string($gpvar) && (isset($_POST[$gpvar]) || isset($_GET[$gpvar])))
    {
        $val = isset($_POST[$gpvar]) ? $_POST[$gpvar] : $_GET[$gpvar];
        $GLOBALS[$gpvar] = get_magic_quotes_gpc() ? stripslashes($val) : $val;
    }
    
function fgc($filename)
{
    $fp = fopen($filename, 'rb'); $buffer = fread($fp, filesize($filename));
    fclose($fp); return $buffer; // file_get_contents: PHP >= 4.3.0
}


function give_up($msg)
{
	echo '<p align="center">MetaData:<br><b>? ', 
	    htmlspecialchars($msg), '</b></p>'; exit;
} 


function getpar($name, $description, $default = '')
{
    $value = isset($_GET[$value = strtolower($name)]) ? $_GET[$value] : '';
    $value = get_magic_quotes_gpc() ? stripslashes($value) : $value;
    if (!$value) $value = $default;
    if ($value == '') give_up('URL parameter ' . strtoupper($name) . ' - ' . 
                $description . ' - is required');
    
    define(strtoupper($name), $value);
}


function get_course_path()
{
    return function_exists('api_get_path') ? // 1.6
        api_get_path('SYS_COURSE_PATH') : api_get_path(SYS_PATH);  // 1.5.4
}

function get_course_web()
{
    return function_exists('api_get_path') ? // 1.6
        api_get_path('WEB_COURSE_PATH') : api_get_path(WEB_PATH);  // 1.5.4
}


function define_htt($htt_file, $urlp, $course_path)
{
    ($htt_file_contents = @fgc($htt_file))
        or give_up('Templates file "' . $htt_file . '" is missing...');
    
    $xhtDoc = new xhtdoc($htt_file_contents);
    if ($xhtDoc->htt_error) 
        give_up('Templates file "' . $htt_file . '": ' . $xhtDoc->htt_error);
    
    $xhtDoc->xht_param['self'] = $_SERVER['PHP_SELF'] . $urlp;
    
    $xhtDoc->xht_param['dateTime'] = date('Y-m-d');
    
    $ckw = $course_path . '/CourseKwds.js';
    define('KEYWORDS_CACHE', get_course_path() . $ckw);
    
    if (file_exists(KEYWORDS_CACHE)) $kcdt = 
        htmlspecialchars(date('Y/m/d H:i:s', filemtime(KEYWORDS_CACHE)));
    
    $xhtDoc->xht_param['keywordscache'] = $kcdt ?
        '<script type="text/javascript" src="' . get_course_web() . $ckw . '"></script>' . 
        '<br /><small><i>(CourseKwds cache: ' . $kcdt . ')</i></small>' : '';
    
    return $xhtDoc;
}


function make_uri()
{
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


// KEYWORD TREE --------------------------------------------------------------->

/* NOTE about NESTED_DIVS_FOR_KEYWORDTREE and NESTED_DIV:

    The 'document.write' + 'replace's are just a compression mechanism, 
    they reduce 100K of HTML to an insertable JS file of 25K...
    
    Special characters & combinations: " " " < > <>
    
    Read NESTED_DIV as follows:
    
    {-D bcv class="lfn" value=" "-}
    {-R * P empty-}
    {-T number >= 1 D bcv class="btn" value="+" onClick="openOrClose(this);"-}
    
    <div noWrap="1" class="dvc" level="{-L NLevel-}">
        <input type="button" {-P bcv}/>
        &#xa0;
        <span class="lbl" onClick="spanClick(this, event);">{-L NName-}</span>
        <br/>
        {-R * C NESTED_DIV-}
    </div>
*/

function define_kwds_htt() { return new xhtdoc(<<<EOD

<!-- {-NESTED_DIVS_FOR_KEYWORDTREE-} -->
document.write((''
{-R * C NESTED_DIV-})
.replace(/"/g,   '<div noWrap="1" class="dvc" level="')
.replace(/"/g,'"><input type="button" class="btn" value="+" onClick="openOrClose(this);"/>&#xa0;<span class="lbl" onClick="spanClick(this, event);"')
.replace(/"/g, '"><input type="button" class="lfn" value=" "/>&#xa0;<span class="lbl" onClick="spanClick(this, event);"')
.replace(/<>/g, ' title="').replace(/<>/g, '</span><br/>')
.replace(/</g, '</span><i>').replace(/>/g, '</i><br/>')
.replace(//g, '</div>')
);

<!-- {-NESTED_DIV-} -->
+'"{-L NLevel-}"{-R * P empty-}{-T number >= 1 -}{-L NPost-}>{-L NName-}'
{-R * C NESTED_DIV-}+''

<!-- {--} -->
EOD
);
}

function define_kwds($mdo) 
{
    global $xhtDocKw;  // only used here and in the inner function
    
    function not_get_lang($word, $node)  // only for the above templates
    {
        global $xhtDocKw;
        
        if ($word == 'NLevel')  // e.g. 001003002
        {
            $result = '';
            for ($k = 1; $k <= $xhtDocKw->xht_param['rdepth']; $k++) 
                $result .= substr('00' . $xhtDocKw->xht_param['rdepth'.$k], -3);
            return $result;
        }
        
        if ($word == 'NPost')
        {
            $postit = $xhtDocKw->xht_xmldoc->attributes[$node]['postit'];
            return $postit ? '<>' . str_replace("'", "\'", $postit) . '"' : '';
        }
        
        if ($word == 'NName')  // replace _ by , in nodename
            return str_replace('_', ', ', $xhtDocKw->xht_xmldoc->name[$node]) . 
                '<'. str_replace("'", "\'", 
                    $xhtDocKw->xht_xmldoc->attributes[$node]['comment']) .'>';
        
        return get_lang($word);
    }
    
    if (!($newtext = trim(@fgc(get_course_path() . $mdo->mdo_course['path'] . 
            '/document' . $mdo->mdo_path ))))
    {
        unlink(KEYWORDS_CACHE); return;
    }
    
    $xhtDocKw = define_kwds_htt();
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
    
    $xhtDocKw->xht_get_lang = 'not_get_lang';
    
    $newtext = $xhtDocKw->xht_fill_template('NESTED_DIVS_FOR_KEYWORDTREE');
    
    $fileHandler = @fopen(KEYWORDS_CACHE, 'w');
    @fwrite($fileHandler, $newtext); @fclose($fileHandler);
}


// METADATA STORE ------------------------------------------------------------->

class mdstore
{

var $mds_something;

function mds_get($eid, $column = 'mdxmltext', $must_exist = '')  // none: FALSE
{
    if (($mdt = mysql_fetch_array($this->_query("SELECT " . $column . 
        " FROM " . MDS_TABLE . " WHERE ", $eid)))) return $mdt[$column];
    
    if ($must_exist) give_up($must_exist . $this->_coldat('eid', $eid));
    
    return FALSE;
}

function mds_get_dc_elements($mdo)  // no record: FALSE
{
    if (!($mdt = $this->mds_get($mdo->mdo_eid))) return FALSE;
    
    $xmlDoc = new xmddoc(explode("\n", $mdt)); if ($xmlDoc->error) return FALSE;
    
    $result = array();
    foreach ($mdo->mdo_dcmap_v as $dce => $xp)
    {
        $result[$dce] = $xmlDoc->xmd_value($xp);
    }
    
    return $result;
}

function mds_get_many($columns, $where_clause)
{
    $cols = '';
    foreach (explode(',', $columns) as $col) $cols .= "," . trim($col);
    if (!$cols) return;
    
    return $this->_query("SELECT " . substr($cols, 1) . 
        " FROM " . MDS_TABLE . " WHERE ". $where_clause);
}

function mds_put($eid, $data, $column = 'mdxmltext', $exists = TRUE)
{
    if ($exists === TRUE)
        return $this->_query("UPDATE " . MDS_TABLE . " SET " . 
            $this->_coldat($column, $data) . " WHERE ", $eid);
    elseif ($exists === FALSE)
        return $this->_query("INSERT INTO " . MDS_TABLE . " SET " . 
            $this->_coldat($column, $data) . ", ", $eid);
    else  // user doesn't know, check first whether the record exists
        return $this->mds_put($eid, $data, $column, 
            !($this->mds_get($eid) === FALSE));
}

function mds_put_dc_elements($mdo, $dcelem)
{
    if (($mdt = $this->mds_get($mdo->mdo_eid)) === FALSE)
    {
        $mdt = $mdo->mdo_generate_default_xml_metadata(); $exists = FALSE;
    }
    else $exists = TRUE;
    
    $xmlDoc = new xmddoc(explode("\n", $mdt)); if ($xmlDoc->error) return FALSE;
    
    foreach ($dcelem as $dce => $value)
    {
        $xmlDoc->xmd_update($mdo->mdo_dcmap_v[$dce], (string) $value);
    }
    
    $this->mds_put($mdo->mdo_eid, '', 'md5', $exists);
    
    return $this->mds_put($mdo->mdo_eid, $xmlDoc->xmd_xml());
}

function mds_append($eid, $moredata, $column = 'indexabletext')
{
    if (($olddata = $this->mds_get($eid, $column)) === FALSE) return FALSE;
    $this->mds_put($eid, $olddata . $moredata, $column); return $olddata;
}

function mds_delete($eid)
{
    return $this->_query("DELETE FROM " . MDS_TABLE . " WHERE ", $eid);
}

function mds_delete_offspring($eid, $sep = '.')
{
    return $this->_query("DELETE FROM " . MDS_TABLE . " WHERE ", $eid, $sep);
}

function mds_delete_many($idarray)
{
    if (!is_array($idarray) || count($idarray) == 0) return FALSE;
    
    return $this->_query("DELETE FROM " . MDS_TABLE . " WHERE eid IN ('" . 
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
            if (($text = substr($update, $nameLth + 1)) === FALSE) $text = '';
            
            if (!($path = trim(substr($update, 0, $nameLth)))) continue;
            
            if (($sc = strpos($path, ';')))  // e.g. 'gen/tit,gen/des;str@lang'
                $xmlDoc->xmd_update_many(substr($path, 0, $sc), 
                    substr($path, $sc + 1), $text);
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

function mdstore($allow_create)
{
    global $_course; if (!isset($_course)) return;
    
    define('MDS_TABLE', Database::get_course_table('metadata'));

    if (!api_sql_query("SELECT eid FROM " . MDS_TABLE))
    if ($allow_create)
        $this->_query("CREATE TABLE " . MDS_TABLE . " (    " . 
                "eid varchar(250) NOT NULL," .      // entry-id, e.g. doc.1
                "mdxmltext text default ''," .      // MD-text, XML-formatted
                "md5 char(32) default ''," .        // hash-validator
                "htmlcache1 text default ''," .     // cached HTML, part 1
                "htmlcache2 text default ''," .     // cached HTML, part 2
                "indexabletext text default ''," .  // indexable for search
                "PRIMARY KEY (eid)           )");
    else give_up('No metadata store is available for this course.');
}

function _coldatstart($column, $data)
{
    return $column . " LIKE '" . addslashes($data) . "%'";
}

function _coldat($column, $data)
{
    return $column . "='" . addslashes($data) . "'";
}

function _query($sql, $eid = '', $sep = '')
{
    if ($eid) $sql .= $sep ? $this->_coldatstart('eid', $eid . $sep) :
        $this->_coldat('eid', $eid);
    
    return api_sql_query($sql, __FILE__, __LINE__);
}

}

/*
<!--
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

  -->
*/

?>