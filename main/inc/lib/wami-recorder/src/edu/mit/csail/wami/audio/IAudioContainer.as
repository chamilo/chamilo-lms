/* 
* Copyright (c) 2011
* Spoken Language Systems Group
* MIT Computer Science and Artificial Intelligence Laboratory
* Massachusetts Institute of Technology
*
* Permission is hereby granted, free of charge, to any person
* obtaining a copy of this software and associated documentation
* files (the "Software"), to deal in the Software without
* restriction, including without limitation the rights to use, copy,
* modify, merge, publish, distribute, sublicense, and/or sell copies
* of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
* BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
* ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
* CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*/
package edu.mit.csail.wami.audio
{
	import flash.utils.ByteArray;
	
	/**
	 * There are a number of ways to store raw audio.  WAV is a container from
	 * Microsoft.  AU is a container from Sun Microsystems.  This interface
	 * helps us separate the container format from the audio format itself.
	 */
	public interface IAudioContainer
	{
		function toByteArray(format:AudioFormat, length:int = -1):ByteArray;

		/**
		 * If successful, the position is left at the first byte after
		 * the header.  If the bytes do not represent the expected container
		 * header null is returned and the position is returned to 0.
		 */
		function fromByteArray(bytes:ByteArray):AudioFormat;
		
		/**
		 * Some containers (e.g. WAV) require the length of the data to be specified,
		 * and thus are not amenable to streaming.  Others (e.g. AU) have well
		 * defined ways of dealing with data of unknown length.
		 */
		function isLengthRequired():Boolean;
	}
}