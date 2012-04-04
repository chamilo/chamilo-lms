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
package edu.mit.csail.wami.utils
{
	import flash.utils.ByteArray;

	/**
	 * Accumulates the written bytes into an array.  If a max number of bytes 
	 * is specified this will act as a circular buffer. 
	 */
	public class BytePipe extends Pipe
	{
		private var buffer:ByteArray = new ByteArray();
		private var done:Boolean = false;
		private var maxBytes:uint;
		private var start:uint = 0;
		
		function BytePipe(maxBytes:uint = uint.MAX_VALUE):void 
		{
			this.maxBytes = maxBytes;
		}
		
		override public function write(bytes:ByteArray):void
		{
			if (maxBytes <= 0) return;  // no room!
			
			var available:uint = Math.min(maxBytes - buffer.length, bytes.bytesAvailable);
			if (available > 0) 
			{
				bytes.readBytes(buffer, buffer.length, available);
			}
			
			while (bytes.bytesAvailable) 
			{
				// Read bytes into the circular buffer.
				available = Math.min(buffer.length - start, bytes.bytesAvailable);
				bytes.readBytes(buffer, start, available);
				start = (start + available) % maxBytes;
			}
	
			buffer.position = 0;
		}
		
		override public function close():void
		{
			super.close();
			done = true;
		}
		
		public function getByteArray():ByteArray
		{
			if (!done)
			{
				throw new Error("BytePipe should be done before accessing byte array.");
			}
			
			var array:ByteArray = new ByteArray();
			buffer.position = start;
			buffer.readBytes(array);
			buffer.position = 0;
			if (start > 0) 
			{
				buffer.readBytes(array, array.length, start);
			}
			array.position = 0;
			return array;
		}
	}
}