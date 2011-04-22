// Vars
var _wrs_currentPath = window.location.toString().substr(0, window.location.toString().lastIndexOf('/') + 1);
var _wrs_isNewElement = true;
var _wrs_temporalImage;

var _wrs_xmlCharacters = {
	'tagOpener': '<',		// \x3C
	'tagCloser': '>',		// \x3E
	'doubleQuote': '"',		// \x22
	'ampersand': '&',		// \x26
	'quote': '\''			// \x27
};

var _wrs_safeXmlCharacters = {
	'tagOpener': '«',		// \xAB
	'tagCloser': '»',		// \xBB
	'doubleQuote': '¨',		// \xA8
	'ampersand': '§',		// \xA7
	'quote': '`'			// \xB4
};

var _wrs_safeXmlCharactersEntities = {
	'tagOpener': '&laquo;',
	'tagCloser': '&raquo;',
	'doubleQuote': '&uml;'
}

/**
 * Cross-browser addEventListener/attachEvent function.
 * @param object element Element target
 * @param event event Event
 * @param function func Function to run
 */
function wrs_addEvent(element, event, func) {
	if (element.addEventListener) {
		element.addEventListener(event, func, false);
	}
	else if (element.attachEvent) {
		element.attachEvent('on' + event, func);
	}
}

/**
 * Adds iframe events.
 * @param object iframe Target
 * @param function doubleClickHandler Function to run when user double clicks the iframe
 * @param function mousedownHandler Function to run when user mousedowns the iframe
 * @param function mouseupHandler Function to run when user mouseups the iframe
 */
function wrs_addIframeEvents(iframe, doubleClickHandler, mousedownHandler, mouseupHandler) {
	if (doubleClickHandler) {
		wrs_addEvent(iframe.contentWindow.document, 'dblclick', function (event) {
			var realEvent = (event) ? event : window.event;
			var element = realEvent.srcElement ? realEvent.srcElement : realEvent.target;
			doubleClickHandler(iframe, element, realEvent);
		});
	}
	
	if (mousedownHandler) {
		wrs_addEvent(iframe.contentWindow.document, 'mousedown', function (event) {
			var realEvent = (event) ? event : window.event;
			var element = realEvent.srcElement ? realEvent.srcElement : realEvent.target;
			mousedownHandler(iframe, element, realEvent);
		});
	}
	
	if (mouseupHandler) {
		wrs_addEvent(iframe.contentWindow.document, 'mouseup', function (event) {
			var realEvent = (event) ? event : window.event;
			var element = realEvent.srcElement ? realEvent.srcElement : realEvent.target;
			mouseupHandler(iframe, element, realEvent);
		});
	}
}

/**
 * Adds textarea events.
 * @param object textarea Target
 * @param function clickHandler Function to run when user clicks the textarea.
 */
function wrs_addTextareaEvents(textarea, clickHandler) {
	if (clickHandler) {
		wrs_addEvent(textarea, 'click', function (event) {
			var realEvent = (event) ? event : window.event;
			clickHandler(textarea, realEvent);
		});
	}
}

/**
 * Converts applet code to img object.
 * @param object creator Object with "createElement" method
 * @param string appletCode Applet code
 * @param string image Base 64 image stream
 * @param int imageWidth Image width
 * @param int imageHeight Image height
 * @return object
 */
function wrs_appletCodeToImgObject(creator, appletCode, image, imageWidth, imageHeight) {
	var imageSrc = wrs_createImageCASSrc(image);
	var imgObject = creator.createElement('img');
	imgObject.title = 'Double click to edit';
	imgObject.src = imageSrc;
	imgObject.align = 'middle';
	imgObject.width = imageWidth;
	imgObject.height = imageHeight;
	imgObject.setAttribute(_wrs_conf_CASMathmlAttribute, wrs_mathmlEncode(appletCode));
	imgObject.className = 'Wiriscas';
	
	return imgObject;
}

/**
 * Checks if an element contains a class.
 * @param object element
 * @param string className
 * @return bool
 */
function wrs_containsClass(element, className) {
	var currentClasses = element.className.split(' ');
	
	for (var i = currentClasses.length - 1; i >= 0; --i) {
		if (currentClasses[i] == className) {
			return true;
		}
	}
	
	return false;
}

