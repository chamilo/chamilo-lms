<?php /*                                       <!-- Dokeos phpdig/search.php -->
                                                             <!-- 2005/05/02 -->

<!-- Copyright (C) 2005 rene.haentjens@UGent.be -  see metadata/md_funcs.php -->

--------------------------------------------------------------------------------
PhpDig Version 1.8.6 is provided WITHOUT warranty under the GNU/GPL license.
See the LICENSE file for more information about the GNU/GPL license.
Contributors are listed in the CREDITS and CHANGELOG files in this package.
Developer from inception to and including PhpDig v.1.6.2: Antoine Bajolet
Developer from PhpDig v.1.6.3 to and including current version: Charter
Copyright (C) 2001 - 2003, Antoine Bajolet, http://www.toiletoine.net/
Copyright (C) 2003 - current, Charter, http://www.phpdig.net/
Contributors hold Copyright (C) to their code submissions.
Do NOT edit or remove this copyright or licence information upon redistribution.
If you modify code and redistribute, you may ADD your copyright to this notice.
--------------------------------------------------------------------------------
*/

/**
============================================================================== 
*	Dokeos Metadata: search Dokeos course objects via PhpDig 1.8.6
*
*   customized search.php 1.8.6 for Dokeos 1.6 assumes $template == "array"
*
*	@package dokeos.metadata
============================================================================== 
*/

// name of the language file that needs to be included 
$language_file = "md_mix";
include('../../../main/inc/global.inc.php');
if (! $is_allowed_in_course) api_not_allowed();
	
// start of part copied (with some changes) from standard PhpDig search.php
$relative_script_path = '.';
$no_connect = 0;

if (is_file("$relative_script_path/includes/config.php")) {
    include "$relative_script_path/includes/config.php";
}
else {
    die("Cannot find config.php file.\n");
}

if (is_file("$relative_script_path/libs/search_function.php")) {
    include "$relative_script_path/libs/search_function.php";
}
else {
   die("Cannot find PhpDig search_function.php file.\n");
}

// extract vars
extract(phpdigHttpVars(
     array('query_string'=>'string',
           'mdsc'=>'string', 'kwdswere_string'=>'string',  // Dokeos
           'refine'=>'integer',
           'refine_url'=>'string',
           'site'=>'string', // set to integer later
           'limite'=>'integer',
           'option'=>'string',
           'lim_start'=>'integer',
           'browse'=>'integer',
           'path'=>'string'
           )
     ),EXTR_SKIP);

  $adlog_flag = 0;
  $rssdf = "";
// end of part copied (with some changes) from standard PhpDig search.php


// Course keywords

$_course = api_get_course_info(); $ckw = $_course['path'] . '/CourseKwds.js';
define('KEYWORDS_CACHE', api_get_path('SYS_COURSE_PATH') . $ckw);

if (file_exists(KEYWORDS_CACHE)) $kcdt = 
    htmlspecialchars(date('Y/m/d H:i:s', filemtime(KEYWORDS_CACHE)));

$keywordscache = $kcdt ?
    '<script type="text/javascript" src="' . api_get_path('WEB_COURSE_PATH') . $ckw . '"></script>' . 
    '<br /><small><i>(CourseKwds cache: ' . $kcdt . ')</i></small>' : '';


// Dokeos header

$nameTools = get_lang('Search');
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="../../../main/metadata/md_styles.css">';
$htmlHeadXtra[] = '<script type="text/javascript" src="../../../main/metadata/md_script.js"></script>';
$htmlHeadXtra[] = '
    <script type="text/javascript">
    /* <![CDATA[ */
        var kwdswere = "' . $kwdswere_string . '";
        
        function selakw(word)
        {
            pU_select(word); return false;
        }
        
        function seladw(word)
        {
            document.getElementById("mdsc").value += "\n" + word;
            return false;
        }
    /* ]]> */
    </script>';

Display::display_header($nameTools); echo "\n";

echo '<table width="100%"><tr><td><h3>', get_lang('Search'), '</h3></td>', 
    '<td align="right"><a href="http://www.phpdig.net"><img src="phpdig_powered_2.gif"/></a></td></tr></table>';


// Store new extra criteria (see below), or execute PhpDig Search 
// and echo result message, table with results, pages bar

if (!$query_string) $query_string = trim($mdsc);

$ckwcdt = file_exists($ckwc = KEYWORDS_CACHE . 'c') ? 
    date('Y/m/d H:i:s', filemtime($ckwc)) : '?'; $pkwc = '';

if (strpos($query_string, '>') && api_is_allowed_to_edit())
{
    if ($ckwcdt{0} != '?')
    {
        $fckwc = fopen($ckwc, 'rb'); $pkwc = fread($fckwc, filesize($ckwc));
        fclose($fckwc); unset($fckwc);
    }
    
    if(($fckwc = fopen($ckwc, 'wb')))
    {
        fwrite($fckwc, $query_string); fclose($fckwc); unset($fckwc);
        $ckwcdt = file_exists($ckwc) ? 
            date('Y/m/d H:i:s', filemtime($ckwc)) : '? Write Error';
    }
    else $ckwcdt = '? Open Error';
    
    $phpdigSearchResults = array('result_message' => $ckw . 'c: ' . $ckwcdt, 
        'pages_bar' => '', 'results' => array());
}
else
{
    $phpdigSearchResults = phpdigSearch($id_connect, $query_string, $option, 
        $refine, $refine_url, $lim_start, $limite, $browse, $site, $path, 
        $relative_script_path, $template, $adlog_flag, $rssdf, $template_demo);
}

