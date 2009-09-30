<?php /*                                    <!-- testMiniDom.php, 2005/03/16 -->
                                            <!-- for XML MiniDom, 2005/03/16 -->

<!-- Copyright (C) 2005 rene.haentjens@UGent.be                              -->

*/

/**
==============================================================================
*	Dokeos Metadata: XMD test and demo
*
*	@package dokeos.metadata
==============================================================================
*/



function file_get_contents_n($filename)  // normalize \r and \r\n to \n
{
    $fp = fopen($filename, 'rb');
    $buffer = fread($fp, filesize($filename));
    fclose($fp);  // note file_get_contents is >= PHP 4.3.0

    return str_replace("\r", "\n", str_replace("\r\n", "\n", $buffer));
}


require("../../inc/lib/xmd.lib.php");


$testdoc = new xmddoc('<docroot/>');  // docroot is element 0


function showDoc($title, $morestuff = '')
{
    global $testdoc; echo '<h4>', $title, '</h4>', '<pre>',
        htmlspecialchars($morestuff ? $morestuff : $testdoc->xmd_xml()), '</pre>';
}


$sometag1 = $testdoc->xmd_add_element('sometag');
$testdoc->xmd_set_attribute(0, 'owner', 'rene');
$testdoc->xmd_add_text('text in my first child element', $sometag1);

showDoc('Small XML document');

$sometag2 = $testdoc->xmd_add_element('sometag', 0, array('x' => 'somevalue'));
$testdoc->xmd_add_text('bizarre <text> in "my& 2nd child', $sometag2);
$testdoc->xmd_add_text(' + more text in first one', $sometag1);
$testdoc->xmd_set_attribute($sometag2, 'owner', '<c&a">');
$testdoc->xmd_add_element('innertag', $sometag2);

showDoc('Slightly changed');

showDoc('All text', $testdoc->xmd_text());

$stuff = '';
foreach ($testdoc->xmd_get_element($sometag2) as $key => $value)
    $stuff .= $key . ': ' . $value . "\n";

showDoc('Children, attributes, name and parent of 2nd sometag', $stuff);

$testdoc->xmd_remove_nodes('text in my first child element', $sometag1);
// note: remove text may remove more than one node...
$testdoc->xmd_set_attribute(0, 'owner', 'haentjens');  // new value

showDoc('Text removed from 1st sometag, docroot owner changed');

$testdoc->xmd_remove_element($sometag2);
$sometag2 = $testdoc->xmd_add_text_element('��', 'alors!');

showDoc('2nd sometag replaced by new subelement with French name');

$testdoc->name[$sometag2] = 'sometag';  // properties are read/write
$testdoc->xmd_set_attribute($sometag2, 'xmlns:tn', 'urn:ugent-be');  // namesp def
$subtag = $testdoc->xmd_add_element('urn:ugent-be:subtag', $sometag2);
$testdoc->xmd_set_attribute($sometag2, 'urn:ugent-be:owner', 'FTW');

showDoc('French name replaced, namespace definition added and used');

$testdoc->xmd_set_attribute($sometag1, 'urn:ugent-be:owner', 'FTW');
$testdoc->xmd_set_attribute($sometag1, 'urn:rug-ac-be:owner2', 'FLA');
// restriction: cannot add attribute 'urn:rug-ac-be:owner' (same name)

showDoc('Attributes with namespaces added, ns def is auto-generated');

$stuff = 'subtag => ' . $testdoc->xmd_get_ns_uri($subtag) . "\n";
foreach ($testdoc->attributes[$sometag1] as $name => $value)
    $stuff .= $name . ' => ' . $testdoc->xmd_get_ns_uri($sometag1, $name) . "\n";

showDoc('Namespace-URI of subtag, of 1st sometag attributes', $stuff);

$subsub = $testdoc->xmd_add_element('urn:sample-default:subsub', $subtag,
    array('xmlns' => 'urn:sample-default', 'someatt' => 'somevalue'));
$subsubsub = $testdoc->xmd_add_element('urn:sample-default:subsubsub', $subsub);

showDoc('Subsub element has default namespace');

$stuff = 'subsub => ' . $testdoc->xmd_get_ns_uri($subsub) . "\n";
$stuff .= 'subsubsub => ' . $testdoc->xmd_get_ns_uri($subsubsub) . "\n";
foreach ($testdoc->attributes[$subsub] as $name => $value)
    $stuff .= $name . ' => ' . $testdoc->xmd_get_ns_uri($subsub, $name) . "\n";

showDoc('Namespace-URI of subsub and subsubsub; attributes have none', $stuff);

$testdoc->xmd_update('!newtag', 'text for newtag');
showDoc("After update '!newtag', 'text for newtag'");

$testdoc->xmd_update('newtag', 'new text for newtag');
showDoc("After update 'newtag', 'new text for newtag'");

$testdoc->xmd_update('newtag/@someatt', 'attval');
showDoc("After update 'newtag/@someatt', 'attval'");

$testdoc->xmd_update('newtag/~', '');
showDoc("After update 'newtag/~', ''");

$keepdoc = $testdoc;

$wrongdoc = "<html>\n  <body>\n    <p>Text</p>\n    <p>More text" .
    "\n  </body>\n</html>";