/**
 * Cross-browser solution for creating new elements.
 * 
 * It fixes some browser bugs.
 *
 * @param string elementName The tag name of the wished element.
 * @param object attributes An object where each key is a wished attribute name and each value is its value.
 * @param object creator Optional param. If supplied, this function will use the "createElement" method from this param. Else, "document" will be used.
 * @return object The DOM element with the specified attributes assignated.
 */
function wrs_createElement(elementName, attributes, creator) {
	if (attributes === undefined) {
		attributes = {};
	}
	
	if (creator === undefined) {
		creator = document;
	}
	
	var element;
	
	/*
	 * Internet Explorer fix:
	 * If you create a new object dynamically, you can't set a non-standard attribute.
	 * For example, you can't set the "src" attribute on an "applet" object.
	 * Other browsers will throw an exception and will run the standard code.
	 */
	
	try {
		var html = '<' + elementName + ' ';
		
		for (var attributeName in attributes) {
			html += attributeName + '="' + wrs_htmlentities(attributes[attributeName]) + '" ';
		}
		
		html += '>';
		element = creator.createElement(html);
	}
	catch (e) {
		element = creator.createElement(elementName);
		
		for (var attributeName in attributes) {
			element.setAttribute(attributeName, attributes[attributeName]);
		}
	}
	
	return element;
}

/**
 * Cross-browser httpRequest creation.
 * @return object
 */
function wrs_createHttpRequest() {
	if (typeof XMLHttpRequest != 'undefined') {
		return new XMLHttpRequest();
	}
			
	try {
		return new ActiveXObject('Msxml2.XMLHTTP');
	}
	catch (e) {
		try {
			return new ActiveXObject('Microsoft.XMLHTTP');
		}
		catch (oc) {
		}
	}
	
	return false;
}

/**
 * Gets CAS image src with AJAX.
 * @param string image Base 64 image stream
 * @return string
 */
function wrs_createImageCASSrc(image, appletCode) {
	var httpRequest = wrs_createHttpRequest();
	
	if (httpRequest) {
		var data = 'image=' + wrs_urlencode(image);
		
		if (appletCode) {
			data += '&mml=' + wrs_urlencode(appletCode);
		}
		
		if (_wrs_conf_createcasimagePath.substr(0, 1) == '/' || _wrs_conf_createcasimagePath.substr(0, 7) == 'http://' || _wrs_conf_createimagePath.substr(0, 8) == 'https://') {
			httpRequest.open('POST', _wrs_conf_createcasimagePath, false);
		}
		else {
			httpRequest.open('POST', _wrs_currentPath + _wrs_conf_createcasimagePath, false);
		}
		
		httpRequest.setRequestHeader('Content-type', 'application/x-www-form-urlencoded; charset=UTF-8');
		httpRequest.send(data);
		
		return httpRequest.responseText;
	}
	
	alert('Your browser is not compatible with AJAX technology. Please, use the latest version of Mozilla Firefox.');
	return '';
}

/**
 * Gets formula image src with AJAX.
 * @param mathml Mathml code
 * @param wirisProperties
 * @return string Image src
 */
function wrs_createImageSrc(mathml, wirisProperties) {
	var httpRequest = wrs_createHttpRequest();
	
	if (httpRequest) {
		var data = (wirisProperties) ? wirisProperties : {};
		data['mml'] = mathml;
		
		if (window._wrs_conf_useDigestInsteadOfMathml && _wrs_conf_useDigestInsteadOfMathml) {
			data['returnDigest'] = 'true';
		}
		
		if (_wrs_conf_createimagePath.substr(0, 1) == '/' || _wrs_conf_createimagePath.substr(0, 7) == 'http://' || _wrs_conf_createimagePath.substr(0, 8) == 'https://') {
			httpRequest.open('POST', _wrs_conf_createimagePath, false);
		}
		else {
			httpRequest.open('POST', _wrs_currentPath + _wrs_conf_createimagePath, false);
		}
		
		httpRequest.setRequestHeader('Content-type', 'application/x-www-form-urlencoded; charset=UTF-8');
		httpRequest.send(wrs_httpBuildQuery(data));
		
		return httpRequest.responseText;
	}
	
	alert('Your browser is not compatible with AJAX technology. Please, use the latest version of Mozilla Firefox.');
	return '';
}

