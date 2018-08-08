<?php
/* Integrate svg-edit libraries with Chamilo default documents
 * @author Juan Carlos Raña Trabado
 * @since 25/september/2010
*/

require_once '../../../../../../inc/global.inc.php';

//Add security from Chamilo
api_protect_course_script();
api_block_anonymous_users();

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$course_info = api_get_course_info();
$groupId = api_get_group_id();

$group_properties = GroupManager::get_group_properties($groupId);
$groupIid = isset($group_properties['iid']) ? $group_properties['iid'] : 0;
$groupdirpath = $group_properties['directory'];
$group_disk_path = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/document'.$groupdirpath.'/';
$group_web_path = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/document'.$groupdirpath.'/';

//get all group files and folders
$docs_and_folders = DocumentManager::getAllDocumentData(
    $course_info,
    $groupdirpath,
    $groupIid,
    null,
    $is_allowed_to_edit,
    false
);

//get all group filenames
$array_to_search = !empty($docs_and_folders) ? $docs_and_folders : [];
$all_files = [];

if (count($array_to_search) > 0) {
    foreach ($array_to_search as $key => $value) {
		$all_files[] = basename($array_to_search[$key]['path']);
	}
}

//get all svg and png group files
$accepted_extensions = array('.svg', '.png');
$png_svg_files = [];

if (is_array($all_files) && count($all_files) > 0) {
    foreach ($all_files as & $file) {
        $slideshow_extension = strrchr($file, '.');
        $slideshow_extension = strtolower($slideshow_extension);
        if (in_array($slideshow_extension, $accepted_extensions)) {
            $png_svg_files[] =$file;
        }
    }
}

$style = '<style>';
$style .= '@import "'.api_get_path(WEB_CSS_PATH).'base.css";';
$style .= '@import "'.api_get_path(WEB_CSS_PATH).'themes/'.api_get_visual_theme().'/default.css";';
$style .='</style>';

?>
<!doctype html>
<?php echo api_get_jquery_js(); ?>
<?php echo $style ?>
<body>
<?php
echo '<h2>'.get_lang('GroupSingle').': '.$group_properties['name'].'</h2>';

if ((
    $group_properties['doc_state'] == 2 &&
    ($is_allowed_to_edit || GroupManager :: is_user_in_group($_user['user_id'], $group_properties))) ||
    $group_properties['doc_state'] == 1
){

	if (!empty($png_svg_files)) {
		echo '<h3>'.get_lang('SelectSVGEditImage').'</h3>';
		echo '<ul>';
		foreach($png_svg_files as $filename) {
			$image = $group_disk_path.$filename;

			if (strpos($filename, "svg")){
				$new_sizes['width'] = 60;
				$new_sizes['height'] = 60;
			} else {
				$new_sizes = api_resize_image($image, 60, 60);
			}
            echo '<li style="display:inline; padding:8px;">';
            echo '<a href = "'.$group_web_path.$filename.'" alt="'.$filename.'" title="'.$filename.'">';
            echo '<img src = "'.$group_web_path.$filename.'" width = "'.$new_sizes['width'].'" height="'.$new_sizes['height'].'" border="0"></a></li>';
		}
		echo '</ul>';
	}
} else {
	echo Display::return_message(get_lang('OnlyAccessFromYourGroup'), 'warning');
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
