<?php /*                          <!-- md_link.php for Dokeos metadata/*.php -->
                                                             <!-- 2006/12/15 -->

<!-- Copyright (C) 2006 rene.haentjens@UGent.be -  see metadata/md_funcs.php -->

*/

/**
==============================================================================
*	Dokeos Metadata: class mdobject for Link-type objects
*
*	@package dokeos.metadata
==============================================================================
*/

class mdobject
{

var $mdo_course;
var $mdo_type;
var $mdo_id;
var $mdo_eid;

var $mdo_dcmap_e;
var $mdo_dcmap_v;

var $mdo_url;
var $mdo_title;
var $mdo_description;
var $mdo_category;
var $mdo_category_title;

function mdo_define_htt() { return new xhtdoc(<<<EOD

<!-- {-INDEXABLETEXT-} -->

Title: {-V metadata/lom/general/title/string-} txt-sep
Keyword(s): {-R metadata/lom/general/keyword C KWTEXT-} txt-sep
Category: {-V metadata/lom/general/coverage/string-} txt-sep
 {-V metadata/lom/general/description[1]/string-} txt-end
 link-type


<!-- {-KWTEXT-} -->

 {-V string-}-kw


<!-- {--} -->
EOD
);
}

function mdo_generate_default_xml_metadata()
{
    global $iso639_2_code, $ieee_xml;

    $xhtDoc = new xhtdoc($ieee_xml); $_user = api_get_user_info();

    if ($xhtDoc->htt_error)
        give_up('IEEE XML (metadata/md_funcs): ' . $xhtDoc->htt_error);

    $xhtDoc->xht_get_lang = 'get_lang'; $xhtDoc->xht_xmldoc = new xmddoc('');
    if ($xhtDoc->xht_xmldoc->error) give_up($xhtDoc->xht_xmldoc->error);

    $xhtDoc->xht_param['siteUri'] = make_uri();

    $xhtDoc->xht_param['entry'] = $this->mdo_course['sysCode'] .
        '.Link.' . $this->mdo_id;  // 2005-05-30: path->sysCode

    $xhtDoc->xht_param['location'] = $this->mdo_url . '';

    $xhtDoc->xht_param['mdlang'] = strtolower($iso639_2_code);
    $xhtDoc->xht_param['lang'] =   strtolower($iso639_2_code);

    $xhtDoc->xht_param['title'] =
        $this->mdo_title ? $this->mdo_title : get_lang('MdTitle');

    if (($d = $this->mdo_description))
    {
        if ($keywords = $this->_find_keywords($d)) $d = array_pop($keywords);
        $xhtDoc->xht_param['description'] = $d;
    }
    else
        $xhtDoc->xht_param['description'] = get_lang('MdDescription');

    $xhtDoc->xht_param['coverage'] = $this->mdo_category_title ?
        $this->mdo_category_title : get_lang('MdCoverage');

    if (isset($_user))
    {
        $xhtDoc->xht_param['author'] = "BEGIN:VCARD\\nFN:" .
            api_get_person_name($_user['firstName'], $_user['lastName'], null, PERSON_NAME_EMAIL_ADDRESS) .
            "\\nEMAIL:".$_user['mail'] . "\\nEND:VCARD\\n";
    }

    $xhtDoc->xht_param['dateTime'] = date('Y-m-d');

    $xhtDoc->xht_param['format'] = ''; $xhtDoc->xht_param['size'] = '0';

    if (count($keywords))
    {
        $xd = new xmddoc(explode("\n",
            $mdt = $xhtDoc->xht_fill_template('XML')));
        if ($xd->error) return $mdt;  // and worry later

        $this->_add_keywords($xd, $keywords);

        return $xd->xmd_xml();
    }

    return $xhtDoc->xht_fill_template('XML');
}


function mdo_override(&$xmlDoc)  // by ref!
{
    if ($this->mdo_url)
    {
        $xmlDoc->xmd_update('metadata/lom/technical/location', $this->mdo_url);

        $ge = $xmlDoc->xmd_select_single_element("metadata/lom/general");

        $xmlDoc->xmd_update('title[1]/string', $this->mdo_title, $ge);
        $xmlDoc->xmd_update('coverage[1]/string', $this->mdo_category_title, $ge);

        if (($d = $this->mdo_description))
            if ($keywords = $this->_find_keywords($d)) $d = array_pop($keywords);

        $xmlDoc->xmd_update('description[1]/string', $d, $ge);

        $xmlDoc->xmd_remove_nodes($xmlDoc->xmd_select_elements('keyword', $ge), $ge);

        if (count($keywords)) $this->_add_keywords($xmlDoc, $keywords);
    }

    return $xmlDoc->xmd_xml();
}


function mdo_storeback(&$xmlDoc)  // by ref!
{
    if (!$this->mdo_url) return;  // no record in link table, most probably

    if (!($v = $xmlDoc->xmd_value('metadata/lom/technical/location'))) return;

    if ($v != $this->mdo_url)
    { $this->mdo_url = $v;   $u .= ", url = '" . addslashes($v) . "'"; }

    $ge = $xmlDoc->xmd_select_single_element("metadata/lom/general");

    $v = $xmlDoc->xmd_value('title[1]/string', $ge);
    if ($v != $this->mdo_title)
    { $this->mdo_title = $v; $u .= ", title = '" . addslashes($v) . "'"; }

    $vd = $xmlDoc->xmd_value('description[1]/string', $ge);
    $vk = $xmlDoc->xmd_value('keyword/string', $ge, array('in' => ', '));
    $v = $vk ? '<i kw="' . htmlspecialchars($vk) . '">' .
        ereg_replace('\[((/?(b|big|i|small|sub|sup|u))|br/)\]', '<\\1>',
            htmlspecialchars($vd)) . '</i>' : $vd;

    if ($v != $this->mdo_description)
    {
        $this->mdo_description = $v;
        $u .= ", description = '" . addslashes($v) . "'";
    }

    // do not store back a modified coverage as category...

    $link_table = Database::get_course_table(TABLE_LINK);
    if ($u) Database::query("UPDATE $link_table SET " . substr($u, 2) .
        " WHERE id='" . addslashes($this->mdo_id) . "'", __FILE__, __LINE__);
}


function mdo_add_breadcrump_nav()
{
    global $interbreadcrumb;

    $regs = array(); // for use with ereg()

    $docurl = api_get_self();  // should be .../main/xxx/yyy.php
    if (ereg('^(.+[^/\.]+)/[^/\.]+/[^/\.]+.[^/\.]+$', $docurl, $regs))
        $docurl = $regs[1] . '/link/link.php';

    $interbreadcrumb[]= array ('url' => $docurl,
        "name"=> get_lang('MdCallingTool'));
}


function mdobject($_course, $id)
{
    global $ieee_dcmap_e, $ieee_dcmap_v;  // md_funcs

    $this->mdo_course = $_course; $this->mdo_type = 'Link';
    $this->mdo_id = $id; $this->mdo_eid = $this->mdo_type . '.' . $id;

    $this->mdo_dcmap_e = $ieee_dcmap_e; $this->mdo_dcmap_v = $ieee_dcmap_v;

    $link_table = Database::get_course_table(TABLE_LINK);
    if (($linkinfo = @mysql_fetch_array(Database::query(
            "SELECT url,title,description,category_id FROM $link_table WHERE id='" .
            addslashes($id) . "'", __FILE__, __LINE__))))
    {
        $this->mdo_url =         $linkinfo['url'];
        $this->mdo_title =       $linkinfo['title'];
        $this->mdo_description = $linkinfo['description'];
        $this->mdo_category =    ($lci = $linkinfo['category_id']);

        $linkcat_table = Database::get_course_table(TABLE_LINK_CATEGORY);
        if (($catinfo = @mysql_fetch_array(Database::query(
                "SELECT category_title FROM $linkcat_table WHERE id='" .
                addslashes($lci) . "'", __FILE__, __LINE__))))
            $this->mdo_category_title =    $catinfo['category_title'];
    }
}


function _find_keywords($d)
{
    $dd = new xmddoc($d); if ($dd->error) return NULL;

    $regs = array(); // for use with ereg()

    foreach ($dd->attributes[0] as $name => $value)
        if ($name == 'kw' && ereg('^<?([^>]+)>?$', $value, $regs))
        {
            $kwa = array_map('trim', explode(',', $regs[1]));

            if (ereg('^<' . ($tag = $dd->name[0]) . '[^>]*>(.*)</'.$tag.'>$',
                    $d, $regs))  // e.g. <i kw="...">A &amp; <b>B</b>!</i>
            {
                $htdc = array_flip(get_html_translation_table(HTML_ENTITIES));
                $d = strtr(ereg_replace(  // first  <b>  -> [b] etc.
                    '<((/?(b|big|i|small|sub|sup|u))|br/)>', '[\\1]',
                    ($regs[1])), $htdc);  // then  &amp; ->  &  etc.
                $d = strtr(str_replace("\r\n", " ", $d), "\r\n", "  ");
            }
            else $d = $dd->xmd_text();

            array_push($kwa, $d); return $kwa;
        }

    return NULL;
}

function _add_keywords(&$xmlDoc, $keywords)  // by ref!
{
    $ge = $xmlDoc->xmd_select_single_element("metadata/lom/general");
    $dl = array("language" =>
        $xmlDoc->xmd_value("description/string/@language", $ge));

    foreach ($keywords as $kw)
        $xmlDoc->xmd_add_text_element("string", $kw,
            $xmlDoc->xmd_add_element("keyword", $ge), $dl);
}

}
?>
