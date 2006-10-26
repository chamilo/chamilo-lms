/*
Macromedia(r) Flash(r) JavaScript Integration Kit License


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
"Macromedia Flash", nor may "Macromedia" or "Macromedia Flash" appear in their
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

--

This code is part of the Flash / JavaScript Integration Kit:
http://www.macromedia.com/go/flashjavascript/

Created by:

Christian Cantrell
http://weblogs.macromedia.com/cantrell/
mailto:cantrell@macromedia.com

Mike Chambers
http://weblogs.macromedia.com/mesh/
mailto:mesh@macromedia.com

Macromedia
*/

/**
 * Create a new Exception object.
 * name: The name of the exception.
 * message: The exception message.
 */
function Exception(name, message)
{
    if (name)
        this.name = name;
    if (message)
        this.message = message;
}

/**
 * Set the name of the exception. 
 */
Exception.prototype.setName = function(name)
{
    this.name = name;
}

/**
 * Get the exception's name. 
 */
Exception.prototype.getName = function()
{
    return this.name;
}

/**
 * Set a message on the exception. 
 */
Exception.prototype.setMessage = function(msg)
{
    this.message = msg;
}

/**
 * Get the exception message. 
 */
Exception.prototype.getMessage = function()
{
    return this.message;
}

/**
 * Generates a browser-specific Flash tag. Create a new instance, set whatever
 * properties you need, then call either toString() to get the tag as a string, or
 * call write() to write the tag out.
 */

/**
 * Creates a new instance of the FlashTag.
 * src: The path to the SWF file.
 * width: The width of your Flash content.
 * height: the height of your Flash content.
 */
function FlashTag(src, width, height)
{
    this.src       = src;
    this.width     = width;
    this.height    = height;
    this.version   = '7,0,14,0';
    this.id        = null;
    this.bgcolor   = 'ffffff';
    this.flashVars = null;
}

/**
 * Sets the Flash version used in the Flash tag.
 */
FlashTag.prototype.setVersion = function(v)
{
    this.version = v;
}

/**
 * Sets the ID used in the Flash tag.
 */
FlashTag.prototype.setId = function(id)
{
    this.id = id;
}

/**
 * Sets the background color used in the Flash tag.
 */
FlashTag.prototype.setBgcolor = function(bgc)
{
    this.bgcolor = bgc;
}

/**
 * Sets any variables to be passed into the Flash content. 
 */
FlashTag.prototype.setFlashvars = function(fv)
{
    this.flashVars = fv;
}

/**
 * Get the Flash tag as a string. 
 */
FlashTag.prototype.toString = function()
{
    var ie = (navigator.appName.indexOf ("Microsoft") != -1) ? 1 : 0;
    var flashTag = new String();
    if (ie)
    {
        flashTag += '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" ';
        if (this.id != null)
        {
            flashTag += 'id="'+this.id+'" ';
        }
        flashTag += 'codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version='+this.version+'" ';
        flashTag += 'width="'+this.width+'" ';
        flashTag += 'height="'+this.height+'">';
        flashTag += '<param name="movie" value="'+this.src+'"/>';
        flashTag += '<param name="quality" value="high"/>';
        flashTag += '<param name="bgcolor" value="#'+this.bgcolor+'"/>';
        if (this.flashVars != null)
        {
            flashTag += '<param name="flashvars" value="'+this.flashVars+'"/>';
        }
        flashTag += '</object>';
    }
    else
    {
        flashTag += '<embed src="'+this.src+'" ';
        flashTag += 'quality="high" '; 
        flashTag += 'bgcolor="#'+this.bgcolor+'" ';
        flashTag += 'width="'+this.width+'" ';
        flashTag += 'height="'+this.height+'" ';
        flashTag += 'type="application/x-shockwave-flash" ';
        if (this.flashVars != null)
        {
            flashTag += 'flashvars="'+this.flashVars+'" ';
        }
        if (this.id != null)
        {
            flashTag += 'name="'+this.id+'" ';
        }
        flashTag += 'pluginspage="http://www.macromedia.com/go/getflashplayer">';
        flashTag += '</embed>';
    }
    return flashTag;
}

/**
 * Write the Flash tag out. Pass in a reference to the document to write to. 
 */
FlashTag.prototype.write = function(doc)
{
    doc.write(this.toString());
}

/**
 * The FlashSerializer serializes JavaScript variables of types object, array, string,
 * number, date, boolean, null or undefined into XML. 
 */

/**
 * Create a new instance of the FlashSerializer.
 * useCdata: Whether strings should be treated as character data. If false, strings are simply XML encoded.
 */
function FlashSerializer(useCdata)
{
    this.useCdata = useCdata;
}

/**
 * Serialize an array into a format that can be deserialized in Flash. Supported data types are object,
 * array, string, number, date, boolean, null, and undefined. Returns a string of serialized data.
 */
