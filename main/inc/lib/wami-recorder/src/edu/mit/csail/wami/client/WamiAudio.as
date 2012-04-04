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
	import edu.mit.csail.wami.play.IPlayer;
	import edu.mit.csail.wami.play.WamiPlayer;
	import edu.mit.csail.wami.record.IRecorder;
	import edu.mit.csail.wami.record.WamiRecorder;
	import edu.mit.csail.wami.utils.External;
	
	import flash.display.MovieClip;

	public class WamiAudio extends MovieClip
	{
		private var recorder:IRecorder;
		private var player:IPlayer;
		
		private var checkSettingsIntervalID:int = 0;
		private var checkSettingsInterval:int = 1000;

		function WamiAudio(params:WamiParams)
		{
			recorder = new WamiRecorder(params.getMicrophone(), params);
			player = new WamiPlayer();
			
			External.addCallback("startListening", startListening);
			External.addCallback("stopListening", stopListening);
			External.addCallback("startRecording", startRecording);
			External.addCallback("stopRecording",stopRecording);
			External.addCallback("getRecordingLevel", getRecordingLevel);
			
			External.addCallback("startPlaying",startPlaying);
			External.addCallback("stopPlaying",stopPlaying);
			External.addCallback("getPlayingLevel", getPlayingLevel);
		}
		
		internal function startPlaying(url:String, 
									   startedCallback:String = null, 
									   finishedCallback:String = null,
									   failedCallback:String = null):void
		{
			recorder.stop(true);
			player.start(url, new WamiListener(startedCallback, finishedCallback, failedCallback));
		}
		
		internal function stopPlaying():void
		{
			player.stop();
		}
		
		internal function getPlayingLevel():int
		{
			return player.level();
		}
		
		private function startListening(paddingMillis:uint = 200):void
		{
			recorder.listen(paddingMillis);
		}
		
		private function stopListening():void
		{
			recorder.unlisten();
		}
		
		internal function startRecording(url:String,
										 startedCallback:String = null, 
										 finishedCallback:String = null, 
										 failedCallback:String = null):void
		{
			recorder.start(url, new WamiListener(startedCallback, finishedCallback, failedCallback));
		}
		
		internal function stopRecording():void
		{
			recorder.stop();
		}
		
		internal function getRecordingLevel():int
		{
			return recorder.level();
		}
	}
}

