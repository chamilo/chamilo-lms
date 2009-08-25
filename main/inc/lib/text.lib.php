<?php // $Id: text.lib.php 20709 2009-05-15 19:12:37Z ivantcholakov $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	This is the text library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
==============================================================================
*/

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

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

function make_clickable($string)
{
	if(!stristr($string,' src=') && !stristr($string,' href='))
	{
		$string=eregi_replace("(https?|ftp)://([a-z0-9#?/&=._+:~%-]+)","<a href=\"\\1://\\2\" target=\"_blank\">\\1://\\2</a>",$string);
		$string=eregi_replace("([a-z0-9_.-]+@[a-z0-9.-]+)","<a href=\"mailto:\\1\">\\1</a>",$string);
	}

	return $string;
}

/**
 * formats the date according to the locale settings
 *
 * @author  Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author  Christophe Gesch� <gesche@ipm.ucl.ac.be>
 *          originally inspired from from PhpMyAdmin
 * @param  string  $date_format date pattern
 * @param  integer $time_stamp, default is NOW.
 * @return the formatted date
 */

function format_locale_date($date_format, $time_stamp = -1, $language = null)
{
	/*
	static $initialized = false;
	static $days_short, $days_long, $months_short, $months_long;

	if (!$initialized)
	{
		// Defining the shorts for the days
		$days_short = array (get_lang("SundayShort"), get_lang("MondayShort"), get_lang("TuesdayShort"), get_lang("WednesdayShort"), get_lang("ThursdayShort"), get_lang("FridayShort"), get_lang("SaturdayShort"));
		// Defining the days of the week to allow translation of the days
		$days_long = array (get_lang("SundayLong"), get_lang("MondayLong"), get_lang("TuesdayLong"), get_lang("WednesdayLong"), get_lang("ThursdayLong"), get_lang("FridayLong"), get_lang("SaturdayLong"));
		// Defining the shorts for the months
		$months_short = array (get_lang("JanuaryShort"), get_lang("FebruaryShort"), get_lang("MarchShort"), get_lang("AprilShort"), get_lang("MayShort"), get_lang("JuneShort"), get_lang("JulyShort"), get_lang("AugustShort"), get_lang("SeptemberShort"), get_lang("OctoberShort"), get_lang("NovemberShort"), get_lang("DecemberShort"));
		// Defining the months of the year to allow translation of the months
		$months_long = array (get_lang("JanuaryLong"), get_lang("FebruaryLong"), get_lang("MarchLong"), get_lang("AprilLong"), get_lang("MayLong"), get_lang("JuneLong"), get_lang("JulyLong"), get_lang("AugustLong"), get_lang("SeptemberLong"), get_lang("OctoberLong"), get_lang("NovemberLong"), get_lang("DecemberLong"));

		$initialized = true;
	}

	if ($time_stamp == -1) $time_stamp = time();

	// with the ereg  we  replace %aAbB of date format
	//(they can be done by the system when  locale date aren't aivailable

	$date = ereg_replace('%[A]', $days_long[(int)strftime('%w', $time_stamp)], $date_format);
	$date = ereg_replace('%[a]', $days_short[(int)strftime('%w', $time_stamp)], $date);
	$date = ereg_replace('%[B]', $months_long[(int)strftime('%m', $time_stamp)-1], $date);
	$date = ereg_replace('%[b]', $months_short[(int)strftime('%m', $time_stamp)-1], $date);

	return strftime($date, $time_stamp);
	*/
	return api_format_date($date_format, $time_stamp, $language);
} // end function format_locale_date


