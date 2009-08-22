<?php
/**
 * This library is the test of the multibyte_string_functions.lib 
 * that is a common purpose library for supporting multibyte string 
 * aware functions
 * @author Ricardo Rodriguez Salazar
 */

class TestMultibyte_String_Functions extends UnitTestCase {
	
	function TestMultibyte_String_Functions() {
        $this->UnitTestCase('Multibyte String Functions tests');
	}
	
	public function testApiByteCount(){
		$string='xxxxxxx';
		static $use_mb_strlen;
		$res = api_byte_count($string);
		$this->assertTrue($res);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	public function testApiConvertEncoding(){
		$string='xxxxxx'; 
		$to_encoding=''; 
		$res = api_convert_encoding($string, $to_encoding);		
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	public function testApiUtf8Encode(){
		$string='xxxáéíóú?';
		$from_encoding = utf8_encode;
		$res = api_utf8_encode($string, $from_encoding);
		$this->assertTrue(is_string($res)); 
		//var_dump($res);
	}
	
	public function testApiUtf8Decode(){
		$string='xxxx1ws?!áéíóú@';
		$to_encoding= null;
		$res = api_utf8_decode($string, $to_encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res)); 
		//var_dump($res);
	}
	
	public function testApiToSystemEncoding(){
		$string='!?/\áéíóú@'; 
		$from_encoding = null; 
		$check_utf8_validity = false;
		$res = api_to_system_encoding($string, $from_encoding, $check_utf8_validity);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	public function testApiHtmlEntities(){
		$string='áéíóú@!?/\-_`*ç´`';
		$quote_style = ENT_COMPAT; 
		$encoding = null;
		$res = api_htmlentities($string, $quote_style = ENT_COMPAT, $encoding = null);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);	
	}
	
	public function testApiHtmlEntityDecode(){
		$string='áéíóú@/\!?Ç´`+*?-_ '; 
		$quote_style = ENT_COMPAT; 
		$encoding = null;
		$res= api_html_entity_decode($string, $quote_style, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	public function testApiXmlHttpResponseEncode(){
		$string='áéíóú@/\!?Ç´`+*?-_';
		$from_encoding = null;
		$res = api_xml_http_response_encode($string, $from_encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	public function testApiFileSystemEncode(){
		$string='áéíóú@/\!?Ç´`+*?-_';
		$from_encoding = null;
		$res = api_file_system_encode($string, $from_encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	public function testApiFileSystemDecode(){
		$string='áéíóú@/\!?Ç´`+*?-_';
		$to_encoding = null;	
		$res = api_file_system_decode($string, $to_encoding); 
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}	
	
	public function testApiEreg($pattern, $string, & $regs = null){	
		$pattern='@scorm/showinframes\.php([^\s"\'&]*)(&|&amp;)file=([^\s"\'&]*)@';
		$string='áéíóú@/\!?Ç´`+*?-_';
		$regs = null;
		$res = api_ereg($pattern, $string, & $regs);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	public function testapi_ereg_replace(){
		$pattern='@scorm/showinframes\.php([^\s"\'&]*)(&|&amp;)file=([^\s"\'&]*)@'; 
		$replacement='aeiou'; 
		$string='áéíóú@/\!?Ç´`+*?-_'; 
		$option = null;
		$res = api_ereg_replace($pattern, $replacement, $string, $option);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testApiArrayUtf8Decode(){
		$variable='áéíóú';
		$encoding=utf8_encode;
		$res = _api_array_utf8_decode($variable, $encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testapi_eregi(){
		$pattern='@scorm/showinframes\.php([^\s"\'&]*)(&|&amp;)file=([^\s"\'&]*)@'; 
		$string='áéíóú!/|\º?Ç`´+*';
		$regs = null;
		$res = api_eregi($pattern, $string, & $regs);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	public function testApiEregiReplace(){
		$pattern='@scorm/showinframes\.php([^\s"\'&]*)(&|&amp;)file=([^\s"\'&]*)@';
		$replacement='aeiou';
		$string='áéíóú';
		$option = null;
		$res = api_eregi_replace($pattern, $replacement, $string, $option);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testApiSplit(){
		$pattern='@scorm/showinframes\.php([^\s"\'&]*)(&|&amp;)file=([^\s"\'&]*)@';
		$string='áéíóúº|\/?Ç][ç]'; 
		$limit = null;
		$res =api_split($pattern, $string, $limit);
		$this->assertTrue(is_array($res));
		//var_dump($res);
		//$this->assertTrue();
	}
	
	public function testApiStrIreplace(){
		$search='á';
		$replace='a';
		$subject='bájando';
		$count = null; 
		$encoding = utf8_encode;
		$res = api_str_ireplace($search, $replace, $subject, & $count, $encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_array($res) || is_string($res));
		//var_dump($res);
	}
	
	public function testApiStrSplit(){
		$string='áéíóúº|\/?Ç][ç]';
		$split_length = 1;
		$encoding = null;
		$res = api_str_split($string, $split_length, $encoding);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	
	public function testApiStripos(){
		$haystack='bájando';
		$needle='á';
		$offset = 0;
		$encoding = null;
		$res = api_stripos($haystack, $needle, $offset, $encoding);
		$this->assertTrue(is_numeric($res)|| is_bool($res));
		//var_dump($res);
	}
	
	public function testApiStristr(){
		$haystack='actuacion';
		$needle='o';
		$part = false;
		$encoding = null;
		$res = api_stristr($haystack, $needle, $part, $encoding);
		$this->assertTrue(is_bool($res) || is_string($res));
		//var_dump($res);
	}
	
	public function testApiStrlen(){
		$string='áéíóúº|\/?Ç][ç]';
		$encoding = null;
		$res = api_strlen($string, $encoding);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	public function testApiStrpos(){
		$haystack='';
		$needle='';
		$offset = 0;
		$encoding = null;
		$res = api_strpos($haystack, $needle, $offset, $encoding);
		$this->assertTrue(is_numeric($res) || is_bool($res));
		//var_dump($res);
	}
	
	public function testApiStrrchr(){
		$haystack='aviación';
		$needle='ó';
		$part = false;
		$encoding = null;
		$res = api_strrchr($haystack, $needle, $part, $encoding);
		$this->assertTrue(is_string($res)|| is_bool($res));
		//var_dump($res);
	}
	
	public function testApiStrrev(){
		$string='áéíóúº|\/?Ç][ç]';
		$encoding = null;
		$res = api_strrev($string, $encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testApiStrrpos(){
		$haystack='aviación';
		$needle='ó';
		$offset = 0;
		$encoding = null;
		$res = api_strrpos($haystack, $needle, $offset, $encoding);
		$this->assertTrue(is_numeric($res) || is_bool($res));
		//var_dump($res);
	}
	
	public function testApiStrstr(){
		$haystack='aviación';
		$needle='ó'; 
		$part = false; 
		$encoding = null;		
		$res = api_strstr($haystack, $needle, $part, $encoding);
		$this->assertTrue(is_bool($res)|| is_string($res));
		//var_dump($res);
	}
	
	public function testApiStrtolower(){
		$string='áéíóúº|\/?Ç][ç]';
		$encoding = null;
		$res =api_strtolower($string, $encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testApiStrtoupper(){
		$string='áéíóúº|\/?Ç][ç]';
		$encoding = null;
		$res = api_strtoupper($string, $encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testApiStrtr(){
		$string='áéíóúº|\/?Ç][ç]';
		$from='';
		$to = null;
		$encoding = null;
		$res = api_strtr($string, $from, $to, $encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testApiSubstr(){
		$string='áéíóúº|\/?Ç][ç]';
		$start='áéíóu';
		$length = null; 
		$encoding = null;
		$res = api_substr($string, $start, $length, $encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testApiSubstrReplace(){
		$string='áéíóúº|\/?Ç][ç]';
		$replacement='aeiou'; 
		$start='á'; 
		$length = null; 
		$encoding = null;
		$res = api_substr_replace($string, $replacement, $start, $length, $encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testApiUcfirst(){
		$string='áéíóúº|\/?Ç][ç]';
		$encoding = null;
		$res = api_ucfirst($string, $encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testApiUcwords(){
		$string='áéíóúº|\/?Ç][ç]';
		$encoding = null;
		$res = api_ucwords($string, $encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testApiStrcasecmp(){
		$string1='áéíóu';
		$string2='áeióu';
		$language = null;
		$encoding = null;
		$res = api_strcasecmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	public function testApiStrcmp(){
		$string1='ã¡ã©ã­ã³u'; 
		$string2='áeióu'; 
		$language = null; 
		$encoding = null;
		$res = api_strcmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	public function testApiStrnatcasecmp(){
		$string1='aeiouáéíóú';
		$string2='aeiou'; 
		$language = null; 
		$encoding = null;
		$res = api_strnatcasecmp($string1, $string2, $language, $encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	public function  testApiStrnatcmp(){
		$string1='aeiou'; 
		$string2='aeiouáéíóú'; 
		$language = null;
		$encoding = null;
		$res = api_strnatcmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	public function testapiinArrayNocase(){
		$needle='áéíó';
		$haystack='aeio, uáé, íóú';
		$strict = false; 
		$encoding = null;
		$res = api_in_array_nocase($needle, $haystack, $strict, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}
	/**
	 * Function collator_create has not work because the version of php
	 * must be PHP 5 >= 5.3.0, PECL intl >= 1.0.0.
	 */
	/*
	public function testApiGetCollator(){
		$language = null;
		 $res = _api_get_collator($language = null);
		$this->assertTrue($res);
		var_dump($res);
	}
	
	public function testApiGetAlphaNumericalCollator(){
		static $collator = array();
		$language = null;
		$res = _api_get_alpha_numerical_collator($language);
		$this->assertTrue($res);
		var_dump($res);
	}
	*/
	public function testApiAsort(){
		$array='áéd, aíó, úéo'; 
		$sort_flag = SORT_REGULAR; 
		$language = null; 
		$encoding = null;
		$res = api_asort(&$array, $sort_flag, $language, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}
	
	public function testApiArsort(){
		$array='áá,aa,áá,éé,ee'; 
		$sort_flag = SORT_REGULAR; 
		$language = null; 
		$encoding = null;
		$res = api_arsort(&$array, $sort_flag, $language, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}

	public function testApiNatsort(){
		$array=''; 
		$language = null; 
		$encoding = null;
		$res = api_natsort(&$array, $language, $encoding);
		if(!is_null($res)) :
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		endif;
		//var_dump($res);
	}
	
	public function testApiNatrsort(){
		$array='aañañañ, asñasñ, asñas, ñasña, ñsasas';
		$language = null; 
		$encoding = null;
		$res = api_natrsort(&$array, $language, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}

	public function testApiNatcasesort(){
		$array='AAA, BBB, CCC';
		$language = null; 
		$encoding = null;
		$res = api_natcasesort(&$array, $language, $encoding);
		if(!is_null($res)) :
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false || $res=== true);
		endif;
		//var_dump($res);
	}
	
	public function testApiNatcasersort(){
		$array='aa, bb, cfd'; 
		$language = null; 
		$encoding = null;
		$res = api_natcasersort(&$array, $language, $encoding);
		if(!is_null($res)) :
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		endif;
		//var_dump($res);
	}
	
	public function testApiKsort(){
		$array='aaa, bbb, ccc'; 
		$sort_flag = SORT_REGULAR; 
		$language = null; 
		$encoding = null;
		$res = api_ksort(&$array, $sort_flag, $language, $encoding);
		if(!is_null($res)) :
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		endif;
		//var_dump($res);
	}
	
	public function testApiKrsort(){
		$array='aaa, bb, cfd, frr'; 
		$sort_flag = SORT_REGULAR; 
		$language = null; 
		$encoding = null;
		$res = api_krsort(&$array, $sort_flag, $language, $encoding);
		if(!is_null($res)) :
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		endif;
		//var_dump($res);
	}
	
	public function testApiKnatsort(){
		$array=''; 
		$language = null; 
		$encoding = null;
		$res = api_knatsort(&$array, $language, $encoding);
		if(!is_null($res)) :
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		endif;
		//var_dump($res);
	}
	
	public function testApiKnatrsort(){
		$array='aaa, bbb, ccc, ddd,';
		$language = null; 
		$encoding = null;
		$res = api_knatrsort(&$array, $language, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res ===false);
		//var_dump($res);
	}
	
	public function testApiKnatcasesort(){
		$array='AAA, BBB, CCC, ááá'; 
		$language = null; 
		$encoding = null;
		$res = api_knatcasesort(&$array, $language, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}
	
	public function testApiKnatcasersort(){
		$array='aAa, BbB, CCc'; 
		$language = null; 
		$encoding = null;
		$res = api_knatcasersort(&$array, $language, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res===true || $res === false);
		//var_dump($res);
	}
	
	public function testApiSort(){
		$array='AAA, BBB, CCC'; 
		$sort_flag = SORT_REGULAR; 
		$language = null; 
		$encoding = null;
		$res = api_sort(&$array, $sort_flag, $language, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}
	
	public function testapi_rsort(){
		$array='aaa, bbb, ccc'; 
		$sort_flag = SORT_REGULAR; 
		$language = null; 
		$encoding = null;
		$res = api_rsort(&$array, $sort_flag, $language, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}
	/*
	public function testApiCmp(){
		$string1='aaa'; 
		$string2='áaa';
		$res = _api_cmp($string1, $string2);
		$this->assertTrue($res);
		var_dump();
	}
	/*
	public function testApiRcmp(){
		$string1='ááá'; 
		$string2='ááa';
		$res =_api_rcmp($string1, $string2);
		$this->assertTrue($res);
		var_dump($res);
	}
	
	public function testApiCasecmp(){
		$string1='aáá';
		$string2='ááa';
		$res = _api_casecmp($string1, $string2)	;
		$this->assertTrue($res);
		var_dump($res);
	}
	
	public function testApiCasercmp(){
		$string1='áaá';
		$string2='aáa';
		$res = _api_casercmp($string1, $string2);
		$this->assertTrue($res);
		var_dump($res);
	}
	*/
	public function testApiStrnatrcmp(){
		$string1='BBb'; 
		$string2='bbB';
		$res = _api_strnatrcmp($string1, $string2);
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	public function testApiStrnatcasercmp(){
		$string1='aaa'; 
		$string2='áaa';
		$res = _api_strnatcasercmp($string1, $string2);
		$this->assertTrue($res);
		//var_dump($res);
	}
	/*
	public function testApiGetCollatorSortFlag(){
		$sort_flag = SORT_REGULAR;
		$res = _api_get_collator_sort_flag($sort_flag);
		$this->assertTrue($res);
		var_dump($res);
	}
	*/
	public function testApiTransliterate(){
		$string='aaaaaa?';
		$unknown = '?'; 
		$from_encoding = null;
		$res = api_transliterate($string, $unknown, $from_encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testApiGetNonUtf8Encoding(){
		$language = null;
		$res = api_get_non_utf8_encoding($language);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testApiNonUtf8Encodings(){
		$res = & _api_non_utf8_encodings();
		$this->assertTrue($res);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	
	public function testApiRefineEncodingId(){
		$encoding='KOI8-R';
		$res = api_refine_encoding_id($encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testApiEqualEncodings(){
		$encoding1='aaáá';
		$encoding2='aaáá';
		$res =	api_equal_encodings($encoding1, $encoding2);
		$this->assertTrue($res);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}
	
	public function testApiIsUtf8(){
		$encoding='UTF-8';
		$res = api_is_utf8($encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res ===true || $res === false);
		//var_dump($res);
	}
	
	public function testApiIsLatin1(){
		$encoding='ISO-8859-15';
		$res = api_is_latin1($encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res=== true || $res === false);
		//var_dump($res);
	}
	
	public function testApiGetSystemEncoding(){
		$res = api_get_system_encoding();
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	public function testApiGetFileSystemEncoding(){
		$res = api_get_file_system_encoding();
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	public function testApiSetDefaultEncoding(){
		$encoding ='UTF-8';
		$res = api_set_string_library_default_encoding($encoding);
		api_set_string_library_default_encoding($res); // This is for restoring the original internal value.
		if(!is_null($res)) :
		$this->assertTrue($res);
		endif;
		//var_dump($res);
	}
	
	public function testapi_mb_internal_encoding(){
		$encoding = null;
		$res = _api_mb_internal_encoding($encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	public function testapi_mb_regex_encoding(){
		$encoding = null;
		$res = _api_mb_regex_encoding($encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	public function testapi_iconv_get_encoding($type){
		$type='UTF-8';
		$res = _api_iconv_get_encoding($type);
		if(!is_string($res)) :
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res=== true || $res === false);
		endif;
		//var_dump($res);
	}
	
	public function testApiIconvSetEncoding(){
		$type='UTF-8';
		$encoding = null;
		$res = _api_iconv_set_encoding($type, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true|| $res === false);
		//var_dump($res);
	}
	
	public function testapi_is_encoding_supported(){
		$encoding='';
		$res = api_is_encoding_supported($encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	public function testapi_mb_supports(){
		$encoding='UTF-8';
		$res = _api_mb_supports($encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}
	
	public function testapi_iconv_supports(){
		$encoding='UTF-8';
		$res = _api_iconv_supports($encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	public function testapi_html_entity_supports(){
		$encoding='UTF-8';
		$res = _api_html_entity_supports($encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}
	
	public function testapi_is_valid_utf8(){
		$string='áéíóú1@\/-ḉ`´';
		$res = api_is_valid_utf8($string);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}
	
	public function testapi_is_valid_ascii(){
		$string ='áéíóú';
		$res = api_is_valid_ascii($string);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}
	
	public function testapi_refine_language_id(){
		$language='EN';
		$res = api_refine_language_id($language);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testapi_is_latin1_compatible(){
		$language='portuguese';
		$res = api_is_latin1_compatible($language);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}
	
	public function testapi_get_latin1_compatible_languages(){
		$res = api_get_latin1_compatible_languages();
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	
	public function testapi_get_locale_from_language(){
		$language = 'EN';
		$res = _api_get_locale_from_language($language);
		if(!is_null($res)):
		$this->assertTrue($res);
		$this->assertTrue(is_string);
		endif;
		//var_dump($res);
	}
	
	public function testapi_set_default_locale(){
		$locale = null;
		$res = _api_set_default_locale($locale);
		if(!is_string($res)) :
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		endif;
		//var_dump($res);
	}
	
	public function testapi_get_default_locale(){
		$res = api_get_default_locale();
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

}
?>
