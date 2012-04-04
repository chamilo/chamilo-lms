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
	import edu.mit.csail.wami.utils.External;
	
	import flash.utils.ByteArray;
	import flash.utils.Endian;
	
	/**
	 * This container is better for streaming, because it explicitly
	 * says what to do when the length of the audio is unknown.  It's typically
	 * associated with mu-law compression (which wouldn't be too hard too implement)
	 * but here we're using linear PCM.
	 */
	public class AuContainer implements IAudioContainer
	{
		public function isLengthRequired():Boolean {
			return false;
		}
		
		public function toByteArray(format:AudioFormat, length:int = -1):ByteArray
		{
			var dataLength:uint = 0xffffffff;
			if (length > -1) 
			{
				dataLength = length;
			}
			
			if (format.endian != Endian.BIG_ENDIAN) 
			{
				throw new Error("AU is a container for big endian data");
			}
			// http://en.wikipedia.org/wiki/Au_file_format			
			var header:ByteArray = new ByteArray();
			header.endian = format.endian;
			header.writeUTFBytes(".snd");
			header.writeInt(24);               // Data offset
			header.writeInt(dataLength);
			
			var bits:uint = getEncodingFromBits(format);
			header.writeInt(bits);
			header.writeInt(format.rate);	
			header.writeInt(format.channels);
			header.position = 0;
			External.debugBytes(header);
			return header;
		}	
		
		private function getEncodingFromBits(format:AudioFormat):uint
		{
			if (format.bits == 16) 
			{
				return 3;
			}
			else if (format.bits == 24) 
			{
				return 4;
			}
			else if (format.bits == 32) 
			{
				return 5;
			}
			
			throw new Error("Bits not supported");
		}
		
		private function getBitsFromEncoding(encoding:uint):uint
		{
			if (encoding == 3) 
			{
				return 16;
			}
			else if (encoding == 4) 
			{
				return 24;
			}
			else if (encoding == 5) 
			{
				return 32;
			}
			
			throw new Error("Encoding not supported: " + encoding);
		}
		
		public function fromByteArray(header:ByteArray):AudioFormat
		{
			if (header.bytesAvailable < 24)
			{
				return notAu(header, "Header not yet long enough for Au");
			}
			
			var b:ByteArray = new ByteArray();
			header.readBytes(b, 0, 24);
			External.debugBytes(b);
			header.position = 0;
			
			header.endian = Endian.BIG_ENDIAN;     // Header is big-endian 

			var magic:String = header.readUTFBytes(4);
			if (magic != ".snd")
			{
				return notAu(header, "Not an AU header, first bytes should be .snd");
			}
			
			var dataOffset:uint = header.readInt();  
			var dataLength:uint = header.readInt();  

			if (header.bytesAvailable < dataOffset - 12)
			{
				return notAu(header, "Header of length " + header.bytesAvailable + " not long enough yet to include offset of length " + dataOffset);
			}
			
			var encoding:uint = header.readInt();

			var bits:uint;
			try {
				bits = getBitsFromEncoding(encoding);
			} catch (e:Error) {
				return notAu(header, e.message);
			}

			var rate:uint = header.readInt();
			var channels:uint = header.readInt();

			header.position = dataOffset;
			
			var format:AudioFormat;
			try 
			{
				format = new AudioFormat(rate, channels, bits, Endian.BIG_ENDIAN);
			} catch (e:Error) 
			{
				return notAu(header, e.message);
			}
			
			return format;
		}
		
		private function notAu(header:ByteArray, msg:String):AudioFormat
		{
			External.debug("Not Au: " + msg);
			header.position = 0;
			return null;
		}
	}
}