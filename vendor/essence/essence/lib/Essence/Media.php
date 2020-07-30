<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence;

use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use Essence\Configurable;



/**
 *	Stores informations about an embed response.
 *	This class is useful to ensure that any response from any provider will
 *	follow the same conventions.
 *
 *	@package Essence
 */

class Media implements IteratorAggregate, JsonSerializable {

	use Configurable;



	/**
	 *	Embed data, indexed by property name. Providers must try to fill these
	 *	default properties with appropriate data before adding their own, to
	 *	ensure consistency accross the API.
	 *
	 *	These default properties are gathered from the OEmbed and OpenGraph
	 *	protocols, and provide all the basic informations needed to embed a
	 *	media.
	 *
	 *	@var array
	 */

	protected $_properties = [

		// OEmbed type
		// OG type
		'type' => '',

		// OEmbed version
		'version' => '',

		// OEmbed title
		// OG title
		'title' => '',

		// Sometimes provided in OEmbed (i.e. Vimeo)
		// OG description
		'description' => '',

		// OEmbed author_name
		'authorName' => '',

		// OEmbed author_url
		'authorUrl' => '',

		// OEmbed provider_name
		// OG site_name
		'providerName' => '',

		// OEmbed provider_url
		'providerUrl' => '',

		// OEmbed cache_age
		'cacheAge' => '',

		// OEmbed thumbnail_url
		// OG image
		// OG image:url
		'thumbnailUrl' => '',

		// OEmbed thumbnail_width
		'thumbnailWidth' => '',

		// OEmbed thumbnail_height
		'thumbnailHeight' => '',

		// OEmbed html
		'html' => '',

		// OEmbed width
		// OG image:width
		// OG video:width
		'width' => '',

		// OEmbed height
		// OG image:height
		// OG video:height
		'height' => '',

		// OEmbed url
		// OG url
		'url' => ''
	];



	/**
	 *	Constructs a Media from the given dataset.
	 *
	 *	@see $properties
	 *	@param array $properties An array of media informations.
	 */

	public function __construct( array $properties ) {

		$this->configure( $properties );
	}



	/**
	 *	Returns an iterator for the media properties.
	 *
	 *	@return ArrayIterator Iterator.
	 */

	public function getIterator( ) {

		return new ArrayIterator( $this->_properties );
	}



	/**
	 *	Returns serialized properties.
	 *
	 *	@return string JSON representation.
	 */

	public function jsonSerialize( ) {

		return $this->_properties;
	}
}
