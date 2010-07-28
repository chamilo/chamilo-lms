/*
	JavaScriptProxy.as
	
	Part of the Flash / JavaScript Integration Kit
	http://www.macromedia.com/go/flashjavascript
	
	Created by:
	
	Mike Chambers
	http://weblogs.macromedia.com/mesh/
	mesh@macromedia.com
	
	Christian Cantrell
	http://weblogs.macromedia.com/cantrell/
	cantrell@macromedia.com
	
	----
	Macromedia(r) Flash(r)./ JavaScript Integration Kit License


	Copyright (c) 2005 Macromedia, inc. All rights reserved.

	Redistribution and use in source and binary forms, with or without modification,
	are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice, this 
	list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright notice, 
	this list of conditions and the following disclaimer in the documentation and/or
	other materials provided with the distribution.

	3. The end-user documentation included with the redistribution, if any, must 
	include the following acknowledgment:

    "This product includes software developed by Macromedia, Inc. 
    (http://www.macromedia.com)."

	Alternately, this acknowledgment may appear in the software itself, if and 
	wherever such third-party acknowledgments normally appear.

	4. The name Macromedia must not be used to endorse or promote products derived
	from this software without prior written permission. For written permission,
	please contact devrelations@macromedia.com.

	5. Products derived from this software may not be called "Macromedia" or 
	“Macromedia Flash”, nor may "Macromedia" or “Macromedia Flash” appear in their
	 name.

	THIS SOFTWARE IS PROVIDED "AS IS" AND ANY EXPRESSED OR IMPLIED WARRANTIES, 
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND 
	FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL MACROMEDIA OR
	ITS CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, 
	EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT
	OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
	STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
	OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH 
	DAMAGE.
	
*/

//import the serializer class
import com.macromedia.javascript.JavaScriptSerializer;

class com.macromedia.javascript.JavaScriptProxy
{
	private var instance:Object;
	private var lcId:String;
	private var receiving_lc:LocalConnection
	
	
	/*
		Constructor for Class
		Public
		
		Takes two arguments.
		
		lcId : REQUIRED : an id passed in from HTML / Javascript that is used to 
				communicate with the gateway swf. The same id must be passed into 
				the gateway swf.		
		
		instance : the object / class instance that function call will be proxied to.
					This is required if function calls will be made from JavaScript 
					to Flash
						
	*/
	function JavaScriptProxy(lcId:String, instance:Object)
	{
		
		//if either argument is undefined, JavaScript to Flash calls won't work.
		//So we just return.
		//
		//Flash to JavaScript calls will still work
		if(lcId == undefined || instance == undefined)
		{
			return;
		}

		this.instance = instance;

		this.lcId = lcId;
		
		receiving_lc = new LocalConnection();
		
		//the incoming function call will occur in the scope of receiving_lc, so we have
		//to set a property to let us get back to the correct scope.
		receiving_lc.controller = this;
		
		receiving_lc.callFlash = callFlash;
		
		//listen for incoming function calls
		receiving_lc.connect(this.lcId);
	}
	
	/*
		callFlash
		Private
		
		This is called by the FlashProxy in JavaScript to make a functon call into
		the Flash content.
	*/
	private function callFlash(args:Array):Void
	{
		//get a reference to the correct scope (this method is called in the scope
		//of the local connection object)
		var con:Object = this["controller"];
		
		var functionName:Object = args.shift();
		
		var f:Function = con.instance[functionName];

		//call the function in the correct scope, passing the arguments
		f.apply(con.instance, args);
	}
	
	/*
		This proxies function calls to the server, which allows you to call JavaScript
		functions as if they were functions on JavaScriptProxy instance.
		
		i.e.
		
		var j:JavaScriptProxy = new JavaScriptProxy();
		
		j.jsFunction("foo", [1, 2]);
	*/
	public function __resolve(functionName:String):Function
	{		
		var f:Function = function()
		{
			arguments.splice(0,0, functionName);
			var f:Function = call;
			f.apply(this, arguments);		
		};
		
		return f;
	}
	
	/*
		call
		public
		
		This is used to call functions within JavaScript.
		
		functionName : A string of the name of the function being called in JavaScript.
		
		a1, a2 ... an : subsequesnt arguments will be passed to the JavaScript function.
		
		Example:
		
		var j:JavaScriptProxy = new JavaScriptProxy();
		
		j.call("jsFunction", "foo", [1, 2]);
	*/
	public function call(functionName:String):Void
	{
		var len:Number = arguments.length;
		
		var argsString:String = "";
		
		//Serialize the arguments
		for(var i:Number = 0; i < len; i++)
		{
			argsString += JavaScriptSerializer.serializeItem(arguments[i]);
			
			if(i != len - 1)
			{
				argsString += ",";
			}
		}
		
		//Created the javascript URL
		var callString:String = "javascript:FlashProxy.callJS(" + argsString + ");";

		//call out into the HTML / JavaScript environment
		getURL(callString);
	}	
	
}
