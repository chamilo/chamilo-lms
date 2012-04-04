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
package edu.mit.csail.wami.record
{	
	import edu.mit.csail.wami.utils.External;
	import edu.mit.csail.wami.utils.Pipe;
	import edu.mit.csail.wami.utils.StateListener;
	
	import flash.events.Event;
	import flash.events.HTTPStatusEvent;
	import flash.events.IOErrorEvent;
	import flash.events.ProgressEvent;
	import flash.events.SecurityErrorEvent;
	import flash.net.URLLoader;
	import flash.net.URLRequest;
	import flash.net.URLRequestMethod;
	import flash.utils.ByteArray;
	import flash.utils.setInterval;
	
	/**
	 * Write data and POST on close.
	 */
	public class SinglePost extends Pipe
	{	
		private var url:String;
		private var contentType:String = null;
		private var listener:StateListener;

		private var finished:Boolean = false;
		private var buffer:ByteArray = new ByteArray();
		private var timeoutMillis:int;
		
		public function SinglePost(url:String, type:String, timeoutMillis:int, listener:StateListener)
		{
			this.url = url;
			this.contentType = type;
			this.listener = listener;
			this.timeoutMillis = timeoutMillis;
		}
		
		override public function write(bytes:ByteArray):void
		{
			bytes.readBytes(buffer, buffer.length, bytes.bytesAvailable);
		}
		
		override public function close():void 
		{
			buffer.position = 0;
			External.debug("POST " + buffer.length + " bytes of type " + contentType);
			buffer.position = 0;
			var loader:URLLoader = new URLLoader();
			
			loader.addEventListener(Event.COMPLETE, completeHandler);
			loader.addEventListener(Event.OPEN, openHandler);
			loader.addEventListener(ProgressEvent.PROGRESS, progressHandler);
			loader.addEventListener(SecurityErrorEvent.SECURITY_ERROR, securityErrorHandler);
			loader.addEventListener(HTTPStatusEvent.HTTP_STATUS, httpStatusHandler);
			loader.addEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler);
			
			var request:URLRequest = new URLRequest(url);
			request.method = URLRequestMethod.POST;
			request.contentType = contentType;
			request.data = buffer;
			if (buffer.bytesAvailable == 0) {
				External.debug("Note that flash does a GET request if bytes.length == 0");
			}

			try {
				loader.load(request);
			} catch (error:Error) {
				if (listener) 
				{
					listener.failed(error);
				}
			}
			
			super.close();
		}
		
		private function completeHandler(event:Event):void {
			External.debug("POST: completeHandler");
			var loader:URLLoader = URLLoader(event.target);
			loader.removeEventListener(Event.COMPLETE, completeHandler);
			loader.removeEventListener(Event.OPEN, openHandler);
			loader.removeEventListener(ProgressEvent.PROGRESS, progressHandler);
			loader.removeEventListener(SecurityErrorEvent.SECURITY_ERROR, securityErrorHandler);
			loader.removeEventListener(HTTPStatusEvent.HTTP_STATUS, httpStatusHandler);
			loader.removeEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler);
			listener.finished();
			finished = true;
		}
		
		private function openHandler(event:Event):void {
			External.debug("POST openHandler: " + event);
			setInterval(checkFinished, timeoutMillis);
		}
		
		private function checkFinished():void {
			if (!finished && listener) {
				listener.failed(new Error("POST is taking too long."));
			}
			finished = true;
		}
		
		private function progressHandler(event:ProgressEvent):void {
			External.debug("POST progressHandler loaded:" + event.bytesLoaded + " total: " + event.bytesTotal);
		}
		
		private function securityErrorHandler(event:SecurityErrorEvent):void {
			if (!finished && listener) 
			{
				listener.failed(new Error("Record security error: " + event.errorID));
			}
			finished = true;
		}
		
		private function httpStatusHandler(event:HTTPStatusEvent):void {
			// Apparently the event.status can be zero in some environments where nothing is wrong:
			// http://johncblandii.com/2008/04/flex-3-firefox-beta-3-returns-0-for-http-status-codes.html
			if (!finished && listener && event.status != 200 && event.status != 0) 
			{
				listener.failed(new Error("HTTP status error: " + event.status));
			}
			finished = true;
		}
		
		private function ioErrorHandler(event:IOErrorEvent):void {
			if (!finished && listener) 
			{
				listener.failed(new Error("Record IO error: " + event.errorID));
			}
			finished = true;
		}
	}		
}