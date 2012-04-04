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
	import edu.mit.csail.wami.audio.AudioFormat;
	import edu.mit.csail.wami.audio.IAudioContainer;
	import edu.mit.csail.wami.utils.External;
	import edu.mit.csail.wami.utils.Pipe;
	
	import flash.utils.ByteArray;
	import flash.utils.Endian;
	
	/**
	 * Convert WAVE data coming in to the float-based format flash uses.
	 */
	public class DecodePipe extends Pipe
	{
		private var format:AudioFormat;
		private var header:ByteArray = new ByteArray();
		private var containers:Vector.<IAudioContainer>;
		
		public function DecodePipe(containers:Vector.<IAudioContainer>) {
			if (containers.length == 0) {
				throw new Error("Must have at least one container.");
			}
			this.containers = containers;
		}
		
		override public function write(bytes:ByteArray):void
		{
			if (format == null) 
			{
				// Try to get header by parsing from each container
				bytes.readBytes(header, header.length, bytes.length);
				for each (var container:IAudioContainer in containers) {
					format = container.fromByteArray(header);
					if (format != null) {
						// Put the leftover bytes back
						bytes = new ByteArray();
						header.readBytes(bytes); 
						External.debug("Format: " + format);
						break;
					}
				}
			}
			
			if (format != null && bytes.bytesAvailable)
			{
				bytes.endian = format.endian;
				super.write(decode(bytes));
			}
		}
		
		private function decode(bytes:ByteArray):ByteArray
		{
			var decoded:ByteArray = new ByteArray();
			while (bytes.bytesAvailable)
			{
				var sample1:Number = getSample(bytes);
				var sample2:Number = sample1;
				if (format.channels == 2)
				{
					sample2 = getSample(bytes);
				}

				// cheap way to upsample
				var repeat:uint = 44100 / format.rate;
				while (repeat-- > 0) {
					decoded.writeFloat(sample1);
					decoded.writeFloat(sample2);
				}
			}
			decoded.position = 0;
			return decoded;
		}
		
		private function getSample(bytes:ByteArray):Number
		{
			var sample:Number;
			
			if (format.bits == 8)
			{
				sample = bytes.readByte()/0x7f;
			}
			else if (format.bits == 16)
			{
				sample = bytes.readShort()/0x7fff;
			}
			else if (format.bits == 32)
			{
				sample = bytes.readInt()/0x7fffffff;
			}
			else
			{
				throw new Error("Unsupported bits per sample: " + format.bits);
			}
			
			return sample;
		}
	}
}