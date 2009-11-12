<?php /*                         <!-- md_scorm.php for Dokeos metadata/*.php -->
                                                             <!-- 2006/12/15 -->

<!-- Copyright (C) 2005 rene.haentjens@UGent.be -  see metadata/md_funcs.php -->

*/

/**
==============================================================================
*	Dokeos Metadata: class mdobject for Scorm-type objects
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

var $mdo_path;
var $mdo_comment;
var $mdo_filetype;
var $mdo_url;


function mdo_define_htt() { return new xhtdoc(<<<EOD

<!-- {-INDEXABLETEXT-} -->

{-D scormlevel {-V @level-}-}{-D two 2-}
Title: {-V metadata/lom/general/title/string-} txt-sep
{-T scormlevel == two Author(s): {-V metadata/lom/lifeCycle/contribute[1]/entity-} txt-sep-}
Keyword(s): {-R metadata/lom/general/keyword C KWTEXT-} txt-sep
 {-V metadata/lom/general/description[1]/string-}
 {-V metadata/lom/technical/location-} txt-end
 {-V metadata/lom/general/description[2]/string-} scorm-level-{-P scormlevel-}
 {-V metadata/lom/lifeCycle/contribute[1]/entity-}
 {-V metadata/lom/lifeCycle/contribute[1]/date/dateTime-}


<!-- {-KWTEXT-} -->

 {-V string-}-kw


<!-- {--} -->
EOD
);
}


function mdo_generate_default_xml_metadata()
{
    return '<empty/>';
}


function mdo_add_breadcrump_nav()
{
	global $interbreadcrumb;
	$regs = array(); // for use with ereg()

	$docurl = api_get_self();  // should be .../main/xxx/yyy.php
	if (ereg('^(.+[^/\.]+)/[^/\.]+/[^/\.]+.[^/\.]+$', $docurl, $regs))
		$docurl = $regs[1] . '/newscorm/index.php';

	$interbreadcrumb[] = array ('url' => $docurl,
		'name' => get_lang('MdCallingTool'));
}


function mdobject($_course, $id)
{
    global $ieee_dcmap_e, $ieee_dcmap_v;  // md_funcs

    $scormdocument = Database::get_course_table(TABLE_LP_MAIN);

    $this->mdo_course = $_course; $this->mdo_type = 'Scorm';
    $this->mdo_id = $id; $this->mdo_eid = $this->mdo_type . '.' . $id;

    $this->mdo_dcmap_e = $ieee_dcmap_e; $this->mdo_dcmap_v = $ieee_dcmap_v;
	$sql = "SELECT path,description,lp_type FROM $scormdocument WHERE id='" . addslashes($id) . "'";
    if (($docinfo = @Database::fetch_array(Database::query($sql,__FILE__, __LINE__))))
    {
        $this->mdo_path =     $docinfo['path'];
		//Sometimes the new scorm-tool adds '/.' at the end of a directory name, so remove this before continue
		//the process -- bmol
    	if(substr($this->mdo_path,-2) == '/.')
    	{
    		$this->mdo_path = substr($this->mdo_path,0, strlen($this->mdo_path)-2);
    	}
        $this->mdo_comment =  $docinfo['description'];
 		//Don't think the next line is correct. There used to be a 'type' field in the scormdocument table.
 		//This metadata tool only works on folder types -- bmol
        $this->mdo_filetype = ($docinfo['lp_type'] == 2 ? 'folder' : 'xxx');

        $this->mdo_url =  get_course_web() . $this->mdo_course['path'] .
            '/scorm/' . $this->mdo_path . '/index.php';
    }
}

}
?>