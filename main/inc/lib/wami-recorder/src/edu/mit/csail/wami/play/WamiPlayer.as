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
package edu.mit.csail.wami.play
{
	import edu.mit.csail.wami.audio.AuContainer;
	import edu.mit.csail.wami.audio.DecodePipe;
	import edu.mit.csail.wami.audio.IAudioContainer;
	import edu.mit.csail.wami.audio.WaveContainer;
	import edu.mit.csail.wami.utils.BytePipe;
	import edu.mit.csail.wami.utils.External;
	import edu.mit.csail.wami.utils.Pipe;
	import edu.mit.csail.wami.utils.StateListener;
	
	import flash.events.Event;
	import flash.events.HTTPStatusEvent;
	import flash.events.IOErrorEvent;
	import flash.events.ProgressEvent;
	import flash.events.SampleDataEvent;
	import flash.events.SecurityErrorEvent;
	import flash.media.Sound;
	import flash.media.SoundChannel;
	import flash.net.URLLoader;
	import flash.net.URLLoaderDataFormat;
	import flash.net.URLRequest;
	import flash.net.URLRequestMethod;
	import flash.utils.ByteArray;
	
	public class WamiPlayer implements IPlayer
	{
		private var currentChannel:SoundChannel = null;
		private var currentAudio:ByteArray;
		private var listener:StateListener;
		
		public function start(url:String, listener:StateListener):void
		{
			this.listener = listener;
			var loader:URLLoader = new URLLoader();
			loader.dataFormat = URLLoaderDataFormat.BINARY;
			
			loader.addEventListener(Event.COMPLETE, completeHandler);
			loader.addEventListener(Event.OPEN, openHandler);
			loader.addEventListener(ProgressEvent.PROGRESS, progressHandler);
			loader.addEventListener(SecurityErrorEvent.SECURITY_ERROR, securityErrorHandler);
			loader.addEventListener(HTTPStatusEvent.HTTP_STATUS, httpStatusHandler);
			loader.addEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler);
			
			var request:URLRequest = new URLRequest(url);
			request.method = URLRequestMethod.GET;
			
			try {
				loader.load(request);
			} catch (error:Error) {
				listener.failed(error);
			}
			
			function completeHandler(event:Event):void {
				listener.started();
				
				loader.removeEventListener(Event.COMPLETE, completeHandler);
				loader.removeEventListener(Event.OPEN, openHandler);
				loader.removeEventListener(ProgressEvent.PROGRESS, progressHandler);
				loader.removeEventListener(SecurityErrorEvent.SECURITY_ERROR, securityErrorHandler);
				loader.removeEventListener(HTTPStatusEvent.HTTP_STATUS, httpStatusHandler);
				loader.removeEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler)
				
				play(loader.data);
			}
			
			function openHandler(event:Event):void {
				External.debug("openHandler: " + event);
			}
			
			function progressHandler(event:ProgressEvent):void {
				//External.debug("progressHandler loaded:" + event.bytesLoaded + " total: " + event.bytesTotal);
			}
			
			function securityErrorHandler(event:SecurityErrorEvent):void {
				listener.failed(new Error("Security error while playing: " + event.errorID));
			}
			
			function httpStatusHandler(event:HTTPStatusEvent):void {
				External.debug("httpStatusHandler: " + event);
			}
			
			function ioErrorHandler(event:IOErrorEvent):void {
				listener.failed(new Error("IO error while playing: " + event.errorID));
			}	
		}
		
		public function stop():void
		{
			if (currentChannel != null) 
			{
				External.debug("Stop playing.");
				currentChannel.removeEventListener(Event.SOUND_COMPLETE, stop);
				currentChannel.stop();
				External.debug("Listener finished.");
				listener.finished();
				currentChannel = null;
			}
		}
		
		public function level():int
		{
			if (currentChannel != null) {
				return 100 * ((currentChannel.leftPeak + currentChannel.rightPeak) / 2.0);
			}
			
			return 0;
		}
		
		protected function play(audio:ByteArray):void
		{
			stop();  // Make sure we're stopped
			
			var containers:Vector.<IAudioContainer> = new Vector.<IAudioContainer>();
			containers.push(new WaveContainer());
			containers.push(new AuContainer());

			External.debug("Playing audio of " + audio.length + " bytes.");
			var decoder:Pipe = new DecodePipe(containers);
			var pipe:BytePipe = new BytePipe();
			decoder.setSink(pipe);
			decoder.write(audio);
			decoder.close();
			
			currentAudio = pipe.getByteArray();
			External.debug("Playing audio with " + currentAudio.length/4 + " samples.");
			
			var sound:Sound = new Sound();
			sound.addEventListener(SampleDataEvent.SAMPLE_DATA, handleSampleEvent);

			currentChannel = sound.play();
			currentChannel.addEventListener(Event.SOUND_COMPLETE, function(event:Event):void {
				sound.removeEventListener(SampleDataEvent.SAMPLE_DATA, handleSampleEvent);
				stop();
			});
		}
		
		private function handleSampleEvent(event:SampleDataEvent):void
		{
			if (currentAudio == null) return;
			
			var MAX_SAMPLES_PER_EVENT:uint = 4000;
			var count:uint = 0;
			// External.debug("Audio " + currentAudio.bytesAvailable +  " " + event.data.endian);
			while (currentAudio.bytesAvailable && count < MAX_SAMPLES_PER_EVENT) 
			{
				event.data.writeFloat(currentAudio.readFloat());
				event.data.writeFloat(currentAudio.readFloat());
				count += 1;
			}
		}
	}
}