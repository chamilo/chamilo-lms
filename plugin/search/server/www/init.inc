<?php
/**
 * Initialisation code + library of functions to process to the initialisation in
 * a varied set of ways.
 * This script is a modified version that can only be used with the php extension >= 3.2.32
 * and PHP version 4.2 or higher
 * @package chamilo.plugin.search
 */
/**
 * Faking the register_globals code - very bad thing - should escape all vars
 */
if (isset ($HTTP_GET_VARS)) {
	while (list ($var, $val) = each($HTTP_GET_VARS)) {
		$$var = $val;
	}
}
if (isset ($HTTP_POST_VARS)) {
	while (list ($var, $val) = each($HTTP_POST_VARS)) {
		$$var = $val;
	}
}
if (isset ($HTTP_COOKIE_VARS)) {
	while (list ($var, $val) = each($HTTP_COOKIE_VARS)) {
		$$var = $val;
	}
}
if (isset ($HTTP_SERVER_VARS)) {
	while (list ($var, $val) = each($HTTP_SERVER_VARS)) {
		$$var = $val;
	}
}

$self = $PHP_SELF;

/**
 * Setting default values for variables that will be used globally
 */
if ($ps == "")
	$ps = 20;
if ($np == "")
	$np = 0;
if ($o == "")
	$o = 0;
if ($resultslimit <= 0)
	$resultslimit = 0;

if ($excerptsize == "")
	$excerptsize = 256;
if ($excerptpadding == "")
	$excerptsize = 40;

if (($dt != 'back') && ($dt != 'er') && ($dt != 'range'))
	$dt = '';
if ($dp == "")
	$dp = 0;
if (($dx != 0) && ($dx != -1) && ($dx != 1))
	$dx = 0;
if ($dy < 1970)
	$dy = 1970;
if (($dm < 0) || ($dm > 11))
	$dm = 0;
if (($dd <= 0) || ($dd > 31))
	$dd = "01";

$db = urldecode($db);
$de = urldecode($de);
$type = urldecode($type);

$url = urldecode($url);

if ($db == "")
	$db = '01/01/1970';
if ($de == "")
	$de = '31/12/2020';

if ($hlbeg == "")
	$hlbeg = '<b>';
if ($hlend == "")
	$hlend = '</b>';

if ($storedocurl == "")
	$storedocurl = '/cgi-bin/storedoc.cgi';

if (isset ($q)) {
	$q = urldecode($q);
	$have_query_flag = 1;
} else {
	$have_query_flag = 0;
}

$query_orig = $q;

if (isset ($CHARSET_SAVED_QUERY_STRING)) {
	$q_local = urldecode($CHARSET_SAVED_QUERY_STRING);
	if (preg_match('/q=([^&]*)\&/', $q_local, $param)) {
		$q_local = urlencode($param[1]);
	}
	elseif (preg_match('/q=(.*)$/', $q_local, $param)) {
		$q_local = urlencode($param[1]);
	} else {
		$q_local = urlencode($q);
	}
	$q_string = $CHARSET_SAVED_QUERY_STRING;
} else {
	$q_local = urlencode(stripslashes($q));
	$q_string = $QUERY_STRING;
}

$db_local = urlencode($db);
$de_local = urlencode($de);
$type_local = urlencode($type);

$url_local = urlencode($url);

if (($MAX_NP > 0) && ($np > $MAX_NP))
	$np = $MAX_NP;
if (($MAX_PS > 0) && ($ps > $MAX_PS))
	$ps = $MAX_PS;

/**
 * Initialisation function. Gets the global variables defined all over and prepares
 * the udm agent accordingly.
 * 
 */
