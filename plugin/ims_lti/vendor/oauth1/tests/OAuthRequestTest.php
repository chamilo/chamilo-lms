<?php

/*
 * Tests of OAuthRequest
 *
 * The tests works by using OAuthTestUtils::build_request
 * to populare $_SERVER, $_GET & $_POST.
 *
 * Most of the base string and signature tests
 * are either very simple or based upon
 * http://wiki.oauth.net/TestCases
 */

require_once dirname(__FILE__) . '/common.php';

class OAuthRequestTest extends PHPUnit_Framework_TestCase {	
	public function testCanGetSingleParameter() {
		// Yes, a awesomely boring test.. But if this doesn't work, the other tests is unreliable
		$request = new OAuthRequest('', '', array('test'=>'foo'));
		$this->assertEquals( 'foo', $request->get_parameter('test'), 'Failed to read back parameter');

		$request = new OAuthRequest('', '', array('test'=>array('foo', 'bar')));
		$this->assertEquals( array('foo', 'bar'), $request->get_parameter('test'), 'Failed to read back parameter');

	
		$request = new OAuthRequest('', '', array('test'=>'foo', 'bar'=>'baz'));
		$this->assertEquals( 'foo', $request->get_parameter('test'), 'Failed to read back parameter');
		$this->assertEquals( 'baz', $request->get_parameter('bar'), 'Failed to read back parameter');
	}
	
	public function testGetAllParameters() {
		// Yes, a awesomely boring test.. But if this doesn't work, the other tests is unreliable
		$request = new OAuthRequest('', '', array('test'=>'foo'));
		$this->assertEquals( array('test'=>'foo'), $request->get_parameters(), 'Failed to read back parameters');

		$request = new OAuthRequest('', '', array('test'=>'foo', 'bar'=>'baz'));
		$this->assertEquals( array('test'=>'foo', 'bar'=>'baz'), $request->get_parameters(), 'Failed to read back parameters');

		$request = new OAuthRequest('', '', array('test'=>array('foo', 'bar')));
		$this->assertEquals( array('test'=>array('foo', 'bar')), $request->get_parameters(), 'Failed to read back parameters');
	}
	
	public function testSetParameters() {
		$request = new OAuthRequest('', '');
		$this->assertEquals( NULL, $request->get_parameter('test'), 'Failed to assert that non-existing parameter is NULL');

		$request->set_parameter('test', 'foo');
		$this->assertEquals( 'foo', $request->get_parameter('test'), 'Failed to set single-entry parameter');

		$request->set_parameter('test', 'bar');
		$this->assertEquals( array('foo', 'bar'), $request->get_parameter('test'), 'Failed to set single-entry parameter');

		$request->set_parameter('test', 'bar', false);
		$this->assertEquals( 'bar', $request->get_parameter('test'), 'Failed to set single-entry parameter');
	}
	
	public function testUnsetParameter() {
		$request = new OAuthRequest('', '');
		$this->assertEquals( NULL, $request->get_parameter('test'));

		$request->set_parameter('test', 'foo');
		$this->assertEquals( 'foo', $request->get_parameter('test'));

		$request->unset_parameter('test');
		$this->assertEquals( NULL, $request->get_parameter('test'), 'Failed to unset parameter');
	}
	
	public function testCreateRequestFromConsumerAndToken() {
		$cons = new OAuthConsumer('key', 'kd94hf93k423kf44');
		$token = new OAuthToken('token', 'pfkkdhi9sl3r4s00');
		
		$request = OAuthRequest::from_consumer_and_token($cons, $token, 'POST', 'http://example.com');
		$this->assertEquals('POST', $request->get_normalized_http_method());
		$this->assertEquals('http://example.com', $request->get_normalized_http_url());
		$this->assertEquals('1.0', $request->get_parameter('oauth_version'));
		$this->assertEquals($cons->key, $request->get_parameter('oauth_consumer_key'));
		$this->assertEquals($token->key, $request->get_parameter('oauth_token'));
		$this->assertEquals(time(), $request->get_parameter('oauth_timestamp'));
		$this->assertRegExp('/[0-9a-f]{32}/', $request->get_parameter('oauth_nonce'));
		// We don't know what the nonce will be, except it'll be md5 and hence 32 hexa digits
		
		$request = OAuthRequest::from_consumer_and_token($cons, $token, 'POST', 'http://example.com', array('oauth_nonce'=>'foo'));
		$this->assertEquals('foo', $request->get_parameter('oauth_nonce'));
		
		$request = OAuthRequest::from_consumer_and_token($cons, NULL, 'POST', 'http://example.com', array('oauth_nonce'=>'foo'));
		$this->assertNull($request->get_parameter('oauth_token'));
		
		// Test that parameters given in the $http_url instead of in the $parameters-parameter
		// will still be picked up
		$request = OAuthRequest::from_consumer_and_token($cons, $token, 'POST', 'http://example.com/?foo=bar');
		$this->assertEquals('http://example.com/', $request->get_normalized_http_url());
		$this->assertEquals('bar', $request->get_parameter('foo'));
	}
	
