<?php
/**
 * mdApiTest.php
 * @date 2004/09/30
 * @copyright 2004 rene.haentjens@UGent.be -  see metadata/md_funcs.php
 * @package chamilo.metadata
 */
/**
*	Chamilo Metadata: MD API test and demo
*   The API allows other Dokeos scripts to define & manipulate metadata
*   In this example, MD is defined for 'Document.1001', 1002, 1003
*/

require("../md_funcs.php");

define('EID_TYPE', 'Document');
require('../md_' . strtolower(EID_TYPE) . '.php');

// name of the language file that needs to be included
/*
$language_file = 'Whatever';
*/
require("../../inc/global.inc.php");

isset($_course) or give_up("Select a course first...");

$is_allowed_to_edit = isset($_user['user_id']) && $is_courseMember && is_allowed_to_edit();
if (!$is_allowed_to_edit) give_up("You're not allowed to edit...");

$mdStore = new mdstore($is_allowed_to_edit);  // create table if needed

require(api_get_path(LIBRARY_PATH) . 'xmd.lib.php'); // mds_update_xml_and_mdt
require(api_get_path(LIBRARY_PATH) . 'xht.lib.php'); // mdo_generate_default_xml_metadata

$noPHP_SELF = TRUE;
Display::display_header($nameTools); echo "\n";

// if the language file in use is not 'md_' . EID_TYPE ...
$langMdTitle =          'Default Title (if doc not in DB)';
$langMdDescription =    'Default description (if doc has no comment)';
$langMdCoverage =       'bachelor of engineering';
$langMdCopyright =      'Ghent University';


foreach(array(1001, 1002, 1003) as $eid_id)
{
    $mdObj = new mdobject($_course, $eid_id);  // see 'md_' . EID_TYPE . '.php'
    $eid = $mdObj->mdo_eid;

    $titlePath = $mdObj->mdo_dcmap_v['Title'];   // no IEEE dependencies here...

    if (($mdt_rec = $mdStore->mds_get($eid)) === FALSE)
    {
         $mdt = $mdObj->mdo_generate_default_xml_metadata();

         $xmlDoc = new xmddoc(explode("\n", $mdt));
         if (!$xmlDoc->error)
         {
             echo htmlspecialchars($titlePath), ': ';
             $mdTitle = $xmlDoc->xmd_value($titlePath);
             if ($mdTitle == $langMdTitle)
             {
                 $mdTitle = EID_TYPE . ' ' . $eid_id;
                 $xmlDoc->xmd_update($titlePath, $mdTitle);
                 $mdt = $xmlDoc->xmd_xml();
             }
             echo htmlspecialchars($mdTitle), ':';
         }

         $mdStore->mds_put($eid, $mdt, 'mdxmltext', FALSE);
         echo '<a href="../index.php?eid=', urlencode($eid), '">',
            htmlspecialchars($eid), '</a><br>';
    }
}
echo '<br>';


$xmlDoc = new xmddoc(explode("\n", $mdStore->mds_get($eid = EID_TYPE . '.1002')));
if ($xmlDoc->error) give_up($xmlDoc->error);

$mdObj = new mdobject($_course, '1002');
$mda = "~~";  // delete metadata of 'Document.1002'
$mdt = $mdStore->mds_update_xml_and_mdt($mdObj, $xmlDoc, $mda, $eid, $trace);
// note: $xmlDoc and $trace are passed by reference...


$mdObj = new mdobject($_course, '1003');
$xmlDoc = new xmddoc(explode("\n", $mdStore->mds_get($eid = EID_TYPE . '.1003')));
if ($xmlDoc->error) give_up($xmlDoc->error);

$map_lang = 'string/@language';
$dcmap_e_kwplace = 'metadata/lom/general'; $dcmap_e_kwelem = 'keyword';
$dcmap_e_keyword = $dcmap_e_kwplace . '/' . $dcmap_e_kwelem;

$mda =  $mdObj->mdo_dcmap_v['Description'] . '=Nouvelle description' .
        "\n" . $mdObj->mdo_dcmap_e['Coverage'] . "~" .
        "\n" . $dcmap_e_kwplace . '!' . $dcmap_e_kwelem .
        "\n" . $dcmap_e_keyword . "[-1]!string=afrique" .
        "\n" . $dcmap_e_keyword . "[-1]/" . $map_lang . "=en" .
        "\n" . $mdObj->mdo_dcmap_e['Title'] . ',' .
            $mdObj->mdo_dcmap_e['Description'] . ',' .
            $dcmap_e_keyword . ";" . $map_lang . "=fr" .
        "";  // update metadata of 'Document.1003' - see md_funcs
        // note we don't go far with IEEE independence...
$mdt = $mdStore->mds_update_xml_and_mdt($mdObj, $xmlDoc, $mda, $eid, $trace);

echo htmlspecialchars($trace), '<br><br>';


// The simplest API calls: store and fetch DC metadata element values:

$mdObj = new mdobject($_course, '1003');
$mdStore->mds_put_dc_elements($mdObj, array('Coverage' => 'broad...', 'Type' => 'aggressive text'));
// Coverage won't work, because that element has been removed above...
$dcelem = $mdStore->mds_get_dc_elements($mdObj);
foreach (array('Identifier', 'Title', 'Language', 'Description', 'Coverage',
                        'Type', 'Date', 'Creator', 'Format', 'Rights') as $dce)
{
    echo $dce, '= ', htmlspecialchars($dcelem[$dce]), '<br>';
}

echo '<br>';

$mdObj = new mdobject($_course, '1002');
$mdStore->mds_put_dc_elements($mdObj, array('Coverage' => 'broad...'));
$dcelem = $mdStore->mds_get_dc_elements($mdObj);
foreach (array('Identifier', 'Title', 'Language', 'Description', 'Coverage',
                        'Type', 'Date', 'Creator', 'Format', 'Rights') as $dce)
{
    echo $dce, '= ', htmlspecialchars($dcelem[$dce]), '<br>';
}

echo '<br>';

$mdStore->mds_append(EID_TYPE . '.1001', ' search words');
$mdStore->mds_append(EID_TYPE . '.1001', ' more findable terms');


Display::display_footer();
?>
