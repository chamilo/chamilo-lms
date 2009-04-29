// htmlArea v3.0 - Copyright (c) 2002, 2003 interactivetools.com, inc.
// This copyright notice MUST stay intact for use (see license.txt).
//
// Portions (c) dynarch.com, 2003
//
// A free WYSIWYG editor replacement for <textarea> fields.
// For full source code and docs, visit http://www.interactivetools.com/
//
// Version 3.0 developed by Mihai Bazon.
//   http://dynarch.com/mishoo
//
// $Id: popup.js 26 2004-03-31 02:35:21Z Wei Zhuo $

// Slightly modified for the ImageManager, window resizing is done only
// by each window's script. Added translation for a few other HTML elements.

function getAbsolutePos(el) {
	var r = { x: el.offsetLeft, y: el.offsetTop };
	if (el.offsetParent) {
		var tmp = getAbsolutePos(el.offsetParent);
		r.x += tmp.x;
		r.y += tmp.y;
	}
	return r;
};

function comboSelectValue(c, val) {
	var ops = c.getElementsByTagName("option");
	for (var i = ops.length; --i >= 0;) {
		var op = ops[i];
		op.selected = (op.value == val);
	}
	c.value = val;
};

function __dlg_onclose() {
	if(opener.Dialog._return)
		opener.Dialog._return(null);
};

function __dlg_init(bottom) {
	var body = document.body;
	var body_height = 0;
	if (typeof bottom == "undefined") {
		var div = document.createElement("div");
		body.appendChild(div);
		var pos = getAbsolutePos(div);
		body_height = pos.y;
	} else {
		var pos = getAbsolutePos(bottom);
		body_height = pos.y + bottom.offsetHeight;
	}
	if(opener && opener.Dialog && opener.Dialog._arguments)
		window.dialogArguments = opener.Dialog._arguments;
	if (!document.all) {
		//window.sizeToContent();
		//window.sizeToContent();	// for reasons beyond understanding,
					// only if we call it twice we get the
					// correct size.
		window.addEventListener("unload", __dlg_onclose, true);
		// center on parent
		var x = opener.screenX + (opener.outerWidth - window.outerWidth) / 2;
		var y = opener.screenY + (opener.outerHeight - window.outerHeight) / 2;
		
		// Avoiding being in the center of the page.
		/*
		window.moveTo(x, y);
		*/

		//window.innerWidth = body.offsetWidth + 5;
		//window.innerHeight = body_height + 2;
	} else {
		// window.dialogHeight = body.offsetHeight + 50 + "px";
		// window.dialogWidth = body.offsetWidth + "px";
		//window.resizeTo(body.offsetWidth, body_height);
		var ch = body.clientHeight;
		var cw = body.clientWidth;
		//window.resizeBy(body.offsetWidth - cw, body_height - ch);
		var W = body.offsetWidth;
		var H = 2 * body_height - ch;
		if(ch <= 0) H = body_height;
		var x = (screen.availWidth - W) / 2;
		var y = (screen.availHeight - H) / 2;
		//alert('x:'+x+' y:'+y+' ch:'+ch);

		// Avoiding being in the center of the page.
		/*
		if(Dialog.is_ie)
			window.moveTo(x, y);
		else //opera
			window.moveTo(x, y - H/4);
		*/

	}
	document.body.onkeypress = __dlg_close_on_esc;
};

function __dlg_translate(i18n) {
	var types = ["span", "option", "td", "button", "div", "label", "a","img", "legend"];
	for (var type in types) {
		var spans = document.getElementsByTagName(types[type]);
		for (var i = spans.length; --i >= 0;) {
			var span = spans[i];
			if (span.firstChild && span.firstChild.data) {				
				var txt = i18n[span.firstChild.data];
				if (txt) span.firstChild.data = txt;
			}
			if(span.title){
				var txt = i18n[span.title];
				if(txt) span.title = txt;
			}
			if(span.alt){
				var txt = i18n[span.alt];
				if(txt) span.alt = txt;
			}
		}
	}
	var txt = i18n[document.title];
	if (txt)
		document.title = txt;
};

// closes the dialog and passes the return info upper.
function __dlg_close(val) {
	opener.Dialog._return(val);
	window.close();
};

function __dlg_close_on_esc(ev) {
	ev || (ev = window.event);
	if (ev.keyCode == 27) {
		window.close();
		return false;
	}
	return true;
};

function Dialog(){};
Dialog.agt = navigator.userAgent.toLowerCase();
Dialog.is_ie	   = ((Dialog.agt.indexOf("msie") != -1) && (Dialog.agt.indexOf("opera") == -1));
