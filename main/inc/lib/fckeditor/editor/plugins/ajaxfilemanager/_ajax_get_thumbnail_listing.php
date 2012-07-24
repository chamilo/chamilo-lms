<div id="content">
<?php
$count = 1;
$thumbnailBaseUrl = CONFIG_URL_IMG_THUMBNAIL;

foreach ($fileList as $file) {
	
	///First step for hidden some type of Chamilo files and folders
	//Juan Carlos Raña Trabado

	//hidden files and folders deleted by Chamilo. Hidde folders css, hotpotatoes, chat, certificates
	$deleted_by_chamilo_file	= ' DELETED '; // ' DELETED ' not '_DELETED_' because in $file['name'] _ is replaced with blank see class.manager.php
	$deleted_by_chamilo_folder	= '_DELETED_';
	$css_folder_chamilo			= 'css';
	$hotpotatoes_folder_chamilo	= 'HotPotatoes_files';
	$chat_files_chamilo			= 'chat_files';
	$certificates_chamilo		= 'certificates';
	//hidden directory of the group if the user is not a member of the group
	$group_folder				= '_groupdocs';
	

	//show group's directory only if I'm member. Or I'm a teacher
	$show_doc_group=true;
	if(ereg($group_folder, $file['path'])) {
		$show_doc_group=false;
		if($is_user_in_group ||( $to_group_id!=0 && api_is_allowed_to_edit())) {
			$show_doc_group=true;
		}
	}

	//show icon sharedfolder
	 $shared_folder='shared folder';	 //'shared folder' not 'shared_folder' because  in $file['name'] _ is replaced with blank see class.manager.php
	
	///Second step: hiding as the case
	//Juan Carlos Raña Trabado
	if ((!ereg($deleted_by_chamilo_file, $file['name']) && 
		!ereg($deleted_by_chamilo_folder, $file['path'])) && 
		!ereg($css_folder_chamilo, $file['path']) && 
		!ereg($hotpotatoes_folder_chamilo, $file['path']) && 
		!ereg($chat_files_chamilo, $file['path']) && 
		!ereg($certificates_chamilo, $file['path']) && $show_doc_group && $file['name'][0]!='.') {	
		//hide Nanogong  tag
		if (strpos($file['path'], '_chnano_')) {
			$file['path']= substr_replace($file['path'], '.wav', -12);//into real file name
			$file['name']= substr_replace($file['name'], '.wav', -12);//into web name
		}

	?>
		<dl class="thumbnailListing" id="dl<?php echo $count; ?>">
			 <?php
			 if(preg_match('/shared_folder/', basename($file['path']))) {
				//add icon into ajaxfilemanager if sharedfolder is in Chamilo
			?>
				<dt id="dt<?php echo $count; ?>" class="<?php echo ($file['type'] == 'folder' || empty($file['file']) || empty($file['subdir'])?'folderShared':$file['cssClass']); ?>" class="<?php echo $file['cssClass']; ?>">
			<?php
			} elseif(preg_match('/sf_user_/', basename($file['path']))) {				
			?>
				<dt id="dt<?php echo $count; ?>" class="<?php echo ($file['type'] == 'folder' || empty($file['file']) || empty($file['subdir'])?'unknownUser':$file['cssClass']); ?>" class="<?php echo $file['cssClass']; ?>">
			<?php
			} else {
			?>
				<dt id="dt<?php echo $count; ?>" class="<?php echo ($file['type'] == 'folder' && empty($file['file']) && empty($file['subdir'])?'folderEmpty':$file['cssClass']); ?>" class="<?php echo $file['cssClass']; ?>">
			<?php
			}
			switch($file['cssClass']) {			
				case 'filePicture':
					echo '<a id="thumbUrl' . $count . '" rel="thumbPhotos" href="' . $file['public_path'] . '">';
					///////////////////////////////////////// Chamilo create  thumbnail
					
					//setting
					$allowed_thumbnail_types = array('jpg','jpeg','gif','png');
					$max_thumbnail_width  = 100;
					$max_thumbnail_height = 100;
					$png_compression	  = 0;//0(none)-9
					$jpg_quality  	      = 75;//from 0 to 100 (default is 75). More quality less compression
					
					$directory_thumbnails=dirname($file['path']).'/.thumbs/';
					
					if (!file_exists($directory_thumbnails)) {
						@mkdir($directory_thumbnails, api_get_permissions_for_new_directories());
   				 	}
					
					/*
					//
					//disabled by now, because automatic mode is heavy for server (scandir), only manual please
					//
					
					// Delete orphaned thumbnails
					$directory_images=dirname($file['path']);
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
					
					//create thumbnails

					$image=$file['path'];
					$image_thumbnail= $directory_thumbnails.'.'.basename($file['path']);

					if (file_exists($image)) {
						//check thumbnail
						$imagetype = explode(".", $image);
						$imagetype = strtolower($imagetype[count($imagetype)-1]);//or check $imagetype = image_type_to_extension(exif_imagetype($image), false);
						
						if (in_array($imagetype,$allowed_thumbnail_types)) {
							
							if (!file_exists($image_thumbnail)) {
								$original_image_size = api_getimagesize($image);//run each once we view thumbnails is too heavy, then need move into  !file_exists($image_thumbnail, and only run when haven't the thumbnail
									
								switch ($imagetype) {
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
								
								if($max_thumbnail_width>$original_image_size['width'] && $max_thumbnail_height>$original_image_size['height']){
									$new_thumbnail_size['width']=$original_image_size['width'];
									$new_thumbnail_size['height']=$original_image_size['height'];
								}

								$crop = imagecreatetruecolor($new_thumbnail_size['width'], $new_thumbnail_size['height']);
								
								// preserve transparency
								if ($imagetype == "png") {
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
							
							//show thumbnail
							echo '<img src="' . appendQueryString($thumbnailBaseUrl, ' path=' . base64_encode($image_thumbnail)) . '" id="thumbImg' . $count . '"></a>' . "\n";
						}
						else{
							
							echo '<img src="' . appendQueryString($thumbnailBaseUrl, ' path=' . base64_encode($file['path'])) . '" id="thumbImg' . $count . '"></a>' . "\n";
						}//end allowed image types
					}//end if exist file image
					
			///////////////////////////////////////// End Chamilo create  thumbnail

					break;
				case 'fileFlash':
				case 'fileVideo':
				case 'fileMusic':
					break;
				default:
					echo '&nbsp;';
			}
			
			if ($_GET['editor'] != 'stand_alone') {										
				$path_chamilo_file ='../'.$file['path'];// fix for makes a good show when pressed next on window preview, don't only one image
			} else{
				$path_chamilo_file = $file['path'];
			}
		?>		
		</dt>
		<dd id="dd<?php echo $count; ?>" class="thumbnailListing_info">
			<span id="flag<?php echo $count; ?>" class="<?php echo $file['flag']; ?>">&nbsp;</span>
			<input id="cb<?php echo $count; ?>" type="checkbox" name="check[]" <?php echo ($file['is_writable']?'':'disabled'); ?> class="radio" value="<?php echo $file['path']; ?>" />
			<a <?php echo ($file['cssClass']== 'filePicture'?'rel="orgImg"':''); ?> href="<?php echo $path_chamilo_file;// fix for Chamilo ?>" title="<?php echo $file['name']; ?>" id="a<?php echo $count; ?>">
				<?php echo shortenFileName($file['name']); ?>
			</a>
		</dd>
        </dl>
        <?php
			}//end if hidden files and folders deleted by Chamilo
			$count++;
		}
?>
</div>