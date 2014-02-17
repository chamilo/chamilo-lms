function addBtnActionJCapture($btn, props, edid) {
	$btn.click(function() {
		var appletDiv = document.getElementById("jCaptureAppletDiv");
		if (appletDiv==null) {
	        	var oNewDiv = document.createElement("div");
		        oNewDiv.id="jCaptureAppletDiv";
		        //oNewDiv.style.display='none';
		        document.body.appendChild(oNewDiv);
			jQuery("#jCaptureAppletDiv").load(DOKU_BASE+"lib/plugins/jcapture/applet.php?edid="+edid+"&pageName="+document.forms['dw__editform'].elements['id'].value);
		} else {
	    		document.getElementById("jCaptureApplet").showCaptureFrame();
		}	    
        	return false;
	});
 
	return true;
}