/**
 * Creates new object using its html code.
 * @param string objectCode
 * @return object
 */
function wrs_createObject(objectCode, creator) {
	if (creator === undefined) {
		creator = document;
	}

	// Internet Explorer can't include "param" tag when is setting an innerHTML property.
	objectCode = objectCode.split('<applet ').join('<span wirisObject="WirisApplet" ').split('<APPLET ').join('<span wirisObject="WirisApplet" ');	// It is a 'span' because 'span' objects can contain 'br' nodes.
	objectCode = objectCode.split('</applet>').join('</span>').split('</APPLET>').join('</span>');
	
	objectCode = objectCode.split('<param ').join('<br wirisObject="WirisParam" ').split('<PARAM ').join('<br wirisObject="WirisParam" ');			// It is a 'br' because 'br' can't contain nodes.
	objectCode = objectCode.split('</param>').join('</br>').split('</PARAM>').join('</br>');
	
	var container = wrs_createElement('div', {}, creator);
	container.innerHTML = objectCode;
	
	function recursiveParamsFix(object) {
		if (object.getAttribute && object.getAttribute('wirisObject') == 'WirisParam') {
			var attributesParsed = {};
			
			for (var i = 0; i < object.attributes.length; ++i) {
				if (object.attributes[i].nodeValue !== null) {
					attributesParsed[object.attributes[i].nodeName] = object.attributes[i].nodeValue;
				}
			}
			
			var param = wrs_createElement('param', attributesParsed, creator);
			
			// IE fix
			if (param.NAME) {
				param.name = param.NAME;
				param.value = param.VALUE;
			}
			
			param.removeAttribute('wirisObject');
			object.parentNode.replaceChild(param, object);
		}
		else if (object.getAttribute && object.getAttribute('wirisObject') == 'WirisApplet') {
			var attributesParsed = {};
			
			for (var i = 0; i < object.attributes.length; ++i) {
				if (object.attributes[i].nodeValue !== null) {
					attributesParsed[object.attributes[i].nodeName] = object.attributes[i].nodeValue;
				}
			}
			
			var applet = wrs_createElement('applet', attributesParsed, creator);
			applet.removeAttribute('wirisObject');
			
			for (var i = 0; i < object.childNodes.length; ++i) {
				recursiveParamsFix(object.childNodes[i]);

				if (object.childNodes[i].nodeName.toLowerCase() == 'param') {
					applet.appendChild(object.childNodes[i]);
					--i;	// When we insert the object child into the applet, object loses one child.
				}
			}

			object.parentNode.replaceChild(applet, object);
		}
		else {
			for (var i = 0; i < object.childNodes.length; ++i) {
				recursiveParamsFix(object.childNodes[i]);
			}
		}
	}
	
	recursiveParamsFix(container);
	return container.firstChild;
}

/**
 * Converts an object to its HTML code.
 * @param object object
 * @return string
 */
function wrs_createObjectCode(object, creator) {
	if (creator === undefined) {
		creator = document;
	}
	
	var parent = object.parentNode;
	var toReturn;
	
	if (parent != null) {
		var newParent = wrs_createElement(parent.tagName, {}, creator);
		parent.replaceChild(newParent, object);
		newParent.appendChild(object);
		toReturn = newParent.innerHTML;
		parent.replaceChild(object, newParent);
	}
	else {
		var newParent = wrs_createElement('div', {}, creator);
		newParent.appendChild(object);
		toReturn = newParent.innerHTML;
		newParent.removeChild(object);
	}

	return toReturn;
}

/**
 * Parses end HTML code, converts CAS images to CAS applets.
 * @param string code
 * @return string
 */
