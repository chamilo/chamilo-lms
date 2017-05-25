<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence;

use Essence\Configurable;
use Essence\Exception;
use Essence\Media;
use Essence\Media\Preparator;
use Essence\Log\Logger;



/**
 *	Base class for a Provider.
 *
 *	@package Essence
 */

abstract class Provider {

	use Configurable;



	/**
	 *	Internal logger.
	 *
	 *	@var Essence\Log\Logger
	 */

	protected $_Logger = null;



	/**
	 *	Media preparator.
	 *
	 *	@var Essence\Media\Preparator
	 */

	protected $_Preparator = null;



	/**
	 *	Configuration options.
	 *
	 *	### Options
	 *
	 *	- 'prepare' callable( string $url ) A function to prepare the given URL.
	 *
	 *	@var array
	 */

	protected $_properties = [
		'prepare' => 'self::prepareUrl'
	];



	/**
	 *	Constructor.
	 *
	 *	@param Essence\Log\Logger $Logger Logger.
	 *	@param Essence\Log\Preparator $Preparator Preparator.
	 */

	public function __construct( Logger $Logger, Preparator $Preparator = null ) {

		$this->_Logger = $Logger;
		$this->_Preparator = $Preparator;
	}



	/**
	 *	Fetches embed information from the given URL.
	 *
	 *	@param string $url URL to fetch informations from.
	 *	@param array $options Custom options to be interpreted by the provider.
	 *	@return Media|null Embed informations, or null if nothing could be
	 *		fetched.
	 */

	public final function embed( $url, array $options = [ ]) {

		$Media = null;

		if ( is_callable( $this->prepare )) {
			$url = call_user_func( $this->prepare, $url, $options );
		}

		try {
			$Media = $this->_embed( $url, $options );
			$Media->setDefault( 'url', $url );

			if ( $this->_Preparator ) {
				$this->_Preparator->complete( $Media, $options );
			}
		} catch ( Exception $Exception ) {
			$this->_Logger->log(
				Logger::notice,
				"Unable to embed $url",
				[ 'exception' => $Exception ]
			);
		}

		return $Media;
	}



	/**
	 *	Does the actual fetching of informations.
	 *
	 *	@param string $url URL to fetch informations from.
	 *	@param array $options Custom options to be interpreted by the provider.
	 *	@return Media Embed informations.
	 *	@throws Essence\Exception
	 */

	abstract protected function _embed( $url, array $options );



	/**
	 *	Trims and returns the given string.
	 *
	 *	@param string $url URL.
	 *	@param array $options Embed options.
	 *	@return string Trimmed URL.
	 */

	public static function prepareUrl( $url, array $options = [ ]) {

		return trim( $url );
	}
}
