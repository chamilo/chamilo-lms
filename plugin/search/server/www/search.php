<?php

/*   mnoGoSearch-php v.3.2.10
 *   for mnoGoSearch ( formely known as UdmSearch ) free web search engine
 *   (C) 2001 by Sergey Kartashoff <gluke@mail.ru>,
 *               mnoGoSearch Developers Team <devel@mnogosearch.org>
 */

if (!extension_loaded('mnogosearch')) {
	print "<b>This script requires PHP4.3.0+ with mnoGoSearch extension</b>";
	exit;
}

define("UDM_ENABLED", 1);
define("UDM_DISABLED", 0);

require ('./config.inc');
require ('./common.inc');
require ('./template.inc');

require ('./init.inc');
//api_block_anonymous_users();
//require ('filter_user.functions.php');

if ($lang_content_negotiation == 'yes') {
	// path to template file ($lang_content_negotiation = 'yes')
	// please refer to docs on this feature before using it.
	$template_file = preg_replace("/\.php\.*/", ".xml.php", basename($SCRIPT_FILENAME));
	$template_file = "./".$template_file;
} else {
	// path to template file ($lang_content_negotiation = 'no')
	$template_file = './search.xml.php';
}

// -----------------------------------------------
//  M A I N
// -----------------------------------------------

