<?php
/* For licensing terms, see /license.txt */

/**
 *	This is the text library for Chamilo.
 *	Include/require it in your code to use its functionality.
 *
 *	@package chamilo.library
 */

/*	FUNCTIONS */

/**
 * function make_clickable($string)
 *
 * @desc   completes url contained in the text with "<a href ...".
 *         However the function simply returns the submitted text without any
 *         transformation if it already contains some "<a href:" or "<img src=".
 * @param string $text text to be converted
 * @return text after conversion
 * @author Rewritten by Nathan Codding - Feb 6, 2001.
 *         completed by Hugues Peeters - July 22, 2002
 *
 * Actually this function is taken from the PHP BB 1.4 script
 * - Goes through the given string, and replaces xxxx://yyyy with an HTML <a> tag linking
 * 	to that URL
 * - Goes through the given string, and replaces www.xxxx.yyyy[zzzz] with an HTML <a> tag linking
 * 	to http://www.xxxx.yyyy[/zzzz]
 * - Goes through the given string, and replaces xxxx@yyyy with an HTML mailto: tag linking
 *		to that email address
 * - Only matches these 2 patterns either after a space, or at the beginning of a line
 *
 * Notes: the email one might get annoying - it's easy to make it more restrictive, though.. maybe
 * have it require something like xxxx@yyyy.zzzz or such. We'll see.
 */

function make_clickable($string) {
	// TODO: eregi_replace() is deprecated as of PHP 5.3
	if (!stristr($string, ' src=') && !stristr($string, ' href=')) {
		$string = eregi_replace("(https?|ftp)://([a-z0-9#?/&=._+:~%-]+)", "<a href=\"\\1://\\2\" target=\"_blank\">\\1://\\2</a>", $string);
		$string = eregi_replace("([a-z0-9_.-]+@[a-z0-9.-]+)", "<a href=\"mailto:\\1\">\\1</a>", $string);
	}
	return $string;
}

/**
 * @desc This function does some parsing on the text that gets inputted. This parsing can be of any kind
 * 		LaTeX notation, Word Censoring, Glossary Terminology (extension will available soon), Musical Notations, ...
 *		The inspiration for this filter function came from Moodle an phpBB who both use a similar approach
 * @param $input string. some text
 * @return $output string. some text that contains the parsed elements.
 * @example [tex]\sqrt(2)[/tex]
 * @author Patrick Cool <patrick.cool@UGent.be>
 * @version March 2OO6
 */
function text_filter($input, $filter = true) {

	//$input = stripslashes($input);

	if ($filter) {
		// ***  parse [tex]...[/tex] tags  *** //
		// which will return techexplorer or image html depending on the capabilities of the
		// browser of the user (using some javascript that checks if the browser has the TechExplorer plugin installed or not)
		$input = _text_parse_tex($input);

		// *** parse [teximage]...[/teximage] tags *** //
		// these force the gif rendering of LaTeX using the mimetex gif renderer
		//$input=_text_parse_tex_image($input);

		// *** parse [texexplorer]...[/texexplorer] tags  *** //
		// these force the texeplorer LaTeX notation
		$input = _text_parse_texexplorer($input);

		// *** Censor Words *** //
		// censor words. This function removes certain words by [censored]
		// this can be usefull when the campus is open to the world.
		// $input=text_censor_words($input);

		// *** parse [?]...[/?] tags *** //
		// for the glossary tool
		$input = _text_parse_glossary($input);

		// parse [wiki]...[/wiki] tags
		// this is for the coolwiki plugin.
		// $input=text_parse_wiki($input);

		// parse [tool]...[/tool] tags
		// this parse function adds a link to a certain tool
		// $input=text_parse_tool($input);

		// parse [user]...[/user] tags

		// parse [email]...[/email] tags

		// parse [code]...[/code] tags
	}

	return $input;
}

/**
 * Applies parsing for tex commandos that are seperated by [tex]
 * [/tex] to make it readable for techexplorer plugin.
 * This function should not be accessed directly but should be accesse through the text_filter function
 * @param string $text The text to parse
 * @return string The text after parsing.
 * @author Patrick Cool <patrick.cool@UGent.be>
 * @version June 2004
*/
function _text_parse_tex($textext) {
	//$textext = str_replace(array ("[tex]", "[/tex]"), array ('[*****]', '[/*****]'), $textext);
	//$textext=stripslashes($texttext);

	$input_array = preg_split("/(\[tex]|\[\/tex])/", $textext, -1, PREG_SPLIT_DELIM_CAPTURE);

	foreach ($input_array as $key => $value) {
		if ($key > 0 && $input_array[$key - 1] == '[tex]' AND $input_array[$key + 1] == '[/tex]') {
			$input_array[$key] = latex_gif_renderer($value);
			unset($input_array[$key - 1]);
			unset($input_array[$key + 1]);
			//echo 'LaTeX: <embed type="application/x-techexplorer" texdata="'.stripslashes($value).'" autosize="true" pluginspage="http://www.integretechpub.com/techexplorer/"><br />';
		}
	}

	$output=implode('',$input_array);
	return $output;
}

