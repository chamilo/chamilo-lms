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
	import edu.mit.csail.wami.utils.External;
	import edu.mit.csail.wami.utils.StateListener;

	/**
	 * Translates audio events into Javascript callbacks.
	 */
	public class WamiListener implements StateListener
	{
		private var startCallback:String, finishedCallback:String, failedCallback:String;
		
		function WamiListener(startCallback:String, finishedCallback:String, failedCallback:String) 
		{
			this.startCallback = startCallback;
			this.finishedCallback = finishedCallback;
			this.failedCallback = failedCallback;
		}
		
		public function started():void
		{
			External.call(startCallback);
		}
		
		public function finished():void
		{
			External.call(finishedCallback);
		}
		
		public function failed(error:Error):void
		{
			External.call(failedCallback, error.message);
		}
	}
}