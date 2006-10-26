<?php

class Loader {
	
	var $form_name;
	var $src_anim;

    function Loader($form_name) {
    	
    	$this->form_name = $form_name;
    	/*
		if(api_get_navigator() == 'Internet Explorer'){
    		$this->src_anim = api_get_path(WEB_IMG_PATH).'anim-frame1.gif';
    	}
    	else {
    		$this->src_anim = api_get_path(WEB_IMG_PATH).'anim-loader.gif';
    	}
    	*/
		$this->src_anim = api_get_path(WEB_IMG_PATH).'anim-loader.gif';

    }
    
    function init() {    	
    	
    	echo "<div id=\"loader\" style=\"display: none;\" align=\"center\">
				".get_lang("ProcessingDatas")."<br />
				<img id='animloader' src=\"".$this->src_anim."\" />
			  </div>
    	      <div id=\"myform\">";
    	
    }
    
    function close() {
    	
    	echo "</div>";
    	echo "<script type=\"text/javascript\">\r\n
				var currentImage = 1;
				function displayLoader(){\r\n					
					document.getElementById('myform').style.display =  'none';\r\n
					document.getElementById('loader').style.display =  'block';\r\n
					/*if(navigator.appName == 'Microsoft Internet Explorer'){
						setInterval(changeImage,400);
					}*/
				}\r\n
				function changeImage(){\r\n
					if(currentImage == 1){\r\n
						document.getElementById('animloader').src = '".api_get_path(WEB_IMG_PATH)."anim-frame2.gif';\r\n
						currentImage = 2;\r\n
						return;
					}\r\n
					if(currentImage == 2){\r\n
						document.getElementById('animloader').src = '".api_get_path(WEB_IMG_PATH)."anim-frame3.gif';\r\n
						currentImage = 3;\r\n
						return;
					}\r\n
					if(currentImage == 3){\r\n
						document.getElementById('animloader').src = '".api_get_path(WEB_IMG_PATH)."anim-frame4.gif';\r\n
						currentImage = 4;\r\n
						return;
					}\r\n
					if(currentImage == 4){\r\n
						document.getElementById('animloader').src = '".api_get_path(WEB_IMG_PATH)."anim-frame1.gif';\r\n
						currentImage = 1;\r\n
						return;
					}\r\n
				}\r\n
				document.".$this->form_name.".onsubmit = displayLoader;\r\n
				</script>";
    	
    }
}
?>