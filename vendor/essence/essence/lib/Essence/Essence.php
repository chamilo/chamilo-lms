<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence;

use Essence\Cacheable;
use Essence\Configurable;
use Essence\Di\Container\Standard as StandardContainer;
use Essence\Cache\Engine as CacheEngine;
use Essence\Dom\Parser as DomParser;
use Essence\Http\Client as HttpClient;
use Essence\Log\Logger;
use Essence\Provider\Collection;
use Essence\Exception;



/**
 *	Gathers embed informations from URLs.
 *
 *	@package Essence
 */

class Essence {

	use Cacheable;
	use Configurable;



	/**
	 *	A collection of providers to query.
	 *
	 *	@var Essence\ProviderCollection
	 */

	protected $_Collection = null;



	/**
	 *	Internal HTTP client.
	 *
	 *	@var Essence\Http\Client
	 */

	protected $_Http = null;



	/**
	 *	Internal DOM parser.
	 *
	 *	@var Essence\Dom\Parser
	 */

	protected $_Dom = null;



	/**
	 *	Internal Logger.
	 *
	 *	@var Essence\Log\Logger
	 */

	protected $_Logger = null;



	/**
	 *	Configuration options.
	 *
	 *	### Options
	 *
	 *	- 'urlPattern' string A pattern to match URLs.
	 *
	 *	@var array
	 */

