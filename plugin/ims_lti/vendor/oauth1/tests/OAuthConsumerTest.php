<?php

require 'common.php';

class OAuthConsumerTest extends PHPUnit_Framework_TestCase {
	public function testConvertToString() {
		$consumer = new OAuthConsumer('key', 'secret');
		$this->assertEquals('OAuthConsumer[key=key,secret=secret]', (string) $consumer);
	}
}