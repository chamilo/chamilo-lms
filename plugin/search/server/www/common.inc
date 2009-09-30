<?php
/**
 * Library for the common formatting functions
 */
/**
 * Exit, dropping the temporary tables just before that
 * @param	integer	Whether to print an error message (passed to drop_temp_table())
 */
function exit_local($print_err = 1) {
    drop_temp_table($print_err);
    exit;
}

/**
 * Prints the error and the template bottom, then exit
 * @param	string	The error message
 */
function print_error_local($str){
    global $error;
    
    $error=$str;

    print_template('error');
    print_template('bottom');
    
    exit;
}

/**
 * Gets the number of documents from the udm object
 * @return	mixed	An integer or "Unknown" if not found
 */
function get_doc_count() {
    global $udm_agent;

    if (udm_api_version() >= 30111) {
		return(udm_get_doc_count($udm_agent));
    } else {
    	return "Unknown";
    } 
} 


/**
 * Format last modified date
 * @param	integer	UNIX Timestamp?
 * @return	string	Formatted date
 */
function format_lastmod($lastmod) {
	$temp=$lastmod;
	if (!$temp) $temp = 'undefined';
	elseif (udm_api_version() < 30204) $temp = strftime('%a, %d %b %Y %H:%M:%S %Z',$temp);

	return $temp;
}

/**
 * Format date in seconds from a source in s,M,h,d,m,y
 * @param	string	Date
 * @return	integer	Date in seconds
 */
function format_dp($dp) {
	$result=0;

	while ($dp != '') {
		$param = array();
		if (preg_match('/^([\-\+]?\d+)([sMhdmy]?)/',$dp,$param)) {			
			switch ($param[2]) {
				case 's':  $multiplier=1; break;
                case 'M':  $multiplier=60; break;
                case 'h':  $multiplier=3600; break;
                case 'd':  $multiplier=3600*24; break;
                case 'm':  $multiplier=3600*24*31; break;
                case 'y':  $multiplier=3600*24*365; break;
				default: $multiplier=1;
			}

			$result += $param[1]*$multiplier;
                        $dp=preg_replace("/^[\-\+]?\d+$param[2]/",'',$dp);
		} else {
			return 0;
		}
	}

	return $result;
}

/**
 * Formats the date using the user's settings as defined in search.html
 * @param	string	Date
 * @return	string	Formatted date
 */
function format_userdate($date) {
	$result=0;

	$param = array();
	if (preg_match('/^(\d+)\/(\d+)\/(\d+)$/',$date,$param)) {
		$day=$param[1];
		$mon=$param[2];
		$year=$param[3];

		$result=mktime(0,0,0,$mon,$day,$year);
	}

	return $result;
}

/**
 * Parses the given text for the words searched and highlight the words, the returns the string
 * @param	string	Resulting document excerpt
 * @return	string	Same string but with highlight tags (as defined in search.html)
 */
function ParseDocText($text){
    global $all_words;
    global $hlbeg, $hlend;
       
    $str=$text;

    if (udm_api_version() < 30200) {
    	for ($i=0; $i<count($all_words); $i+=1) {
	    $word=$all_words[$i];
	    $str = preg_replace("/([\s\t\r\n\~\!\@\#\$\%\^\&\*\(\)\-\_\=\+\\\|\{\}\[\]\;\:\'\"\<\>\?\/\,\.]+)($word)/i","\\1$hlbeg\\2$hlend",$str);
	    $str = preg_replace("/^($word)/i","$hlbeg\\1$hlend",$str);
    	}
    } else {
    	$str = str_replace("\2",$hlbeg,$str);
    	$str = str_replace("\3",$hlend,$str);
	while (substr_count($str,$hlbeg) > substr_count($str,$hlend)) {
	    $str.=$hlend;
	}
    }

    return $str;
}

/**
 * Parses the global variable $QUERY_STRING
 */
function ParseQString() {
    global $QUERY_STRING;
    global $ul, $ul_arr;
    global $tag, $tag_arr;
    global $cat, $cat_arr;
    global $lang, $lang_arr;
    global $type, $type_arr;
	
    $pairs = explode("&", $QUERY_STRING);
    $ul_arr = array();
    $tag_arr = array();
    $cat_arr = array();
    $lang_arr = array();
    $type_arr = array();
	
    for($i=0; $i<count($pairs); $i+=1) {
	$pairs[$i]=str_replace('+',' ',$pairs[$i]);
	list($name, $value) = explode("=",$pairs[$i]);
	if ($name=='ul') {
	    $ul_arr[]=urldecode($value);
	    if (!count($ul_arr)) $ul=urldecode($value);
	} elseif ($name=='tag') {
	    $tag_arr[]=urldecode($value);
	    if (!count($tag_arr)) $tag=urldecode($value);
	} elseif ($name=='cat') {
	    $cat_arr[]=urldecode($value);
	    if (!count($cat_arr)) $cat=urldecode($value);
	} elseif ($name=='lang') {
	    $lang_arr[]=urldecode($value);
	    if (!count($lang_arr)) $lang=urldecode($value);
	} elseif ($name=='type') {
	    $type_arr[]=urldecode($value);
	    if (!count($type_arr)) $type=urldecode($value);
	} else continue;
    }
}

?>