	protected $_properties = [
		// http://daringfireball.net/2010/07/improved_regex_for_matching_urls
		'urlPattern' =>
			'#
				(?<url>
					(?<!=["\'])
					(?:https?:)//
					(?:www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)?
					(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+
					(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'"\.,<>?«»“”‘’])
				)
			#ix'
	];



	/**
	 *	Constructor.
	 *
	 *	@param Essence\ProviderCollection $Collection Provider collection.
	 *	@param Essence\Cache\Engine $Cache Cache engine.
	 *	@param Essence\Http\Client $Http HTTP client.
	 *	@param Essence\Dom\Parser $Cache DOM parser.
	 *	@param Essence\Log\Logger $Logger Logger.
	 */

	public function __construct(
		Collection $Collection,
		CacheEngine $Cache,
		HttpClient $Http,
		DomParser $Dom,
		Logger $Logger
	) {
		$this->_Collection = $Collection;
		$this->_Cache = $Cache;
		$this->_Http = $Http;
		$this->_Dom = $Dom;
		$this->_Logger = $Logger;
	}



	/**
	 *	Builds a fully configured instance of Essence.
	 *
	 *	@param array $configuration Dependency injection configuration.
	 *	@return Essence\Essence Essence instance.
	 */

	public static function instance( array $configuration = [ ]) {

		$Container = new StandardContainer( $configuration );
		return $Container->get( 'Essence' );
	}



	/**
	 *	Extracts embeddable URLs from either an URL or an HTML source.
	 *
	 *	@param string $source The URL or HTML source to be extracted.
	 *	@return array An array of extracted URLs.
	 */

	public function extract( $source ) {

		return $this->_cached( '_extract', $source );
	}



	/**
	 *	Implementation of the extract method.
	 *
	 *	@see extract( )
	 *	@param string $source The URL or HTML source to be extracted.
	 *	@return array An array of extracted URLs.
	 */

	protected function _extract( $source ) {

		if ( filter_var( $source, FILTER_VALIDATE_URL )) {
			try {
				$source = $this->_Http->get( $source );
			} catch ( Exception $Exception ) {
				$this->_Logger->log(
					Logger::notice,
					"Unable to fetch $source",
					[ 'exception' => $Exception ]
				);

				return [ ];
			}
		}

		$urls = $this->_extractUrls( $source );
		$embeddable = [ ];

		foreach ( $urls as $url ) {
			if ( $this->_Collection->hasProvider( $url )) {
				$embeddable[ ] = $url;
			}
		}

		return array_unique( $embeddable );
	}



	/**
	 *	Extracts URLs from an HTML source.
	 *
	 *	@param string $html The HTML source to extract URLs from.
	 *	@return array Extracted URLs.
	 */

	protected function _extractUrls( $html ) {

		$options = [
			'a' => 'href',
			'embed' => 'src',
			'iframe' => 'src'
		];

		try {
			$attributes = $this->_Dom->extractAttributes( $html, $options );
		} catch ( Exception $Exception ) {
			$this->_Logger->log(
				Logger::notice,
				'Error parsing HTML source',
				[ 'exception' => $Exception, 'html' => $html ]
			);

			return [ ];
		}

		$urls = [ ];

		foreach ( $options as $tagName => $attributeName ) {
			foreach ( $attributes[ $tagName ] as $tag ) {
				$urls[ ] = $tag[ $attributeName ];
			}
		}

		return $urls;
	}



	/**
	 *	Fetches embed informations from the given URL.
	 *
	 *	This method now supports an array of options that can be interpreted
	 *	at will by the providers.
	 *
	 *	Thanks to Peter Niederlag (https://github.com/t3dev) for his request
	 *	(https://github.com/felixgirault/essence/pull/1).
	 *
	 *	@param string $url URL to fetch informations from.
	 *	@param array $options Custom options to be interpreted by a provider.
	 *	@return Essence\Media Embed informations.
	 */

	public function embed( $url, array $options = [ ]) {

		return $this->_cached( '_embed', $url, $options );
	}



	/**
	 *	Implementation of the embed method.
	 *
	 *	@see embed( )
	 *	@param string $url URL to fetch informations from.
	 *	@param array $options Custom options to be interpreted by a provider.
	 *	@return Essence\Media Embed informations.
	 */

	protected function _embed( $url, array $options ) {

		$providers = $this->_Collection->providers( $url );
		$Media = null;

		foreach ( $providers as $Provider ) {
			if ( $Media = $Provider->embed( $url, $options )) {
				break;
			}
		}

		return $Media;
	}



	/**
	 *	Fetches embed informations from the given URLs.
	 *
	 *	@param array $urls An array of URLs to fetch informations from.
	 *	@param array $options Custom options to be interpreted by a provider.
	 *	@return array An array of embed informations, indexed by URL.
	 */

	public function embedAll( array $urls, array $options = [ ]) {

		$medias = [ ];

		foreach ( $urls as $url ) {
			$medias[ $url ] = $this->embed( $url, $options );
		}

		return $medias;
	}



	/**
	 *	Replaces URLs in the given text by media informations if they point on
	 *	an embeddable resource.
	 *	By default, links will be replaced by the html property of Media.
	 *	If $callback is a callable function, it will be used to generate
	 *	replacement strings, given a Media object.
	 *
	 *	@code
	 *	$text = $Essence->replace( $text, function( $Media ) {
	 *		return '<div class="title">' . $Media->title . '</div>';
	 *	});
	 *	@endcode
	 *
	 *	This behavior should make it easy to integrate third party templating
	 *	engines.
	 *	The pattern to match urls can be configured using the 'urlPattern'
	 *	configuration option.
	 *
	 *	Thanks to Stefano Zoffoli (https://github.com/stefanozoffoli) for his
	 *	idea (https://github.com/felixgirault/essence/issues/4).
	 *
	 *	@param string $text Text in which to replace URLs.
	 *	@param callable $callback Templating callback.
	 *	@param array $options Custom options to be interpreted by a provider.
	 *	@return string Text with replaced URLs.
	 */

	public function replace( $text, $callback = null, array $options = [ ]) {

		return preg_replace_callback(
			$this->urlPattern,
			function ( $matches ) use ( $callback, $options ) {
				if ( $Media = $this->embed( $matches['url'], $options )) {
					return is_callable( $callback )
						? call_user_func( $callback, $Media )
						: $Media->get( 'html' );
				}

				return $matches['url'];
			},
			$text
		);
	}
}
