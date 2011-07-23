<?php 
/**
 * testXht.php, 
 * @date 2005/03/16
 * @date for XML HTML Templates, 2005/03/16
 * @copyright 2005 rene.haentjens@UGent.be                              -->
 * @package chamilo.metadata
 */
/**
 * Chamilo Metadata: XHT test and demo
 */

require('../../inc/lib/xmd.lib.php');
require('../../inc/lib/xht.lib.php');


// XML DOCUMENT --------------------------------------------------------------->

$testdoc = new xmddoc(
<<<EOD
<docroot xmlns:imsmd="http://www.imsglobal.org/xsd/imsmd_v1p2">
    <title>Test for XML HTML Templates</title>
    <description>This is a [b]test[/b] for &amp;#911; XML with &lt;some&gt; &quot;funny&quot; stuf&#102;...
    a new line and <x>1</x> inside tag.</description>
    <keywords>
        <keyword>kw1</keyword>
        <keyword>kw2</keyword>
        <keyword>kw3</keyword>
    </keywords>
    <metadata>
        <schema>IMS Content</schema>
        <schemaversion>1.2.2</schemaversion>
        <imsmd:lom>
          <imsmd:general>
            <imsmd:catalogentry>
              <imsmd:catalog />
              <imsmd:entry>
                <imsmd:langstring xml:lang="en" />
              </imsmd:entry>
            </imsmd:catalogentry>
            <imsmd:language>en</imsmd:language>
            <imsmd:description>
              <imsmd:langstring xml:lang="en">Simple exemplar content package


, this description was
                         modified
by
               Ren&eacute;
                                       </imsmd:langstring>
            </imsmd:description>
          </imsmd:general>
        </imsmd:lom>
    </metadata>
</docroot>
EOD
);

if ($testdoc->error) die($testdoc->error);


// TEMPLATES ------------------------------------------------------------------>

$xhtDoc = new xhtdoc(
<<<EOD
<!-- {-HTTP-} -->

Expires: Mon, 26 Jul 1997 05:00:00 GMT


<!-- {-HEAD-} -->

<style type="text/css"> .bg3 {background-color:#E2E2E2} </style>


<!-- {-MAIN-} -->

<h1>testXht</h1>

<h3>{-X title-}</h3>

Hello {-P p1-}! {-X description-}<br>
{-X metadata/lom/general/description/langstring-}<br><br>

{-D label This is a funny <La"bel>-}{-C LABEL-}<br><br>
<table>
    {-R keywords/keyword C KEYWORD-}
</table>
There are {-R keywords/keyword P empty-}{-P number-} keywords...<br><br>

<select>
    {-D selkey nl-}{-R Langnames C OPTION-}
</select>
<br><br>

{-R Langnames C LEVEL1-}<br><br>

{-D author {-V author-}-}
{-T author != empty
<h5>There is an author</h5>
<!-- Note1: T tests parameters, not XML values directly -->
<!-- Note2: the space after 'empty' is necessary! - see below
 -}
{-T author != empty
parses wrong because missing space after 'empty'
 -}<br><br>

Special parentheses {-can still be used-} for other -}{-{-purposes...
<br>
Nesting is {-H {-L Am-}-}
<br><br>

{-E md_cache C RECALC-}


<!-- {-RECALC-} -->

This text is re-calculated when the cache is no longer valid.


<!-- {-LABEL-} -->

<span class="bg3">{-H {-P label-}-}&#xa0;:</span>&#xa0;


<!-- {-KEYWORD-} -->

<tr>
    <td>{-D label {-L Kw-}-}{-C LABEL-}{-X .-}</td>
    <td><input type="checkbox" title="keyword{-P number-}"/></td>
</tr>


<!-- {-OPTION-} -->

<option value="{-H {-P key-}-}" {-T key == selkey selected-}>{-H {-P value-}-}</option>


<!-- {-LEVEL1-} -->

<b>{-P rdepth-}.{-P key-}</b>: {-R keywords/keyword C LEVEL2-}<br>


<!-- {-LEVEL2-} -->

{-P rdepth-}.{-P number-}


<!-- {--} -->
EOD
);

if ($xhtDoc->htt_error) die($xhtDoc->htt_error);

$xhtDoc->xht_xmldoc = $testdoc;


// PREPARE FOR PROCESSING ----------------------------------------------------->

function get_lang($word)
{
    if ($word == 'Kw') return 'Keyword';
    elseif ($word == 'Am') return '"Automatic"';
    elseif ($word == 'Langnames')
        return array("de"=>"German", "fr"=>"French", "nl"=>"Dutch");
    else return 'To be translated';
}

$xhtDoc->xht_get_lang = 'get_lang';

$xhtDoc->xht_param['p1'] = 'world';

function md_cache($newtext)  // callback from template (for cached HTML)
{
    if ($newtext === FALSE)  // this is always the first callback
    {
        $cachedHtmlIsValid = FALSE;  // in real examples, not always

        if ($cachedHtmlIsValid)
            return 'Cached HTML';
        else
            // do some preparations
            return FALSE;  // signals XHT to generate new text from template
    }
    else    // after template expansion, XHT does a second callback
    {
        // store the new text in the cache...
        // possibly modify the text to be output...
        return $newtext;  // often the output is identical to the new text
    }
}


// GENERATE OUTPUT ------------------------------------------------------------>

foreach (explode("\n", $xhtDoc->htt_array['HTTP']) as $httpXtra)
    if ($httpXtra) header($httpXtra);

echo "<html>\n<head>", $xhtDoc->xht_fill_template('HEAD'),
    "\n</head>\n\n<body>\n";

$xhtDoc->xht_dbgn = 0;  // for template debug info, set to e.g. 10000

echo $xhtDoc->xht_fill_template('MAIN'),
    '<br><br>Child nodes of "description":';

foreach($testdoc->children[$testdoc->xmd_select_single_element('description')] as $child)
    echo '<br>', strlen($child), ': ', htmlspecialchars($child);
echo "\n\n</body>\n</html>\n";

if ($xhtDoc->xht_dbgn) echo $xhtDoc->xht_dbgo;

// Note: XML document and templates would normally be fetched from (different)
// external sources, such as a file or a DB record...
?>
