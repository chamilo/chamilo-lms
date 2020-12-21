<script>
/* For licensing terms, see /license.txt */
/*
 * JS library to deal with event handlers.
 * This script needs to be included from a script where the global include file has already been loaded.
 * @package chamilo.inc.lib.javascript
 * @author Yannick Warnier
 * @author Julio Montoya - Adding twig support
 */

/*
 * Assigns any event handler to any element
 * @param	object	Element on which the event is added
 * @param	string	Name of event
 * @param	string	Function to trigger on event
 * @param	boolean	Capture the event and prevent
 */

function addEvent(elm, evType, fn, useCapture) {
    if (elm.addEventListener){
		elm.addEventListener(evType, fn, useCapture);
		return true;
	} else if(elm.attachEvent) {
		var r = elm.attachEvent('on' + evType, fn);
		return r;
	} else {
		elm['on' + evType] = fn;
	}
}

/*
 * Adds the event listener
 */
function addListeners(e) {
	var my_links = $('.clickable_email_link');
	for(var i=0;i < my_links.length;i++) {
		addEvent(my_links[i],'click',loadEmailEditor,false);
	}
}

/*
 * Loads a specific page on event triggering
 */
function loadEmailEditor(e) {
	var el;
	if(window.event && window.event.srcElement) {
		el = window.event.srcElement;
	}
	if (e && e.target) {
		el = e.target;
	}
	if(!el) {
		return;
	}
	//el is now my link object, so I can get el.href here to load the new window
	var link = el.href.replace('mailto:','');
	document.location = "{{ _p.web_main }}inc/{{ email_editor }}?dest=" + link;
	//cancel default link action
	if (window.event && window.event.returnValue){
		window.event.returnValue = false;
	}
	if(e && e.preventDefault){
		e.preventDefault();
	}
}

$(document).ready(function() {
    addEvent(window,'load',addListeners,false);
});

</script>