/**
 * Applies parsing for tex commandos that are seperated by [tex]
 * [/tex] to make it readable for techexplorer plugin.
 * This function should not be accessed directly but should be accesse through the text_filter function
 * @param string $text The text to parse
 * @return string The text after parsing.
 * @author Patrick Cool <patrick.cool@UGent.be>
 * @version June 2004
*/
function _text_parse_texexplorer($textext) {
	if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
		$textext = str_replace(array("[texexplorer]", "[/texexplorer]"), array("<object classid=\"clsid:5AFAB315-AD87-11D3-98BB-002035EFB1A4\"><param name=\"autosize\" value=\"true\" /><param name=\"DataType\" value=\"0\" /><param name=\"Data\" value=\"", "\" /></object>"), $textext);
	} else {
		$textext = str_replace(array("[texexplorer]", "[/texexplorer]"), array("<embed type=\"application/x-techexplorer\" texdata=\"", "\" autosize=\"true\" pluginspage=\"http://www.integretechpub.com/techexplorer/\">"), $textext);
	}
	return $textext;

}

/**
* This function should not be accessed directly but should be accesse through the text_filter function
* @author 	Patrick Cool <patrick.cool@UGent.be>
*/
function _text_parse_glossary($input) {
	return $input;
}

/**
* @desc this function makes a valid link to a different tool
*		This function should not be accessed directly but should be accesse through the text_filter function
* @author Patrick Cool <patrick.cool@UGent.be>
*/
function _text_parse_tool($input) {
	// an array with all the valid tools
	$tools[] = array(TOOL_ANNOUNCEMENT, 'announcements/announcements.php');
	$tools[] = array(TOOL_CALENDAR_EVENT, 'calendar/agenda.php');

	// check if the name between the [tool] [/tool] tags is a valid one
}

/**
* render LaTeX code into a gif or retrieve a cached version of the gif
* @author Patrick Cool <patrick.cool@UGent.be> Ghent University
*/
function latex_gif_renderer($latex_code) {
	global $_course;

	// setting the paths and filenames
	$mimetex_path = api_get_path(LIBRARY_PATH).'mimetex/';
	$temp_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/temp/';
	$latex_filename = md5($latex_code).'.gif';

	if (!file_exists($temp_path.$latex_filename) OR isset($_GET['render'])) {
		if ((PHP_OS == "WINNT") || (PHP_OS == "WIN32") || (PHP_OS == "Windows")) {
			$mimetex_command = $mimetex_path.'mimetex.exe -e "'.$temp_path.md5($latex_code).'.gif" '.escapeshellarg($latex_code).'';
		} else {
			$mimetex_command = $mimetex_path.'mimetex.linux -e "'.$temp_path.md5($latex_code).'.gif" '.escapeshellarg($latex_code);
		}
		exec($mimetex_command);
		//echo 'volgende shell commando werd uitgevoerd:<br /><pre>'.$mimetex_command.'</pre><hr>';
	}

	$return  = "<a href=\"\" onclick=\"javascript: newWindow=window.open('".api_get_path(WEB_CODE_PATH)."inc/latex.php?code=".urlencode($latex_code)."&amp;filename=$latex_filename','latexCode','toolbar=no,location=no,scrollbars=yes,resizable=yes,status=yes,width=375,height=250,left=200,top=100');\">";
	$return .= '<img src="'.api_get_path(WEB_COURSE_PATH).$_course['path'].'/temp/'.$latex_filename.'" alt="'.$latex_code.'" border="0" /></a>';
	return $return;
}

/**
 * This functions cuts a paragraph
 * i.e cut('Merry Xmas from Lima',13) = "Merry Xmas fr..."
 * @param string the text to "cut"
 * @param int count of chars
 * @param bool	Whether to embed in a <span title="...">...</span>
 * @return string
 * */
function cut($text, $maxchar, $embed = false) {
	if (api_strlen($text) > $maxchar) {
		if ($embed) {
			return '<span title="'.$text.'">'.api_substr($text, 0, $maxchar).'...</span>';
		}
		return api_substr($text, 0, $maxchar).'...'	;
	}
	return $text;
}

/**
 * Show a number as only integers if no decimals, but will show 2 decimals if exist.
 *
 * @param mixed number to convert
 * @param int  decimal points 0=never, 1=if needed, 2=always
 * @return mixed an integer or a float depends on the parameter
 */
function float_format($number, $flag = 1) {
	if (is_numeric($number)) { // a number
		if (!$number) { // zero
			$result = ($flag == 2 ? '0.00' : '0'); // output zero
		} else { // value
			if (floor($number) == $number) { // whole number
				$result = number_format($number, ($flag == 2 ? 2 : 0)); // format
			} else { // cents
				$result = number_format(round($number, 2), ($flag == 0 ? 0 : 2)); // format
			} // integer or decimal
		} // value
		return $result;
	}
}

/**
 * Function to obtain last week timestamps
 * @return array  times for every day inside week
 */
function get_last_week() {
    $week = date('W');
    $year = date('Y');

    $lastweek = $week - 1;
    if ($lastweek == 0) {
        $week = 52;
        $year--;
    }

    $lastweek = sprintf("%02d", $lastweek);
    for ($i=1; $i<=7; $i++) {
        $arrdays[] = strtotime("$year"."W$lastweek"."$i");
    }
    return $arrdays;
}
