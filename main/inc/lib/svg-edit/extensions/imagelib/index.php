<?php
/* Integrate svg-edit libraries with Chamilo default documents
 * @author Juan Carlos Raña Trabado
 * @since 25/september/2010
*/
//Chamilo load libraries
require_once '../../../../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';

//Add security from Chamilo
api_protect_course_script();
api_block_anonymous_users();

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$curdirpath='/images/gallery'; //path of library directory

$course_info = api_get_course_info();

//get all files and folders
$docs_and_folders = DocumentManager::get_all_document_data($course_info, $curdirpath, 0, null, $is_allowed_to_edit, false);

//get all filenames
$array_to_search = is_array($docs_and_folders) ? $docs_and_folders : array();

if (count($array_to_search) > 0) {
	while (list($key) = each($array_to_search)) {
		$all_files[] = basename($array_to_search[$key]['path']);
	}
}

//get all svg and png files
$accepted_extensions = array('.svg', '.png');

if (is_array($all_files) && count($all_files) > 0) {
	foreach ($all_files as & $file) {
		$slideshow_extension = strrchr($file, '.');
		$slideshow_extension = strtolower($slideshow_extension);
		if (in_array($slideshow_extension, $accepted_extensions)) {
			$png_svg_files[] =$file;
		}
	}
}

$disk_path = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/document/images/gallery/';
$web_path  = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/document/images/gallery/';

$style = '<style>';
$style .= '@import "'.api_get_path(WEB_CSS_PATH).'base.css";';
$style .= '@import "'.api_get_path(WEB_CSS_PATH).api_get_visual_theme().'/default.css";';
$style .='</style>';

?>
<!doctype html>

<script src="../../jquery.js"></script><!--Chamilo TODO: compress this file and changing loads -->
<?php echo $style ?>
<body>
<?php
echo '<h2>'.get_lang('Course').': '.$course_info['name'].'</h2>';
if (!empty($png_svg_files)) {
	echo '<h3>'.get_lang('SelectSVGEditImage').'</h3>';
	echo '<ul>';
	foreach($png_svg_files as $filename) {
		$image=$disk_path.$filename;
		
		if (strpos($filename, "svg")){
			$new_sizes['width'] = 60;
			$new_sizes['height'] = 60;
		}
		else {
			$new_sizes = api_resize_image($image, 60, 60);
		}
		
		echo '<li style="display:inline; padding:8px;"><a href="'.$web_path.$filename.'" alt "'.$filename.'" title="'.$filename.'"><img src="'.$web_path.$filename.'" width="'.$new_sizes['width'].'" height="'.$new_sizes['height'].'" border="0"></a></li>';
	}
	echo '</ul>';
} else {
	Display::display_warning_message(get_lang('NoSVGImagesInImagesGalleryPath'));
}
?>
</body>
<script>
$('a').click(function() {
	var href = this.href;
	
	// Convert Non-SVG images to data URL first 
	// (this could also have been done server-side by the library)
	if(this.href.indexOf('.svg') === -1) {

		var meta_str = JSON.stringify({
			name: $(this).text(),
			id: href
		});
		window.top.postMessage(meta_str, "*");
	
		var img = new Image();
		img.onload = function() {
			var canvas = document.createElement("canvas");
			canvas.width = this.width;
			canvas.height = this.height;
			// load the raster image into the canvas
			canvas.getContext("2d").drawImage(this,0,0);
			// retrieve the data: URL
			try {
				var dataurl = canvas.toDataURL();
			} catch(err) {
				// This fails in Firefox with file:// URLs :(
				alert("Data URL conversion failed: " + err);
				var dataurl = "";
			}
			window.top.postMessage('|' + href + '|' + dataurl, "*");
		}
		img.src = href;
	} else {
		// Send metadata (also indicates file is about to be sent)
		var meta_str = JSON.stringify({
			name: $(this).text(),
			id: href
		});
		window.top.postMessage(meta_str, "*");
		// Do ajax request for image's href value
		$.get(href, function(data) {
			data = '|' + href + '|' + data;
			// This is where the magic happens!
			window.top.postMessage(data, "*");
			
		}, 'html'); // 'html' is necessary to keep returned data as a string
	}
	return false;
});
</script>