$testdoc = new xmddoc(explode("\n", $wrongdoc));

showDoc('Xml doc with syntax error + error message',
    $wrongdoc . "\n\n" . $testdoc->error);

$xmlFile = 'imsmanifest_reload.xml';

($presXmlFileContents = @file_get_contents_n($xmlFile))
    or die('XML file  ' . htmlspecialchars($xmlFile) . ' is missing...');

showDoc('XML file to be parsed', $presXmlFileContents);

$testdoc = new xmddoc(explode("\n", $presXmlFileContents));
unset($presXmlFileContents);

if ($testdoc->error) die($xmlFile . ':<br><br>' . $testdoc->error);

$testdoc->xmd_update_many('metadata/lom/general/title,metadata/lom/general/description', 'langstring/@lang', 'fr');
$testdoc->xmd_copy_foreign_child($keepdoc, $keepdoc->xmd_select_single_element('sometag[2]'));

showDoc('After parsing, and after changing 2* langstring/@lang to fr, ' .
    'and after adding a foreign doc, reconstruction from memory');

showDoc('Element tagname of first metadata/lom/* element',
    $testdoc->name[$testdoc->xmd_select_single_element('metadata/lom/*')]);

showDoc('Element namespace URI of metadata/lom/*[2]',
    $testdoc->xmd_get_ns_uri($testdoc->xmd_select_single_element('metadata/lom/*[2]')));

showDoc('Number of metadata/lom/* elements',
    count($testdoc->xmd_select_elements('metadata/lom/*')));

showDoc('Number of resources/resource/file elements with @href',
    count($testdoc->xmd_select_elements_where_notempty(
        'resources/resource/file', '@href')));

$elems = $testdoc->xmd_select_elements_where('resources/resource',
            'file[1]/@href', 'three.html');
showDoc('Resource identifier where file[1]/@href is three.html',
    $testdoc->xmd_value('@identifier', $elems[0]));

$elems = $testdoc->xmd_select_elements_where('resources/resource', '@identifier',
    $testdoc->xmd_value('organizations/organization/item[2]/@identifierref'));
showDoc('Resource href for item[2]',
    $testdoc->xmd_value('@href', $elems[0]));

$stuff = '';
foreach (array('@identifier', 'metadata/schema', '*/*/*/*[1]/langstring',
        'resources/resource[3]/@href', 'resources/resource[3]/file/@href',
        'resources/resource[3]/@*', 'resources/resource[3]/-/@href',
        'resources/resource[3]/+/@href', 'resources/resource[1]/-/@href',
        'resources/../../../../../../../@identifier', '@*', 'resources/@*',
        'organizations/organization/item[4]/title',
        'organizations/organization/item[-2]/title',
        'organizations/organization/item[4]/@*',
        'organizations/organization/item[4]/@*item',
        'organizations/organization/item[2]/+item/title',
        'organizations/organization/item[2]/+/+/+/title',
        'organizations/organization/item[2]/-item',
        'organizations/organization/item[1]/-item',
        'organizations/organization/item[1]/-',
        'organizations/organization/item[1]/-/@.'
        ) as $path)
    $stuff .= $path . ' => ' . $testdoc->xmd_value($path) . "\n";

showDoc('Values of: @identifier, metadata/schema, ... (see below)', $stuff);


function showHtml($path)
{
    global $testdoc; echo '<h4>Html-value of ', htmlspecialchars($path),
        '</h4><pre>', $testdoc->xmd_html_value($path), '</pre>';
}


showHtml('/*/organizations/organization/item[1]/title');

showHtml('organizations/organization/item/title');

showHtml('organizations/organization/item/title *');

showHtml('Titles:  -% organizations/organization/item/titl ,  %- .');
// if no elements are found, prefix and postfix are not generated

showHtml('Titles:  -% organizations/organization/item/title ,  %- .');

showHtml('<ul><li> -% resources/resource/file/../@identifier </li><li> %- </li></ul>');

showHtml('metadata/lom/general/description/langstring');
echo '<h5>The same, but in a HTML construct:</h5>',
    $testdoc->xmd_html_value('metadata/lom/general/description/langstring');


function getmicrotime()
{
   list($usec, $sec) = explode(" ",microtime());
   return ((float)$usec + (float)$sec);
}

$xmlFile = 'imsmanifest_reload.xml';

($presXmlFileContents = @file_get_contents_n($xmlFile))
    or die('XML file  ' . htmlspecialchars($xmlFile) . ' is missing...');
$presXmlFileContents = explode("\n", $presXmlFileContents);

$seconds = getmicrotime();
$testdoc2 = new xmddoc($presXmlFileContents);
$seconds = getmicrotime() - $seconds;

showDoc('Time to parse', $seconds);

$seconds = getmicrotime();
$testdoc2->xmd_cache();
$seconds = getmicrotime() - $seconds;

showDoc('Time to cache', $seconds);

$seconds = getmicrotime();
$testdoc = new xmddoc($testdoc2->names, $testdoc2->numbers,
    $testdoc2->textstring);
$seconds = getmicrotime() - $seconds;

showDoc('Time to restore from cache', $seconds);

showDoc('OK after restore');
?>