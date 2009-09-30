/***********************************************
* IFrame SSI script II- © Dynamic Drive DHTML code library (http://www.dynamicdrive.com)
* Visit DynamicDrive.com for hundreds of original DHTML scripts
* This notice must stay intact for legal use
***********************************************/

//Input the IDs of the IFRAMES you wish to dynamically resize to match its content height:
//Separate each ID with a comma. Examples: ["myframe1", "myframe2"] or ["myframe"] or [] for none:
var iframeid="lp_content_frame";

//Should script hide iframe from browsers that don't support this script (non IE5+/NS6+ browsers. Recommended):
var iframehide="no";

//var getFFVersion=navigator.userAgent.substring(navigator.userAgent.indexOf("Firefox")).split("/")[1];
var FFextraHeight=32; //parseFloat(getFFVersion)>=0.1? 16 : 0; //extra height in px to add to iframe in FireFox 1.0+ browsers

function resizeCaller() {
	if (document.getElementById)
		resizeIframe(iframeid);
	//reveal iframe for lower end browsers? (see var above):
	if ((document.all || document.getElementById) && iframehide=="no"){
		var tempobj=document.all? document.all[iframeid] : document.getElementById(iframeid);
		tempobj.style.display="block";
	}
}

function resizeIframe(frameid){
	var currentfr=document.getElementById(frameid);
	if (currentfr && !window.opera){
		currentfr.style.display="block";
		if (currentfr.contentDocument && currentfr.contentDocument.body.offsetHeight){ //ns6 syntax
			currentfr.height = currentfr.contentDocument.body.offsetHeight+FFextraHeight;
		}
		else if (currentfr.Document && currentfr.Document.body.scrollHeight) //ie5+ syntax
			currentfr.height = currentfr.Document.body.scrollHeight;

		if(currentfr.height < 580){
			currentfr.height = 580;
		}
		if (currentfr.addEventListener)
			currentfr.addEventListener("load", readjustIframe, false);
		else if (currentfr.attachEvent){
			currentfr.detachEvent("onload", readjustIframe); // Bug fix line
			currentfr.attachEvent("onload", readjustIframe);
		}
	}
}

function readjustIframe(loadevt) {
	var crossevt=(window.event)? event : loadevt;
	var iframeroot=(crossevt.currentTarget)? crossevt.currentTarget : crossevt.srcElement;
	if (iframeroot)
		resizeIframe(iframeroot.id);
}

if (window.addEventListener)
	window.addEventListener("load", resizeCaller, false);
else if (window.attachEvent)
	window.attachEvent("onload", resizeCaller);
else
	window.onload=resizeCaller;