<?php
/* For licensing terms, see /license.txt */
/**
 * @author Patrick Cool patrick.cool@UGent.be Ghent University Mai 2004
 * @author Julio Montoya Lots of improvements, cleaning, adding security
 * @author Juan Carlos Raña Trabado herodoto@telefonica.net	January 2008
 * @package chamilo.document
 */
/**
 * Code
 */
// Language files that need to be included
$language_file = array('slideshow', 'document');

require_once '../inc/global.inc.php';


api_protect_course_script();

$noPHP_SELF = true;
$path = Security::remove_XSS($_GET['curdirpath']);
$pathurl = urlencode($path);
$slide_id = Security::remove_XSS($_GET['slide_id']);

if(empty($slide_id)) {
	$edit_slide_id = 1;
} else {
	$edit_slide_id = $slide_id;
}

if ($path != '/') {
	$folder = $path.'/';
} else {
	$folder = '/';
}
$sys_course_path = api_get_path(SYS_COURSE_PATH);
	
// Including the functions for the slideshow
require_once 'slideshow.inc.php';

// Breadcrumb navigation
$url = 'document.php?curdirpath='.$pathurl;
$originaltoolname = get_lang('Documents');
$interbreadcrumb[] = array('url' => Security::remove_XSS($url), 'name' => $originaltoolname);

// Because $nametools uses $_SERVER['PHP_SELF'] for the breadcrumbs instead of $_SERVER['REQUEST_URI'], I had to
// bypass the $nametools thing and use <b></b> tags in the $interbreadcrump array
//$url = 'slideshow.php?curdirpath='.$pathurl;
$originaltoolname = get_lang('SlideShow');
//$interbreadcrumb[] = array('url'=>$url, 'name' => $originaltoolname);

Display :: display_header($originaltoolname, 'Doc');

// Loading the slides from the session
if (isset($_SESSION['image_files_only'])) {
	$image_files_only = $_SESSION['image_files_only'];
}

// Calculating the current slide, next slide, previous slide and the number of slides
if ($slide_id != 'all') {
	$slide = $slide_id ? $slide_id : 0;
	$previous_slide = $slide - 1;
	$next_slide = $slide + 1;
}
$total_slides = count($image_files_only);
?>
<script language="JavaScript" type="text/javascript">
<!--
function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}
//-->
</script>

<div class="actions">
<?php

if ($slide_id != 'all') {
	$image = $sys_course_path.$_course['path'].'/document'.$folder.$image_files_only[$slide];
	if (file_exists($image)) {

		echo '<div style="float: right; vertical-align: middle; padding-top: 3px; padding-bottom: 3px;"><nobr>';

		$a_style = 'margin-left: 0px; margin-right: 0px; padding-left: 0px; padding-right: 0px;';
		$img_style = 'margin-left: 5px; margin-right: 5px; padding-left: 0px; padding-right: 0px;';

		// Back forward buttons
		if ($slide == 0) {
			$imgp = 'action_prev_na.png';
			$first = '<img src="'.api_get_path(WEB_IMG_PATH).'action_first_na.png" style="'.$img_style.'">';
		} else {
			$imgp = 'action_prev.png';
			$first = '<a href="slideshow.php?slide_id=0&curdirpath='.$pathurl.'" style="'.$a_style.'"><img src="'.api_get_path(WEB_IMG_PATH).'action_first.png"  style="'.$img_style.'" title="'.get_lang('FirstSlide').'" alt="'.get_lang('FirstSlide').'"></a>';
		}

		// First slide
		echo $first;

		// Previous slide
		if ($slide > 0) {
			echo '<a href="slideshow.php?slide_id='.$previous_slide.'&amp;curdirpath='.$pathurl.'" style="'.$a_style.'">';
		}
		echo '<img src="'.api_get_path(WEB_IMG_PATH).$imgp.'" style="'.$img_style.'" title="'.get_lang('Previous').'" alt="'.get_lang('Previous').'">';
		if ($slide > 0) {
			echo '</a>';
		}

		// Divider
		echo ' [ '.$next_slide.'/'.$total_slides.' ] ';

		// Next slide
		if ($slide < $total_slides - 1) {
			echo '<a href="slideshow.php?slide_id='.$next_slide.'&curdirpath='.$pathurl.'" style="'.$a_style.'">';
		}
		if ($slide == $total_slides - 1) {
			$imgn = 'action_next_na.png';
			$last = '<img src="'.api_get_path(WEB_IMG_PATH).'action_last_na.png" style="'.$img_style.'" title="'.get_lang('LastSlide').'" alt="'.get_lang('LastSlide').'">';
		} else {
			$imgn = 'action_next.png';
			$last = '<a href="slideshow.php?slide_id='.($total_slides-1).'&curdirpath='.$pathurl.'" style="'.$a_style.'"><img src="'.api_get_path(WEB_IMG_PATH).'action_last.png" style="'.$img_style.'" title="'.get_lang('LastSlide').'" alt="'.get_lang('LastSlide').'"></a>';
		}
		echo '<img src="'.api_get_path(WEB_IMG_PATH).$imgn.'" style="'.$img_style.'" title="'.get_lang('Next').'" alt="'.get_lang('Next').'">';
		if ($slide > 0) {
			echo '</a>';
		}

		// Last slide
		echo $last;

		echo '</nobr></div>';
	}
}