	public function testBuildRequestFromPost() {
		OAuthTestUtils::build_request('POST', 'http://testbed/test', 'foo=bar&baz=blargh');
		$this->assertEquals(array('foo'=>'bar','baz'=>'blargh'), OAuthRequest::from_request()->get_parameters(), 'Failed to parse POST parameters');
	}
	
	public function testBuildRequestFromGet() {
		OAuthTestUtils::build_request('GET', 'http://testbed/test?foo=bar&baz=blargh');		
		$this->assertEquals(array('foo'=>'bar','baz'=>'blargh'), OAuthRequest::from_request()->get_parameters(), 'Failed to parse GET parameters');
	}

	public function testBuildRequestFromHeader() {
		$test_header = 'OAuth realm="",oauth_foo=bar,oauth_baz="bla,rgh"';
		OAuthTestUtils::build_request('POST', 'http://testbed/test', '', $test_header);
		$this->assertEquals(array('oauth_foo'=>'bar','oauth_baz'=>'bla,rgh'), OAuthRequest::from_request()->get_parameters(), 'Failed to split auth-header correctly');
	}
	
	public function testHasProperParameterPriority() {
		$test_header = 'OAuth realm="",oauth_foo=header';
		OAuthTestUtils::build_request('POST', 'http://testbed/test?oauth_foo=get', 'oauth_foo=post', $test_header);
		$this->assertEquals('header', OAuthRequest::from_request()->get_parameter('oauth_foo'), 'Loaded parameters in with the wrong priorities');		

		OAuthTestUtils::build_request('POST', 'http://testbed/test?oauth_foo=get', 'oauth_foo=post');
		$this->assertEquals('post', OAuthRequest::from_request()->get_parameter('oauth_foo'), 'Loaded parameters in with the wrong priorities');		

		OAuthTestUtils::build_request('POST', 'http://testbed/test?oauth_foo=get');
		$this->assertEquals('get', OAuthRequest::from_request()->get_parameter('oauth_foo'), 'Loaded parameters in with the wrong priorities');				
	}
	
	public function testNormalizeHttpMethod() {
		OAuthTestUtils::build_request('POST', 'http://testbed/test');
		$this->assertEquals('POST', OAuthRequest::from_request()->get_normalized_http_method(), 'Failed to normalize HTTP method: POST');

		OAuthTestUtils::build_request('post', 'http://testbed/test');
		$this->assertEquals('POST', OAuthRequest::from_request()->get_normalized_http_method(), 'Failed to normalize HTTP method: post');

		OAuthTestUtils::build_request('GET', 'http://testbed/test');
		$this->assertEquals('GET', OAuthRequest::from_request()->get_normalized_http_method(), 'Failed to normalize HTTP method: GET');

		OAuthTestUtils::build_request('PUT', 'http://testbed/test');
		$this->assertEquals('PUT', OAuthRequest::from_request()->get_normalized_http_method(), 'Failed to normalize HTTP method: PUT');
	}
	
