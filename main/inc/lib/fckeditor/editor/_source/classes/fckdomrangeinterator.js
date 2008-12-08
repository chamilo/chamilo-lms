/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2007 Frederico Caldeira Knabben
 *
 * == BEGIN LICENSE ==
 *
 * Licensed under the terms of any of the following licenses at your
 * choice:
 *
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 *
 * This class can be used to interate through nodes inside a range.
 *
 * During interation, the provided range can become invalid, due to document
 * mutations, so CreateBookmark() used to restore it after processing, if
 * needed.
 */

var FCKDomRangeInterator = function( range )
{
	/**
	 * The FCKDomRange object that marks the interation boundaries.
	 */
	this.Range = range ;

	/**
	 * Indicates that <br> elements must be used as paragraph boundaries.
	 */
	this.ForceBrBreak = false ;
	
	/**
	 * Guarantees that the interator will always return "real" block elements.
	 * If "false", elements like <li>, <th> and <td> are returned. If "true", a
	 * dedicated block element block element will be created inside those
	 * elements to hold the selected content.
	 */
	this.EnforceRealBlocks = false ;
}

FCKDomRangeInterator.CreateFromSelection = function( targetWindow )
{
	var range = new FCKDomRange( targetWindow ) ;
	range.MoveToSelection() ;
	return new FCKDomRangeInterator( range ) ;
}