// Exit the slideshow
echo '<a href="document.php?action=exit_slideshow&curdirpath='.$pathurl.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'',ICON_SIZE_MEDIUM).'</a>';

// Show thumbnails
if ($slide_id != 'all') {
	echo '<a href="slideshow.php?slide_id=all&curdirpath='.$pathurl.'">'.Display::return_icon('thumbnails.png',get_lang('_show_thumbnails'),'',ICON_SIZE_MEDIUM).'</a>';
} else {
	echo Display::return_icon('thumbnails_na.png',get_lang('_show_thumbnails'),'',ICON_SIZE_MEDIUM);
}
// Slideshow options
echo '<a href="slideshowoptions.php?curdirpath='.$pathurl.'">'.Display::return_icon('settings.png',get_lang('_set_slideshow_options'),'',ICON_SIZE_MEDIUM).'</a>';

?>
</div>

<?php
echo '<br />';

/*	TREATING THE POST DATA FROM SLIDESHOW OPTIONS */

// If we come from slideshowoptions.php we sessionize (new word !!! ;-) the options
if (isset($_POST['Submit'])) {
	// We come from slideshowoptions.php
	
	//$_SESSION["auto_image_resizing"]=Security::remove_XSS($_POST['auto_radio_resizing']);
	
	$_SESSION["image_resizing"] = Security::remove_XSS($_POST['radio_resizing']);
	
	if ($_POST['radio_resizing'] == "resizing" && $_POST['width'] != '' && $_POST['height'] != '') {
		//echo "resizing";
		$_SESSION["image_resizing_width"] = Security::remove_XSS($_POST['width']);
		$_SESSION["image_resizing_height"] = Security::remove_XSS($_POST['height']);
	} else {
		//echo "unsetting the session heighte and width";
		$_SESSION["image_resizing_width"] = null;
		$_SESSION["image_resizing_height"] = null;
	}
}
$target_width = $target_height = null;
// The target height and width depends if we choose resizing or no resizing
if (isset($_SESSION["image_resizing"]) &&  $_SESSION["image_resizing"] == "resizing") {
	$target_width = $_SESSION["image_resizing_width"];
	$target_height = $_SESSION["image_resizing_height"];
}

/*	THUMBNAIL VIEW */

