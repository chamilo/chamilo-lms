<div id="content">
<?php
$count = 1;

$thumbnailBaseUrl = CONFIG_URL_IMG_THUMBNAIL;

foreach ($fileList as $file) {
	
	///First step for hidden some type of Chamilo files and folders
	//Juan Carlos Ra�a

	//hidden files and folders deleted by Chamilo. Hidde folders css, hotpotatoes, chat, certificates
	$deleted_by_chamilo_file=' DELETED '; // ' DELETED ' not '_DELETED_' because in $file['name'] _ is replaced with blank see class.manager.php
	$deleted_by_chamilo_folder='_DELETED_';
	$css_folder_chamilo='css';
	$hotpotatoes_folder_chamilo='HotPotatoes_files';
	$chat_files_chamilo='chat_files';
	$certificates_chamilo='certificates';
	//hidden directory of the group if the user is not a member of the group
	$group_folder='_groupdocs';


	//show group's directory only if I'm member. Or I'm a teacher
	$show_doc_group=true;
	if(ereg($group_folder, $file['path']))
	{
		$show_doc_group=false;
		if($is_user_in_group ||( $to_group_id!=0 && api_is_allowed_to_edit()))
		{
			$show_doc_group=true;
		}
	}

	//show icon sharedfolder
	 $shared_folder='shared folder';	 //'shared folder' not 'shared_folder' because  in $file['name'] _ is replaced with blank see class.manager.php
	
	///Second step: hiding as the case
	//Juan Carlos Ra�a
	if ((!ereg($deleted_by_chamilo_file, $file['name']) && 
		!ereg($deleted_by_chamilo_folder, $file['path'])) && 
		!ereg($css_folder_chamilo, $file['path']) && 
		!ereg($hotpotatoes_folder_chamilo, $file['path']) && 
		!ereg($chat_files_chamilo, $file['path']) && 
		!ereg($certificates_chamilo, $file['path']) && $show_doc_group && $file['name'][0]!='.') {		
	
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
					echo '<img src="' . appendQueryString($thumbnailBaseUrl, 'path=' . base64_encode($file['path'])) . '" id="thumbImg' . $count . '"></a>' . "\n";
					break;
			case 'fileFlash':
			case 'fileVideo':
			case 'fileMusic':
				break;
			default:
				echo '&nbsp;';
		}
		if(Security::remove_XSS($_GET['editor'])!='stand_alone'){										
			$path_chamilo_file='../'.$file['path'];// fix for makes a good show when pressed next on window preview, don't only one image
		}
		else{
			$path_chamilo_file=$file['path'];
		}
	?>
		
		</dt>
		<dd id="dd<?php echo $count; ?>" class="thumbnailListing_info"><span id="flag<?php echo $count; ?>" class="<?php echo $file['flag']; ?>">&nbsp;</span><input id="cb<?php echo $count; ?>" type="checkbox" name="check[]" <?php echo ($file['is_writable']?'':'disabled'); ?> class="radio" value="<?php echo $file['path']; ?>" />
		<a <?php echo ($file['cssClass']== 'filePicture'?'rel="orgImg"':''); ?> href="<?php echo $path_chamilo_file;// fix for Chamilo ?>" title="<?php echo $file['name']; ?>" id="a<?php echo $count; ?>"><?php echo shortenFileName($file['name']); ?></a></dd>
                </dl>
                <?php
			}//end if hidden files and folders deleted by Chamilo
			$count++;
		}
?>
</div>