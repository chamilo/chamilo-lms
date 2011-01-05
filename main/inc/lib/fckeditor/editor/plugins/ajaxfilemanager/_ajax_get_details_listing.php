<div id="content">
<table class="tableList" id="tableList" cellpadding="0" cellspacing="0" border="0">
					<thead>
						<tr>
							<th width="5"><a href="#" class="check_all" id="tickAll" title="<?php echo TIP_SELECT_ALL; ?>" onclick="checkAll(this);">&nbsp;</a></th>
							<th width="10" class="fileColumns">&nbsp;</th>
							<th class="docName"><?php echo LBL_NAME; ?></th>
							<th  width="70" class="fileColumns"><?php echo LBL_SIZE; ?></th>
                            <!-- hide while implementing this Chamilo -->
							<!--<th class="fileColumns"><?php // echo LBL_MODIFIED; ?></th> -->						
						</tr>
					</thead>
					<tbody id="fileList">
						<?php
							$count = 1;
							$css = "";
							//list all documents (files and folders) under this current folder, 
							//echo appendQueryString(appendQueryString(CONFIG_URL_FILEnIMAGE_MANAGER, "path=" . $file['path']), makeQueryString(array('path'))); 
							foreach($fileList as $file)
							{
								$css = ($css == "" || $css == "even"?"odd":"even");
								$strDisabled = ($file['is_writable']?"":" disabled");
								$strClass = ($file['is_writable']?"left":" leftDisabled");
								///First step for hidden some type of Chamilo files and folders
								//Juan Carlos Raña

									//hidden files and folders deleted by Chamilo. Hidde folders css, hotpotatoes, chat_files, certificates
									$deleted_by_chamilo_file=' DELETED '; // ' DELETED ' not '_DELETED_' because in $file['name'] _ is replaced with blank see class.manager.php
									$deleted_by_chamilo_folder='_DELETED_';
									$css_folder_chamilo='css';
									$hotpotatoes_folder_chamilo='HotPotatoes_files';
									$chat_files_chamilo='chat_files';
									$certificates_chamilo='certificates';
									//show group's directory only if I'm member. Or if I'm a teacher. TODO: check groups not necessary because the student dont have access to main folder documents (only to document/group or document/shared_folder). Teachers can access to all groups ?
									$group_folder='_groupdocs';

									$show_doc_group=true;
									if(ereg($group_folder, $file['path']))
									{
										$show_doc_group=false;
										if($is_user_in_group ||( $to_group_id!=0 && api_is_allowed_to_edit()))
										{
											$show_doc_group=true;
										}

									}

								///Second step: hiding as the case
								//Juan Carlos Raña

								if((!ereg($deleted_by_chamilo_file, $file['name']) && !ereg($deleted_by_chamilo_folder, $file['path'])) && !ereg($css_folder_chamilo, $file['path']) && !ereg($hotpotatoes_folder_chamilo, $file['path']) && !ereg($chat_files_chamilo, $file['path']) && !ereg($certificates_chamilo, $file['path']) && $show_doc_group && $file['name'][0]!='.')
								{							
								
									if($file['type'] == 'file')
									{
	
										if(Security::remove_XSS($_GET['editor'])!='stand_alone'){
										
											$path_chamilo_file='../'.$file['path'];// fix for makes a good show when pressed next on window preview, don't only one image
										}
										else{
											$path_chamilo_file=$file['path'];
										}
	
									?>
									<tr class="<?php echo $css; ?>" id="row<?php echo $count; ?>"  >
										<td align="center" id="tdz<?php echo $count; ?>"><span id="flag<?php echo $count; ?>" class="<?php echo $file['flag']; ?>">&nbsp;</span><input type="checkbox"  name="check[]" id="cb<?php echo $count; ?>" value="<?php echo $file['path']; ?>" <?php echo $strDisabled; ?> /></td>
                                        <td align="center" class="fileColumns" id="tdst<?php echo $count; ?>">&nbsp;<a id="a<?php echo $count; ?>" href="<?php echo $path_chamilo_file; // fix for Chamilo ?>" target="_blank"><span class="<?php echo $file['cssClass']; ?>">&nbsp;</span></a></td>
                                     <td class="<?php echo $strClass; ?> docName"  id="tdnd<?php echo $count; ?>"><a id="aa<?php echo $count; ?>" href="<?php echo $path_chamilo_file; //fix for Chamilo ?>" target="_blank"><?php echo $file['name']; ?></a></td>
                                        
										<td class="docInfo" id="tdrd<?php echo $count; ?>"><?php echo transformFileSize($file['size']); ?></td>
										<!-- hide while implementing this Chamilo -->
										<!--<td class="docInfo" id="tdth<?php //echo $count; ?>"><?php //echo @date(DATE_TIME_FORMAT,$file['mtime']); ?></td> -->

									</tr>
									<?php
									}else
									{
										?>
										<tr class="<?php echo $css; ?>" id="row<?php echo $count; ?>" >
											<td align="center" id="tdz<?php echo $count; ?>"><span id="flag<?php echo $count; ?>" class="<?php echo $file['flag']; ?>">&nbsp;</span><input type="checkbox" name="check[]" id="cb<?php echo $count; ?>" value="<?php echo $file['path']; ?>" <?php echo $strDisabled; ?>/>
											</td>
                                            
                                            
                                          <?php
                                            
											if(preg_match('/shared_folder/', basename($file['path'])))
                                            {
											//add icon into ajaxfilemanager if sharedfolder is into Chamilo
											?>
                                            	<td  lign="center" class="fileColumns" id="tdst<?php echo $count; ?>">&nbsp;<a id="a<?php echo $count; ?>" href="<?php echo $file['path']; ?>" <?php echo $file['cssClass'] == 'filePicture'?'rel="ajaxPhotos"':''; ?>  ><span class="<?php echo ($file['type'] == 'folder '?$file['cssClass']:"folderShared"); ?>">&nbsp;</span></a></td>
                                            <?php
                                            }
											elseif(preg_match('/sf_user_/', basename($file['path'])))
											{
											?>
											<td  lign="center" class="fileColumns" id="tdst<?php echo $count; ?>">&nbsp;<a id="a<?php echo $count; ?>" href="<?php echo $file['path']; ?>" <?php echo $file['cssClass'] == 'filePicture'?'rel="ajaxPhotos"':''; ?>  ><span class="<?php echo ($file['type'] == 'folder '?$file['cssClass']:"unknownUser"); ?>">&nbsp;</span></a></td>											
											<?php
											}
                                            else
											{
											?>
                                            	<td  lign="center" class="fileColumns" id="tdst<?php echo $count; ?>">&nbsp;<a id="a<?php echo $count; ?>" href="<?php echo $file['path']; ?>" <?php echo $file['cssClass'] == 'filePicture'?'rel="ajaxPhotos"':''; ?>  ><span class="<?php echo ($file['file']||$file['subdir']?$file['cssClass']:"folderEmpty"); ?>">&nbsp;</span></a></td>
                                            <?php
                                            }
                                            ?>                                            
                                            <td class="<?php echo $strClass; ?> docName" id="tdnd<?php echo $count; ?>"><a id="aa<?php echo $count; ?>" href="<?php echo "../".$file['path']; ?>" target="_blank"><?php echo $file['name']; ?></a></td>
											<td class="docInfo" id="tdrd<?php echo $count; ?>">&nbsp;</td>
                                             <!-- hide while implementing this Chamilo -->
											<!--<td class="docInfo" id="tdth<?php// echo $count; ?>"><?php //echo @date(DATE_TIME_FORMAT,$file['mtime']); ?></td> -->
										</tr>
										<?php
									}

								}//end if hidden files and folders deleted by Chamilo

								$count++;
							}
						?>
					</tbody>
				</table>
</div>