// This is for viewing all the images in the slideshow as thumbnails.
$image_tag = array ();
if ($slide_id == 'all') {
	
	// Config for make thumbnails	
	$allowed_thumbnail_types = array('jpg','jpeg','gif','png');
	$max_thumbnail_width     = 100;
	$max_thumbnail_height    = 100;
	$png_compression	     = 0;//0(none)-9
	$jpg_quality  	         = 75;//from 0 to 100 (default is 75). More quality less compression
	
	$directory_thumbnails=$sys_course_path.$_course['path'].'/document'.$folder.'.thumbs/';
	
	//Other parameters only for show tumbnails
	$row_items 			     = 4;//only in slideshow.php
	$number_image 			 = 7;//num icons cols to show
	$thumbnail_width_frame=$max_thumbnail_width;//optional $max_thumbnail_width+x
	$thumbnail_height_frame=$max_thumbnail_height;
	
	// Create the template_thumbnails folder (if no exist)
	
	
	if (!file_exists($directory_thumbnails)) {
		@mkdir($directory_thumbnails, api_get_permissions_for_new_directories());
    }
	
	/*
	//
	//disabled by now, because automatic mode is heavy for server (scandir), only manual
	//
	
	// Delete orphaned thumbnails
	$directory_images=$sys_course_path.$_course['path'].'/document'.$folder;
	$all_thumbnails  = scandir($directory_thumbnails);
	$all_files  = scandir($directory_images);
	foreach ($all_thumbnails as $check_thumb) {
		$temp_filename=substr($check_thumb,1);//erase the first dot in file, and translate .. to .
		if ($temp_filename=='.') {
			 continue; //need because scandir also return . and .. simbols
		}
		if(in_array($filename, $all_files)==false) {
			unlink($directory_thumbnails.'.'.$temp_filename);
		}
	}
	*/
			  
	// check files and thumbnails
	if (is_array($image_files_only)) {

		foreach ($image_files_only as $one_image_file) {
			$image = $sys_course_path.$_course['path'].'/document'.$folder.$one_image_file;
			$image_thumbnail= $directory_thumbnails.'.'.$one_image_file;

			if (file_exists($image)) {
				//check thumbnail
				$imagetype = explode(".", $image);
				$imagetype = strtolower($imagetype[count($imagetype)-1]);//or check $imagetype = image_type_to_extension(exif_imagetype($image), false);
				
				if(in_array($imagetype,$allowed_thumbnail_types)) {
					if (!file_exists($image_thumbnail)){
						$original_image_size = api_getimagesize($image);//run each once we view thumbnails is too heavy, then need move into  !file_exists($image_thumbnail, and only run when haven't the thumbnail		
						switch($imagetype) {
							case 'gif':
								$source_img = imagecreatefromgif($image);
								break;
							case 'jpg':
								$source_img = imagecreatefromjpeg($image);
								break;
							case 'jpeg':
								$source_img = imagecreatefromjpeg($image);
								break;
							case 'png':
								$source_img = imagecreatefrompng($image);
								break;
						}
						
						$new_thumbnail_size = api_calculate_image_size($original_image_size['width'], $original_image_size['height'], $max_thumbnail_width, $max_thumbnail_height);
						$crop = imagecreatetruecolor($new_thumbnail_size['width'], $new_thumbnail_size['height']);
						
						// preserve transparency
						if($imagetype == "png"){
							imagesavealpha($crop, true);
							$color = imagecolorallocatealpha($crop,0x00,0x00,0x00,127);
							imagefill($crop, 0, 0, $color); 
						}
						
						if ($imagetype == "gif") {
							$transindex = imagecolortransparent($source_img);
                            $palletsize = imagecolorstotal($source_img);
							 //GIF89a for transparent and anim (first clip), either GIF87a
							 if ($transindex >= 0 && $transindex < $palletsize){
								 $transcol = imagecolorsforindex($source_img, $transindex);
								 $transindex = imagecolorallocatealpha($crop, $transcol['red'], $transcol['green'], $transcol['blue'], 127);
								 imagefill($crop, 0, 0, $transindex);
								 imagecolortransparent($crop, $transindex);
							 }
						}

						//resampled image
						imagecopyresampled($crop,$source_img,0,0,0,0,$new_thumbnail_size['width'],$new_thumbnail_size['height'],$original_image_size['width'],$original_image_size['height']);
						
						switch($imagetype) {
							case 'gif':
								imagegif($crop,$image_thumbnail);
								break;
							case 'jpg':
								imagejpeg($crop,$image_thumbnail,$jpg_quality);
								break;
							case 'jpeg':
								imagejpeg($crop,$image_thumbnail,$jpg_quality);
								break;
							case 'png':
								imagepng($crop,$image_thumbnail,$png_compression);
								break;
						}
		
						//clean memory
						imagedestroy($crop);					
					}//end !exist thumbnail
					
					//show thumbnail and link
					$one_image_thumbnail_file='.thumbs/.'.$one_image_file;//get path thumbnail
					$doc_url = ($path && $path !== '/') ? $path.'/'.$one_image_thumbnail_file : $path.$one_image_thumbnail_file;
					$image_tag[] = '<img src="download.php?doc_url='.$doc_url.'" border="0" title="'.$one_image_file.'">';	
				}
				else{
					//if images aren't support by gd (not gif, jpg, jpeg, png)
					
					if ($imagetype=="bmp"){
						$original_image_size = api_getimagesize($image);//it isn't heavey here because thumbnails are already created. Here only for no gif, jpg, or png image files. But if there are many image files no supported can be heavy here too.
						if($original_image_size['width']>$max_thumbnail_width || $original_image_size['height']>$max_thumbnail_height){
							$image_height_width = resize_image($image, $max_thumbnail_width, $max_thumbnail_height, 1);
							$image_height = $image_height_width[0];
							$image_width = $image_height_width[1];
						}
						else{
							$image_height=$original_image_size['height'];
							$image_width=$original_image_size['width'];
						}
					}
					else{
						//example for svg files
						$image_width=$max_thumbnail_width;
						$image_height=$max_thumbnail_height;
					}

					$doc_url = ($path && $path !== '/') ? $path.'/'.$one_image_file : $path.$one_image_file;
					$image_tag[] = '<img src="download.php?doc_url='.$doc_url.'" border="0" width="'.$image_width.'" height="'.$image_height.'" title="'.$one_image_file.'">';	
					
				}//end allowed image types
			}//end if exist file image
		}//end foreach
	}//end image files only
}