function wrs_endParse(code) {
	var containerCode = '<div>' + code + '</div>';
	var container = wrs_createObject(containerCode);
	var imgList = container.getElementsByTagName('img');
	var convertToXml = false;
	var convertToSafeXml = false;
	
	if (window._wrs_conf_saveMode) {
		if (_wrs_conf_saveMode == 'safeXml') {
			convertToXml = true;
			convertToSafeXml = true;
		}
		else if (_wrs_conf_saveMode == 'xml') {
			convertToXml = true;
		}
	}
	
	for (var i = 0; i < imgList.length; ++i) {
		if (imgList[i].className == 'Wirisformula') {
			if (convertToXml) {
				var xmlCode = imgList[i].getAttribute(_wrs_conf_imageMathmlAttribute);
				var replacementObject;
				
				if (!convertToSafeXml) {
					xmlCode = wrs_mathmlDecode(xmlCode);
					var span = document.createElement('span');
					span.innerHTML = xmlCode;
					replacementObject = span.firstChild;
				}
				else {
					replacementObject = document.createTextNode(xmlCode);
				}
				
				imgList[i].parentNode.replaceChild(replacementObject, imgList[i]);
				--i;		// One image has been deleted.
			}
		}
		else if (imgList[i].className == 'Wiriscas') {
			var appletCode = imgList[i].getAttribute(_wrs_conf_CASMathmlAttribute);
			appletCode = wrs_mathmlDecode(appletCode);
			var appletObject = wrs_createObject(appletCode);
			appletObject.setAttribute('src', imgList[i].src);
			var object = appletObject;
			
			if (convertToSafeXml) {
				object = document.createTextNode(wrs_mathmlEncode(wrs_createObjectCode(appletObject)));
			}
			
			imgList[i].parentNode.replaceChild(object, imgList[i]);
			--i;		// One image has been deleted.
		}
	}

	return container.innerHTML;
}

/**
 * Gets the formula mathml or CAS appletCode using its image hash code.
 * @param string variableName Variable to send on POST query to the server.
 * @param string imageHashCode
 * @return string
 */
function wrs_getCode(variableName, imageHashCode) {
	var data = wrs_urlencode(variableName) + '=' + imageHashCode;
	var httpRequest = wrs_createHttpRequest();
	
	if (httpRequest) {
		if (_wrs_conf_getmathmlPath.substr(0, 1) == '/' || _wrs_conf_getmathmlPath.substr(0, 7) == 'http://' || _wrs_conf_getmathmlPath.substr(0, 8) == 'https://') {
			httpRequest.open('POST', _wrs_conf_getmathmlPath, false);
		}
		else {
			httpRequest.open('POST', _wrs_currentPath + _wrs_conf_getmathmlPath, false);
		}
		
		httpRequest.setRequestHeader('Content-type', 'application/x-www-form-urlencoded; charset=UTF-8');
		httpRequest.send(data);
		return httpRequest.responseText;
	}
	
	alert('Your browser is not compatible with AJAX technology. Please, use the latest version of Mozilla Firefox.');
	return '';
}

/**
 * Parses a text and replaces all HTML special characters by their entities.
 * @param string input
 * @return string
 */
function wrs_htmlentities(input) {
    var container = document.createElement('span');
    var text = document.createTextNode(input);
    container.appendChild(text);
    return container.innerHTML.split('"').join('&quot;');
}

/**
 * Converts a hash to a HTTP query.
 * @param hash properties
 * @return string
 */
function wrs_httpBuildQuery(properties) {
	var result = '';
	
	for (i in properties) {
		if (properties[i] != null) {
			result += wrs_urlencode(i) + '=' + wrs_urlencode(properties[i]) + '&';
		}
	}
	
	return result;
}

/**
 * Parses initial HTML code, converts CAS applets to CAS images.
 * @param string code
 * @return string
 */