/**
* @desc this function does some parsing on the text that gets inputted. This parsing can be of any kind
* 		LaTeX notation, Word Censoring, Glossary Terminology (extension will available soon), Musical Notations, ...
*		The inspiration for this filter function came from Moodle an phpBB who both use a similar approach 
* @param $input string. some text
* @return $output string. some text that contains the parsed elements.
* @example [tex]\sqrt(2)[/tex]
* @author Patrick Cool <patrick.cool@UGent.be>
* @version March 2OO6
*/
function text_filter($input, $filter=true)
{

	//$input=stripslashes($input);
	
	if ($filter==true)
	{
		// ***  parse [tex]...[/tex] tags  *** //
		// which will return techexplorer or image html depending on the capabilities of the
		// browser of the user (using some javascript that checks if the browser has the TechExplorer plugin installed or not)
		$input=_text_parse_tex($input);
	
		
		// *** parse [teximage]...[/teximage] tags *** //
		// these force the gif rendering of LaTeX using the mimetex gif renderer
		//$input=_text_parse_tex_image($input);
		
		
		// *** parse [texexplorer]...[/texexplorer] tags  *** //
		// these force the texeplorer LaTeX notation
		$input=_text_parse_texexplorer($input);
		
		// *** Censor Words *** //
		// censor words. This function removes certain words by [censored]
		// this can be usefull when the campus is open to the world. 
		// $input=text_censor_words($input);
		
		// *** parse [?]...[/?] tags *** //
		// for the glossary tool (see http://www.dokeos.com/extensions)
		$input=_text_parse_glossary($input);
	
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
 * Apply parsing to content to parse tex commandos that are seperated by [tex]
 * [/tex] to make it readable for techexplorer plugin.
 * This function should not be accessed directly but should be accesse through the text_filter function
 * @param string $text The text to parse
 * @return string The text after parsing.
 * @author Patrick Cool <patrick.cool@UGent.be>
 * @version June 2004
*/
function _text_parse_tex($textext)
{
	//$textext = str_replace(array ("[tex]", "[/tex]"), array ('[*****]', '[/*****]'), $textext);
	//$textext=stripslashes($texttext);
	
	$input_array=preg_split("/(\[tex]|\[\/tex])/",$textext,-1, PREG_SPLIT_DELIM_CAPTURE);
	
	
	foreach ($input_array as $key=>$value)
	{
		if ($key>0 && $input_array[$key-1]=='[tex]' AND $input_array[$key+1]=='[/tex]')
		{
			$input_array[$key]=latex_gif_renderer($value);
			unset($input_array[$key-1]);
			unset($input_array[$key+1]);
			//echo 'LaTeX: <embed type="application/x-techexplorer" texdata="'.stripslashes($value).'" autosize="true" pluginspage="http://www.integretechpub.com/techexplorer/"><br />';
		}
	}
	
	$output=implode('',$input_array);
	return $output;
}
/**
 * Apply parsing to content to parse tex commandos that are seperated by [tex]
 * [/tex] to make it readable for techexplorer plugin.
 * This function should not be accessed directly but should be accesse through the text_filter function
 * @param string $text The text to parse
 * @return string The text after parsing.
 * @author Patrick Cool <patrick.cool@UGent.be>
 * @version June 2004
*/
function _text_parse_texexplorer($textext)
{
	if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
	{
		$textext = str_replace(array ("[texexplorer]", "[/texexplorer]"), array ("<object classid=\"clsid:5AFAB315-AD87-11D3-98BB-002035EFB1A4\"><param name=\"autosize\" value=\"true\" /><param name=\"DataType\" value=\"0\" /><param name=\"Data\" value=\"", "\" /></object>"), $textext);
	}
	else
	{
		$textext = str_replace(array ("[texexplorer]", "[/texexplorer]"), array ("<embed type=\"application/x-techexplorer\" texdata=\"", "\" autosize=\"true\" pluginspage=\"http://www.integretechpub.com/techexplorer/\">"), $textext);
	}
	return $textext;

}
/**
* This function should not be accessed directly but should be accesse through the text_filter function
* @author 	Patrick Cool <patrick.cool@UGent.be>
*/
function _text_parse_glossary($input)
{
	return $input;
}

/**
* @desc this function makes a valid link to a different tool
*		This function should not be accessed directly but should be accesse through the text_filter function
* @author Patrick Cool <patrick.cool@UGent.be>
*/
function _text_parse_tool($input)
{
	// an array with all the valid tools
	$tools[]=array(TOOL_ANNOUNCEMENT, 'announcements/announcements.php');
	$tools[]=array(TOOL_CALENDAR_EVENT, 'calendar/agenda.php');
	
	// check if the name between the [tool] [/tool] tags is a valid one
}




/**
* render LaTeX code into a gif or retrieve a cached version of the gif
* @author Patrick Cool <patrick.cool@UGent.be> Ghent University
*/
function latex_gif_renderer($latex_code)
{
	global $_course; 
	
	// setting the paths and filenames
	$mimetex_path=api_get_path(LIBRARY_PATH).'mimetex/';
	$temp_path=api_get_path(SYS_COURSE_PATH).$_course['path'].'/temp/';
	$latex_filename=md5($latex_code).'.gif';
	
	if(!file_exists($temp_path.$latex_filename) OR isset($_GET['render']))
	{
		if ((PHP_OS == "WINNT") || (PHP_OS == "WIN32") || (PHP_OS == "Windows"))
		{
			$mimetex_command=$mimetex_path.'mimetex.exe -e "'.$temp_path.md5($latex_code).'.gif" '.escapeshellarg($latex_code).'';
		}
		else 
		{
			$mimetex_command=$mimetex_path.'mimetex.linux -e "'.$temp_path.md5($latex_code).'.gif" '.escapeshellarg($latex_code);
		}
		exec($mimetex_command);	
		//echo 'volgende shell commando werd uitgevoerd:<br /><pre>'.$mimetex_command.'</pre><hr>'; 
	}
	
	$return  = "<a href=\"\" onclick=\"newWindow=window.open('".api_get_path(WEB_CODE_PATH)."inc/latex.php?code=".urlencode($latex_code)."&amp;filename=$latex_filename','latexCode','toolbar=no,location=no,scrollbars=yes,resizable=yes,status=yes,width=375,height=250,left=200,top=100');\">";
	$return .= '<img src="'.api_get_path(WEB_COURSE_PATH).$_course['path'].'/temp/'.$latex_filename.'" alt="'.$latex_code.'" border="0" /></a>';
	return $return; 
}


/**
 * Returns the difference between the current date (date(now)) with the parameter $date in a string format like "2 days, 1 hour" 
 * Example: $date="2008-03-07 15:44:08"; 
 * 			date_to_str($date) it will return 3 days, 20 hours 		
 *  
 * @param  string The string has to be the result of a date function in this format -> date("Y-m-d H:i:s",time());
 * @return string The difference between the current date and the parameter in a literal way "3 days, 2 hour" * 
 * @author Julio Montoya 
 */

function date_to_str_ago($date)
{
	static $initialized = false;
	static $today, $yesterday;
	static $min_decade, $min_year, $min_month, $min_week, $min_day, $min_hour, $min_minute;
	static $min_decades, $min_years, $min_months, $min_weeks, $min_days, $min_hours, $min_minutes;
	static $sec_time_time, $sec_time_sing, $sec_time_plu;

	if (!$initialized) {
		$today = api_ucfirst(get_lang('Today'));
		$yesterday = api_ucfirst(get_lang('Yesterday'));

		$min_decade=get_lang('MinDecade');
		$min_year=get_lang('MinYear');
		$min_month=get_lang('MinMonth');
		$min_week=get_lang('MinWeek');
		$min_day=get_lang('MinDay');
		$min_hour=get_lang('MinHour');
		$min_minute=get_lang('MinMinute');
	
		$min_decades=get_lang('MinDecades');
		$min_years=get_lang('MinYears');
		$min_months=get_lang('MinMonths');
		$min_weeks=get_lang('MinWeeks');
		$min_days=get_lang('MinDays');
		$min_hours=get_lang('MinHours');
		$min_minutes=get_lang('MinMinutes');
	
		// original 1
		//$sec_time=array("century"=>3.1556926*pow(10,9),"decade"=>315569260,"year"=>31556926,"month"=>2629743.83,"week"=>604800,"day"=>86400,"hour"=>3600,"minute"=>60,"second"=>1);
		//$sec_time=array(get_lang('MinDecade')=>315569260,get_lang('MinYear')=>31556926,get_lang('MinMonth')=>2629743.83,get_lang('MinWeek')=>604800,get_lang('MinDay')=>86400,get_lang('MinHour')=>3600,get_lang('MinMinute')=>60);
		$sec_time_time=array(315569260,31556926,2629743.83,604800,86400,3600,60);		
		$sec_time_sing=array($min_decade,$min_year,$min_month,$min_week,$min_day,$min_hour,$min_minute);
		$sec_time_plu =array($min_decades,$min_years,$min_months,$min_weeks,$min_days,$min_hours,$min_minutes);
		$initialized = true;
	}

	$dst_date=strtotime($date);
	//for not call date several times
	$date_array=date("s/i/G/j/n/Y",$dst_date);
	$date_split=explode("/",$date_array);

	$dst_s=$date_split[0];
	$dst_m=$date_split[1];
	$dst_h=$date_split[2];
	$dst_day=$date_split[3];
	$dst_mth=$date_split[4];
	$dst_yr=$date_split[5];

	$dst_date = mktime($dst_h,$dst_m,$dst_s,$dst_mth,$dst_day,$dst_yr);
	$time=$offset = time()-$dst_date; //seconds between current days and today

	//------------ Here start the functions sec_to_str
	$act_day=date('d');
	$act_mth=date('n');
	$act_yr = date('Y');
	
	if ($dst_day==$act_day && $dst_mth==$act_mth && $dst_yr == $act_yr ) {
		return $today;
	}

	if ($dst_day==$act_day-1 && $dst_mth==$act_mth && $dst_yr == $act_yr ) {
		return $yesterday;
	}	
				
	$str_result=array();	
	$time_result=array();
	$key_result=array();
	
	$str='';
	$i=0;		
	for ($i=0;$i<count($sec_time_time);$i++) {
		$seconds=$sec_time_time[$i];			
		if($seconds > $time) {
			continue;
		}					
		$current_value=intval($time/$seconds);
					
		if ($current_value != 1) {			
			$date_str=	$sec_time_plu[$i];
		} else {
			$date_str=	$sec_time_sing[$i];
	
		}			
		$key_result[]=$sec_time_sing[$i];
					
		$str_result[]=$current_value.' '.$date_str;		
		$time_result[]=	$current_value;				
		$str.=$current_value.$date_str;				
		$time%=$seconds;			
	}
			
	if ($key_result[0]== $min_day && $key_result[1]== $min_minute) {
		$key_result[1]=' 0 '.$min_hours;
		$str_result[0]=$time_result[0].' '.$key_result[0];
		$str_result[1]=$key_result[1];		
	}
	
	if ($key_result[0]== $min_year && ($key_result[1]== $min_day || $key_result[1]== $min_week)) {
		$key_result[1]=' 0 '.$min_months;
		$str_result[0]=$time_result[0].' '.$key_result[0];
		$str_result[1]=$key_result[1];		
	}
	
	if (!empty($str_result[1])) {
		$str=$str_result[0].', '.$str_result[1];
	} else {
		$str=$str_result[0];
	}	
	return $str;	
}
/**
 * This functions cuts a paragraph 
 * i.e cut('Merry Xmas from Lima',13) = "Merry Xmas fr..."
 * @param string the text to "cut" 
 * @param int count of chars
 * @param bool	Whether to embed in a <span title="...">...</span>
 * @return string 
 * */
function cut($text,$maxchar,$embed=false)
{
	if (api_strlen($text) > $maxchar) {
		if ($embed==true) {
			return '<span title="'.$text.'">'.api_substr($text, 0, $maxchar).'...</span>';
		}
		return api_substr($text, 0, $maxchar).'...'	;
	} else {
		return $text;
	}	
}
/**
 * Show a number as only integers if no decimals, but will show 2 decimals if exist.
 * 
 * @param mixed number to convert 
 * @param int  decimal points 0=never, 1=if needed, 2=always
 * @return mixed an integer or a float depends on the parameter
 */
function float_format($number, $flag = 1) 
{ 
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

?>
