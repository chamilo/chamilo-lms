<?php /*                                                    <!-- xht.lib.php -->
                                         <!-- XML HTML Templates, 2006/12/14 -->

<!-- Copyright (C) 2006 rene.haentjens@UGent.be - see note at end of text    -->
<!-- Released under the GNU GPL V2, see http://www.gnu.org/licenses/gpl.html -->

*/

/**
*	This is an XML HTML template library.
*	Include/require it in your code to use its functionality.
*
*   This library defines function xht_htmlwchars & class xhtdoc with methods:
*   - xht_fill_template($template_name)
*   - xht_substitute($subtext)
*
*   Check htt_error after defining a new xhtdoc(htt_file_contents).
*
*   Assign xht_xmldoc (, xht_get_lang, xht_resource, xht_dbgn)
*   before calling the class methods.
*
*	@package chamilo.library
*/



function xht_htmlwchars($s)  // use only where ISO-8859-1 is not required!
{
    //return ereg_replace('\[((/?(b|big|i|small|sub|sup|u))|br/)\]', '<\\1>',
    //    str_replace('@�@', '&',
    //    htmlspecialchars(ereg_replace('&#([0-9]+);', '@�@#\\1;', $s))));
	global $charset;
    return api_ereg_replace('\[((/?(b|big|i|small|sub|sup|u))|br/)\]', '<\\1>',
        str_replace('@�@', '&',
        htmlspecialchars(api_ereg_replace('&#([0-9]+);', '@�@#\\1;', $s), ENT_QUOTES, $charset)));
    // replaces htmlspecialchars for double-escaped xml chars like '&amp;#nnn;'
    // and                       when html tags <...> are represented as [...]
}

function xht_is_assoclist($s) // ":key1:value1,, key2:value2,, ..."
{
    return is_string($s) && strpos($s, ',,');
}

function xht_explode_assoclist($s)
{
    $result = array(); if (!xht_is_assoclist($s)) return $result;

    foreach (explode(',,', api_substr($s, 1)) as $keyplusvalue)
        if ($cp = api_strpos($keyplusvalue, api_substr($s, 0, 1)))
            $result[trim(api_substr($keyplusvalue, 0, $cp))] =
                api_substr($keyplusvalue, $cp+1);

    return $result;
}