if (!$cc) {
	$XMLOutput = 1;
	init();

	if (!$have_query_flag) {
		print_template('bottom');
		return;
	}
	elseif ($have_query_flag && ($q == '')) {
		print_template('noquery');
		print_template('bottom');
		return;
	}

	$res = udm_find($udm_agent, $q);

	if (!$res) {
		print_error_local(udm_error($udm_agent));
	} else {
		$found = udm_get_res_param($res, UDM_PARAM_FOUND);
		$rows = udm_get_res_param($res, UDM_PARAM_NUM_ROWS);
		//YW commented out because broke everything
		//if (udm_api_version() >= 30231) {
		//    $wordinfo=Udm_Get_Agent_Param_Ex($udm_agent,'W');
		//} else {
		$wordinfo = udm_get_res_param($res, UDM_PARAM_WORDINFO_ALL);
		//}
		$searchtime = udm_get_res_param($res, UDM_PARAM_SEARCHTIME);
		$first_doc = udm_get_res_param($res, UDM_PARAM_FIRST_DOC);
		$last_doc = udm_get_res_param($res, UDM_PARAM_LAST_DOC);

		if (!$found) {
			$ws = '';
			if ((udm_api_version() >= 30233) && ($suggest == 'yes')) {
				$ws = udm_get_agent_param_ex($udm_agent, 'WS');
			}

			print_template('notfound');
			print_template('bottom');
			return;
		}

		$from = IntVal($np) * IntVal($ps);
		$to = IntVal($np +1) * IntVal($ps);

		if ($to > $found)
			$to = $found;
		if (($from + $ps) < $found)
			$isnext = 1;
		$nav = make_nav($query_orig);

		print_template('restop');

		$global_doc_res = $res;
		$my_skip = 0;

		for ($i = 0; $i < $rows; $i += 1) {
			$excerpt_flag = 0;
			$clonestr = '';

			$rec_id = udm_get_res_field($res, $i, UDM_FIELD_URLID);

			$global_res_position = $i;

			if (udm_api_version() >= 30207) {
				$origin_id = udm_get_res_field($res, $i, UDM_FIELD_ORIGINID);
				if ($origin_id)
					continue;
				else {
					for ($j = 0; $j < $rows; $j += 1) {
						$cl_origin_id = udm_get_res_field($res, $j, UDM_FIELD_ORIGINID);
						if (($cl_origin_id) && ($cl_origin_id == $rec_id)) {
							$url = udm_get_res_field($res, $j, UDM_FIELD_URL);
							//YW

							/*if (!access_check($url)) {
								$my_skip ++;
								continue;
							}*/
							//YW
							$contype = udm_get_res_field($res, $j, UDM_FIELD_CONTENT);
							$docsize = udm_get_res_field($res, $j, UDM_FIELD_SIZE);
							$lastmod = format_lastmod(udm_get_res_field($res, $j, UDM_FIELD_MODIFIED));
							if (udm_api_version() >= 30207) {
								$pop_rank = udm_get_res_field($res, $i, UDM_FIELD_POP_RANK);
							} else
								$pop_rank = '';
							$clonestr .= print_template('clone', 0)."\n";
						}
					}
				}
			}

			if (udm_api_version() >= 30204) {
				$excerpt_flag = udm_make_excerpt($udm_agent, $res, $i);
			}
			//YW
			/*
			$ndoc = udm_get_res_field($res, $i, UDM_FIELD_ORDER) - $my_skip;
			*/
			//YW
			$rating = udm_get_res_field($res, $i, UDM_FIELD_RATING);
			$url = udm_get_res_field($res, $i, UDM_FIELD_URL);
			//YW
			/*
			if (!access_check($url)) {
				$my_skip ++;
				continue;
			}*/
			//YW

			$contype = udm_get_res_field($res, $i, UDM_FIELD_CONTENT);
			$docsize = udm_get_res_field($res, $i, UDM_FIELD_SIZE);
			$lastmod = format_lastmod(udm_get_res_field($res, $i, UDM_FIELD_MODIFIED));

			$title = udm_get_res_field($res, $i, UDM_FIELD_TITLE);
			$title = ($title) ? htmlspecialchars($title) : basename($url);

			$title = ParseDocText($title);
			$text = ParseDocText(htmlspecialchars(udm_get_res_field($res, $i, UDM_FIELD_TEXT)));
			//$text=ParseDocText(htmlspecialchars(udm_get_res_field_ex($res,$i,"Body")));
			$keyw = ParseDocText(htmlspecialchars(udm_get_res_field($res, $i, UDM_FIELD_KEYWORDS)));
			$desc = ParseDocText(htmlspecialchars(udm_get_res_field($res, $i, UDM_FIELD_DESC)));

			$crc = udm_get_res_field($res, $i, UDM_FIELD_CRC);

			if (udm_api_version() >= 30203) {
				$doclang = udm_get_res_field($res, $i, UDM_FIELD_LANG);
				$doccharset = udm_get_res_field($res, $i, UDM_FIELD_CHARSET);
			}

			if ($phpver >= 40006) {
				$category = udm_get_res_field($res, $i, UDM_FIELD_CATEGORY);
			} else {
				$category = '';
			}

			reset($alias_arr);
			$save_url = $url;
			while (list ($t_alias, $t_url) = each($alias_arr)) {
				$url = str_replace($t_alias, $t_url, $url);
			}

			if (udm_api_version() <= 30223) {
				if (udm_api_version() >= 30204) {
					if ($excerpt_flag) {
						if (udm_api_version() >= 30216) {
							if (udm_get_res_field_ex($res, $i, "CachedCopy") != '') {
								$stored_href = "$self?cc=1"."&url=".urlencode($save_url)."&q=".urlencode($query_orig);
							}
						}
						elseif (udm_api_version() >= 30211) {
							$stored_href = "$storedocurl?rec_id=".udm_hash32($udm_agent, $save_url)."&DM=".urlencode($lastmod)."&DS=$docsize"."&L=$doclang"."&CS=$doccharset"."&DU=".urlencode($save_url)."&CT=".urlencode($contype)."&q=".urlencode($query_orig);
						} else {
							$stored_href = "$storedocurl?rec_id=".udm_CRC32($udm_agent, $save_url)."&DM=".urlencode($lastmod)."&DS=$docsize"."&L=$doclang"."&CS=$doccharset"."&DU=".urlencode($save_url)."&CT=".urlencode($contype)."&q=".urlencode($query_orig);
						}
						if ($stored_href != '')
							$storedstr = print_template('stored', 0);
					} else
						$storedstr = '';
				} else
					$storedstr = '';
			} else {
				if (udm_get_res_field_ex($res, $i, "CachedCopy") != '') {
					if (udm_get_res_field_ex($res, $i, "dbnum") == '') {
						$stored_href = "$self?cc=1"."&url=".urlencode($save_url)."&q=".urlencode($query_orig);
					} else {
						$stored_href = "$self?cc=1"."&dbnum=".udm_get_res_field_ex($res, $i, "dbnum")."&url=".urlencode($save_url)."&q=".urlencode($query_orig);
					}
					$storedstr = print_template('stored', 0);
				}
			}

			$sitelimitstr = $persite = '';
			if ((udm_api_version() >= 30207) && ($groupbysite == 'yes')) {
				if (!$site) {
					$sitelimit_href = "$PHP_SELF?$QUERY_STRING";
					$sitelimit_href = preg_replace("/\&np=\d*/", '', $sitelimit_href);
					$sitelimit_href .= "&np=0&site=".udm_get_res_field($res, $i, UDM_FIELD_SITEID);
					$persite = udm_get_res_field_ex($res, $i, "PerSite");
					$sitelimitstr = print_template('site_limit', 0);
				}
			}

			if (udm_api_version() >= 30207) {
				$pop_rank = udm_get_res_field($res, $i, UDM_FIELD_POP_RANK);
			} else
				$pop_rank = '';

			if ((substr($url, 0, 6) == "ftp://") && ($templates['ftpres'][0] != '')) {
				print_template('ftpres');
			}
			elseif (((substr($url, 0, 7) == "http://") || (substr($url, 0, 8) == "https://")) && ($templates['httpres'][0] != '')) {
				print_template('httpres');
			} else {
				print_template('res');
			}
		}
		$global_doc_res = '';

		print_template('resbot');
		print_template('bottom');

		// Free result
		udm_free_res($res);
	}
} else {
	/* show cached copy */
	init_cc();
	$res = udm_store_doc_cgi($udm_agent);

	$id = udm_get_agent_param_ex($udm_agent, 'ID');
	$last_modified = udm_get_agent_param_ex($udm_agent, 'Last-Modified');
	$content = udm_get_agent_param_ex($udm_agent, 'Content-Type');
	$length = udm_get_agent_param_ex($udm_agent, 'Content-Length');
	$charset = udm_get_agent_param_ex($udm_agent, 'Charset');

	Header("Content-Type: text/html; charset=$charset");

	print_template('storedoc_top');

	if ($res) {
		$document = ParseDocText(udm_get_agent_param_ex($udm_agent, 'document'));
		print_template('storedoc');
	}

	print_template('storedoc_bottom');
}

udm_free_agent($udm_agent);
?>
