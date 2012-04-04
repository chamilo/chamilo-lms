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
	 * This class builds a WAVE header formt the audio format.
	 */
	public class WaveContainer implements IAudioContainer
	{
		public function isLengthRequired():Boolean {
			return true;
		}
		
		public function toByteArray(audioFormat:AudioFormat, length:int = -1):ByteArray
		{
			// https://ccrma.stanford.edu/courses/422/projects/WaveFormat/
			var id:String = (audioFormat.endian == Endian.LITTLE_ENDIAN) ? "RIFF" : "RIFX";
			var bytesPerSample:uint = audioFormat.channels*audioFormat.bits/8;
			
			var header:ByteArray = new ByteArray();
			
			// Little-endian is generally the way to go for WAVs
			header.endian = Endian.LITTLE_ENDIAN;
			header.writeUTFBytes(id);
			header.writeInt(length > 0 ? 36 + length : 0);
			header.writeUTFBytes("WAVE");
			header.writeUTFBytes("fmt ");
			header.writeInt(16);
			header.writeShort(1);
			header.writeShort(audioFormat.channels);
			header.writeInt(audioFormat.rate);
			header.writeInt(audioFormat.rate*bytesPerSample);
			header.writeShort(bytesPerSample);
			header.writeShort(audioFormat.bits);
			header.writeUTFBytes('data');
			header.writeInt(length);
			header.position = 0;
			return header;
		}	
		
		public function fromByteArray(header:ByteArray):AudioFormat
		{
			if (header.bytesAvailable < 44) {
				var msg:String = "This header is not yet long enough ";
				msg += "(need 44 bytes only have " + header.bytesAvailable + ")."
				return notWav(header, msg);
			}
			
			var endian:String = Endian.LITTLE_ENDIAN;
			var chunkID:String = header.readUTFBytes(4);     
			if (chunkID == "RIFX")
			{
				endian = Endian.BIG_ENDIAN;
			}
			else if (chunkID != "RIFF")
			{
				return notWav(header, "Does not look like a WAVE header: " + chunkID);
			}
			
			header.endian = Endian.LITTLE_ENDIAN;                 // Header is little-endian 
			var totalLength:uint = header.readInt() + 8;
			var waveFmtStr:String = header.readUTFBytes(8);       // "WAVEfmt "
			if (waveFmtStr != "WAVEfmt ") 
			{
				return notWav(header, "RIFF header, but not a WAV.");
			}
			var subchunkSize:uint = header.readUnsignedInt();     // 16
			var audioFormat:uint = header.readShort();            // 1
			if (audioFormat != 1) {
				return notWav(header, "Currently we only support linear PCM");
			}
			var channels:uint = header.readShort();
			var rate:uint = header.readInt();
			var bps:uint = header.readInt();
			var bytesPerSample:uint = header.readShort();
			var bits:uint = header.readShort();
			var dataStr:String = header.readUTFBytes(4);          // "data"
			var length:uint = header.readInt();
			
			var format:AudioFormat;
			try 
			{
				format = new AudioFormat(rate, channels, bits, endian);
			} catch (e:Error) 
			{
				return notWav(header, e.message);
			}
			return format;
		}

		/**
		 * Emit error message for debugging, reset the ByteArray and 
		 * return null.
		 */
		private function notWav(header:ByteArray, msg:String):AudioFormat
		{
			External.debug("Not WAV: " + msg);
			header.position = 0;
			return null;
		}
	}
}