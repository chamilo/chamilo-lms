/*
	JavaScriptSerializer.as
	
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

class com.macromedia.javascript.JavaScriptSerializer
{

	/**************** Serialization Methods ****************/

	/*
		Static function that serializes any supported data types.
		
		Returns a String
	*/
	public static function serializeItem(item:Object):String
	{
		var type:String = typeof(item);
		var out:String;
		
		switch (type)
		{
			case "string":
			{
				out = JavaScriptSerializer.serializeString(String(item));
				break;
			}
			case "number":
			{
				out = JavaScriptSerializer.serializeNumber(Number(item));
				break;
			}
			case "boolean":
			{
				out = JavaScriptSerializer.serializeBoolean(Boolean(item));
				break;
			}
			case "null":
			{
				out = JavaScriptSerializer.serializeNull();
				break;
			}
			case "undefined":
			{
				out = JavaScriptSerializer.serializeUndefined();
				break;
			}
			case "object":
			{
				if(item instanceof Date)
				{
					out = JavaScriptSerializer.serializeDate(new Date(item.getTime()));
				}
				else if(item instanceof Array)
				{
					out = JavaScriptSerializer.serializeArray(item);
				}
				else
				{
					//treat it as regular Object
					out = JavaScriptSerializer.serializeObject(item);
				}
				
				break;
			}																	
		}
		
		return out;
		
	}

	/* Serializes an Object */
	public static function serializeObject(o:Object):String
	{
		var sb:String = "{";
		
		for(var x:String in o)
		{

			//dont include functions
			if(typeof(x[o]) == "function")
			{
				continue;
			}

			sb += x + ":" + serializeItem(o[x]) + ",";
		}
		
		//remove the trailing ","
		if(sb.substring(sb.length - 1) == ",")
		{
			sb = sb.substring(0, sb.length - 1);
		}
		
		sb += "}";
		
		return sb;
	}
	
	/* Serializes an Array */
	//not typed since I can't cast an object to Array
	public static function serializeArray(o):String
	{
		var len:Number = o.length;
		
		var sb:String = "[";
		
		for(var i:Number  = 0; i < len; i++)
		{
			sb += serializeItem(o[i]);
			
			if(i != len - 1)
			{
				sb += ",";
			}
		}
		
		sb += "]";
		
		return sb;
	}	

	/* Serializes a String */
	public static function serializeString(s:String):String
	{
		return "'" + s + "'";
	}

	/* Serializes a Number */
	public static function serializeNumber(n:Number):String
	{
		return String(n);
	}

	/* Serializes a Boolean value */
	public static function serializeBoolean(b:Boolean):String
	{
		return String(b);
	}	
	
	/* Serializes undefined */
	public static function serializeUndefined(Void):String
	{
		return "undefined";
	}
	
	/* Serializes null */
	public static function serializeNull(Void):String
	{
		return "null";
	}
	
	/* Serializes a Date */
	public static function serializeDate(d:Date):String
	{
		return "new Date(" + d.getTime() + ")";
	}	
	 
	
	/**************** De-Serialization Methods ****************/
	
	/*
		Static function that de-serializes any supported data types.
		
		Returns a String
	*/
	public static function deserializeItem(type:String, data:String):Object 
	{
		var out:Object;
		
		switch (type)
		{
			case "str":
			{
				out = JavaScriptSerializer.deserializeString(data);
				break;
			}
			case "num":
			{
				out = JavaScriptSerializer.deserializeNumber(data);
				break;
			}
			case "bool":
			{
				out = JavaScriptSerializer.deserializeBoolean(data);
				break;
			}
			case "null":
			{
				out = JavaScriptSerializer.deserializeNull();
				break;
			}
			case "undf":
			{
				out = JavaScriptSerializer.deserializeUndefined();
				break;
			}
			case "date":
			{
				out = JavaScriptSerializer.deserializeDate(data);

				break;
			}
			case "xser":
			{
				out = JavaScriptSerializer.deserializeXMLSerializedItem(data);

                trace(data);

				break;
			}	
																					
		}
		
		return out;
	}	
	
	/* Deserializes a String */
	public static function deserializeString(s:String):String
	{
		return s;
	} 	
	
	/* Deserializes a Number */
	public static function deserializeNumber(s:String):Number
	{
		return Number(s);
	} 	
	
	/* Deserializes a Boolean Value */
	public static function deserializeBoolean(s:String):String
	{
		return Boolean(s);
	} 	
	
	/* Deserializes undefined */
	//returns undefined
	public static function deserializeUndefined(s:String)
	{
		return undefined;
	} 	
	
	/* Deserializes null */
	//returns null
	public static function deserializeNull(s:String)
	{
		return null;
	} 		
	
	/* Deserializes a Date */
	public static function deserializeDate(s:String):Date
	{
		return new Date(Number(s));
	}
	
	
	/**************** De-Serialization XML Methods ****************/
	
	/*
		The methods below are for deserializing data serialized in XML format.
		
		This is used for serializing Objects and Arrays
	*/
	
	
		/*
		Static function that de-serializes any supported  XML serialized data types.
		
		Returns a String
	*/
	public static function deserializeXMLSerializedItem(data:String):Object 
	{
		var x:XML = new XML();
		x.ignoreWhite = true;
		x.parseXML(data);
		
		var out:Object = parseNode(x.firstChild.firstChild, new Object);
	
		return out;
	}	
	
	/* recursive function that parses the xml tree */
	public static function parseNode(x:XMLNode, o:Object):Object
	{
		
		var nodeName:String = x.nodeName;
		var nodeValue:String = x.firstChild.nodeValue;
		var varName:String = x.attributes["name"];
		
		var children:Array = x.childNodes;
		var len:Number = children.length;		
		
		switch(nodeName)
		{
			case "obj":
			{
				if(varName == null)
				{
					o = new Object();
				}
				else
				{
					o[varName] = new Object();
				}
				break;
			}		
			case "str":
			{					
				if(varName == undefined)
				{
					o = String(nodeValue);
				}
				else
				{
					o[varName] = nodeValue;
				}
				
				break;
			}
			case "num":
			{
				if(varName == null)
				{
					o = Number(nodeValue);
				}
				else
				{
					o[varName] = Number(nodeValue);
				}
				
				break;
			}
			case "bool":
			{
				if(varName == null)
				{
					o = Boolean(nodeValue);
				}
				else
				{
					o[varName] = Boolean(nodeValue);
				}
				
				break;
			}	
			case "null":
			{
				if(varName == null)
				{
					o = null;
				}
				else
				{
					o[varName] = null;
				}
				
				break;
			}
			case "undf":
			{
				if(varName == null)
				{
					o = undefined;
				}
				else
				{
					o[varName] = undefined;
				}
				
				break;
			}	
			case "date":
			{
				if(varName == null)
				{
					o = new Date(Number(nodeValue));
				}
				else
				{
					o[varName] = new Date(Number(nodeValue));
				}
				
				break;
			}	
			case "array":
			{
				//this is not typed because the compiler gets confused about 
				//the explicit type change for o below.
				var arr;
				if(varName == null)
				{
					o = new Array();
					arr = o;
				}
				else
				{
					o[varName] = new Array();
					arr = o[varName];
				}	
				
				for(var x:Number = 0; x < len; x++)
				{
					arr.push(parseNode(children[x], o));
				}
				 	
				return arr;
			}																	
		}

		for(var i:Number = 0; i < len; i++)
		{
			parseNode(children[i], o);
		}

		
		return o;
	}
	

	
}
