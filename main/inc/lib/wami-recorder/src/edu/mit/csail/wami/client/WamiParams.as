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
package edu.mit.csail.wami.client
{	
	import edu.mit.csail.wami.audio.AudioFormat;
	import edu.mit.csail.wami.utils.External;
	
	import flash.media.Microphone;
	import flash.utils.ByteArray;
	import flash.utils.Endian;
	
	/**
	 * A class documents the possible parameters and sets a few defaults.
	 * The defaults are set up to stream to localhost.
	 */
	public class WamiParams
	{
		private var mic:Microphone;
		
		// Show the debug interface.
		public var visible:Boolean = true;

		// Append this many milliseconds of audio before 
		// and after calls to startRecording/stopRecording.
		public var paddingMillis:uint = 250;

		// Send the audio using multiple HTTP Posts.
		public var stream:Boolean = false;
				
		// The URLs used in the debugging interface.
		public var testRecordUrl:String = "https://wami-recorder.appspot.com/audio";
		public var testPlayUrl:String = "https://wami-recorder.appspot.com/audio";

		// Callbacks for loading the client.
		public var loadedCallback:String;
		public var format:AudioFormat;
	
		public function WamiParams(params:Object):void
		{
			mic = Microphone.getMicrophone();
			
			External.addCallback("setSettings", setSettings);
			External.addCallback("getSettings", getSettings);
			
			if (params.stream != undefined)
			{
				stream = params.stream == "true";
			}
			
			if (params.visible != undefined) 
			{
				visible = params.visible == "true";
			}
			
			if (params.console != undefined) {
				External.debugToConsole = params.console == "true";
			}
			
			// Override to allow recording at 8000 and 16000 as well.
			// Note that playback at these sample-rates will be sped up.
			if (params.allrates != undefined) {
				AudioFormat.allrates = params.allrates == "true";
			}
			
			loadedCallback = params.loadedCallback;

			var rate:uint = 22050;
			if (params.rate != undefined) 
			{
				rate = uint(params.rate);
			}
			format = new AudioFormat(rate, 1, 16, Endian.LITTLE_ENDIAN);
		}
		
		public function getMicrophone():Microphone {
			return mic;
		}
		
		// Settings (including microphone security) are passed back here.
		internal function getSettings():Object
		{
			var json:Object = {
				"container" : (stream) ? "au" : "wav",
				"encoding" : "pcm",
				"signed" : true,
				"sampleSize" : format.bits,
				"bigEndian" : format.endian == Endian.BIG_ENDIAN,
				"sampleRate" : format.rate,
				"numChannels" : format.channels,
				"interleaved" : true,
				"microphone" : {
					"granted" : (mic != null && !mic.muted)
				}
			};
			
			return json;
		}
		
		internal function setSettings(json:Object):void
		{	
			if (json) 
			{
				// For now the type also specifies streaming or not.
				if (json.container == "au")
				{
					stream = true;
				}
				else if (json.container == "wav") 
				{
					stream = false;
				}

				if (json.encoding) 
				{
					throw new Error("Encodings such as mu-law could be implemented.");	
				}
				
				if (json.signed)
				{
					throw new Error("Not implemented yet.");	
				}
				
				if (json.bigEndian) 
				{
					throw new Error("Automatically determined.");
				}
				
				if (json.numChannels) 
				{
					format.channels = json.numChannels;	
				}
				
				if (json.sampleSize) 
				{
					format.bits = json.sampleSize;	
				}
				
				if (json.interleaved)
				{
					throw new Error("Always true.");
				}
				
				if (json.microphone)
				{
					throw new Error("Only the user can change the microphone security settings.");	
				}
				
				if (json.sampleRate) 
				{
					format.rate = json.sampleRate;
				}
				
			}
		}
	}
}