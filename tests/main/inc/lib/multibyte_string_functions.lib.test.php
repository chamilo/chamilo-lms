<?php

/**
 * This is a test of multibyte_string_functions.lib which is
 * a common purpose library for supporting multibyte string 
 * aware functions. Only the public API is tested here.
 * @author Ricardo Rodriguez Salazar, 2009.
 * @author Ivan Tcholakov, August 2009.
 * For licensing terms, see /dokeos_license.txt
 *
 * Notes:
 * 1. While saving this file, please, preserve its UTF-8 encoding.
 * Othewise this tes would be broken.
 * 2. While running this test, send a header declaring UTF-8 encoding.
 * Then you would see variable dumps correctly.
 * 3. Tests about string comparison and sorting might give false results
 * if the intl extension has not been installed.
 */


class TestMultibyte_String_Functions extends UnitTestCase {

	function TestMultibyte_String_Functions() {
        $this->UnitTestCase('Multibyte String Functions Tests');
	}


/**
 * ----------------------------------------------------------------------------
 * A safe way to calculate binary lenght of a string (as number of bytes)
 * ----------------------------------------------------------------------------
 */

	public function test_api_byte_count() {
		$string = 'xxxáéíóú?'; // UTF-8
		$res = api_byte_count($string);
		$this->assertTrue($res == 14);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Multibyte string conversion functions
 * ----------------------------------------------------------------------------
 */

	public function test_api_convert_encoding() {
		$string = 'xxxáéíóú?€'; // UTF-8
		$from_encoding = 'UTF-8';
		$to_encoding = 'ISO-8859-15';
		$res = api_convert_encoding($string, $to_encoding, $from_encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue(api_convert_encoding($res, $from_encoding, $to_encoding) == $string);
		//var_dump($res);
		//var_dump(api_convert_encoding($res, $from_encoding, $to_encoding));
	}

	public function test_api_utf8_encode() {
		$string = 'xxxáéíóú?€'; // UTF-8
		$from_encoding = 'ISO-8859-15';
		$string1 = api_utf8_decode($string, $from_encoding);
		$res = api_utf8_encode($string1, $from_encoding);
		$this->assertTrue(is_string($res)); 
		$this->assertTrue($res == $string);
		//var_dump($res);
	}

	public function test_api_utf8_decode() {
		$string = 'xxxx1ws?!áéíóú@€'; // UTF-8
		$to_encoding = 'ISO-8859-15';
		$res = api_utf8_decode($string, $to_encoding);
		$this->assertTrue(is_string($res)); 
		$this->assertTrue(api_utf8_encode($res, $to_encoding) == $string);
		//var_dump($res);
	}

	public function test_api_to_system_encoding() {
		$string = '!?/\áéíóú@€'; // UTF-8
		$from_encoding = 'UTF-8'; 
		$check_utf8_validity = false;
		$res = api_to_system_encoding($string, $from_encoding, $check_utf8_validity);
		$this->assertTrue(is_string($res));
		$this->assertTrue(api_convert_encoding($res, $from_encoding, api_get_system_encoding()) == $string);
		//var_dump($res);
	}

	public function test_api_htmlentities() {
		$string = 'áéíóú@!?/\-_`*ç´`'; // UTF-8
		$quote_style = ENT_QUOTES; 
		$encoding = 'UTF-8';
		$res = api_htmlentities($string, $quote_style, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue(api_convert_encoding($res, $encoding, 'HTML-ENTITIES') == $string);
		//var_dump($res);	
	}

	public function test_api_html_entity_decode() {
		$string = 'áéíóú@/\!?Ç´`+*?-_ '; // UTF-8
		$quote_style = ENT_QUOTES; 
		$encoding = 'UTF-8';
		$res = api_html_entity_decode(api_convert_encoding($string, 'HTML-ENTITIES', $encoding), $quote_style, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == $string);
		//var_dump($res);
	}

	public function test_api_xml_http_response_encode() {
		$string='áéíóú@/\!?Ç´`+*?-_'; // UTF-8
		$from_encoding = 'UTF-8';
		$res = api_xml_http_response_encode($string, $from_encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function test_api_file_system_encode() {
		$string = 'áéíóú@/\!?Ç´`+*?-_'; // UTF-8
		$from_encoding = 'UTF-8';
		$res = api_file_system_encode($string, $from_encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function test_api_file_system_decode() {
		$string='áéíóú@/\!?Ç´`+*?-_'; // UTF-8
		$to_encoding = 'UTF-8';	
		$res = api_file_system_decode($string, $to_encoding); 
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function test_api_transliterate() {
		$string = 'Фёдор Михайлович Достоевкий'; // UTF-8
		/*
		// If you have broken by mistake UTF-8 encoding of this source, try the following equivalent:
		$string = api_html_entity_decode(
			'&#1060;&#1105;&#1076;&#1086;&#1088; '.
			'&#1052;&#1080;&#1093;&#1072;&#1081;&#1083;&#1086;&#1074;&#1080;&#1095; '.
			'&#1044;&#1086;&#1089;&#1090;&#1086;&#1077;&#1074;&#1082;&#1080;&#1081;',
			ENT_QUOTES, 'UTF-8');
		*/
		$unknown = 'X'; 
		$from_encoding = 'UTF-8';
		$res = api_transliterate($string, $unknown, $from_encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'Fyodor Mihaylovich Dostoevkiy');
		//var_dump($string);
		//var_dump($res);
	}

	
/**
 * ----------------------------------------------------------------------------
 * Common multibyte string functions
 * ----------------------------------------------------------------------------
 */

	public function test_api_str_ireplace() {
		$search = 'á'; // UTF-8
		$replace = 'a';
		$subject = 'bájando'; // UTF-8
		$count = null; 
		$encoding = 'UTF-8';
		$res = api_str_ireplace($search, $replace, $subject, & $count, $encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'bajando');
		//var_dump($res);
	}

	public function test_api_str_split() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$split_length = 1;
		$encoding = 'UTF-8';
		$res = api_str_split($string, $split_length, $encoding);
		$this->assertTrue(is_array($res));
		$this->assertTrue(count($res) == 15);
		//var_dump($res);
	}

	public function test_api_stripos() {
		$haystack = 'bájando'; // UTF-8
		$needle = 'Á';
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_stripos($haystack, $needle, $offset, $encoding);
		$this->assertTrue(is_numeric($res)|| is_bool($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}

	public function test_api_stristr() {
		$haystack = 'bájando'; // UTF-8
		$needle = 'Á';
		$part = false;
		$encoding = 'UTF-8';
		$res = api_stristr($haystack, $needle, $part, $encoding);
		$this->assertTrue(is_bool($res) || is_string($res));
		$this->assertTrue($res == 'ájando');
		//var_dump($res);
	}

	public function test_api_strlen() {
		$string='áéíóúº|\/?Ç][ç]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_strlen($string, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 15);
		//var_dump($res);
	}

	public function test_api_strpos() {
		$haystack = 'bájando'; // UTF-8
		$needle = 'á';
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_strpos($haystack, $needle, $offset, $encoding);
		$this->assertTrue(is_numeric($res)|| is_bool($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}

	public function test_api_strrchr() {
		$haystack = 'aviación aviación'; // UTF-8
		$needle = 'ó';
		$part = false;
		$encoding = 'UTF-8';
		$res = api_strrchr($haystack, $needle, $part, $encoding);
		$this->assertTrue(is_string($res)|| is_bool($res));
		$this->assertTrue($res == 'ón');
		//var_dump($res);
	}

	public function test_api_strrev() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_strrev($string, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == ']ç[]Ç?/\|ºúóíéá');
		//var_dump($res);
	}

	public function test_api_strrpos() {
		$haystack = 'aviación aviación'; // UTF-8
		$needle = 'ó';
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_strrpos($haystack, $needle, $offset, $encoding);
		$this->assertTrue(is_numeric($res) || is_bool($res));
		$this->assertTrue($res == 15);
		//var_dump($res);
	}

	public function test_api_strstr() {
		$haystack = 'aviación'; // UTF-8
		$needle = 'ó'; 
		$part = false; 
		$encoding = 'UTF-8';		
		$res = api_strstr($haystack, $needle, $part, $encoding);
		$this->assertTrue(is_bool($res)|| is_string($res));
		$this->assertTrue($res == 'ón');
		//var_dump($res);
	}

	public function test_api_strtolower() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_strtolower($string, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'áéíóúº|\/?ç][ç]');
		//var_dump($res);
	}

	public function test_api_strtoupper() {
		$string='áéíóúº|\/?Ç][ç]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_strtoupper($string, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res =='ÁÉÍÓÚº|\/?Ç][Ç]');
		//var_dump($res);
	}

	public function test_api_substr() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$start = 10;
		$length = 4; 
		$encoding = 'UTF-8';
		$res = api_substr($string, $start, $length, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'Ç][ç');
		//var_dump($res);
	}

	public function test_api_substr_replace() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$replacement = 'eiou'; 
		$start= 1; 
		$length = 4; 
		$encoding = 'UTF-8';
		$res = api_substr_replace($string, $replacement, $start, $length, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'áeiouº|\/?Ç][ç]');
		//var_dump($res);
	}

	public function test_api_ucfirst() {
		$string = 'áéíóúº|\/? xx ][ xx ]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_ucfirst($string, $encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'Áéíóúº|\/? xx ][ xx ]');
		//var_dump($res);
	}

	public function test_api_ucwords() {
		$string = 'áéíóúº|\/? xx ][ xx ]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_ucwords($string, $encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'Áéíóúº|\/? Xx ][ Xx ]');
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * String operations using regular expressions
 * ----------------------------------------------------------------------------
 */

	public function test_api_preg_match() {
		$pattern = '/иван/i'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) --'; // UTF-8
		$matches = null;
		$flags = 0;
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_preg_match($pattern, $subject, $matches, $flags, $offset, $encoding);
		$this->assertTrue($res == 1);
		//var_dump($res);
		//var_dump($matches);
	}

	public function test_api_preg_match_all() {
		$pattern = '/иван/i'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) -- иван --'; // UTF-8
		$matches = null;
		$flags = PREG_PATTERN_ORDER;
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_preg_match_all($pattern, $subject, $matches, $flags, $offset, $encoding);
		$this->assertTrue($res == 2);
		//var_dump($res);
		//var_dump($matches);
	}

	public function test_api_preg_replace() {
		$pattern = '/иван/i'; // UTF-8
		$replacement = 'ИВАН'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) -- иван --'; // UTF-8
		$limit = -1;
		$count = null;
		$encoding = 'UTF-8';
		$res = api_preg_replace($pattern, $replacement, $subject, $limit, $count, $encoding);
		$this->assertTrue($res == '-- Ivan (en) -- ИВАН (bg) -- ИВАН --'); // UTF-8
		//var_dump($res);
	}

	public function test_api_preg_replace_callback() {
		$pattern = '/иван/i'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) -- иван --'; // UTF-8
		$limit = -1;
		$count = null;
		$encoding = 'UTF-8';
		$res = api_preg_replace_callback($pattern, create_function('$matches', 'return api_ucfirst($matches[0], \'UTF-8\');'), $subject, $limit, $count, $encoding);
		$this->assertTrue($res == '-- Ivan (en) -- Иван (bg) -- Иван --'); // UTF-8
		//var_dump($res);
	}

	public function test_api_preg_split() {
		$pattern = '/иван/i'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) -- иван --'; // UTF-8
		$limit = -1;
		$count = null;
		$encoding = 'UTF-8';
		$res = api_preg_split($pattern, $subject, $limit, $count, $encoding);
		$this->assertTrue($res[0] == '-- Ivan (en) -- '); // UTF-8
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Obsolete string operations using regular expressions, to be deprecated
 * ----------------------------------------------------------------------------
 */

	public function test_api_ereg() {	
		$pattern = 'scorm/showinframes.php([^"\'&]*)(&|&amp;)file=([^"\'&]*)$';
		$string = 'http://localhost/dokeos/main/scorm/showinframes.php?id=5&amp;file=test.php';
		$res = api_ereg($pattern, $string, $regs);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 45);
		//var_dump($res);
	}