class xhtdoc
{

var $htt_array;     // array with HTML templates
var $htt_error;     // error while parsing htt_file_contents to templates

var $xht_xmldoc;    // XML Mini-DOM document for which templates are processed
var $xht_param;     // parameter array for template processing
var $xht_get_lang;  // user-supplied function for {-L xx-}, e.g. Dokeos get_lang
var $xht_resource;  // user-supplied function for '=/' in path

var $xht_dbgn;      // set to a positive value for debugging output
var $xht_dbgo;      // debugging output accumulates here

var $_prev_param;   // old parameter values


function xht_fill_template($template_name, $cur_elem = 0)
{
    $template_name = trim($template_name);
    if (!$template_name || (api_strpos($template_name, ' ') !== FALSE)) return '';
    if (!is_string($httext = $this->htt_array[$template_name]))     return '';

    if ($this->xht_dbgn) $this->xht_dbgo .= '<!-- ' . XHT_LP . $template_name .
            ' ' . XHT_RP . $this->_show_param() . " -->\n";

    $prev_lpp = 0; $prev_sub = ''; $scanpos = 0;

    while (($rpp = api_strpos($httext, XHT_RP, $scanpos)) !== FALSE)  // first -}
    {
        if (($lpp = api_strpos($httext, XHT_LP)) === FALSE) break;  // no {- for -}
        if ($lpp > $rpp) break;  // no {- preceding -}

        while (($next_lpp = api_strpos($httext, XHT_LP, $lpp + XHT_PL)) !== FALSE)
            if ($next_lpp > $rpp) break;
            else $lpp = $next_lpp;  // find {- closest to -}

        $subtext = api_substr($httext, $lpp + XHT_PL, $rpp - $lpp - XHT_PL);

        $httext = api_substr($httext, 0, $lpp) .
            $this->xht_substitute($subtext, $cur_elem, XHT_LP, XHT_RP) .
            api_substr($httext, $rpp + XHT_PL);  // substitute or leave intact

        if ($lpp == $prev_lpp && $subtext == $prev_sub)  // prevent looping
        {
            $scanpos = $rpp + 1; $prev_lpp = 0; $prev_sub = '';
        }
        else
        {
            $prev_lpp = $lpp; $prev_sub = $subtext;
        }
    }

    return $httext;
}


function xht_substitute($subtext, $cur_elem = 0, $pre = '', $post = '')
{
	global $charset;

	$regs = array(); // for use with ereg()
    if (!api_ereg(XHT_SUBS2, $subtext, $regs) && !api_ereg(XHT_SUBS1, $subtext, $regs))
        return $pre . $subtext . $post;

    $type = $regs[1]; $text = $regs[2]; $result = ''; $subnum = FALSE;
    $subtext = isset($regs[3]) ? $regs[3] : '';

    if ($this->xht_dbgn)  // generate debugging output, with new number $subnum
    {
        $subnum = ++ $this->xht_dbgn;
        $this->xht_dbgo .= '<!-- ' . XHT_LP . $type . $subnum . '+ ' .
            htmlspecialchars($text, ENT_QUOTES, $charset) . ' ' . XHT_RP .
            $this->_show_param() .  " -->\n";
    }

    if     ($type == 'D')  // Define, e.g. {-D key value-}
    {
        // Assign the value to parameter [key]
        $this->xht_param[$text] = $subtext;
        // used to be:          = $this->xht_substitute($subtext, $cur_elem);
    }
    elseif ($type == 'E')  // Escape, e.g. {-E userFunction subtext-}
    {
        $result = call_user_func($text, FALSE);  // get cached result, if any

        if ($result === FALSE)  // no cached result available
        {
            $result = $this->xht_substitute($subtext, $cur_elem);
            $result = call_user_func($text, $result);  // allow to cache
        }
    }
    elseif ($type == 'R')  // Repeat, e.g. {-R general/title/string subtext-}
    {
        $rdepthn = 'rdepth' . (++ $this->xht_param['rdepth']);
        $n = 0; $this->xht_param['number'] = '0';

        if (is_array($a = $this->_lang($text, $cur_elem)))  // repeat on array
        {
            foreach ($a as $key => $value)
            {
                $this->xht_param['number'] = (string) (++ $n);
                $this->xht_param[$rdepthn] = (string) ($n);
                $this->xht_param['key'] = $key;
                $this->xht_param['value'] = $value;
                $result .= $this->xht_substitute($subtext, $cur_elem);
            }
        }
        elseif (xht_is_assoclist($a))  // repeat on associative list
        {
            foreach (xht_explode_assoclist($a) as $key => $value)
            {
                $this->xht_param['number'] = (string) (++ $n);
                $this->xht_param[$rdepthn] = (string) ($n);
                $this->xht_param['key'] = $key;
                $this->xht_param['value'] = $value;
                $result .= $this->xht_substitute($subtext, $cur_elem);
            }
        }
        elseif (!is_object($this->xht_xmldoc))
        {
            $result = '? R Error: no XML doc has been assigned to xht_xmldoc';
        }
        else  // repeat on XML elements
        {
            $sub_elems =
                $this->xht_xmldoc->xmd_select_elements($text, $cur_elem);

            foreach ($sub_elems as $subElem)
            {
                $this->xht_param['number'] = (string) (++ $n);
                $this->xht_param[$rdepthn] = (string) ($n);
                $result .= $this->xht_substitute($subtext, $subElem);
            }
        }
        // removed 2004/10/05: template_array (security)
        // added   2005/03/08: associative list (lang arrays deprecated)

        $this->xht_param['rdepth'] --;

        // As there is only one ['number'] or one set ['key'] + ['value'],
        // using them in nested repeats may not have the desired result.
    }
    elseif ($type == 'T')  // Test, e.g. {-T key1 == key2 text-}
    {
        if (api_ereg('^(=|==|<=|<|!=|<>|>|>=) +([^ ]+) +(.*)$', $subtext, $regs))
        {
            // Comparand= parameter value if set, else languagevar value

            $cmp1 = isset($this->xht_param[$text]) ?
                $this->xht_param[$text] : $this->_lang($text, $cur_elem);
            $cmp3 = isset($this->xht_param[$cmp3 = $regs[2]]) ?
                $this->xht_param[$cmp3] : $this->_lang($cmp3, $cur_elem);
            $cmp = strcmp($cmp1, $cmp3); $op = ' ' . $regs[1] . ' ';

            if ($subnum) $this->xht_dbgo .= '<!-- ' . XHT_LP . $type . $subnum .
                    '  ' . htmlspecialchars($cmp1.$op.$cmp3.' = '.$cmp, ENT_QUOTES, $charset) .
                    ' ' . XHT_RP . " -->\n";  // debugging output

            if (    ($cmp <  0  &&  api_strpos('  <= < != <> ', $op)) ||
                    ($cmp == 0  &&  api_strpos('  = == <= >= ', $op)) ||
                    ($cmp >  0  &&  api_strpos('  != <> > >= ', $op))   )
                $result = $this->xht_substitute($regs[3], $cur_elem);
            // else $result is empty
        }
        else
        {
            $result = $pre . $subtext . $post;
        }
    }
    else
    {
        if (api_strpos('CLPVX', $type) !== FALSE)  // used to be always
            $text = $this->xht_substitute($text, $cur_elem);  // nested escape

        if     ($type == 'C') // Call, e.g. {-C SUBTEMPLATE-}
                $result = $this->xht_fill_template($text, $cur_elem);
        elseif ($type == 'H') $result = htmlspecialchars($text, ENT_QUOTES, $charset);
        elseif ($type == 'L') $result = $this->_lang($text, $cur_elem);
        elseif ($type == 'P') $result = $this->xht_param[$text];
        elseif ($type == 'U') $result = urlencode($text);
        elseif ($type == 'W') $result = xht_htmlwchars($text);
        elseif (!is_object($this->xht_xmldoc))
        {
            $result = '? V/X Error: no XML doc has been assigned to xht_xmldoc';
        }
        else // $type == 'V' or 'X'
        {

            if (api_ereg('^(.*)=/(.+)$', $text, $regs))  // special resource-marker
            {
                $path = $regs[1]; $text = $regs[2];
                if (api_substr($path, -1) == '/') $path = api_substr($path, 0, -1);

                if ($path) $cur_elem = $this->xht_xmldoc->
                    xmd_select_single_element($path, $cur_elem);

                $cur_elem = call_user_func($this->xht_resource, $cur_elem);
            }

            $result = ($type == 'V') ?
                $this->xht_xmldoc->xmd_value($text, $cur_elem) :
                $this->xht_xmldoc->
                    xmd_html_value($text, $cur_elem, 'xht_htmlwchars');
        }
    }

    if ($subnum) $this->xht_dbgo .= '<!-- ' . XHT_LP . $type . $subnum . '- ' .
            htmlspecialchars((api_strlen($result) <= 60) ?
                $result . ' ': api_substr($result, 0, 57).'...', ENT_QUOTES, $charset) . XHT_RP . " -->\n";

    return $result;
}


function xht_add_template($template_name, $httext)
{
    $this->htt_array[$template_name] = $httext;
        // removed 2004/10/05: (substr($httext, 0, 6) == 'array(') ?
        // removed 2004/10/05:     @eval('return ' . $httext . ';') : $httext;

    if (!$this->htt_array[$template_name])
    {
        $this->htt_error = 'template ' . $template_name .
        ' is empty or invalid'; return;
    }
}


function xhtdoc($htt_file_contents)
{
    $htt_file_contents =  // normalize \r (Mac) and \r\n (Windows) to \n
        str_replace("\r", "\n", str_replace("\r\n", "\n", $htt_file_contents));

    while (api_substr($htt_file_contents, -1) == "\n")
        $htt_file_contents = api_substr($htt_file_contents, 0, -1);

    $last_line = api_strrchr($htt_file_contents, "\n"); $this->htt_error = '';

    if (api_strlen($last_line) < 12 || api_substr($last_line, 0, 6) != "\n<!-- "
        || api_strlen($last_line) % 2 != 0 || api_substr($last_line, -4) != " -->")
    {
        $this->htt_error = 'last line must be of the form <!-- {--} -->';
        return;
    }

    define('XHT_PL', (int) (api_strlen($last_line) - 10) / 2);    // Parentheses Lth
    define('XHT_LP', api_substr($last_line, 6, XHT_PL));          // Left Par
    define('XHT_RP', api_substr($last_line, 6 + XHT_PL, XHT_PL)); // Right Par

    if (XHT_LP == XHT_RP)
    {
        $this->htt_error =
            'parentheses in last line <!-- {--} --> must not be identical';
        return;
    }

    $this->htt_array = array();  // named elements are arrays and strings

    foreach (explode("\n<!-- " . XHT_LP, "\n" . $htt_file_contents)
            as $name_and_text)
    {
        if (($name_length = api_strpos($name_and_text, XHT_RP . " -->\n")))
        {
            $template_name = trim(api_substr($name_and_text, 0, $name_length));

            if (api_strpos($template_name, ' ') !== FALSE) give_up('Template ' .
                $template_name . ' has a space in its name');
            $httext = api_substr($name_and_text, $name_length + XHT_PL + 5);

            while (api_substr($httext, 0, 1) == "\n") $httext = api_substr($httext, 1);
            while (api_substr($httext, -1) == "\n") $httext = api_substr($httext,0,-1);

            $this->xht_add_template($template_name, $httext);
        }
    }

    define('XHT_SUBS1', '^(C|H|L|P|U|V|W|X) +(.*)$');   // substitution types 1:
    // Call, Htmlchars, Lang, Param, Urlencode, Value, Wchars, Xvalue
    define('XHT_SUBS2', '^(D|E|R|T) +([^ ]+) +(.*)$');  // substitution types 2:
    // Define, Escape, Repeat, Test

    $this->xht_dbgo = '';

    $this->xht_param = array(0 => '0', 1 => '1',
        '' => '', 'empty' => '', 'rdepth' => 0);
    $this->_prev_param = $this->xht_param;
    // empty:  {-R * P empty-} puts the number of subelements in {-P number-}
    // rdepth: current number of nested R's
    // rdepth1, rdepth2, ...: key or number, see R
}


function _show_param()  // for debugging info
{
	global $charset;
    $result = '';
    foreach ($this->xht_param as $k => $v)
        if ($v !== $this->_prev_param[$k]) {
            $this->_prev_param[$k] = $v; $result .= ', ' . $k . ': ' .
                ((api_strlen($v) <= 20) ? $v : api_substr($v, 0, 17).'...');
        }
    return $result ? htmlspecialchars(api_substr($result, 1), ENT_QUOTES, $charset) : '';
}

function _lang($var, $cur_elem = 0)
{
    $result = @call_user_func($this->xht_get_lang, $var, $cur_elem);

	// This is a hack for proper working of the language selectors
	// in the forms that are generated through templates.
	if ($var == 'Langs')
    {
    	global $langLangs;
    	if (isset($langLangs))
    	{
    		$result = $langLangs;
    	}
    }

    return isset($result) ? $result : $var;
}


}