	public function testNormalizeParameters() {
		// This is mostly repeats of OAuthUtilTest::testParseParameters & OAuthUtilTest::TestBuildHttpQuery

		// Tests taken from
		// http://wiki.oauth.net/TestCases ("Normalize Request Parameters")
		OAuthTestUtils::build_request('POST', 'http://testbed/test', 'name');
		$this->assertEquals( 'name=', OAuthRequest::from_request()->get_signable_parameters());

		OAuthTestUtils::build_request('POST', 'http://testbed/test', 'a=b');
		$this->assertEquals( 'a=b', OAuthRequest::from_request()->get_signable_parameters());
		
		OAuthTestUtils::build_request('POST', 'http://testbed/test', 'a=b&c=d');
		$this->assertEquals( 'a=b&c=d', OAuthRequest::from_request()->get_signable_parameters());
		
		OAuthTestUtils::build_request('POST', 'http://testbed/test', 'a=x%21y&a=x+y');
		$this->assertEquals( 'a=x%20y&a=x%21y', OAuthRequest::from_request()->get_signable_parameters());
		
		OAuthTestUtils::build_request('POST', 'http://testbed/test', 'x%21y=a&x=a');
		$this->assertEquals( 'x=a&x%21y=a', OAuthRequest::from_request()->get_signable_parameters());
		
		OAuthTestUtils::build_request('POST', 'http://testbed/test', 'a=1&c=hi there&f=25&f=50&f=a&z=p&z=t');
		$this->assertEquals( 'a=1&c=hi%20there&f=25&f=50&f=a&z=p&z=t', OAuthRequest::from_request()->get_signable_parameters());
	}
	
	public function testNormalizeHttpUrl() {
		OAuthTestUtils::build_request('POST', 'http://example.com');
		$this->assertEquals('http://example.com', OAuthRequest::from_request()->get_normalized_http_url());
		
		OAuthTestUtils::build_request('POST', 'https://example.com');
		$this->assertEquals('https://example.com', OAuthRequest::from_request()->get_normalized_http_url());
		
		// Tests that http on !80 and https on !443 keeps the port
		OAuthTestUtils::build_request('POST', 'http://example.com:8080');
		$this->assertEquals('http://example.com:8080', OAuthRequest::from_request()->get_normalized_http_url());
		
		OAuthTestUtils::build_request('POST', 'https://example.com:80');
		$this->assertEquals('https://example.com:80', OAuthRequest::from_request()->get_normalized_http_url());
		
		OAuthTestUtils::build_request('POST', 'http://example.com:443');
		$this->assertEquals('http://example.com:443', OAuthRequest::from_request()->get_normalized_http_url());
		
		OAuthTestUtils::build_request('POST', 'http://Example.COM');
		$this->assertEquals('http://example.com', OAuthRequest::from_request()->get_normalized_http_url());
		
		// Emulate silly behavior by some clients, where there Host header includes the port
		OAuthTestUtils::build_request('POST', 'http://example.com');
		$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'];
		$this->assertEquals('http://example.com', OAuthRequest::from_request()->get_normalized_http_url());
	}
	
	public function testBuildPostData() {
		OAuthTestUtils::build_request('POST', 'http://example.com');
		$this->assertEquals('', OAuthRequest::from_request()->to_postdata());

		OAuthTestUtils::build_request('POST', 'http://example.com', 'foo=bar');
		$this->assertEquals('foo=bar', OAuthRequest::from_request()->to_postdata());

		OAuthTestUtils::build_request('GET', 'http://example.com?foo=bar');
		$this->assertEquals('foo=bar', OAuthRequest::from_request()->to_postdata());	
	}
	
	public function testBuildUrl() {
		OAuthTestUtils::build_request('POST', 'http://example.com');
		$this->assertEquals('http://example.com', OAuthRequest::from_request()->to_url());

		OAuthTestUtils::build_request('POST', 'http://example.com', 'foo=bar');
		$this->assertEquals('http://example.com?foo=bar', OAuthRequest::from_request()->to_url());

		OAuthTestUtils::build_request('GET', 'http://example.com?foo=bar');
		$this->assertEquals('http://example.com?foo=bar', OAuthRequest::from_request()->to_url());	
	}

	public function testConvertToString() {
		OAuthTestUtils::build_request('POST', 'http://example.com');
		$this->assertEquals('http://example.com', (string) OAuthRequest::from_request());

		OAuthTestUtils::build_request('POST', 'http://example.com', 'foo=bar');
		$this->assertEquals('http://example.com?foo=bar', (string) OAuthRequest::from_request());

		OAuthTestUtils::build_request('GET', 'http://example.com?foo=bar');
		$this->assertEquals('http://example.com?foo=bar', (string) OAuthRequest::from_request());	
	}
	