// Creating the table
$html_table = '';
echo '<table align="center" width="760px" border="0" cellspacing="10">';
$i = 0;
$count_image = count($image_tag);
$number_iteration = ceil($count_image/$number_image);
$p = 0;
for ($k = 0; $k < $number_iteration; $k++) {
	echo '<tr height="'.$thumbnail_height.'">';
	for ($i = 0; $i < $number_image; $i++) {
		if (!is_null($image_tag[$p])) {
			echo '<td>';
			//TODO:move styles to css files and center image vertical
			?>
            
			<style>
			div.thumbnail:hover {
			  border-color: #0088cc;
			  background-color:#FBFFFF;
			  -webkit-box-shadow: 0 1px 4px rgba(0, 105, 214, 0.25);
			  -moz-box-shadow: 0 1px 4px rgba(0, 105, 214, 0.25);
			  box-shadow: 0 1px 4px rgba(0, 105, 214, 0.25);
			}
			</style>

			<?php			
			echo '<div class="thumbnail" style="text-align: center; margin:5px; padding:9px; height:'.$thumbnail_height_frame.'px; width:'.$thumbnail_width_frame.'px">';
			echo '<a href="slideshow.php?slide_id='.$p.'&curdirpath='.$pathurl.'">'.$image_tag[$p].'</a>';
			echo '</div>';
			echo '</td>';
		}
		$p++;
	}
	echo '</tr>';
}
echo '</table>';






/*	ONE AT A TIME VIEW */
$course_id = api_get_course_int_id();