/*

A word of explanation...

The last line of a template file (example below) defines the special markers
that are used around template names such as INPUT and OPTION and around HTML
escapes such as 'P value' and 'L Store', { and } in the example. You can also
use markers of more than one character as long as both have the same length,
e.g. <% and %>. The markers must not be equal. In templates with JavaScript
or <style>, the use of { and } might be confusing; however, it does work.

A template starts with a special comment line giving the name of the template
and ends where the next template starts.

Templates contain escapes of the form {one-letter the-rest}, e.g.
{L IdentifierTip}, where the-rest can contain a nested-escape as in e.g.
{R metadata/lom/general/keyword C KEYWORDTEMPLATE}.

Multiple spaces count as one in many places, but not in all. Best is to use
correct spacing. In particular, a T escape such as the example below requires
a space at the end of the first line:
{T x == y
text
}

Names (of templates, parameters, langvars and user-functions)
are case sensitive and cannot contain spaces.

Templates are called with a certain currency in the XML doc, usually the root
element; for an xml-path repeat, the found element is the current one.

One single array with named elements and string values contains the
template parameters; they are shared for all template processing.

Initially, params contains
    0 => '0', 1 => '1', '' => '', 'empty' => '', 'rdepth' => 0.
rdepth keeps track of the current number of nested repeats. In a repeat,
params number, key, value and rdepthN (N is 1, 2, ...) get assigned values.

Types of escapes:

C template-name/nested-escape   Call a sub-template
D parameter-name text           Define a parameter value
E user-function nested-escape   Escape to user-function or do nested-escape
H text                          HtmlSpecialChars (e.g. transform < to &lt;)
L languagevar/nested-escape     Language variable value (see also below)
P parameter-name/nested-escape  Parameter value
R repeat-part nested-escape     Repeat nested-escape for each ... (see below)
T key1 operator key2 nested-esc Test, do nested-esc if test succeeds (id.)
U text                          UrlEncode (e.g. transform < to %XX)
V xml-path/nested-escape        Value from XML document element or attribute
W text                          xht_htmlwchars (see below)
X extended-xml-path/nested-esc  eXtended Value from XML (see below)

nested-escape= without the special markers. Nesting with special markers is
    always possible and in that case the inner nesting is evaluated first.
    Note that as from the 2005/03/15 version, some implicit nestings are no
    longer possible, e.g. {H {P key}} and {D label {L Keyword}} now require
    the inner markers.

Escape allows the caller to cache template output:
    user-function is called with parameter (FALSE);
    if it returns a string, that string is the result;
    if it returns FALSE, nested-escape is executed to produce a result
    and user-function is called again with the result, allowing it
    to cache the result somewhere; user-function then returns the real result.

repeat-part can be:
    the name of a languagevar which contains an associative list such as:
        ":key1:value1,, key2:value2,, key3:value3" (note double ,,):
        nested-escape is repeated for each list element,
        params 'key' and 'value' refer to the current element;
        first character defines key-value-separator (here a colon);
        key cannot contain key-value-separators and is trimmed;
        value can contain anything except ,, and it is not trimmed;
        put ',,' at the end of a 0- or 1-element-list.
    the name of a languagevar which has an array value:
        nested-escape is repeated for each array element,
        params 'key' and 'value' refer to the current element.
    an xml-path to 0, 1 or more elements in the XML document:
        nested-escape is repeated for each element, param 'number' = 1, 2, ...

test operators compare strings: = ==  <=  <  !=  <>  >  >=
    key1 and key2 can be parameter-name or languagevar-name.

xml-path: see XML Mini-DOM; examples:
    organizations/@default, body/p[1] (1=first), keyword/*, node/*[-3]
    / * /... starts from XML document root, regardless of context
    other extensions: .. - + @* @. (parent, prev, next, number, name)
    .. stops at the root if too many
    -name, +name and @*name means only elements of that name

xht_htmlwchars is like HtmlSpecialChars, but moreover translates:
    [b] to <b>, likewise for big, i, small, sub, sup, u, br, and closing tags;
    &amp;#nnn; to &#nnn; (double-escape for non-UTF8-channels)

eXtended Value: see method xmd_html_value of the XML Mini-DOM;
    the values (but not the pre-, in- and suffixes) are always W-processed;
    extended-xml-path examples:
    'keyword/string ,'            generates e.g. 'kwd1,kwd2,kwd3'
    'keyword/string , '           generates e.g. 'kwd1, kwd2, kwd3'
    '( -% keyword/string ,  %- )' generates e.g. '(kwd1,kwd2,kwd3)'
        but will generate nothing if no keywords are found in the XML doc;
        note that the special markers ' -% ' and ' %- ' must have the spaces.

V and X escapes can have an xml-path containing '=/': this calls a user-supplied
function for finding an associated element. It can e.g. be used when reading
a SCORM manifest, for finding the <resource> of an <item>.

L also calls a user-supplied function. Its main target is language-dependent
texts, e.g. Dokeos 'get_lang'. But the current element is passed as second
argument, allowing other functionality in the callback.

Array templates functionality has been removed on 2004/10/05, because of a
security issue when users can submit templates.

--------------------------------------------------------------------------------

Example template file:

<!-- {HEAD} -->
<style type="text/css">
    body    {font-family: Arial}
</style>

<!-- {METADATA} -->
<h3>{H {L Tool}: {P entry}}</h3>
<table>
    <tr>
        <td>{D label {L Language}}{D tip {L LanguageTip}}{C LABEL}</td>
        <td><select>{D thislang {V general/language}}{R Langs C OPTION}</select></td>
        <td><input type="text" disabled value="urn:ugent-be:minerva."/></td>
    </tr>
{R general/keyword C KEYWORD}
</table>

<!----------------------  E-n-d Of script Output  ---------------------->

<!-- {LABEL} -->
<span title="{H {P tip}}">{H {P label}}&#xa0;:</span>
<!-- {OPTION} -->
<option value="{H {P key}}" {T key == thislang selected}>{H {P value}}</option>
<!-- {INPUT} -->
<input type="text" title="{P title}" class="wide" value="{H {P value}}"/>
<!-- {KEYWORD} -->
<tr>
    <td>{D label {L Keyword}}{D tip {L KeywordTip}}{C LABEL}</td>
    <td nowrap><select>{D thislang {V string/@language}}{R Langs C OPTION}</select></td>
    <td>{D value {X string}}{D title general/keyword[{P number}]/string}{C INPUT}</td>
</tr>
<!-- {} -->

--------------------------------------------------------------------------------

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
