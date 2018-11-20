<?php

require_once 'common.php';
require_once 'Mock_OAuthBaseStringRequest.php';

class OAuthSignatureMethodPlaintextTest extends PHPUnit_Framework_TestCase {
	private $method;
	
	public function setUp() {
		$this->method = new OAuthSignatureMethod_PLAINTEXT();
	}
	
	public function testIdentifyAsPlaintext() {
		$this->assertEquals('PLAINTEXT', $this->method->get_name());
	}
	
	public function testBuildSignature() {
		// Tests based on from http://wiki.oauth.net/TestCases section 9.2 ("HMAC-SHA1")
		$request  = new Mock_OAuthBaseStringRequest('__unused__');
		$consumer = new OAuthConsumer('__unused__', 'cs');
		$token    = NULL;
		$this->assertEquals('cs&', $this->method->build_signature( $request, $consumer, $token) );
		
		$request  = new Mock_OAuthBaseStringRequest('__unused__');
		$consumer = new OAuthConsumer('__unused__', 'cs');
		$token    = new OAuthToken('__unused__', 'ts');
		$this->assertEquals('cs&ts', $this->method->build_signature( $request, $consumer, $token) );

		$request  = new Mock_OAuthBaseStringRequest('__unused__');
		$consumer = new OAuthConsumer('__unused__', 'kd94hf93k423kf44');
		$token    = new OAuthToken('__unused__', 'pfkkdhi9sl3r4s00');
		$this->assertEquals('kd94hf93k423kf44&pfkkdhi9sl3r4s00', $this->method->build_signature( $request, $consumer, $token) );
		
		// Tests taken from Chapter 9.4.1 ("Generating Signature") from the spec
		$request  = new Mock_OAuthBaseStringRequest('__unused__');
		$consumer = new OAuthConsumer('__unused__', 'djr9rjt0jd78jf88');
		$token    = new OAuthToken('__unused__', 'jjd999tj88uiths3');
		$this->assertEquals('djr9rjt0jd78jf88&jjd999tj88uiths3', $this->method->build_signature( $request, $consumer, $token) );

		$request  = new Mock_OAuthBaseStringRequest('__unused__');
		$consumer = new OAuthConsumer('__unused__', 'djr9rjt0jd78jf88');
		$token    = new OAuthToken('__unused__', 'jjd99$tj88uiths3');
		$this->assertEquals('djr9rjt0jd78jf88&jjd99%24tj88uiths3', $this->method->build_signature( $request, $consumer, $token) );
	}
	
	public function testVerifySignature() {
		// Tests based on from http://wiki.oauth.net/TestCases section 9.2 ("HMAC-SHA1")
		$request   = new Mock_OAuthBaseStringRequest('__unused__');
		$consumer  = new OAuthConsumer('__unused__', 'cs');
		$token     = NULL;
		$signature = 'cs&';
		$this->assertTrue( $this->method->check_signature( $request, $consumer, $token, $signature) );
		
		$request   = new Mock_OAuthBaseStringRequest('__unused__');
		$consumer  = new OAuthConsumer('__unused__', 'cs');
		$token     = new OAuthToken('__unused__', 'ts');
		$signature = 'cs&ts';
		$this->assertTrue($this->method->check_signature( $request, $consumer, $token, $signature) );

		$request   = new Mock_OAuthBaseStringRequest('__unused__');
		$consumer  = new OAuthConsumer('__unused__', 'kd94hf93k423kf44');
		$token     = new OAuthToken('__unused__', 'pfkkdhi9sl3r4s00');
		$signature = 'kd94hf93k423kf44&pfkkdhi9sl3r4s00';
		$this->assertTrue($this->method->check_signature( $request, $consumer, $token, $signature) );
		
		// Tests taken from Chapter 9.4.1 ("Generating Signature") from the spec
		$request   = new Mock_OAuthBaseStringRequest('__unused__');
		$consumer  = new OAuthConsumer('__unused__', 'djr9rjt0jd78jf88');
		$token     = new OAuthToken('__unused__', 'jjd999tj88uiths3');
		$signature = 'djr9rjt0jd78jf88&jjd999tj88uiths3';
		$this->assertTrue($this->method->check_signature( $request, $consumer, $token, $signature) );

		$request   = new Mock_OAuthBaseStringRequest('__unused__');
		$consumer  = new OAuthConsumer('__unused__', 'djr9rjt0jd78jf88');
		$token     = new OAuthToken('__unused__', 'jjd99$tj88uiths3');
		$signature = 'djr9rjt0jd78jf88&jjd99%24tj88uiths3';
		$this->assertTrue($this->method->check_signature( $request, $consumer, $token, $signature) );
	}
}