FlashSerializer.prototype.serialize = function(args)
{
    var qs = new String();

    for (var i = 0; i < args.length; ++i)
    {
        switch(typeof(args[i]))
        {
            case 'undefined':
                qs += 't'+(i)+'=undf';
                break;
            case 'string':
                qs += 't'+(i)+'=str&d'+(i)+'='+escape(args[i]);
                break;
            case 'number':
                qs += 't'+(i)+'=num&d'+(i)+'='+escape(args[i]);
                break;
            case 'boolean':
                qs += 't'+(i)+'=bool&d'+(i)+'='+escape(args[i]);
                break;
            case 'object':
                if (args[i] == null)
                {
                    qs += 't'+(i)+'=null';
                }
                else if (args[i] instanceof Date)
                {
                    qs += 't'+(i)+'=date&d'+(i)+'='+escape(args[i].getTime());
                }
                else // array or object
                {
                    try
                    {
                        qs += 't'+(i)+'=xser&d'+(i)+'='+escape(this._serializeXML(args[i]));
                    }
                    catch (exception)
                    {
                        throw new Exception("FlashSerializationException",
                                            "The following error occurred during complex object serialization: " + exception.getMessage());
                    }
                }
                break;
            default:
                throw new Exception("FlashSerializationException",
                                    "You can only serialize strings, numbers, booleans, dates, objects, arrays, nulls, and undefined.");
        }

        if (i != (args.length - 1))
        {
            qs += '&';
        }
    }

    return qs;
}

/**
 * Private
 */
FlashSerializer.prototype._serializeXML = function(obj)
{
    var doc = new Object();
    doc.xml = '<fp>'; 
    this._serializeNode(obj, doc, null);
    doc.xml += '</fp>'; 
    return doc.xml;
}

/**
 * Private
 */
FlashSerializer.prototype._serializeNode = function(obj, doc, name)
{
    switch(typeof(obj))
    {
        case 'undefined':
            doc.xml += '<undf'+this._addName(name)+'/>';
            break;
        case 'string':
            doc.xml += '<str'+this._addName(name)+'>'+this._escapeXml(obj)+'</str>';
            break;
        case 'number':
            doc.xml += '<num'+this._addName(name)+'>'+obj+'</num>';
            break;
        case 'boolean':
            doc.xml += '<bool'+this._addName(name)+' val="'+obj+'"/>';
            break;
        case 'object':
            if (obj == null)
            {
                doc.xml += '<null'+this._addName(name)+'/>';
            }
            else if (obj instanceof Date)
            {
                doc.xml += '<date'+this._addName(name)+'>'+obj.getTime()+'</date>';
            }
            else if (obj instanceof Array)
            {
                doc.xml += '<array'+this._addName(name)+'>';
                for (var i = 0; i < obj.length; ++i)
                {
                    this._serializeNode(obj[i], doc, null);
                }
                doc.xml += '</array>';
            }
            else
            {
                doc.xml += '<obj'+this._addName(name)+'>';
                for (var n in obj)
                {
                    if (typeof(obj[n]) == 'function')
                        continue;
                    this._serializeNode(obj[n], doc, n);
                }
                doc.xml += '</obj>';
            }
            break;
        default:
            throw new Exception("FlashSerializationException",
                                "You can only serialize strings, numbers, booleans, objects, dates, arrays, nulls and undefined");
            break;
    }
}

/**
 * Private
 */
FlashSerializer.prototype._addName= function(name)
{
    if (name != null)
    {
        return ' name="'+name+'"';
    }
    return '';
}

/**
 * Private
 */
FlashSerializer.prototype._escapeXml = function(str)
{
    if (this.useCdata)
        return '<![CDATA['+str+']]>';
    else
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;');
}

/**
 * The FlashProxy object is what proxies function calls between JavaScript and Flash.
 * It handles all argument serialization issues.
 */

/**
 * Instantiates a new FlashProxy object. Pass in a uniqueID and the name (including the path)
 * of the Flash proxy SWF. The ID is the same ID that needs to be passed into your Flash content as lcId.
 */
function FlashProxy(uid, proxySwfName)
{
    this.uid = uid;
    this.proxySwfName = proxySwfName;
    this.flashSerializer = new FlashSerializer(false);
}

/**
 * Call a function in your Flash content.  Arguments should be:
 * 1. ActionScript function name to call,
 * 2. any number of additional arguments of type object,
 *    array, string, number, boolean, date, null, or undefined. 
 */
FlashProxy.prototype.call = function()
{

    if (arguments.length == 0)
    {
        throw new Exception("Flash Proxy Exception",
                            "The first argument should be the function name followed by any number of additional arguments.");
    }

    var qs = 'lcId=' + escape(this.uid) + '&functionName=' + escape(arguments[0]);

    if (arguments.length > 1)
    {
        var justArgs = new Array();
        for (var i = 1; i < arguments.length; ++i)
        {
            justArgs.push(arguments[i]);
        }
        qs += ('&' + this.flashSerializer.serialize(justArgs));
    }

    var divName = '_flash_proxy_' + this.uid;
    if(!document.getElementById(divName))
    {
        var newTarget = document.createElement("div");
        newTarget.id = divName;
        document.body.appendChild(newTarget);
    }
    var target = document.getElementById(divName);
    var ft = new FlashTag(this.proxySwfName, 1, 1);
    ft.setVersion('6,0,65,0');
    ft.setFlashvars(qs);
    target.innerHTML = ft.toString();
}

/**
 * This is the function that proxies function calls from Flash to JavaScript.
 * It is called implicitly.
 */
FlashProxy.callJS = function()
{
    var functionToCall = eval(arguments[0]);
    var argArray = new Array();
    for (var i = 1; i < arguments.length; ++i)
    {
        argArray.push(arguments[i]);
    }
    functionToCall.apply(functionToCall, argArray);
}