FCKDomRangeInterator.prototype =
{
	/**
	 * Get the next paragraph element. It automatically breaks the document
	 * when necessary to generate block elements for the paragraphs.
	 */
	GetNextParagraph : function()
	{
		var block ;
		var range ;
		var isLast ;

		var boundarySet = this.ForceBrBreak ? FCKListsLib.ListBoundaries : FCKListsLib.BlockBoundaries ;

		// This is the first interaction. Let's initialize it.
		if ( !this._LastNode )
		{
			var range = this.Range.Clone() ;
			range.Expand( this.ForceBrBreak ? 'list_contents' : 'block_contents' ) ;

			this._NextNode = range.StartNode ;
			this._LastNode = range.EndNode ;

			// Let's reuse this variable.
			range = null ;
		}

		var currentNode = this._NextNode ;
		var lastNode = this._LastNode ;

		while ( currentNode )
		{
			// closeRange indicates that a paragraph boundary has been found,
			// so the range can be closed.
			var closeRange = false ;

			// includeNode indicates that the current node is good to be part
			// of the range. By default, any non-element node is ok for it.
			var includeNode = ( currentNode.nodeType != 1 ) ;

			var continueFromSibling = false ;

			// If it is an element node, let's check if it can be part of the
			// range.
			if ( !includeNode )
			{
				var nodeName = currentNode.nodeName.toLowerCase() ;

				if ( boundarySet[ nodeName ] )
				{
					// <br> boundaries must be part of the range. It will
					// happen only if ForceBrBreak.
					if ( nodeName == 'br' )
						includeNode = true ;
					else if ( !range && currentNode.childNodes.length == 0 && nodeName != 'hr' )
					{
						// If we have found an empty block, and haven't started
						// the range yet, it means we must return this block.
						block = currentNode ;
						break ;
					}

					closeRange = true ;
				}
				else
				{
					// If we have child nodes, let's check them.
					if ( currentNode.firstChild )
					{
						// If we don't have a range yet, let's start it.
						if ( !range )
						{
							range = new FCKDomRange( this.Range.Window ) ;
							range.SetStart( currentNode, 3, true ) ;
						}

						currentNode = currentNode.firstChild ;
						continue ;
					}
					includeNode = true ;
				}
			}
			else if ( currentNode.nodeType == 3 )
			{
				// Ignore normal whitespaces (i.e. not including &nbsp; or
				// other unicode whitespaces) before/after a block node.
				if ( /^[\r\n\t ]+$/.test( currentNode.nodeValue ) )
					includeNode = false ;
			}

			// The current node is good to be part of the range and we are
			// starting a new range, initialize it first.
			if ( includeNode && !range )
			{
				range = new FCKDomRange( this.Range.Window ) ;
				range.SetStart( currentNode, 3, true ) ;
			}

			// The last node has been found.		
			isLast = ( currentNode == lastNode ) ;

			// If we are in an element boundary, let's check if it is time
			// to close the range, otherwise we include the parent within it.
			if ( range && !closeRange )
			{
				var parentNode = currentNode.parentNode ;
				while ( currentNode == parentNode.lastChild && !isLast )
				{
					if ( boundarySet[ parentNode.nodeName.toLowerCase() ] )
					{
						closeRange = true ;
						break ;
					}

					currentNode = parentNode ;
					continueFromSibling = true ;
				}
			}

			// Now finally include the node.
			if ( includeNode )
				range.SetEnd( currentNode, 4, true ) ;

			// We have found a block boundary. Let's close the range and move out of the
			// loop.
			if ( ( closeRange && range ) || isLast )
			{
				if ( range )
					range._UpdateElementInfo() ;

				break ;
			}

			currentNode = FCKDomTools.GetNextSourceNode( currentNode, continueFromSibling ) ;
		}

		// Now, based on the processed range, look for (or create) the block to be returned.
		if ( !block )
		{
			// If no range has been found, this is the end.
			if ( !range )
			{
				this._NextNode = null ;
				return null ;
			}

			block = range.StartBlock ;

			if ( !block
				&& !this.EnforceRealBlocks
				&& range.StartBlockLimit.nodeName.IEquals( 'DIV', 'TH', 'TD' )
				&& range.CheckStartOfBlock()
				&& range.CheckEndOfBlock() )
			{
				block = range.StartBlockLimit ;
			}
			else if ( !block || ( this.EnforceRealBlocks && block.nodeName.toLowerCase() == 'li' ) )
			{
				// Create the fixed block.
				block = this.Range.Window.document.createElement( FCKConfig.EnterMode == 'p' ? 'p' : 'div' ) ;

				// Move the contents of the temporary range to the fixed block.
				range.ExtractContents().AppendTo( block ) ;
				FCKDomTools.TrimNode( block ) ;

				// Insert the fixed block into the DOM.
				range.InsertNode( block ) ;
				
				// When fixing a block, we can safely remove any remaining <br>
				// before of it.
				var previousSibling = block.previousSibling ;
				if ( previousSibling && previousSibling.nodeType == 1 && previousSibling.nodeName.toLowerCase() == 'br' )
					previousSibling.parentNode.removeChild( previousSibling ) ;
			}
			else if ( block.nodeName.toLowerCase() != 'li' )
			{
				// If the range doesn't includes the entire contents of the
				// block, we must split it, isolating the range in a dedicated
				// block.
				if ( !range.CheckStartOfBlock() || !range.CheckEndOfBlock() )
				{
					// The resulting block will be a clone of the current one.
					block = block.cloneNode( false ) ;

					// Extract the range contents, moving it to the new block.
					range.ExtractContents().AppendTo( block ) ;
					FCKDomTools.TrimNode( block ) ;

					// Split the block. At this point, the range will be in the
					// right position for our intents.
					range.SplitBlock() ;

					// Insert the new block into the DOM.
					range.InsertNode( block ) ;
				}
			}
			else if ( !isLast )
			{
				// LIs are returned as is, with all their children (due to the
				// nested lists). But, the next node is the node right after
				// the current range, which could be an <li> child (nested
				// lists) or the next sibling <li>.
				
				this._NextNode = FCKDomTools.GetNextSourceNode( range.EndNode, true ) ;
				return block ;
			}
		}

		// Get a reference for the next element. This is important because the
		// above block can be removed or changed, so we can rely on it for the
		// next interation.
		this._NextNode = isLast ? null : FCKDomTools.GetNextSourceNode( block, true ) ;

		return block ;
	}
} ;