	public function testBuildHeader() {
		OAuthTestUtils::build_request('POST', 'http://example.com');
		$this->assertEquals('Authorization: OAuth', OAuthRequest::from_request()->to_header());
		$this->assertEquals('Authorization: OAuth realm="test"', OAuthRequest::from_request()->to_header('test'));

		OAuthTestUtils::build_request('POST', 'http://example.com', 'foo=bar');
		$this->assertEquals('Authorization: OAuth', OAuthRequest::from_request()->to_header());
		$this->assertEquals('Authorization: OAuth realm="test"', OAuthRequest::from_request()->to_header('test'));

		OAuthTestUtils::build_request('POST', 'http://example.com', 'oauth_test=foo');
		$this->assertEquals('Authorization: OAuth oauth_test="foo"', OAuthRequest::from_request()->to_header());
		$this->assertEquals('Authorization: OAuth realm="test",oauth_test="foo"', OAuthRequest::from_request()->to_header('test'));

		// Is headers supposted to be Urlencoded. More to the point:
		// Should it be baz = bla,rgh or baz = bla%2Crgh ??
		// - morten.fangel
		OAuthTestUtils::build_request('POST', 'http://example.com', '', 'OAuth realm="",oauth_foo=bar,oauth_baz="bla,rgh"');
		$this->assertEquals('Authorization: OAuth oauth_foo="bar",oauth_baz="bla%2Crgh"', OAuthRequest::from_request()->to_header());
		$this->assertEquals('Authorization: OAuth realm="test",oauth_foo="bar",oauth_baz="bla%2Crgh"', OAuthRequest::from_request()->to_header('test'));
	}
	
	public function testWontBuildHeaderWithArrayInput() {
		$this->setExpectedException('OAuthException');
		OAuthTestUtils::build_request('POST', 'http://example.com', 'oauth_foo=bar&oauth_foo=baz');
		OAuthRequest::from_request()->to_header();
	}

	public function testBuildBaseString() {
		OAuthTestUtils::build_request('POST', 'http://testbed/test', 'n=v');
		$this->assertEquals('POST&http%3A%2F%2Ftestbed%2Ftest&n%3Dv', OAuthRequest::from_request()->get_signature_base_string());
		
		OAuthTestUtils::build_request('POST', 'http://testbed/test', 'n=v&n=v2');
		$this->assertEquals('POST&http%3A%2F%2Ftestbed%2Ftest&n%3Dv%26n%3Dv2', OAuthRequest::from_request()->get_signature_base_string());
		
		OAuthTestUtils::build_request('GET', 'http://example.com?n=v');
		$this->assertEquals('GET&http%3A%2F%2Fexample.com&n%3Dv', OAuthRequest::from_request()->get_signature_base_string());
		
		$params  = 'oauth_version=1.0&oauth_consumer_key=dpf43f3p2l4k3l03&oauth_timestamp=1191242090';
		$params .= '&oauth_nonce=hsu94j3884jdopsl&oauth_signature_method=PLAINTEXT&oauth_signature=ignored';
		OAuthTestUtils::build_request('POST', 'https://photos.example.net/request_token', $params);			
		$this->assertEquals('POST&https%3A%2F%2Fphotos.example.net%2Frequest_token&oauth_'
							.'consumer_key%3Ddpf43f3p2l4k3l03%26oauth_nonce%3Dhsu94j3884j'
							.'dopsl%26oauth_signature_method%3DPLAINTEXT%26oauth_timestam'
							.'p%3D1191242090%26oauth_version%3D1.0', 
							OAuthRequest::from_request()->get_signature_base_string());									

		$params  = 'file=vacation.jpg&size=original&oauth_version=1.0&oauth_consumer_key=dpf43f3p2l4k3l03';
		$params .= '&oauth_token=nnch734d00sl2jdk&oauth_timestamp=1191242096&oauth_nonce=kllo9940pd9333jh';
		$params .= '&oauth_signature=ignored&oauth_signature_method=HMAC-SHA1';
		OAuthTestUtils::build_request('GET', 'http://photos.example.net/photos?'.$params);			
		$this->assertEquals('GET&http%3A%2F%2Fphotos.example.net%2Fphotos&file%3Dvacation'
							.'.jpg%26oauth_consumer_key%3Ddpf43f3p2l4k3l03%26oauth_nonce%'
							.'3Dkllo9940pd9333jh%26oauth_signature_method%3DHMAC-SHA1%26o'
							.'auth_timestamp%3D1191242096%26oauth_token%3Dnnch734d00sl2jd'
							.'k%26oauth_version%3D1.0%26size%3Doriginal', 
							OAuthRequest::from_request()->get_signature_base_string());
	}

