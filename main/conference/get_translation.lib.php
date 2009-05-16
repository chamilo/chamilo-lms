<?php
/* See license terms in /dokeos_license.txt */
/**
 * Library for language translation from Dokeos language files to XML for videoconference
 * @uses main_api.lib.php for api_get_path() 
 */
/**
 * This function reads a Dokeos language file and transforms it into XML, 
 * then returns the XML string to the caller. 
 */
function get_language_file_as_xml($language='english')
{
	$path = api_get_path(SYS_LANG_PATH).$language.'/';
	if(!is_dir($path) or !is_readable($path))
	{ 
		if($language != 'english')
		{
			return get_language_file_as_xml('english');
		}
		else
		{
			return '';
		}
	}
	//error_log('Analysing path '.$path);
	$file = $path.'videoconf.inc.php';
	if(!is_file($file) or !is_readable($file))
	{
		if($language != 'english')
		{
			return get_language_file_as_xml('english');
		}
		else
		{
			return '';
		}
	}

	/*
	$convert = true;
	if(substr($language,-7,7) == 'unicode')
	{//do not convert if the language ends with 'unicode', which means it's in UTF-8
		$convert=false;
	}
	$list = file($file);
	$xml = '';
	foreach ( $list as $line )
	{
		if(substr($line,0,1)=='$')
		{
			$items = array();
			$match = preg_match('/^\$([^\s]*)\s*=\s*"(.*)";$/',$line,$items);
			if($match)
			{
				//todo: The following conversion should only happen for old language files (encoded in ISO-8859-1).
				if($convert)
				{
					$string = mb_convert_encoding($items[2],'UTF-8','ISO-8859-1');
				}
				else
				{
					$string = $items[2];
				}
				$xml .= '<labelfield><labelid>'.$items[1].'</labelid><labelvalue>'.stripslashes($string).'</labelvalue></labelfield>'."\n";
			}
		}
	}
	*/

	//---------
	$non_utf8_encoding = api_get_non_utf8_encoding($language);
	$list = file($file);
	$xml = '';
	foreach ( $list as $line ) {
		if(substr($line, 0, 1)=='$') {
			$items = array();
			$match = preg_match('/^\$([^\s]*)\s*=\s*"(.*)";$/', $line, $items);
			if($match) {
				$string = $items[2];
				if (!api_is_valid_utf8($string)) {
					$string = api_html_entity_decode(api_utf8_encode($string, $non_utf8_encoding), ENT_QUOTES, 'UTF-8');
				}
				$xml .= '<labelfield><labelid>'.$items[1].'</labelid><labelvalue>'.stripslashes($string).'</labelvalue></labelfield>'."\n";
			}
		}
	}
	//---------

	if(empty($xml) && $language!='english')
	{
		return get_language_file_as_xml('english');
	}
	return $xml;
}
?>
