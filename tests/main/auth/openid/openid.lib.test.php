<?php
//To can run this test case you need commet the line 35 and 55 "exit;" inside the file openid.lib.php
require_once(api_get_path(SYS_CODE_PATH).'auth/openid/openid.lib.php');

// Diffie-Hellman Key Exchange Default Value.
define('OPENID_DH_DEFAULT_MOD', '155172898181473697471232257763715539915724801'.
       '966915404479707795314057629378541917580651227423698188993727816152646631'.
       '438561595825688188889951272158842675419950341258706556549803580104870537'.
       '681476726513255747040765857479291291572334510643245094715007229621094194'.
       '349783925984760375594985848253359305585439638443');

// Constants for Diffie-Hellman key exchange computations.
define('OPENID_DH_DEFAULT_GEN', '2');
define('OPENID_SHA1_BLOCKSIZE', 64);
define('OPENID_RAND_SOURCE', '/dev/urandom');

// OpenID namespace URLs
define('OPENID_NS_2_0', 'http://specs.openid.net/auth/2.0');
define('OPENID_NS_1_1', 'http://openid.net/signon/1.1');
define('OPENID_NS_1_0', 'http://openid.net/signon/1.0');


class TestOpenId extends UnitTestCase {

	function test_openid_create_message() {
		$data='';
		$serialized .= "$key:$value\n";
		$res=_openid_create_message($data);
		$this->assertTrue(is_string($res));
		$this->assertTrue(is_string($serialized));
		//var_dump($serialized);
	}

	function test_openid_dh_base64_to_long() {
		$str='';
	    $b64 = base64_decode($str);
		$res=_openid_dh_base64_to_long($str);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue(is_string($b64));
		//var_dump($res);
	}

	function test_openid_dh_binary_to_long() {
		$str='';
		$bytes = array_merge(unpack('C*', $str));
		$res=_openid_dh_binary_to_long($str);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue(is_array($bytes));
		//var_dump($bytes);
	}

	function test_openid_dh_long_to_base64() {
		$str='';
		$res=_openid_dh_long_to_base64($str);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function test_openid_dh_long_to_binary() {
		$long='';
		$res=_openid_dh_long_to_binary($long);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function test_openid_dh_rand() {
		$stop='';
		$rbytes = _openid_dh_long_to_binary($stop);
		$nbytes = strlen($rbytes);
		$mxrand = bcpow(256, $nbytes);
		$duplicate = bcmod($mxrand, $stop);
		$duplicate_cache = array();
		$duplicate_cache[$rbytes] = array($duplicate, $nbytes);
		$res=_openid_dh_rand($stop);
		if(!is_array($res))$this->assertTrue(is_null($res));
		$this->assertTrue(is_array($duplicate_cache));
		//var_dump($res);
		//var_dump($duplicate_cache);
	}

	function test_openid_dh_xorsecret() {
		$shared='';
		$secret='';
		$res=_openid_dh_xorsecret($shared, $secret);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function test_openid_encode_message() {
		$message='';
		$res=_openid_encode_message($message);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function test_openid_fix_post() {
		$post='';
		$res=_openid_fix_post($post);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function test_openid_get_bytes() {
		static $f = null;
		$num_bytes='';
		$res=_openid_get_bytes($num_bytes);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function test_openid_hmac() {
		$key='';
		$text='';
		$res=_openid_hmac($key, $text);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function test_openid_is_xri() {
		$identifier='';
		$res=_openid_is_xri($identifier);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function test_openid_link_href() {
		$rel='';
		$html='';
		$res=_openid_link_href($rel, $html);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function test_openid_meta_httpequiv() {
		$equiv='';
		$html='';
		$res=_openid_meta_httpequiv($equiv, $html);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function test_openid_nonce() {
		$res=_openid_nonce();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function test_openid_normalize() {
		$identifier='';
		$res=_openid_normalize($identifier);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function test_openid_normalize_url() {
		$url='';
		$res=_openid_normalize_url($url);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function test_openid_normalize_xri() {
		$xri='';
		$res=_openid_normalize_xri($xri);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function test_openid_parse_message() {
		$message='';
		$parsed_message = array();
		$res=_openid_parse_message($message);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function test_openid_sha1() {
		$text='';
		$res=_openid_sha1($text);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function test_openid_signature() {
		$association='';
		$message_array='';
		$keys_to_sign='';
		$res=_openid_signature($association, $message_array, $keys_to_sign);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testbcpowmod() {
		$base='';
		$exp='';
		$mod='';
		$res=bcpowmod($base, $exp, $mod);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testopenid_redirect() {
		$url='http://localhost/dokeossvn186/tests/all.test2.php';
		$message='';
		ob_start();
		$res=openid_redirect($url, $message);
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}

	function testopenid_redirect_http() {
		$url='http://localhost/dokeossvn186/tests/all.test2.php';
		$message='';
		ob_start();
		$res=openid_redirect_http($url, $message);
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}
}
?>