// This is for viewing all the images in the slideshow one at a time.
if ($slide_id != 'all') {

	if (file_exists($image)) {
		$image_height_width = resize_image($image, $target_width, $target_height);

		$image_height = $image_height_width[0];
		$image_width = $image_height_width[1];
	
		$height_width_tags = null;
		if (isset($_SESSION['image_resizing']) && $_SESSION['image_resizing'] == 'resizing') {
			$height_width_tags = 'width="'.$image_width.'" height="'.$image_height.'"';
		}

		// This is done really quickly and should be cleaned up a little bit using the API functions
		$tbl_documents = Database::get_course_table(TABLE_DOCUMENT);
		if ($path == '/') {
			$pathpart = '/';
		} else {
			$pathpart = $path.'/';
		}
		$sql = "SELECT * FROM $tbl_documents WHERE c_id = $course_id AND path='".Database::escape_string($pathpart.$image_files_only[$slide])."'";
		$result = Database::query($sql);
		$row = Database::fetch_array($result);

		echo '<table align="center" border="0" cellspacing="10">';
		echo '<tr>';
		echo '<td style="padding:10px;" align="center">';
		echo Display::tag('h2',$row['title']);
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td id="td_image" align="center">';
		if ($slide < $total_slides - 1 && $slide_id != 'all') {
			echo "<a href='slideshow.php?slide_id=".$next_slide."&curdirpath=$pathurl'>";
		} else {
			echo "<a href='slideshow.php?slide_id=0&curdirpath=$pathurl'>";
		}
        if ($path == '/') {
        	$path = '';
        }

		list($width, $height) = getimagesize($image);
		
		//auto resize
		if($_SESSION["image_resizing"]!="noresizing" && $_SESSION["image_resizing"]!="resizing" ){
		?>
        
		<script type="text/javascript">
			var initial_width='<?php echo $width; ?>';
			var initial_height='<?php echo $height; ?>';
			var height = window.innerHeight -320;
			var width = window.innerWidth -360;
			
			if (initial_height>height || initial_width>width) {
				start_width=width;
				start_height=height;
			}
			else{
				start_width=initial_width;
				start_height=initial_height;
			}
			
			document.write ('<img id="image"  src="<?php echo  'download.php?doc_url='.$path.'/'.$image_files_only[$slide]; ?>" width="'+start_width+'" height="'+start_height+'"  border="0"  alt="<?php echo $image_files_only[$slide] ;?>">');

			function resizeImage() {
				
				var resize_factor_width = width / initial_width;
                var resize_factor_height = height / initial_height;
                var delta_width = width - initial_width * resize_factor_height;
    			var delta_height = height - initial_height * resize_factor_width;
    
				if (delta_width > delta_height) {
					width = Math.ceil(initial_width * resize_factor_height);
					height= Math.ceil(initial_height * resize_factor_height);
				}
				else if(delta_width < delta_height) {
					width = Math.ceil(initial_width * resize_factor_width);
					height = Math.ceil(initial_height * resize_factor_width);
				}
				else {
					width = Math.ceil(width);
					height = Math.ceil(height);
				}
				
				document.getElementById('image').style.height = height +"px";
				document.getElementById('image').style.width = width +"px";
				document.getElementById('td_image').style.background='none';
				document.getElementById('image').style.visibility='visible';
			};
			
			 if (initial_height>height || initial_width>width) {
				document.getElementById('image').style.visibility='hidden';
				document.getElementById('td_image').style.background='url(../img/loadingAnimation.gif) center no-repeat';
				document.getElementById('image').onload = resizeImage;
			    window.onresize = resizeImage;
			}

		</script>
    <?php
		}
		else{
		
			echo "<img src='download.php?doc_url=$path/".$image_files_only[$slide]."' alt='".$image_files_only[$slide]."' border='0'".$height_width_tags.">";
		}
		
		echo '</a>';
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>';
		echo $row['comment'];
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo '<table align="center" border="0">';
		if (api_is_allowed_to_edit(null, true)) {
			echo '<tr>';
			echo '<td align="center">';
			echo '<a href="edit_document.php?'.api_get_cidreq().'&id='.$row['id'].'&origin=slideshow&amp;origin_opt='.$edit_slide_id.'&amp;">
			      <img src="../img/edit.gif" border="0" title="'.get_lang('Modify').'" alt="'.get_lang('Modify').'" /></a><br />';
			$aux = explode('.', htmlspecialchars($image_files_only[$slide]));
			$ext = $aux[count($aux) - 1];
			echo $image_files_only[$slide].' <br />';
			echo $width.' x '.$height.' <br />';
			echo round((filesize($image)/1024), 2).' KB';
			echo ' - '.$ext;
			echo '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td align="center">';
			if ($_SESSION['image_resizing'] == 'resizing') {
				$resize_info = get_lang('_resizing').'<br />';
				$resize_widht = $_SESSION["image_resizing_width"].' x ';
				$resize_height = $_SESSION['image_resizing_height'];
			}
			elseif($_SESSION['image_resizing'] != 'noresizing'){
				$resize_info = get_lang('_resizing').'<br />';
				$resize_widht = get_lang('Auto').' x ';
				$resize_height = get_lang('Auto');
			} else {
				$resize_info = get_lang('_no_resizing').'<br />';
			}
			echo $resize_info;
			echo $resize_widht;
			echo $resize_height;
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';

	} else {
		Display::display_warning_message(get_lang('FileNotFound'));
	}
}

Display :: display_footer();
