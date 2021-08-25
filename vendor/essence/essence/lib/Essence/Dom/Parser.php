<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Dom;



/**
 *	Handles HTML related operations.
 *
 *	@package Essence.Dom
 */

interface Parser {

	/**
	 *	Extracts tags attributes from the given HTML document.
	 *
	 *	Getting all attributes of all img tags in the document:
	 *
	 *	@code
	 *	$attributes = Parser::extractAttributes( $html, [ 'img' ]);
	 *	@endcode
	 *
	 *	Getting src attribute of all img tags in the document:
	 *	(if a tag doesn't have the src attribute, it will not be taken into
	 *	account)
	 *
	 *	@code
	 *	$attributes = Parser::extractAttributes( $html, [ 'img' => 'src' ]);
	 *	@endcode
	 *
	 *	Getting src and alt attributes of all img tags in the document:
	 *	(if a tag doesn't have the src or alt attribute, it will not be taken
	 *	into account)
	 *
	 *	@code
	 *	$attributes = Parser::extractAttributes( $html, [
	 *		'img' => [
	 *			'src',
	 *			'alt'
	 *		]
	 *	]);
	 *	@endcode
	 *
	 *	Getting src attribute of all img tags in the document, where their
	 *	src attribute matches a pattern:
	 *	(if the src attribute of a tag doesn't match the pattern, the tag will
	 *	not be taken into account)
	 *
	 *	@code
	 *	$attributes = Parser::extractAttributes( $html, [
	 *		'img' => [
	 *			'src' => '/foo/i',
	 *			'alt'
	 *		]
	 *	]);
	 *	@endcode
	 *
	 *	Example result:
	 *
	 *	@code
	 *	$attributes = Parser::extractAttributes( $html, [
	 *		'img' => 'src',
	 *		'a' => [
	 *			'href' => '/foo/i',
	 *			'alt'
	 *		]
	 *	]);
	 *
	 *	=> [
	 *		'img' => [
	 *			[ 'src' => 'http://www.website.com/foo.png' ],
	 *			[ 'src' => 'http://www.website.com/bar.png' ],
	 *		],
	 *		'a' => [
	 *			[
	 *				'href' => 'http://www.foo.com',
	 *				'alt' => 'foo'
	 *			]
	 *		]
	 *	]
	 *	@endcode
	 *
	 *	@param string $html An HTML document.
	 *	@param array $options Options defining which attributes to extract.
	 *	@return array Extracted attributes indexed by tag name.
	 */

	public function extractAttributes( $html, array $options );

}
