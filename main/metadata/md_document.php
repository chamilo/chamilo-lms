<?php
/**
 * md_document.php for Dokeos metadata/*.php
 * 2005/09/20
 * Copyright 2005 rene.haentjens@UGent.be -  see metadata/md_funcs.php -->
 *	@package chamilo.metadata
 */
/**
 *	Chamilo Metadata: class mdobject for Document-type items
 */

class mdobject {

var $mdo_course;
var $mdo_type;
var $mdo_id;
var $mdo_eid;

var $mdo_dcmap_e;
var $mdo_dcmap_v;

var $mdo_path;
var $mdo_title;
var $mdo_comment;
var $mdo_filetype;
var $mdo_group;
var $mdo_url;


function mdo_define_htt() { return new xhtdoc(<<<EOD

<!-- {-INDEXABLETEXT-} -->

 {-V metadata/lom/general/title/string-} txt-sep
 {-R metadata/lom/general/keyword C KWTEXT-} txt-sep
 {-V metadata/lom/general/description[1]/string-} txt-end
 document-type
 {-V metadata/lom/lifeCycle/contribute[1]/entity-}
 {-V metadata/lom/lifeCycle/contribute[1]/date/dateTime-}
 {-V metadata/lom/technical/format-}


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
        '.Document.' . $this->mdo_id;  // 2005-05-30: path->sysCode

    $xhtDoc->xht_param['location'] = api_get_path(WEB_PATH) .
        'main/metadata/openobject.php?cidReq=' .
        urlencode($this->mdo_course['sysCode']) . '&eid=' .
        urlencode($this->mdo_eid);

    $xhtDoc->xht_param['mdlang'] = strtolower($iso639_2_code);
    $xhtDoc->xht_param['lang'] =   strtolower($iso639_2_code);

    $xhtDoc->xht_param['title'] =
        $this->mdo_title ? $this->mdo_title :
            ($this->mdo_path ? $this->mdo_path : get_lang('MdTitle', ''));
    $xhtDoc->xht_param['description'] =
        $this->mdo_comment ? $this->mdo_comment : get_lang('MdDescription', '');
    $xhtDoc->xht_param['coverage'] = get_lang('MdCoverage', '');

    if (isset($_user))
    {
        $xhtDoc->xht_param['author'] = "BEGIN:VCARD\\nFN:" .
            api_get_person_name($_user['firstName'], $_user['lastName'], null, PERSON_NAME_EMAIL_ADDRESS) .
            "\\nEMAIL:".$_user['mail'] . "\\nEND:VCARD\\n";
    }

    $xhtDoc->xht_param['dateTime'] = date('Y-m-d');

    if ($this->mdo_filetype == 'folder') $format = "inode/directory";
    else
    {
        require_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');
        $format = DocumentManager::file_get_mime_type($this->mdo_path);
    }

    $xhtDoc->xht_param['format'] = $format;

    $xhtDoc->xht_param['size'] = (($s = filesize(get_course_path() .
        $this->mdo_course['path'] . '/document' . $this->mdo_path))) ? $s : '0';

    return $xhtDoc->xht_fill_template('XML');
}


function mdo_add_breadcrump_nav()
{
    global $interbreadcrumb, $langFormats;
    $regs = array(); // for use with ereg()

    $docurl = api_get_self();  // should be .../main/xxx/yyy.php
    if (ereg('^(.+[^/\.]+)/[^/\.]+/[^/\.]+.[^/\.]+$', $docurl, $regs))
        $docurl = $regs[1] . '/document/document.php';

    $interbreadcrumb[]= array ('url' => $docurl,
        "name"=> get_lang('MdCallingTool'));

    if (($docpath = $this->mdo_path))
    {
        $docpath = substr($docpath, 0, strrpos($docpath, '/'));

        if (strlen($docpath) > 1) $interbreadcrumb[]= array ('url' =>
            $docurl . '?curdirpath=' . urlencode($docpath) .
            ($this->mdo_group ? '&gidReq=' . $this->mdo_group : ''), "name" =>
            htmlspecialchars(substr($docpath, strrpos($docpath, '/') + 1)));
    }

    // Complete assoclist $langFormats from mime types

    require_once(api_get_path(LIBRARY_PATH) . 'xht.lib.php');
    require_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');

    $sep = $langFormats{0} ? $langFormats{0} : ":";
    $arrFormats = xht_explode_assoclist($langFormats);

    foreach (DocumentManager::file_get_mime_type(TRUE) as $format)
        if (!isset($arrFormats[$format]))
            $langFormats .= ",, " . $format . $sep . $format;

    if (!isset($arrFormats["inode/directory"]))
        $langFormats .= ",, inode/directory" . $sep . "inode/directory";

    if (substr($langFormats, 0, 3) == ",, ")
        $langFormats = $sep . substr($langFormats, 3);
}


function mdobject($_course, $id)
{
    global $ieee_dcmap_e, $ieee_dcmap_v;  // md_funcs

    $this->mdo_course = $_course; $this->mdo_type = 'Document';
    $this->mdo_id = $id; $this->mdo_eid = $this->mdo_type . '.' . $id;

    $this->mdo_dcmap_e = $ieee_dcmap_e; $this->mdo_dcmap_v = $ieee_dcmap_v;

    $document_table = Database::get_course_table(TABLE_DOCUMENT);
    if (($docinfo = @Database::fetch_array(Database::query(
            "SELECT path,title,comment,filetype FROM $document_table WHERE id='" .
            addslashes($id) . "'"))))
    {
        $this->mdo_path =     $docinfo['path'];
        $this->mdo_title =    $docinfo['title'];
        $this->mdo_comment =  $docinfo['comment'];
        $this->mdo_filetype = $docinfo['filetype'];
        $this->mdo_group =    '';  // 2005-05-30: find group_id, if any

        $group_info = Database::get_course_table(TABLE_GROUP);
        if (($result = Database::query(
                "SELECT id,secret_directory FROM $group_info")))
            while (($row = Database::fetch_array($result)))
                if (($secdir = $row['secret_directory'] . '/') ==
                        substr($this->mdo_path, 0, strlen($secdir)))
                {
                    $this->mdo_group = $row['id']; break;
                }

          // 2005-05-30: use direct URL
          $this->mdo_url =  api_get_path(WEB_COURSE_PATH) . $_course['path'] .
            '/document' . str_replace('%2F', '/', urlencode($this->mdo_path)) .
            ($this->mdo_group ? '?gidReq=' . $this->mdo_group : '');
    }
}

}
?>
