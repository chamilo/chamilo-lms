<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Dom\Parser;

use PHPUnit_Framework_TestCase;



/**
 *	Test case for Native.
 */

class NativeTest extends PHPUnit_Framework_TestCase {

	/**
	 *
	 */

	public $Native = null;



	/**
	 *
	 */

	public $html = <<<'HTML'
		<meta name="description" content="Description." />
		<meta name="ns:custom" content="Custom namespace." />

		<a href="http://www.test.com" title="Link">
		<a href="http://www.othertest.com" title="Other link" target="_blank">
HTML;



	/**
	 *
	 */

	public function setUp( ) {

		$this->Native = new Native( );
	}



	/**
	 *
	 */

	public function testExtractAttributes( ) {

		$this->setExpectedException( '\\Essence\\Exception' );
		$this->Native->extractAttributes( '', [ ]);
	}



	/**
	 *
	 */

	public function testExtractAttributesFromUnknownTag( ) {

		$this->assertEquals(
			[ 'unknown' => [ ]],
			$this->Native->extractAttributes( $this->html, [ 'unknown' ])
		);
	}



	/**
	 *
	 */

	public function testExtractAllAttributesFromTag( ) {

		$this->assertEquals([
			'a' => [[
				'href' => 'http://www.test.com',
				'title' => 'Link'
			], [
				'href' => 'http://www.othertest.com',
				'title' => 'Other link',
				'target' => '_blank'
			]]
		], $this->Native->extractAttributes( $this->html, [ 'a' ]));
	}



	/**
	 *
	 */

	public function testExtractSomeAttributesFromTag( ) {

		$this->assertEquals([
			'a' => [[
				'href' => 'http://www.othertest.com',
				'target' => '_blank'
			]]
		], $this->Native->extractAttributes( $this->html, [
			'a' => [ 'href', 'target' ]
		]));
	}



	/**
	 *
	 */

	public function testExtractFilteredAttributesFromTag( ) {

		$this->assertEquals([
			'meta' => [[
				'name' => 'ns:custom',
				'content' => 'Custom namespace.'
			]]
		], $this->Native->extractAttributes( $this->html, [
			'meta' => [ 'name' => '#^ns:.+#', 'content' ]
		]));
	}



	/**
	 *
	 */

	public function testExtractAllAttributesFromMultipleTags( ) {

		$this->assertEquals([
			'meta' => [[
				'name' => 'description',
				'content' => 'Description.'
			], [
				'name' => 'ns:custom',
				'content' => 'Custom namespace.'
			]],
			'a' => [[
				'href' => 'http://www.test.com',
				'title' => 'Link'
			], [
				'href' => 'http://www.othertest.com',
				'title' => 'Other link',
				'target' => '_blank'
			]]
		], $this->Native->extractAttributes( $this->html, [ 'meta', 'a' ]));
	}
}