function wrs_initParse(code) {
	if (window._wrs_conf_saveMode) {
		var safeXml = (_wrs_conf_saveMode == 'safeXml');
		var characters = _wrs_xmlCharacters;
		
		if (safeXml) {
			characters = _wrs_safeXmlCharacters;
			code = wrs_parseSafeAppletsToObjects(code);
		}
		
		if (safeXml || _wrs_conf_saveMode == 'xml') {
			// Converting XML to tags.
			code = wrs_parseMathmlToImg(code, characters);
		}
	}
	
	var container = wrs_createObject('<div>' + code + '</div>');
	var appletList = container.getElementsByTagName('applet');
	
	for (var i = 0; i < appletList.length; ++i) {
		if (appletList[i].className == 'Wiriscas' || appletList[i].getAttribute('class') == 'Wiriscas') {		// Internet Explorer can't read className correctly
			var imgObject = wrs_createElement('img');
			imgObject.title = 'Double click to edit';
			imgObject.src = appletList[i].getAttribute('src');
			imgObject.align = 'middle';
			
			var appletCode = wrs_createObjectCode(appletList[i]);
			imgObject.setAttribute(_wrs_conf_CASMathmlAttribute, wrs_mathmlEncode(appletCode));
			imgObject.className = 'Wiriscas';
			
			appletList[i].parentNode.replaceChild(imgObject, appletList[i]);
			--i;		// we have deleted one sleeped applet
		}
	}
	
	return container.innerHTML;
}

/**
 * WIRIS special encoding.
 * We use these entities because IE doesn't support html entities on its attributes sometimes. Yes, sometimes.
 * @param string input
 * @return string
 */
function wrs_mathmlDecode(input) {
	// Decoding entities.
	input = input.split(_wrs_safeXmlCharactersEntities.tagOpener).join(_wrs_safeXmlCharacters.tagOpener);
	input = input.split(_wrs_safeXmlCharactersEntities.tagCloser).join(_wrs_safeXmlCharacters.tagCloser);
	input = input.split(_wrs_safeXmlCharactersEntities.doubleQuote).join(_wrs_safeXmlCharacters.doubleQuote);

	// Decoding characters.
	input = input.split(_wrs_safeXmlCharacters.tagOpener).join(_wrs_xmlCharacters.tagOpener);
	input = input.split(_wrs_safeXmlCharacters.tagCloser).join(_wrs_xmlCharacters.tagCloser);
	input = input.split(_wrs_safeXmlCharacters.doubleQuote).join(_wrs_xmlCharacters.doubleQuote);
	input = input.split(_wrs_safeXmlCharacters.ampersand).join(_wrs_xmlCharacters.ampersand);
	input = input.split(_wrs_safeXmlCharacters.quote).join(_wrs_xmlCharacters.quote);
	
	// We are replacing $ by & for retrocompatibility. Now, the standard is replace § by &
	input = input.split('$').join('&');
	
	return input;
}

/**
 * WIRIS special encoding.
 * We use these entities because IE doesn't support html entities on its attributes sometimes. Yes, sometimes.
 * @param string input
 * @return string
 */
function wrs_mathmlEncode(input) {
	input = input.split(_wrs_xmlCharacters.tagOpener).join(_wrs_safeXmlCharacters.tagOpener);
	input = input.split(_wrs_xmlCharacters.tagCloser).join(_wrs_safeXmlCharacters.tagCloser);
	input = input.split(_wrs_xmlCharacters.doubleQuote).join(_wrs_safeXmlCharacters.doubleQuote);
	input = input.split(_wrs_xmlCharacters.ampersand).join(_wrs_safeXmlCharacters.ampersand);
	input = input.split(_wrs_xmlCharacters.quote).join(_wrs_safeXmlCharacters.quote);
	
	return input;
}

/**
 * Converts special symbols (> 128) to entities.
 * @param string mathml
 * @return string
 */
function wrs_mathmlEntities(mathml) {
	var toReturn = '';
	
	for (var i = 0; i < mathml.length; ++i) {
		//parsing > 128 characters
		if (mathml.charCodeAt(i) > 128) {
			toReturn += '&#' + mathml.charCodeAt(i) + ';';
		}
		else {
			toReturn += mathml.charAt(i);
		}
	}
	
	return toReturn;
}

/**
 * Converts mathml to img object.
 * @param object creator Object with "createElement" method
 * @param string mathml Mathml code
 * @return object
 */
function wrs_mathmlToImgObject(creator, mathml, wirisProperties) {
	var imgObject = creator.createElement('img');
	imgObject.title = 'Double click to edit';
	imgObject.align = 'middle';
	imgObject.className = 'Wirisformula';
	
	var result = wrs_createImageSrc(mathml, wirisProperties);
	
	if (window._wrs_conf_useDigestInsteadOfMathml && _wrs_conf_useDigestInsteadOfMathml) {
		var parts = result.split(':', 2);
		imgObject.setAttribute(_wrs_conf_imageMathmlAttribute, parts[0]);
		imgObject.src = parts[1];
	}
	else {
		imgObject.setAttribute(_wrs_conf_imageMathmlAttribute, wrs_mathmlEncode(mathml));
		imgObject.src = result;
	}
	
	return imgObject;
}

