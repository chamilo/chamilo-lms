<?php

require_once 'common.php';
require_once 'Mock_OAuthBaseStringRequest.php';

class OAuthSignatureMethodHmacSha1Test extends PHPUnit_Framework_TestCase {
	private $method;
	
	public function setUp() {
		$this->method = new OAuthSignatureMethod_HMAC_SHA1();
	}
	
	public function testIdentifyAsHmacSha1() {
		$this->assertEquals('HMAC-SHA1', $this->method->get_name());
	}
	
	public function testBuildSignature() {
		// Tests taken from http://wiki.oauth.net/TestCases section 9.2 ("HMAC-SHA1")
		$request  = new Mock_OAuthBaseStringRequest('bs');
		$consumer = new OAuthConsumer('__unused__', 'cs');
		$token    = NULL;
		$this->assertEquals('egQqG5AJep5sJ7anhXju1unge2I=', $this->method->build_signature( $request, $consumer, $token) );
		
		$request  = new Mock_OAuthBaseStringRequest('bs');
		$consumer = new OAuthConsumer('__unused__', 'cs');
		$token    = new OAuthToken('__unused__', 'ts');
		$this->assertEquals('VZVjXceV7JgPq/dOTnNmEfO0Fv8=', $this->method->build_signature( $request, $consumer, $token) );

		$request  = new Mock_OAuthBaseStringRequest('GET&http%3A%2F%2Fphotos.example.net%2Fphotos&file%3Dvacation.jpg%26'
			. 'oauth_consumer_key%3Ddpf43f3p2l4k3l03%26oauth_nonce%3Dkllo9940pd9333jh%26oauth_signature_method%3DHMAC-SHA1%26'
			. 'oauth_timestamp%3D1191242096%26oauth_token%3Dnnch734d00sl2jdk%26oauth_version%3D1.0%26size%3Doriginal');
		$consumer = new OAuthConsumer('__unused__', 'kd94hf93k423kf44');
		$token    = new OAuthToken('__unused__', 'pfkkdhi9sl3r4s00');
		$this->assertEquals('tR3+Ty81lMeYAr/Fid0kMTYa/WM=', $this->method->build_signature( $request, $consumer, $token) );
	}
	
	public function testVerifySignature() {
		// Tests taken from http://wiki.oauth.net/TestCases section 9.2 ("HMAC-SHA1")
		$request   = new Mock_OAuthBaseStringRequest('bs');
		$consumer  = new OAuthConsumer('__unused__', 'cs');
		$token     = NULL;
		$signature = 'egQqG5AJep5sJ7anhXju1unge2I=';
		$this->assertTrue( $this->method->check_signature( $request, $consumer, $token, $signature) );
		
		$request   = new Mock_OAuthBaseStringRequest('bs');
		$consumer  = new OAuthConsumer('__unused__', 'cs');
		$token     = new OAuthToken('__unused__', 'ts');
		$signature = 'VZVjXceV7JgPq/dOTnNmEfO0Fv8=';
		$this->assertTrue($this->method->check_signature( $request, $consumer, $token, $signature) );

		$request   = new Mock_OAuthBaseStringRequest('GET&http%3A%2F%2Fphotos.example.net%2Fphotos&file%3Dvacation.jpg%26'
			. 'oauth_consumer_key%3Ddpf43f3p2l4k3l03%26oauth_nonce%3Dkllo9940pd9333jh%26oauth_signature_method%3DHMAC-SHA1%26'
			. 'oauth_timestamp%3D1191242096%26oauth_token%3Dnnch734d00sl2jdk%26oauth_version%3D1.0%26size%3Doriginal');
		$consumer  = new OAuthConsumer('__unused__', 'kd94hf93k423kf44');
		$token     = new OAuthToken('__unused__', 'pfkkdhi9sl3r4s00');
		$signature = 'tR3+Ty81lMeYAr/Fid0kMTYa/WM=';
		$this->assertTrue($this->method->check_signature( $request, $consumer, $token, $signature) );
		
	}
}