	public function test_api_ereg_replace() {
		$pattern = 'file=([^"\'&]*)$';
		$string = 'http://localhost/dokeos/main/scorm/showinframes.php?id=5&amp;file=test.php'; 
		$replacement = 'file=my_test.php'; 
		$option = null;
		$res = api_ereg_replace($pattern, $replacement, $string, $option);
		$this->assertTrue(is_string($res));
		$this->assertTrue(strlen($res) == 77);
		//var_dump($res);
	}

	public function testapi_eregi() {
		$pattern = 'scorm/showinframes.php([^"\'&]*)(&|&amp;)file=([^"\'&]*)$';
		$string = 'http://localhost/dokeos/main/scorm/showinframes.php?id=5&amp;file=test.php';
		$res = api_eregi($pattern, $string, $regs);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 45);
		//var_dump($res);
	}

	public function test_api_eregi_replace() {
		$pattern = 'file=([^"\'&]*)$';
		$string = 'http://localhost/dokeos/main/scorm/showinframes.php?id=5&amp;file=test.php'; 
		$replacement = 'file=my_test.php'; 
		$option = null;
		$res = api_eregi_replace($pattern, $replacement, $string, $option);
		$this->assertTrue(is_string($res));
		$this->assertTrue(strlen($res) == 77);
		//var_dump($res);
	}

	public function test_api_split() {
		$pattern = '[/.-]';
		$string = '08/22/2009'; 
		$limit = null;
		$res = api_split($pattern, $string, $limit);
		$this->assertTrue(is_array($res));
		$this->assertTrue(count($res) == 3);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * String comparison
 * ----------------------------------------------------------------------------
 */

	public function test_api_strcasecmp() {
		$string1 = 'áéíóu'; // UTF-8
		$string2 = 'Áéíóu'; // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_strcasecmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 0);
		//var_dump($res);
	}

	public function test_api_strcmp() {
		$string1 = 'áéíóu'; // UTF-8
		$string2 = 'Áéíóu'; // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_strcmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}

	public function test_api_strnatcasecmp() {
		$string1 = '201áéíóu.txt'; // UTF-8
		$string2 = '30Áéíóu.TXT'; // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_strnatcasecmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}

	public function  test_api_strnatcmp() {
		$string1 = '201áéíóu.txt'; // UTF-8
		$string2 = '30áéíóu.TXT'; // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_strnatcmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Sorting arrays
 * ----------------------------------------------------------------------------
 */

	public function test_api_asort() {
		$array = array('úéo', 'aíó', 'áed'); // UTF-8
		$sort_flag = SORT_REGULAR; 
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_asort($array, $sort_flag, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'aíó');
		//var_dump($array);
		//var_dump($res);
	}
	
	public function test_api_arsort() {
		$array = array('aíó', 'úéo', 'áed'); // UTF-8
		$sort_flag = SORT_REGULAR; 
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_arsort($array, $sort_flag, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'úéo');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_natsort() {
		$array = array('img12.png', 'img10.png', 'img2.png', 'img1.png'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_natsort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img1.png');
		//var_dump($array);
		//var_dump($res);
	}
	
	public function test_api_natrsort() {
		$array = array('img2.png', 'img10.png', 'img12.png', 'img1.png'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_natrsort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img12.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_natcasesort() {
		$array = array('img2.png', 'img10.png', 'Img12.png', 'img1.png'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_natcasesort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img1.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_natcasersort() {
		$array = array('img2.png', 'img10.png', 'Img12.png', 'img1.png'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_natcasersort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'Img12.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_ksort() {
		$array = array('aíó' => 'img2.png', 'úéo' => 'img10.png', 'áed' => 'img12.png', 'áedc' => 'img1.png'); // UTF-8
		$sort_flag = SORT_REGULAR; 
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_ksort($array, $sort_flag, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img2.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_krsort() {
		$array = array('aíó' => 'img2.png', 'úéo' => 'img10.png', 'áed' => 'img12.png', 'áedc' => 'img1.png'); // UTF-8
		$sort_flag = SORT_REGULAR; 
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_krsort($array, $sort_flag, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img10.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_knatsort() {
		$array = array('img2.png' => 'aíó', 'img10.png' => 'úéo', 'img12.png' => 'áed', 'img1.png' => 'áedc'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_knatsort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'áedc');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_knatrsort() {
		$array = array('img2.png' => 'aíó', 'img10.png' => 'úéo', 'IMG12.PNG' => 'áed', 'img1.png' => 'áedc'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_knatrsort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'úéo');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_knatcasesort() {
		$array = array('img2.png' => 'aíó', 'img10.png' => 'úéo', 'IMG12.PNG' => 'áed', 'img1.png' => 'áedc'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_knatcasesort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'áedc');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_knatcasersort() {
		$array = array('img2.png' => 'aíó', 'img10.png' => 'úéo', 'IMG12.PNG' => 'áed', 'IMG1.PNG' => 'áedc'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_knatcasersort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'áed');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_sort() {
		$array = array('úéo', 'aíó', 'áed', 'áedc'); // UTF-8
		$sort_flag = SORT_REGULAR; 
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_sort($array, $sort_flag, $language, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[0] == 'aíó');
		//var_dump($array);
		//var_dump($res);
	}

	public function testapi_rsort() {
		$array = array('aíó', 'úéo', 'áed', 'áedc'); // UTF-8
		$sort_flag = SORT_REGULAR; 
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_rsort($array, $sort_flag, $language, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[0] == 'úéo');
		//var_dump($array);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Common sting operations with arrays
 * ----------------------------------------------------------------------------
 */

	public function test_api_in_array_nocase() {
		$needle = 'áéíó'; // UTF-8
		$haystack = array('Áéíó', 'uáé', 'íóú'); // UTF-8
		$strict = false; 
		$encoding = 'UTF-8';
		$res = api_in_array_nocase($needle, $haystack, $strict, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Encoding management functions
 * ----------------------------------------------------------------------------
 */

	public function test_api_refine_encoding_id() {
		$encoding = 'koI8-r';
		$res = api_refine_encoding_id($encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'KOI8-R');
		//var_dump($res);
	}

	public function test_api_equal_encodings() {
		$encoding1 = 'cp65001';
		$encoding2 = 'utf-8';
		$res1 = api_equal_encodings($encoding1, $encoding2);
		$encoding3 = 'WINDOWS-1251';
		$encoding4 = 'WINDOWS-1252';
		$res2 = api_equal_encodings($encoding3, $encoding4);
		$this->assertTrue(is_bool($res1));
		$this->assertTrue(is_bool($res2));
		$this->assertTrue($res1 && !$res2);
		//var_dump($res1);
		//var_dump($res2);
	}

	public function test_api_is_utf8() {
		$encoding = 'cp65001'; // This an alias of UTF-8.
		$res = api_is_utf8($encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_is_latin1() {
		$encoding = 'ISO-8859-15';
		$strict = false;
		$res = api_is_latin1($encoding, false);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_get_system_encoding() {
		$res = api_get_system_encoding();
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_get_file_system_encoding() {
		$res = api_get_file_system_encoding();
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_is_encoding_supported() {
		$encoding1 = 'UTF-8';
		$encoding2 = 'XXXX#%#%VR^%BBDNdjlrsg;d';
		$res1 = api_is_encoding_supported($encoding1);
		$res2 = api_is_encoding_supported($encoding2);
		$this->assertTrue(is_bool($res1) && is_bool($res2));
		$this->assertTrue($res1 && !$res2);
		//var_dump($res1);
		//var_dump($res2);
	}

	public function test_api_get_non_utf8_encoding() {
		$language = 'bulgarian';
		$res = api_get_non_utf8_encoding($language);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'WINDOWS-1251');
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * String validation functions concerning certain encodings
 * ----------------------------------------------------------------------------
 */

	public function test_api_is_valid_utf8() {
		$string = 'áéíóú1@\/-ḉ`´';
		$res = api_is_valid_utf8($string);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_is_valid_ascii() {
		$string = 'áéíóú'; // UTF-8
		$res = api_is_valid_ascii($string);
		$this->assertTrue(is_bool($res));
		$this->assertTrue(!$res);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Language management functions
 * ----------------------------------------------------------------------------
 */

	public function test_api_refine_language_id() {
		$language = 'english_org';
		$res = api_refine_language_id($language);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'english');
		//var_dump($res);
	}

	public function test_api_is_latin1_compatible() {
		$language = 'portuguese';
		$res = api_is_latin1_compatible($language);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

}

?>