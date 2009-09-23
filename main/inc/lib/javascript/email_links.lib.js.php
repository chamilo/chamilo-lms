<?php //$id: $
/**
 * Pseudo JavaScript library to deal with event handlers.
 * This script needs to be included from a script where the global include file has already been loaded.
 * @package dokeos.inc.lib.javascript
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * If the user is not logged in, don't define anything, so the normal
 * handling of mailto link can proceed
 */
if(!empty($_user['user_id']) AND string_2_boolean(api_get_setting('allow_email_editor'))){
?>
<script language="javascript" version="1.3" type="text/javascript">
/**
 * Assigns any event handler to any element
 * @param	object	Element on which the event is added
 * @param	string	Name of event
 * @param	string	Function to trigger on event
 * @param	boolean	Capture the event and prevent
 */
function addEvent(elm, evType, fn, useCapture)
{ //by Scott Andrew
	if(elm.addEventListener){
		elm.addEventListener(evType, fn, useCapture);
		return true;
	} else if(elm.attachEvent) {
		var r = elm.attachEvent('on' + evType, fn);
		return r;
	} else {
		elm['on' + evType] = fn;
	}
}
/**
 * Adds the event listener
 */
function addListeners(e) {
	var my_links = document.getElementsByName('clickable_email_link');
	for(var i=0;i < my_links.length;i++)
	{
		addEvent(my_links[i],'click',loadEmailEditor,false);
	}
}
/**
 * Loads a specific page on event triggering
 */
function loadEmailEditor(e)
{
	var el;
	if(window.event && window.event.srcElement)
	{
		el = window.event.srcElement;
	}
	if (e && e.target)
	{
		el = e.target;
	}
	if(!el)
	{
		return;
	}
	//el is now my link object, so I can get el.href here to load the new window
	var link = el.href.replace('mailto:','');
	document.location = "<?php echo api_get_path(WEB_CODE_PATH);?>messaging/email_editor.php?dest=" + link;
	//cancel default link action
	if(window.event && window.event.returnValue){
		window.event.returnValue = false;
	}
	if(e && e.preventDefault){
		e.preventDefault();
	}
}
addEvent(window,'load',addListeners,false);
</script>
<?php
}
?>