$result_message = ''; $hits = 11;

if ($result_message = $phpdigSearchResults['result_message'])
    if (($cspos = strpos($result_message, ', ')) !== FALSE)
        if (($sppos = strpos($result_message, ' ', $cspos += 2)) !== FALSE)
            if (is_numeric($total = substr($result_message, $cspos, $sppos-$cspos)))
                $hits = (int) $total;

if (!($pages_bar = $phpdigSearchResults['pages_bar'])) $hits = 0;

if ($result_message == phpdigMsg('noresults')) $result_message .= ' '.phpdigMsg('on').' "'.htmlspecialchars($query_string,ENT_QUOTES).'"';

echo $result_message, '<br><br><table border="1">', "\n";

if ($phpdigSearchResults['results']) foreach ($phpdigSearchResults['results'] as $searchResult)
{
    $url = $searchResult['complete_path'];
    
    if (ereg("/[^?/]*\\?.*thumb=", $url))
    {
        // direct URL: $thumburl = ereg_replace("/[^?/]*\\?.*thumb=", "/", $url);
        $thumburl = ereg_replace("\\?.*thumb=", "?th=", $url);  // via index.php
    }
    else
    {
        $thumburl = "tpl_img/link.gif";
    }

    echo '<tr><td><a target="_blank" href="', $url, '"><img src="', $thumburl, 
        '"/></a></td><td><a target="_blank" href="', $url, '">', $searchResult['link_title'], 
        '</a><br>', $searchResult['text'], '</td></tr>', "\n";
}

echo '</table><br>';

if ($result_message && ($hits > 10))
    echo "Results page ", str_replace('?template_demo=', 
        '?kwdswere_string=' . urlencode($kwdswere_string), $pages_bar), '<br><br>';

/* Extra criteria: course manager types in the descriptive zone (Advanced):

keyword=k>
 -
keyword=k>label
<br/>
descriptive-word=d>label for dword

*/

$tdhtm = '';

function tdhtm($xc, $pp, $type = 'k')
{
    $value = substr($xc, 0, $pp);
    if (!($label = trim(substr($xc, $pp + 2 + strlen($type))))) $label = $value;
    
    return '<a href="." onClick="return sela' . 
        $type . 'w(\'' . $value . '\')">' . $label . '</a>';
}

if ($ckwcdt{0} != '?')  // there is a file with extra criteria
{
    $fckwc = fopen($ckwc, 'rb');
    foreach (explode("\n", fread($fckwc, filesize($ckwc))) as $xc)
        $tdhtm .= "\n" . (($pp = strpos($xc, '=k>')) ? tdhtm($xc, $pp) :
            (($pp = strpos($xc, '=d>')) ? tdhtm($xc, $pp, 'd') : $xc));
    fclose($fckwc); unset($fckwc);
}

/* Sample result for the above extra criteria:

<a href="." onClick="return selakw('keyword')">keyword</a> -
<a href="." onClick="return selakw('keyword')">label</a><br/>
<a href="." onClick="return seladw('descriptive-word')">label for dword</a>

*/

// Search criteria form and keywords tree
?>

<div onMouseUp="if ((kw = pU_clicked(event))) pU_select(kw); else pU_hide();">

<input type="text" id="kwds_string" class="kwl" onKeyUp="takeTypeIn(this, 150, -100, '60%'); return true;"/>

<form action="<?php echo $_SERVER['PHP_SELF']?>" method="post">

<div id="descriptive_words" class="dvc">
    <?php echo get_lang('SearchCrit')?>:<br/>
    <table><tr>
    <td><textarea rows=10 name="mdsc" id="mdsc"><?php echo htmlspecialchars($pkwc) ?></textarea></td>
    <td><?php echo $tdhtm ?></td>
    </tr></table>
</div>

<input type="hidden" id="kwdswere_string" name="kwdswere_string"/>
<button onClick="document.getElementById('descriptive_words').className='dvo'; this.style.display='none'; return false;"><?php echo get_lang('Advanced') ?></button>
<input type="submit" onClick="document.getElementById('kwdswere_string').value = document.getElementById('kwds_string').value; return prepSearch(event);" value="<?php echo phpdigMsg('go')?>"/>

<div id="popup" noWrap="1" class="pup">
    Working...
</div>

</form>

<div noWrap="1" id="maindiv">
    <?php if ($keywordscache == '') { ?> &#xa0; <?php } else { ?>
    <input type="button" class="btn" value="+" onClick="if (this.value == '+') deselectAll(event, this); openOrClose(this);"/>
    <input type="button" class="btm" id="btnOpenOrCloseAll" value="++" onClick="openOrCloseAll(this);"/>
    <input type="button" class="btn" value="?" onClick="openOrCloseHelp(this)"/>
    &#xa0;<?php echo get_lang('ClickKw'), $keywordscache; } ?>
</div>

<div id='moreHelp' class='dvc'>
    <?php echo get_lang('KwHelp')?>
</div>

</div><!-- onMouseUp -->

<?php

/***** Example inserting in own domain (not used here)
* PhpDig installed at: http://www.domain.com/phpdig/
* Want search page at: http://www.domain.com/search.php
* Copy http://www.domain.com/phpdig/search.php to http://www.domain.com/search.php
* Copy http://www.domain.com/phpdig/clickstats.php to http://www.domain.com/clickstats.php
* Set $relative_script_path = './phpdig'; in search.php, clickstats.php, and function_phpdig_form.php
* Add ($relative_script_path != "./phpdig") && to if statement
*****/

echo "\n"; Display::display_footer();
?>
