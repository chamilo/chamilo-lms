<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Utility;

use PHPUnit_Framework_TestCase;



/**
 *	Test case for Xml.
 */

class XmlTest extends PHPUnit_Framework_TestCase {

	/**
	 *
	 */

	public $valid =
		'<?xml version="1.0" encoding="utf-8"?>
		<oembed>
			<title>Title</title>
			<type>video</type>
		</oembed>';



	/**
	 *
	 */

	public $invalid =
		'<oembed>
			<title>Title
			<type>video</type>';



	/**
	 *
	 */

	public function testParse( ) {

		$this->assertEquals([
			'title' => 'Title',
			'type' => 'video'
		], Xml::parse( $this->valid ));
	}



	/**
	 *
	 */

	public function testParseInvalid( ) {

		$this->setExpectedException( 'Essence\\Exception' );

		Xml::parse( $this->invalid );
	}
}
