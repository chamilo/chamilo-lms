// Dialog v3.0 - Copyright (c) 2003-2004 interactivetools.com, inc.
// This copyright notice MUST stay intact for use (see license.txt).
//
// Portions (c) dynarch.com, 2003-2004
//
// A free WYSIWYG editor replacement for <textarea> fields.
// For full source code and docs, visit http://www.interactivetools.com/
//
// Version 3.0 developed by Mihai Bazon.
//   http://dynarch.com/mishoo
//
// $Id: dialog.js 26 2004-03-31 02:35:21Z Wei Zhuo $

// Though "Dialog" looks like an object, it isn't really an object.  Instead
// it's just namespace for protecting global symbols.

function Dialog(url, action, init) {
	if (typeof init == "undefined") {
		init = window;	// pass this window object by default
	}
	Dialog._geckoOpenModal(url, action, init);
};

Dialog._parentEvent = function(ev) {
	setTimeout( function() { if (Dialog._modal && !Dialog._modal.closed) { Dialog._modal.focus() } }, 50);
	if (Dialog._modal && !Dialog._modal.closed) {
		Dialog._stopEvent(ev);
	}
};


// should be a function, the return handler of the currently opened dialog.
Dialog._return = null;

// constant, the currently opened dialog
Dialog._modal = null;

// the dialog will read it's args from this variable
Dialog._arguments = null;

Dialog._geckoOpenModal = function(url, action, init) {
	//var urlLink = "hadialog"+url.toString();
	var myURL = "hadialog"+url;
	var regObj = /\W/g;
	myURL = myURL.replace(regObj,'_');
	var dlg = window.open(url, myURL,
			      "toolbar=no,menubar=no,personalbar=no,width=10,height=10," +
			      "scrollbars=no,resizable=yes,modal=yes,dependable=yes");
	Dialog._modal = dlg;
	Dialog._arguments = init;

	// capture some window's events
	function capwin(w) {
		Dialog._addEvent(w, "click", Dialog._parentEvent);
		Dialog._addEvent(w, "mousedown", Dialog._parentEvent);
		Dialog._addEvent(w, "focus", Dialog._parentEvent);
	};
	// release the captured events
	function relwin(w) {
		Dialog._removeEvent(w, "click", Dialog._parentEvent);
		Dialog._removeEvent(w, "mousedown", Dialog._parentEvent);
		Dialog._removeEvent(w, "focus", Dialog._parentEvent);
	};
	capwin(window.document);
	// capture other frames
	for (var i = 0; i < window.frames.length; capwin(window.frames[i++].document));
	// make up a function to be called when the Dialog ends.
	Dialog._return = function (val) {
		if (val && action) {
			action(val);
		}
		relwin(window.document);
		// capture other frames
		for (var i = 0; i < window.frames.length; relwin(window.frames[i++].document));
		Dialog._modal = null;
	};
};


// event handling

Dialog._addEvent = function(el, evname, func) {
	if (Dialog.is_ie) {
		el.attachEvent("on" + evname, func);
	} else {
		el.addEventListener(evname, func, true);
	}
};


Dialog._removeEvent = function(el, evname, func) {
	if (Dialog.is_ie) {
		el.detachEvent("on" + evname, func);
	} else {
		el.removeEventListener(evname, func, true);
	}
};


Dialog._stopEvent = function(ev) {
	if (Dialog.is_ie) {
		ev.cancelBubble = true;
		ev.returnValue = false;
	} else {
		ev.preventDefault();
		ev.stopPropagation();
	}
};

Dialog.agt = navigator.userAgent.toLowerCase();
Dialog.is_ie	   = ((Dialog.agt.indexOf("msie") != -1) && (Dialog.agt.indexOf("opera") == -1));