/**
 * Opens a new CAS window.
 * @return object The opened window
 */
function wrs_openCASWindow() {
	return window.open(_wrs_conf_CASPath, 'WIRISCAS', _wrs_conf_CASAttributes);
}

/**
 * Opens a new editor window.
 * @param string language Language code for the editor
 * @return object The opened window
 */
function wrs_openEditorWindow(language) {
	var path = _wrs_conf_editorPath;
	
	if (language) {
		path += '?lang=' + language;
	}
	
	return window.open(path, 'WIRISeditor', _wrs_conf_editorAttributes);
}

/**
 * Converts all occurrences of mathml code to the corresponding image.
 * @param string content
 * @return string
 */
function wrs_parseMathmlToImg(content, characters) {
	var output = '';
	var mathTagBegin = characters.tagOpener + 'math';
	var mathTagEnd = characters.tagOpener + '/math' + characters.tagCloser;
	var start = content.indexOf(mathTagBegin);
	var end = 0;
	
	while (start != -1) {
		output += content.substring(end, start);
		end = content.indexOf(mathTagEnd, start);
		
		if (end == -1) {
			end = content.length - 1;
		}
		else {
			end += mathTagEnd.length;
		}
		
		var mathml = content.substring(start, end);
		mathml = (characters == _wrs_safeXmlCharacters) ? wrs_mathmlDecode(mathml) : wrs_mathmlEntities(mathml);
		output += wrs_createObjectCode(wrs_mathmlToImgObject(document, mathml));
		start = content.indexOf(mathTagBegin, end);
	}
	
	output += content.substring(end, content.length - 1);
	return output;
}

/**
 * Converts all occurrences of safe applet code to the corresponding code.
 * @param string content
 * @return string
 */
function wrs_parseSafeAppletsToObjects(content) {
	var output = '';
	var appletTagBegin = _wrs_safeXmlCharacters.tagOpener + 'applet';
	var appletTagEnd = _wrs_safeXmlCharacters.tagOpener + '/applet' + _wrs_safeXmlCharacters.tagCloser;
	var start = content.indexOf(appletTagBegin);
	var end = 0;
	
	while (start != -1) {
		output += content.substring(end, start);
		end = content.indexOf(appletTagEnd, start);
		
		if (end == -1) {
			end = content.length - 1;
		}
		else {
			end += appletTagEnd.length;
		}
		
		output += wrs_mathmlDecode(content.substring(start, end));
		start = content.indexOf(appletTagBegin, end);
	}
	
	output += content.substring(end, content.length - 1);
	return output;
}

/**
 * Cross-browser removeEventListener/detachEvent function.
 * @param object element Element target
 * @param event event Event
 * @param function func Function to run
 */
function wrs_removeEvent(element, event, func) {
	if (element.removeEventListener) {
		element.removeEventListener(event, func, false);
	}
	else if (element.detachEvent) {
		element.detachEvent('on' + event, func);
	}
}

/**
 * Inserts or modifies CAS on an iframe.
 * @param object iframe Target
 * @param string appletCode Applet code
 * @param string image Base 64 image stream
 * @param int imageWidth Image width
 * @param int imageHeight Image height
 */