function init() {
	global $q, $q_string, $query_orig, $have_query_flag, $have_spell_flag, $auto_wild;
	global $tag, $tag_arr, $cat, $cat_arr, $np, $ps, $lang, $lang_arr, $wm, $wf, $ul, $m;
	global $dt, $dp, $dx, $dm, $dd, $dy, $db, $de, $s, $resultslimit;
	global $trackquery, $localcharset, $browsercharset, $stopwordtable_arr, $stopwordfile_arr;
	global $minwordlength, $maxwordlength, $phrase, $cache;
	global $ispelluseprefixes, $crosswords, $ispellmode, $spell_host;
	global $affix_file, $spell_file, $vardir, $datadir, $sp, $sy;
	global $dbaddr, $dbmode, $udm_agent, $hlbeg, $hlend, $detectclones;
	global $phpver, $synonym_arr, $searchd_arr, $ul_arr;
	global $REMOTE_ADDR, $storedaddr, $type_arr, $type, $groupbysite, $site;
	global $excerptsize, $excerptpadding, $dateformat, $suggest;
	global $XMLOutput;

	ParseQString();

	/*
	   if (preg_match("/^(\d+)\.(\d+)\.(\d+)/",phpversion(),$param)) {
		$phpver=$param[1];
	   	if ($param[2] < 9) {
	   		$phpver .= "0$param[2]";
	   	} else {
	   		$phpver .= "$param[2]";
	   	}
	        if ($param[3] < 9) {
	   		$phpver .= "0$param[3]";
	   	} else {
	   		$phpver .= "$param[3]";
	   	}
	   } else {
	   	print "Cannot determine php version: <b>".phpversion()."</b>\n";
	   	exit;
	   }
	*/
	$have_spell_flag = 0;

	$stopwordtable_arr = array ();
	$stopwordfile_arr = array ();
	$synonym_arr = array ();
	$searchd_arr = array ();

	read_template();

	if ($sp == '')
		$sp = 0;
	if ($sy == '')
		$sy = 0;

	$udm_agent = udm_alloc_agent_array($dbaddr);
	udm_set_agent_param($udm_agent, UDM_PARAM_PAGE_SIZE, $ps);
	udm_set_agent_param($udm_agent, UDM_PARAM_PAGE_NUM, $np);

	$trackquery = strtolower($trackquery);
	if ($trackquery == 'yes') {
		udm_set_agent_param($udm_agent, UDM_PARAM_TRACK_MODE, UDM_ENABLED);
	} else {
		udm_set_agent_param($udm_agent, UDM_PARAM_TRACK_MODE, UDM_DISABLED);
	}

	$cache = strtolower($cache);
	if ($cache == 'yes') {
		udm_set_agent_param($udm_agent, UDM_PARAM_CACHE_MODE, UDM_ENABLED);
	} else {
		udm_set_agent_param($udm_agent, UDM_PARAM_CACHE_MODE, UDM_DISABLED);
	}

	$ispelluseprefixes = strtolower($ispelluseprefixes);
	if ($ispelluseprefixes == 'yes') {
		udm_set_agent_param($udm_agent, UDM_PARAM_ISPELL_PREFIXES, UDM_ENABLED);
	} else {
		udm_set_agent_param($udm_agent, UDM_PARAM_ISPELL_PREFIXES, UDM_DISABLED);
	}

	$crosswords = strtolower($crosswords);
	if ($crosswords == 'yes') {
		udm_set_agent_param($udm_agent, UDM_PARAM_CROSS_WORDS, UDM_ENABLED);
	} else {
		udm_set_agent_param($udm_agent, UDM_PARAM_CROSS_WORDS, UDM_DISABLED);
	}

	if ($localcharset != '') {
		udm_set_agent_param($udm_agent, UDM_PARAM_CHARSET, $localcharset);
		if (!udm_check_charset($udm_agent, $localcharset)) {
			print_template('top');
			flush();
			print_error_local("Incorrect localcharset $localcharset");
		}
	}

	if ($localcharset == '')
		udm_set_agent_param($udm_agent, UDM_PARAM_CHARSET, 'utf-8');
	if ($browsercharset != '') {
		if (!udm_check_charset($udm_agent, $browsercharset)) {
			print_template('top');
			flush();
			print_error_local("Incorrect browsercharset $browsercharset");
		}
		udm_set_agent_param($udm_agent, UDM_PARAM_BROWSER_CHARSET, $browsercharset);
		if($XMLOutput != 1){
			header("Content-Type: text/html; charset=$browsercharset");
		}else{
			header("Content-Type: text/xml");
			echo "<?xml version='1.0' encoding=\"$browsercharset\" ?>\n";
		}
	} else {
		udm_set_agent_param($udm_agent, UDM_PARAM_BROWSER_CHARSET, 'utf-8');
		if($XMLOutput != 1){
			header("Content-Type: text/html; charset=utf-8");
		}else{
			header("Content-Type: text/xml");
			echo "<?xml version='1.0' encoding=\"utf-8\" ?>\n";
		}
	}
	if ($hlbeg == '')
		$hlbeg = '<font color="000088"><b>';
	udm_set_agent_param($udm_agent, UDM_PARAM_HLBEG, $hlbeg);

	if ($hlend == '')
		$hlend = '</b></font>';
	udm_set_agent_param($udm_agent, UDM_PARAM_HLEND, $hlend);

	for ($i = 0; $i < count($stopwordfile_arr); $i += 1) {
		if ($stopwordfile_arr[$i] != '') {
			udm_set_agent_param($udm_agent, UDM_PARAM_STOPFILE, $stopwordfile_arr[$i]);
		}
	}

	if ($sy) {
		for ($i = 0; $i < count($synonym_arr); $i += 1) {
			if ($synonym_arr[$i] != '') {
				udm_set_agent_param($udm_agent, UDM_PARAM_SYNONYM, $synonym_arr[$i]);
			}
		}
	}

	udm_set_agent_param($udm_agent, UDM_PARAM_QSTRING, $q_string);
	udm_set_agent_param($udm_agent, UDM_PARAM_REMOTE_ADDR, $REMOTE_ADDR);

	if ($have_query_flag)
		udm_set_agent_param($udm_agent, UDM_PARAM_QUERY, $query_orig);
	if ($storedaddr != '')
		udm_set_agent_param($udm_agent, UDM_PARAM_STORED, $storedaddr);

	$groupbysite = strtolower($groupbysite);
	if ($groupbysite == 'yes') {
		udm_set_agent_param($udm_agent, UDM_PARAM_GROUPBYSITE, UDM_ENABLED);
	} else {
		udm_set_agent_param($udm_agent, UDM_PARAM_GROUPBYSITE, UDM_DISABLED);
	}
	if ($site)
		udm_set_agent_param($udm_agent, UDM_PARAM_SITEID, $site);
	$detectclones = strtolower($detectclones);
	if ($detectclones == 'yes') {
		udm_set_agent_param($udm_agent, UDM_PARAM_DETECT_CLONES, UDM_ENABLED);
	} else {
		udm_set_agent_param($udm_agent, UDM_PARAM_DETECT_CLONES, UDM_DISABLED);
	}

	if ($m == 'any') {
		udm_set_agent_param($udm_agent, UDM_PARAM_SEARCH_MODE, UDM_MODE_ANY);
	}
	elseif ($m == 'all') {
		udm_set_agent_param($udm_agent, UDM_PARAM_SEARCH_MODE, UDM_MODE_ALL);
	}
	elseif ($m == 'bool') {
		udm_set_agent_param($udm_agent, UDM_PARAM_SEARCH_MODE, UDM_MODE_BOOL);
	}
	elseif ($m == 'phrase') {
		udm_set_agent_param($udm_agent, UDM_PARAM_SEARCH_MODE, UDM_MODE_PHRASE);
	} else {
		udm_set_agent_param($udm_agent, UDM_PARAM_SEARCH_MODE, UDM_MODE_ALL);
	}

	if ($wm == 'wrd') {
		udm_set_agent_param($udm_agent, UDM_PARAM_WORD_MATCH, UDM_MATCH_WORD);
	}
	elseif ($wm == 'beg') {
		udm_set_agent_param($udm_agent, UDM_PARAM_WORD_MATCH, UDM_MATCH_BEGIN);
	}
	elseif ($wm == 'end') {
		udm_set_agent_param($udm_agent, UDM_PARAM_WORD_MATCH, UDM_MATCH_END);
	}
	elseif ($wm == 'sub') {
		udm_set_agent_param($udm_agent, UDM_PARAM_WORD_MATCH, UDM_MATCH_SUBSTR);
	} else {
		udm_set_agent_param($udm_agent, UDM_PARAM_WORD_MATCH, UDM_MATCH_WORD);
	}

	if ($minwordlength > 0) {
		udm_set_agent_param($udm_agent, UDM_PARAM_MIN_WORD_LEN, $minwordlength);
	} else {
		udm_set_agent_param($udm_agent, UDM_PARAM_MIN_WORD_LEN, 1);
	}

	if ($maxwordlength > 0) {
		udm_set_agent_param($udm_agent, UDM_PARAM_MAX_WORD_LEN, $maxwordlength);
	} else {
		udm_set_agent_param($udm_agent, UDM_PARAM_MAX_WORD_LEN, 32);
	}

	if ($vardir != '')
		udm_set_agent_param($udm_agent, UDM_PARAM_VARDIR, $vardir);
	if ($datadir != '')
		udm_set_agent_param($udm_agent, UDM_PARAM_VARDIR, $datadir);

	if ($wf != '') {
		udm_set_agent_param($udm_agent, UDM_PARAM_WEIGHT_FACTOR, $wf);
	}

	for ($i = 0; $i < count($ul_arr); $i += 1) {
		$temp_ul = $ul_arr[$i];
		if ($temp_ul != '') {
			$auto_wild = strtolower($auto_wild);
			if (($auto_wild == 'yes') || ($auto_wild == '')) {
				if ((substr($temp_ul, 0, 7) == 'http://') || (substr($temp_ul, 0, 8) == 'https://') || (substr($temp_ul, 0, 7) == 'news://') || (substr($temp_ul, 0, 6) == 'ftp://')) {
					udm_add_search_limit($udm_agent, UDM_LIMIT_URL, "$temp_ul%");
				} else {
					udm_add_search_limit($udm_agent, UDM_LIMIT_URL, "%$temp_ul%");
				}
			} else {
				udm_add_search_limit($udm_agent, UDM_LIMIT_URL, $temp_ul);
			}
		}
	}

	for ($i = 0; $i < count($tag_arr); $i += 1) {
		$temp_tag = $tag_arr[$i];
		if ($temp_tag != '')
			udm_add_search_limit($udm_agent, UDM_LIMIT_TAG, $temp_tag);
	}

	for ($i = 0; $i < count($type_arr); $i += 1) {
		$temp_type = $type_arr[$i];
		if ($temp_type != '')
			udm_add_search_limit($udm_agent, UDM_LIMIT_TYPE, $temp_type);
	}

	for ($i = 0; $i < count($cat_arr); $i += 1) {
		$temp_cat = $cat_arr[$i];
		if ($temp_cat != '')
			udm_add_search_limit($udm_agent, UDM_LIMIT_CAT, $temp_cat);
	}

	for ($i = 0; $i < count($lang_arr); $i += 1) {
		$temp_lang = $lang_arr[$i];
		if ($temp_lang != '')
			udm_add_search_limit($udm_agent, UDM_LIMIT_LANG, $temp_lang);
	}

	if (function_exists('udm_set_agent_param_ex')) {
		if ($excerptsize > 0)
			udm_set_agent_param_ex($udm_agent, 'ExcerptSize', $excerptsize);
		if ($excerptpadding > 0)
			udm_set_agent_param_ex($udm_agent, 'ExcerptPadding', $excerptpadding);
		if ($dateformat != '')
			udm_set_agent_param_ex($udm_agent, 'DateFormat', $dateformat);

		if ($s != '')
			udm_set_agent_param_ex($udm_agent, 's', $s);
		if ($resultslimit > 0)
			udm_set_agent_param_ex($udm_agent, 'resultslimit', $resultslimit);

		if (($dt == 'back') || ($dt == 'er') || ($dt == 'range')) {
			udm_set_agent_param_ex($udm_agent, 'dt', $dt);
			udm_set_agent_param_ex($udm_agent, 'dx', $dx);
			udm_set_agent_param_ex($udm_agent, 'dm', $dm);
			udm_set_agent_param_ex($udm_agent, 'dy', $dy);
			udm_set_agent_param_ex($udm_agent, 'dd', $dd);
			udm_set_agent_param_ex($udm_agent, 'dp', $dp);
			udm_set_agent_param_ex($udm_agent, 'db', $db);
			udm_set_agent_param_ex($udm_agent, 'de', $de);
		}

		$suggest = strtolower($suggest);
		if ($suggest == 'yes')
			udm_set_agent_param_ex($udm_agent, 'suggest', 'yes');
	}

	if (($dt == 'back') && ($dp != '0')) {
		$recent_time = format_dp($dp);
		if ($recent_time != 0) {
			$dl = time() - $recent_time;
			udm_add_search_limit($udm_agent, UDM_LIMIT_DATE, ">$dl");
		}
	}
	elseif ($dt == 'er') {
		$recent_time = mktime(0, 0, 0, ($dm +1), $dd, $dy);
		if ($dx == -1) {
			udm_add_search_limit($udm_agent, UDM_LIMIT_DATE, "<$recent_time");
		}
		elseif ($dx == 1) {
			udm_add_search_limit($udm_agent, UDM_LIMIT_DATE, ">$recent_time");
		}
	}
	elseif ($dt == 'range') {
		$begin_time = format_userdate($db);
		if ($begin_time)
			udm_add_search_limit($udm_agent, UDM_LIMIT_DATE, ">$begin_time");

		$end_time = format_userdate($de);
		if ($end_time)
			udm_add_search_limit($udm_agent, UDM_LIMIT_DATE, "<$end_time");
	}

	print_template('top');
	flush();

	$ispellmode = strtolower($ispellmode);
	if ($ispellmode == 'db') {
		if (!udm_load_ispell_data($udm_agent, UDM_ISPELL_TYPE_DB, '', '', 1)) {
			print_error_local(udm_error($udm_agent));
		} else
			$have_spell_flag = 1;
	}
	elseif ($ispellmode == 'server') {
		if (!udm_load_ispell_data($udm_agent, UDM_ISPELL_TYPE_SERVER, $spell_host, '', 1)) {
			print_error_local("Error loading ispell data from server $spell_host");
		} else
			$have_spell_flag = 1;
	}
	elseif (($ispellmode == 'text') && ($sp)) {
		reset($affix_file);
		while (list ($t_lang, $file) = each($affix_file)) {
			$param = array();
			if (preg_match("/(.+)\;(.+)/", $file, $param)) {
				$t_charset = trim($param[1]);
				$t_file = trim($param[2]);
				if (!udm_load_ispell_data($udm_agent, UDM_ISPELL_TYPE_AFFIX, $t_lang, $t_charset, $t_file, 0)) {
					print_error_local("Error loading ispell data from file");
				} else
					$have_spell_flag = 1;
			}

			$temp = $spell_file[$t_lang];
			for ($i = 0; $i < count($temp); $i += 1) {
				if (preg_match("/(.+)\;(.+)/", $temp[$i], $param)) {
					$t_charset = trim($param[1]);
					$t_file = trim($param[2]);
					if (!udm_load_ispell_data($udm_agent, UDM_ISPELL_TYPE_SPELL, $t_lang, $t_charset, $t_file, 1)) {
						print_error_local("Error loading ispell data from file");
					} else
						$have_spell_flag = 1;
				}
			}
		}
	}

	udm_parse_query_string($udm_agent, $q_string);
}

