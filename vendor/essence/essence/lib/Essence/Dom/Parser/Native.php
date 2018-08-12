<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Dom\Parser;

use Essence\Dom\Parser;
use Essence\Exception;
use Essence\Utility\Hash;
use DomDocument;
use DomNode;



/**
 *	Handles HTML related operations through DomDocument.
 *
 *	@package Essence.Dom.Parser
 */

class Native implements Parser {

	/**
	 *	{@inheritDoc}
	 */

	public function extractAttributes( $html, array $options ) {

		$Document = $this->_document( $html );
		$options = Hash::normalize( $options, [ ]);
		$data = [ ];

		foreach ( $options as $name => $required ) {
			$tags = $Document->getElementsByTagName( $name );
			$required = Hash::normalize(( array )$required, '' );
			$data[ $name ] = [ ];

			foreach ( $tags as $Tag ) {
				if ( $Tag->hasAttributes( )) {
					$attributes = $this->_extractAttributesFromTag(
						$Tag,
						$required
					);

					if ( !empty( $attributes )) {
						$data[ $name ][ ] = $attributes;
					}
				}
			}
		}

		return $data;
	}



	/**
	 *	Builds and returns a DomDocument from the given HTML source.
	 *
	 *	@param string $html HTML source.
	 *	@return DomDocument DomDocument.
	 */

	protected function _document( $html ) {

		$reporting = error_reporting( 0 );
		$html = $this->fixCharset( $html );
		$Document = DomDocument::loadHTML( $html );
		error_reporting( $reporting );

		if ( !$Document ) {
			throw new Exception( 'Unable to load HTML document.' );
		}

		return $Document;
	}



	/**
	 *	If necessary, fixes the given HTML's charset to work with the current
	 *	version of Libxml (used by DomDocument). Older versions of Libxml
	 *	recognize only
	 *
	 *      <META http-equiv="Content-Type" content="text/html; charset=UTF-8">
	 *
	 *  from HTML4, and not the new HTML5 form:
	 *
	 *      <meta charset="utf-8">
	 *
	 *  with the result that parsed strings can have funny characters.
	 *
	 *	@param string $html HTML source.
	 *	@return string the fixed HTML
	 *	@see "HTML5, character encodings and DOMDocument loadHTML and loadHTMLFile"
	 *	     http://www.glenscott.co.uk/blog/html5-character-encodings-and-domdocument-loadhtml-and-loadhtmlfile/
	 */

	protected function fixCharset( $html ) {
		// The fix is from https://github.com/glenscott/dom-document-charset/blob/master/DOMDocumentCharset.php
		if ( LIBXML_VERSION < 20800 && stripos($html, 'meta charset') !== false ) {
			$html = preg_replace( '/<meta charset=["\']?([^"\']+)"/i',
					              '<meta http-equiv="Content-Type" content="text/html; charset=$1"',
					              $html );
		}
		return $html;
	}



	/**
	 *	Extracts attributes from the given tag.
	 *
	 *	@param DOMNode $Tag Tag to extract attributes from.
	 *	@param array $required Required attributes.
	 *	@return array Extracted attributes.
	 */

	protected function _extractAttributesFromTag( DOMNode $Tag, array $required ) {

		$attributes = [ ];

		foreach ( $Tag->attributes as $name => $Attribute ) {
			if ( !empty( $required )) {
				if ( isset( $required[ $name ])) {
					$pattern = $required[ $name ];

					if ( $pattern && !preg_match( $pattern, $Attribute->value )) {
						return [ ];
					}
				} else {
					continue;
				}
			}

			$attributes[ $name ] = $Attribute->value;
		}

		$diff = array_diff_key( $required, $attributes );

		return empty( $diff )
			? $attributes
			: [ ];
	}
}
