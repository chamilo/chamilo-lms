/* SelectHelper.js
 * written by Colin Kuebler 2012
 * Part of LDT, dual licensed under GPLv3 and MIT
 * Convenient utilities for cross browser textarea selection manipulation
 */

var SelectHelper = {
	add: function( element ){
		element.insertAtCursor = element.createTextRange ?
			// IE version
			function(x){
				document.selection.createRange().text = x;
			} :
			// standards version
			function(x){
				var s = element.selectionStart,
					e = element.selectionEnd,
					v = element.value;
				element.value = v.substring(0, s) + x + v.substring(e);
				s += x.length;
				element.setSelectionRange(s, s);
			};
	}
};

