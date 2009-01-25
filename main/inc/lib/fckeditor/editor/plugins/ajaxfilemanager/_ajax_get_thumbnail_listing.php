<div id="content">
<?php 

		$count = 1;
		$thumbnailBaseUrl = appendQueryString(CONFIG_URL_IMG_THUMBNAIL, makeQueryString(array('path')));
		foreach($fileList as $file)
		
		{
			///First step for hidden some type of Dokeos files and folders 
			//Juan Carlos Raña
			
				//hidden files and folders deleted by Dokeos. Hidde folders css
				$deleted_by_dokeos='_DELETED_';
				$css_folder_dokeos='css';
				
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
				$shared_folder='shared_folder';
		
			///Second step: hiding as the case
			//Juan Carlos Raña
			if((!ereg($deleted_by_dokeos, $file['name']) || !ereg($deleted_by_dokeos, $file['path'])) && !ereg($css_folder_dokeos, $file['path']) && $show_doc_group==true && $file['name'][0]!='.')
			{
						
				?>
				<dl class="thumbnailListing" id="dl<?php echo $count; ?>">
                
                <?php
                 if(ereg($shared_folder, $file['name']))
                 { //add icon in ajaxfilemanager if sharedfolder is in Dokeos
                ?>
                
                	<dt id="dt<?php echo $count; ?>" class="<?php echo ($file['type'] == 'folder' && empty($file['file']) || empty($file['subdir'])?'folderShared':$file['cssClass']); ?>" class="<?php echo $file['cssClass']; ?>">
                <?php
				}
				else
				{
				?>
                
                	<dt id="dt<?php echo $count; ?>" class="<?php echo ($file['type'] == 'folder' && empty($file['file']) && empty($file['subdir'])?'folderEmpty':$file['cssClass']); ?>" class="<?php echo $file['cssClass']; ?>">
                
				<?php
				}
				?>       
                					
                    
					<?php
						switch($file['cssClass'])
						{
							case 'filePicture':
									echo '<a id="thumbUrl' . $count . '" rel="thumbPhotos" href="' . $file['path'] . '">';
									echo '<img src="' . appendQueryString($thumbnailBaseUrl, 'path=' . $file['path']) . '" id="thumbImg' . $count . '"></a>' . "\n";
									break;
							case 'fileFlash':
							case 'fileVideo':
							case 'fileMusic':
								break;
							default:
								echo '&nbsp;';
						}
					?>
					
					</dt>
					<dd id="dd<?php echo $count; ?>" class="thumbnailListing_info"><span id="flag<?php echo $count; ?>" class="<?php echo $file['flag']; ?>">&nbsp;</span><input id="cb<?php echo $count; ?>" type="checkbox" name="check[]" <?php echo ($file['is_writable']?'':'disabled'); ?> class="radio" value="<?php echo $file['path']; ?>" />
					<a <?php echo ($file['cssClass']== 'filePicture'?'rel="orgImg"':''); ?> href="<?php echo "../".$file['path']; ?>" title="<?php echo $file['name']; ?>" id="a<?php echo $count; ?>"><?php echo shortenFileName($file['name']); ?></a></dd><!-- Juan Carlos Raña Fix for Dokeos: On the path I put a directory up echo "../".$ file [ 'path'], what makes good show when pressed next on window preview, don't only one image -->
					
				</dl>
				<?php
				
				}//end if hidden files and folders deleted by Dokeos
				
			$count++;
		}
?>
</div>