	public function testBuildSignature() {
		$params  = 'file=vacation.jpg&size=original&oauth_version=1.0&oauth_consumer_key=dpf43f3p2l4k3l03';
		$params .= '&oauth_token=nnch734d00sl2jdk&oauth_timestamp=1191242096&oauth_nonce=kllo9940pd9333jh';
		$params .= '&oauth_signature=ignored&oauth_signature_method=HMAC-SHA1';
		OAuthTestUtils::build_request('GET', 'http://photos.example.net/photos?'.$params);			
		$r = OAuthRequest::from_request();
		
		$cons = new OAuthConsumer('key', 'kd94hf93k423kf44');
		$token = new OAuthToken('token', 'pfkkdhi9sl3r4s00');
		
		$hmac = new OAuthSignatureMethod_HMAC_SHA1();
		$plaintext = new OAuthSignatureMethod_PLAINTEXT();
		
		$this->assertEquals('tR3+Ty81lMeYAr/Fid0kMTYa/WM=', $r->build_signature($hmac, $cons, $token));
		$this->assertEquals('kd94hf93k423kf44&pfkkdhi9sl3r4s00', $r->build_signature($plaintext, $cons, $token));
	}

	public function testSign() {
		$params  = 'file=vacation.jpg&size=original&oauth_version=1.0&oauth_consumer_key=dpf43f3p2l4k3l03';
		$params .= '&oauth_token=nnch734d00sl2jdk&oauth_timestamp=1191242096&oauth_nonce=kllo9940pd9333jh';
		$params .= '&oauth_signature=__ignored__&oauth_signature_method=HMAC-SHA1';
		OAuthTestUtils::build_request('GET', 'http://photos.example.net/photos?'.$params);			
		$r = OAuthRequest::from_request();
		
		$cons = new OAuthConsumer('key', 'kd94hf93k423kf44');
		$token = new OAuthToken('token', 'pfkkdhi9sl3r4s00');
		
		$hmac = new OAuthSignatureMethod_HMAC_SHA1();
		$plaintext = new OAuthSignatureMethod_PLAINTEXT();
		
		// We need to test both what the parameter is, and how the serialized request is..
		
		$r->sign_request($hmac, $cons, $token);
		$this->assertEquals('HMAC-SHA1', $r->get_parameter('oauth_signature_method'));
		$this->assertEquals('tR3+Ty81lMeYAr/Fid0kMTYa/WM=', $r->get_parameter('oauth_signature'));
		$expectedPostdata = 'file=vacation.jpg&oauth_consumer_key=dpf43f3p2l4k3l03&oauth_nonce=kllo9940pd9333jh&'
				. 'oauth_signature=tR3%2BTy81lMeYAr%2FFid0kMTYa%2FWM%3D&oauth_signature_method=HMAC-SHA1&'
				. 'oauth_timestamp=1191242096&oauth_token=nnch734d00sl2jdk&oauth_version=1.0&size=original';
		$this->assertEquals( $expectedPostdata, $r->to_postdata());
		
		$r->sign_request($plaintext, $cons, $token);
		$this->assertEquals('PLAINTEXT', $r->get_parameter('oauth_signature_method'));
		$this->assertEquals('kd94hf93k423kf44&pfkkdhi9sl3r4s00', $r->get_parameter('oauth_signature'));
		$expectedPostdata = 'file=vacation.jpg&oauth_consumer_key=dpf43f3p2l4k3l03&oauth_nonce=kllo9940pd9333jh&'
				. 'oauth_signature=kd94hf93k423kf44%26pfkkdhi9sl3r4s00&oauth_signature_method=PLAINTEXT&'
				. 'oauth_timestamp=1191242096&oauth_token=nnch734d00sl2jdk&oauth_version=1.0&size=original';
		$this->assertEquals( $expectedPostdata, $r->to_postdata());
		
	}
}

?>