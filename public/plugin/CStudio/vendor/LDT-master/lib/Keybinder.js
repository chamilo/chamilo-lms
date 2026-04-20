/* Keybinder.js
 * written by Colin Kuebler 2012
 * Part of LDT, dual licensed under GPLv3 and MIT
 * Simplifies the creation of keybindings on any element
 */

var Keybinder = {
	bind: function( element, keymap ){
		element.keymap = keymap;
		var keyNames = {
			8: "Backspace",
			9: "Tab",
			13: "Enter",
			16: "Shift",
			17: "Ctrl",
			18: "Alt",
			19: "Pause",
			20: "CapsLk",
			27: "Esc",
			33: "PgUp",
			34: "PgDn",
			35: "End",
			36: "Home",
			37: "Left",
			38: "Up",
			39: "Right",
			40: "Down",
			45: "Insert",
			46: "Delete",
			112: "F1",
			113: "F2",
			114: "F3",
			115: "F4",
			116: "F5",
			117: "F6",
			118: "F7",
			119: "F8",
			120: "F9",
			121: "F10",
			122: "F11",
			123: "F12",
			145: "ScrLk" };
		var keyEventNormalizer = function(e){
			// get the event object and start constructing a query
			var e = e || window.event;
			var query = "";
			// add in prefixes for each key modifier
			e.shiftKey && (query += "Shift-");
			e.ctrlKey && (query += "Ctrl-");
			e.altKey && (query += "Alt-");
			e.metaKey && (query += "Meta-");
			// determine the key code
			var key = e.which || e.keyCode || e.charCode;
			// if we have a name for it, use it
			if( keyNames[key] )
				query += keyNames[key];
			// otherwise turn it into a string
			else
				query += String.fromCharCode(key).toUpperCase();
			/* DEBUG */
			//console.log("keyEvent: "+query);
			// try to run the keybinding, cancel the event if it returns true
			if( element.keymap[query] && element.keymap[query]() ){
				e.preventDefault && e.preventDefault();
				e.stopPropagation && e.stopPropagation();
				return false;
			}
			return true;
		};
		// capture onkeydown and onkeypress events to capture repeating key events
		// maintain a boolean so we only fire once per character
		var fireOnKeyPress = true;
		element.onkeydown = function(e){
			fireOnKeyPress = false;
			return keyEventNormalizer(e);
		};
		element.onkeypress = function(e){
			if( fireOnKeyPress )
				return keyEventNormalizer(e);
			fireOnKeyPress = true;
			return true;
		};
	}
}

