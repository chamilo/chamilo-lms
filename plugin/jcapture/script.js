function addBtnActionJCapture($btn, props, edid) {
	$btn.click(function() {
		var jCaptureApplet = document.getElementById("jCaptureApplet");
		if (jCaptureApplet==null) {
	        	var oNewDiv = document.createElement("div");
		        oNewDiv.id="jCaptureAppletDiv";
		        //oNewDiv.style.display='none';
		        document.body.appendChild(oNewDiv);
			jQuery("#jCaptureAppletDiv").load(DOKU_BASE+"lib/plugins/jcapture/applet.php?edid="+edid+"&pageName="+document.forms['dw__editform'].elements['id'].value);
		} else {
	    		jCaptureApplet.showCaptureFrame();
		}	    
        	return false;
	});
 
	return true;
}