function wrs_updateCAS(iframe, appletCode, image, imageWidth, imageHeight) {
	try {
		if (iframe && appletCode) {
			iframe.contentWindow.focus();
			var imgObject = wrs_appletCodeToImgObject(iframe.contentWindow.document, appletCode, image, imageWidth, imageHeight);
			
			if (_wrs_isNewElement) {
				if (document.selection) {
					var range = iframe.contentWindow.document.selection.createRange();
					
					iframe.contentWindow.document.execCommand('insertimage', false, imgObject.src);

					if (range.parentElement) {
						var temporalImg = range.parentElement();
						temporalImg.parentNode.insertBefore(imgObject, temporalImg);
						temporalImg.parentNode.removeChild(temporalImg);
					}
				}
				else {
					var sel = iframe.contentWindow.getSelection();
					try {
						var range = sel.getRangeAt(0);
					}
					catch (e) {
						var range = iframe.contentWindow.document.createRange();
					}
					
					sel.removeAllRanges();
					range.deleteContents();
					
					var node = range.startContainer;
					var pos = range.startOffset;
					
					if (node.nodeType == 3) {
						node = node.splitText(pos);
						node.parentNode.insertBefore(imgObject, node);
					}
					else if (node.nodeType == 1) {
						node.insertBefore(imgObject, node.childNodes[pos]);
					}
				}
			}
			else {
				_wrs_temporalImage.parentNode.insertBefore(imgObject, _wrs_temporalImage);
				_wrs_temporalImage.parentNode.removeChild(_wrs_temporalImage);
			}
		}
	}
	catch (e) {
	}
}

/**
 * Inserts or modifies formulas on an iframe.
 * @param object iframe Target
 * @param string mathml Mathml code
 */
function wrs_updateFormula(iframe, mathml, wirisProperties) {
	try {
		if (iframe && mathml) {
			iframe.contentWindow.focus();
			var imgObject = wrs_mathmlToImgObject(iframe.contentWindow.document, mathml, wirisProperties);
			
			if (_wrs_isNewElement) {
				if (document.selection) {
					var range = iframe.contentWindow.document.selection.createRange();
					iframe.contentWindow.document.execCommand('insertimage', false, imgObject.src);

					if (range.parentElement) {
						var temporalImg = range.parentElement();
						temporalImg.parentNode.insertBefore(imgObject, temporalImg);
						temporalImg.parentNode.removeChild(temporalImg);
					}
				}
				else {
					var selection = iframe.contentWindow.getSelection();
					
					try {
						var range = selection.getRangeAt(0);						
					}
					catch (e) {
						var range = iframe.contentWindow.document.createRange();
					}
					
					selection.removeAllRanges();
					range.deleteContents();
				
					var node = range.startContainer;
					var pos = range.startOffset;
					
					if (node.nodeType == 3) {
						node = node.splitText(pos);
						node.parentNode.insertBefore(imgObject, node);
					}
					else if (node.nodeType == 1) {
						node.insertBefore(imgObject, node.childNodes[pos]);
					}
				}
			}
			else {
				_wrs_temporalImage.parentNode.insertBefore(imgObject, _wrs_temporalImage);
				_wrs_temporalImage.parentNode.removeChild(_wrs_temporalImage);
			}
		}
	}
	catch (e) {
	}
}

/**
 * Inserts or modifies formulas or CAS on a textarea.
 * @param object textarea Target
 * @param string text Text to add in the textarea. For example, if you want to add the link to the image, you can call this function as wrs_updateTextarea(textarea, wrs_createImageSrc(mathml));
 */
function wrs_updateTextarea(textarea, text) {
	if (textarea && text) {
		textarea.focus();
		
		if (textarea.selectionStart != null) {
			textarea.value = textarea.value.substring(0, textarea.selectionStart) + text + textarea.value.substring(textarea.selectionEnd, textarea.value.length);
		}
		else {
			var selection = document.selection.createRange();
			selection.text = text;
		}
	}
}

/**
 * URL encode function
 * @param string clearString Input 
 * @return string
 */
function wrs_urlencode(clearString) {
	var output = '';
	var x = 0;
	clearString = clearString.toString();
	var regex = /(^[a-zA-Z0-9_.]*)/;
	
	var clearString_length = ((typeof clearString.length) == 'function') ? clearString.length() : clearString.length;

	while (x < clearString_length) {
		var match = regex.exec(clearString.substr(x));
		if (match != null && match.length > 1 && match[1] != '') {
			output += match[1];
			x += match[1].length;
		}
		else {
			var charCode = clearString.charCodeAt(x);
			var hexVal = charCode.toString(16);
			output += '%' + ( hexVal.length < 2 ? '0' : '' ) + hexVal.toUpperCase();
			++x;
		}
	}
	
	return output;
}
