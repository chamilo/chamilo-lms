<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Media;

use PHPUnit_Framework_TestCase;



/**
 *	Test case for Preparator.
 */

class PreparatorTest extends PHPUnit_Framework_TestCase {

	/**
	 *
	 */

	public $Preparator = null;
	public $Media = null;



	/**
	 *
	 */

	public function setup( ) {

		$this->Media = new Media([
			'url' => 'http://foo.bar.com/resource',
			'title' => 'Title',
			'description' => 'Description',
			'width' => 800,
			'height' => 600
		]);

		$this->Preparator = new Preparator( );
	}



	/**
	 *
	 */

	public function testCompletePhoto( ) {

		$this->Media->set( 'type', 'photo' );
		$this->Preparator->completeMedia( $this->Media );

		$this->assertEquals(
			'<img src="http://foo.bar.com/resource" alt="Description" width="800" height="600" />',
			$this->Media->html
		);
	}



	/**
	 *
	 */

	public function testCompleteVideo( ) {

		$this->Media->set( 'type', 'video' );
		$this->Preparator->completeMedia( $this->Media );

		$this->assertEquals(
			'<iframe src="http://foo.bar.com/resource" width="800" height="600" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen />',
			$this->Media->html
		);
	}



	/**
	 *
	 */

	public function testCompleteDefault( ) {

		$this->Preparator->completeMedia( $this->Media );

		$this->assertEquals(
			'<a href="http://foo.bar.com/resource" alt="Description">Title</a>',
			$this->Media->html
		);
	}
}