/**
 * Some variation of the init function?
 * 
 */
function init_cc() {
	global $q, $q_string, $query_orig, $have_query_flag, $have_spell_flag, $auto_wild;
	global $tag, $tag_arr, $cat, $cat_arr, $np, $ps, $lang, $lang_arr, $wm, $wf, $ul, $m;
	global $dt, $dp, $dx, $dm, $dd, $dy, $db, $de, $s, $resultslimit;
	global $trackquery, $localcharset, $browsercharset, $stopwordtable_arr, $stopwordfile_arr;
	global $minwordlength, $maxwordlength, $phrase, $cache;
	global $ispelluseprefixes, $crosswords, $ispellmode, $spell_host;
	global $affix_file, $spell_file, $vardir, $datadir, $sp, $sy;
	global $dbaddr, $dbmode, $udm_agent, $hlbeg, $hlend, $detectclones;
	global $phpver, $synonym_arr, $searchd_arr, $ul_arr;
	global $REMOTE_ADDR, $storedaddr, $type_arr, $type, $groupbysite, $site;
	global $excerptsize, $excerptpadding, $dateformat, $suggest;

	//parse the $QUERY_STRING global variable and puts pieces into corresponding arrays
	ParseQString();

	//read the template file
	read_template();

	$udm_agent = udm_alloc_agent_array($dbaddr);

	if ($localcharset != '') {
		udm_set_agent_param($udm_agent, UDM_PARAM_CHARSET, $localcharset);
		if (!udm_check_charset($udm_agent, $localcharset)) {
			print_template('top');
			flush();
			print_error_local("Incorrect localcharset $localcharset");
		}
	}

	if ($localcharset == '')
		udm_set_agent_param($udm_agent, UDM_PARAM_CHARSET, 'utf-8');
	if ($browsercharset != '') {
		if (!udm_check_charset($udm_agent, $browsercharset)) {
			print_template('top');
			flush();
			print_error_local("Incorrect browsercharset $browsercharset");
		}
		udm_set_agent_param($udm_agent, UDM_PARAM_BROWSER_CHARSET, $browsercharset);
		header("Content-Type: text/html; charset=$browsercharset");
	} else {
		udm_set_agent_param($udm_agent, UDM_PARAM_BROWSER_CHARSET, 'utf-8');
		header("Content-Type: text/html; charset=utf-8");
	}
	if ($hlbeg == '')
		$hlbeg = '<font color="000088"><b>';
	udm_set_agent_param($udm_agent, UDM_PARAM_HLBEG, $hlbeg);

	if ($hlend == '')
		$hlend = '</b></font>';
	udm_set_agent_param($udm_agent, UDM_PARAM_HLEND, $hlend);

	udm_set_agent_param($udm_agent, UDM_PARAM_QSTRING, $q_string);
	udm_set_agent_param($udm_agent, UDM_PARAM_REMOTE_ADDR, $REMOTE_ADDR);

	if ($have_query_flag)
		udm_set_agent_param($udm_agent, UDM_PARAM_QUERY, $query_orig);

	if (function_exists('udm_set_agent_param_ex')) {
		if ($dateformat != '')
			udm_set_agent_param_ex($udm_agent, 'DateFormat', $dateformat);
	}

	udm_parse_query_string($udm_agent, $q_string);
}
?>
