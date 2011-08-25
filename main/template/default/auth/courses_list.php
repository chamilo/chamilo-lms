<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for courses
* @author Christian Fasanando <christian1827@gmail.com> - Beeznest
* @package chamilo.auth
*/

// Acces rights: anonymous users can't do anything usefull here.
api_block_anonymous_users();
$stok = Security::get_token();
$courses_without_category = $courses_in_category[0];

?>

<!-- Actions: The menu with the different options in cathe course management -->
<div id="actions" class="actions">
    <?php if ($action != 'createcoursecategory') { ?>
	&nbsp;<a href="<?php echo api_get_self(); ?>?action=createcoursecategory"><?php echo Display::return_icon('new_folder.png', get_lang('CreateCourseCategory'),'','32'); ?></a>
    <?php } ?>
</div>

<table class="data_table">
<?php 
	if (!empty($message)) {
		Display::display_confirmation_message($message, false); 
	}
    
	// COURSES WITHOUT CATEGORY
	if (!empty($courses_without_category)) {
		$number_of_courses = count($courses_without_category);
		$key = 0;
		
		foreach ($courses_without_category as $course) { 
            echo '<tr>';
            ?>
   
            <td>
                <a name="course<?php echo $course['code']; ?>"></a>
                <strong><?php echo $course['title']; ?></strong><br />                
                <?php
                if (api_get_setting('display_coursecode_in_courselist') == 'true') { echo $course['visual_code']; }
                if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') { echo " - "; }
                if (api_get_setting('display_teacher_in_courselist') == 'true') { echo $course['tutor']; }
                ?>
            </td>
	                
            <td valign="top">
             	
            
            	<!-- the edit icon OR the edit dropdown list -->
                <?php if (isset($_GET['edit']) && $course['code'] == $_GET['edit']) {
                    $edit_course = Security::remove_XSS($_GET['edit']);
                ?>                
                <div style="float:left;">
	            <form name="edit_course_category" method="post" action="courses.php?action=<?php echo $action; ?>">
	                <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
	                <input type="hidden" name="course_2_edit_category" value="<?php echo $edit_course; ?>" />
	                <select name="course_categories">
	                    <option value="0"><?php echo get_lang("NoCourseCategory"); ?></option>
	                    <?php foreach ($user_course_categories as $row) { ?>
	                        <option value="<?php echo $row['id']; ?>"><?php echo $row['title']; ?></option>
	                    <?php } ?>
	                </select>
	                <button class="save" type="submit" name="submit_change_course_category"><?php echo get_lang('Ok') ?></button>
	            </form><br />
	            </div>                
                <?php } ?>
                
                <div style="float:left; width:110px">
                <?php
                if (api_get_setting('show_courses_descriptions_in_catalog') == 'true') {
                $icon_title = get_lang('CourseDetails') . ' - ' . $course['title'];
            	?>
                <a href="<?php echo api_get_path(WEB_CODE_PATH); ?>inc/ajax/course_home.ajax.php?a=show_course_information&code=<?php echo $course['code'] ?>" title="<?php echo $icon_title ?>" class="thickbox">
                	<?php echo Display::return_icon('info.png', $icon_title, '','22'); ?>
				</a>
				<?php } ?>
                
                 <?php if (isset($_GET['edit']) && $course['code'] == $_GET['edit']) {   ?>               
                        <?php echo Display::display_icon('edit_na.png', get_lang('Edit'),'',22); ?>
                      <?php } else { ?>                                                    
                        <a href="courses.php?action=<?php echo $action; ?>&amp;edit=<?php echo $course['code']; ?>&amp;sec_token=<?php echo $stok; ?>">
                        <?php echo Display::display_icon('edit.png', get_lang('Edit'),'',22); ?>
                        </a>                        
                 <?php } ?>       
                
                <!-- up /down icons-->                
                <?php if ($key > 0) { ?>
                        <a href="courses.php?action=<?php echo $action; ?>&amp;move=up&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                        <?php echo Display::display_icon('up.png', get_lang('Up'),'',22) ?>
                        </a>
                <?php } else {
                		echo Display::display_icon('up_na.png', get_lang('Up'),'',22);
                	  } 
                	  
                	  if ($key < $number_of_courses - 1) { ?>
                        <a href="courses.php?action=<?php echo $action; ?>&amp;move=down&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                        <?php echo Display::display_icon('down.png', get_lang('Down'),'',22); ?>
                        </a>
                <?php } else {
                		echo Display::display_icon('down_na.png', get_lang('Down'),'',22);
                	  }?>
                	</div>
                	 <div style="float:left; margin-right:10px;">
                	  <!-- cancel subscrioption-->
                <?php if ($course['status'] != 1) {
                        if ($course['unsubscr'] == 1) {
                ?>
                            <!-- changed link to submit to avoid action by the search tool indexer -->                           
                            <form action="<?php echo api_get_self(); ?>" method="post" onsubmit="javascript: if (!confirm('<?php echo addslashes(api_htmlentities(get_lang("ConfirmUnsubscribeFromCourse"), ENT_QUOTES, api_get_system_encoding())) ?>')) return false;">
	                            <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
	                            <input type="hidden" name="unsubscribe" value="<?php echo $course['code']; ?>" />
	                            <button class="a_button orange small" value="<?php echo get_lang('_unsubscribe'); ?>" name="unsub">
	                            	<?php echo get_lang('_unsubscribe'); ?>
	                            </button>
                            </form>
                            </div>
                  <?php } else {
                            //echo get_lang('UnsubscribeNotAllowed');
                            //echo Display::url(get_lang('_unsubscribe'), '#', array('class'=>'a_button white small '));
                        }
                    } else {
                        //echo get_lang('CourseAdminUnsubscribeNotAllowed');
                        //echo Display::url(get_lang('_unsubscribe'), '#', array('class'=>'a_button white small '));
                    }
                  ?>              
              
                </td>
                </tr>
                <?php $key++;
	        }
	    } ?>
	
	    <!-- COURSES WITH CATEGORIES -->
	    <?php if (!empty($user_course_categories)) {
	           foreach ($user_course_categories as $row) {
	               if (isset($_GET['categoryid']) && $_GET['categoryid'] == $row['id']) {
	     ?>
					<!-- We display the edit form for the category -->
					<tr><td class="user_course_category">
	                        <a name="category<?php echo $row['id']; ?>"></a>
	                        <form name="edit_course_category" method="post" action="courses.php?action=<?php echo $action; ?>">
	                        <input type="hidden" name="edit_course_category" value="<?php echo $row['id']; ?>" />
	                        <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
	                        <input type="text" name="title_course_category" value="<?php echo $row['title']; ?>" />
	                        <button class="save" type="submit" name="submit_edit_course_category"><?php echo get_lang('Ok'); ?></button>
	                        </form>
	                <?php } else { ?>
	                        <tr>
	                        	<td class="user_course_category">
	                        	<a name="category<?php echo $row['id']; ?>"></a>
	                        	<?php echo $row['title']; ?>
	                <?php } ?>
					</td>
					
					<td class="user_course_category">
	
	                <!-- display category icons -->
	                <?php 
	                $max_category_key = count($user_course_categories);
	                if ($action != 'unsubscribe') { ?>               
	                     	                    
	                        <a href="courses.php?action=sortmycourses&amp;categoryid=<?php echo $row['id']; ?>&amp;sec_token=<?php echo $stok; ?>#category<?php echo $row['id']; ?>">
	                        <?php echo Display::display_icon('edit.png', get_lang('Edit'),'',22); ?>
	                        </a>
	                        
	                        <?php if ($row['id'] != $user_course_categories[0]['id']) { ?>
                        	                                <a href="courses.php?action=<?php echo $action ?>&amp;move=up&amp;category=<?php echo $row['id']; ?>&amp;sec_token=<?php echo $stok; ?>">
                        	                                <?php echo Display::return_icon('up.png', get_lang('Up'),'',22); ?>
                        	                                </a>
                        	                        <?php } else { ?>
                        	                       		<?php echo Display::return_icon('up_na.png', get_lang('Up'),'',22); ?> 
                        	                       <?php } ?>
	                        
	                        <?php if ($row['id'] != $user_course_categories[$max_category_key - 1]['id']) { ?>
                                <a href="courses.php?action=<?php echo $action; ?>&amp;move=down&amp;category=<?php echo $row['id']; ?>&amp;sec_token=<?php echo $stok; ?>">
                                <?php echo Display::return_icon('down.png', get_lang('Down'),'',22); ?>
                                </a>
	                        <?php } else { ?>
	                       		<?php echo Display::return_icon('down_na.png', get_lang('Down'),'',22); ?>
	                        <?php } ?>
	                        
	                        <a href="courses.php?action=deletecoursecategory&amp;id=<?php echo $row['id']; ?>&amp;sec_token=<?php echo $stok; ?>">
	                        <?php echo Display::display_icon('delete.png', get_lang('Delete'), array('onclick' => "javascript: if (!confirm('".addslashes(api_htmlentities(get_lang("CourseCategoryAbout2bedeleted"), ENT_QUOTES, api_get_system_encoding()))."')) return false;"),22) ?>
	                        </a>	                
	                      
	                   
	                <?php } ?>
	
	                </td></tr>
	
	                <!-- Show the courses inside this category -->
	                <?php
	                $number_of_courses = count($courses_in_category[$row['id']]);
	                $key = 0;
	                if (!empty($courses_in_category[$row['id']])) {
	                    foreach ($courses_in_category[$row['id']] as $course) {
	                ?>
	                        <tr>
	                            <td>
	                            <a name="course<?php echo $course['code']; ?>"></a>
	                            <strong><?php echo $course['title']; ?></strong><br />
	                            <?php
	                            if (api_get_setting('display_coursecode_in_courselist') == 'true') { echo $course['visual_code']; }
	                            if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') { echo " - "; }
	                            if (api_get_setting('display_teacher_in_courselist') == 'true') { echo $course['tutor']; }
	                            ?>
	                            </td>
	                            <td valign="top">
	
	
	                            <!-- edit -->	                            
	                           
	                            <?php if (isset($_GET['edit']) && $course['code'] == $_GET['edit']) {
	                                    $edit_course = Security::remove_XSS($_GET['edit']); ?>
	                                  
	                                        <form name="edit_course_category" method="post" action="courses.php?action=<?php echo $action; ?>">
	                                        <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
	                                        <input type="hidden" name="course_2_edit_category" value="<?php echo $edit_course; ?>" />
	                                        <select name="course_categories">
	                                        <option value="0"><?php echo get_lang("NoCourseCategory"); ?></option>
	                                        <?php foreach ($user_course_categories as $row) { ?>
	                                            <option value="<?php echo $row['id'] ?>"><?php echo $row['title']; ?></option>
	                                        <?php } ?>
	                                        </select>
	                                        <button class="save" type="submit" name="submit_change_course_category"><?php echo get_lang('Ok'); ?></button>
	                                        </form>
	                            <?php } else { ?>
	                                  
	                            <?php } ?>
	                            
	                            <div style="float:left;width:110px;"> 
	                            <?php if (api_get_setting('show_courses_descriptions_in_catalog') == 'true') {
	                                    $icon_title = get_lang('CourseDetails') . ' - ' . $course['title'];
	                            ?>
	                            <a href="<?php echo api_get_path(WEB_CODE_PATH); ?>inc/ajax/course_home.ajax.php?a=show_course_information&code=<?php echo $course['code'] ?>" title="<?php echo $icon_title ?>" class="thickbox"><?php echo Display::return_icon('info.png', $icon_title,'','22') ?>
	                               <?php } ?>	                            	</a>
	                            	                            	
	                            <?php if (isset($_GET['edit']) && $course['code'] == $_GET['edit']) { ?>
	                                  <?php echo Display::display_icon('edit_na.png', get_lang('Edit'),'',22); ?>   
                            	<?php } else { ?>	                            
                                	<a href="courses.php?action=<?php echo $action; ?>&amp;edit=<?php echo $course['code']; ?>&amp;sec_token=<?php echo $stok; ?>">
	                                    <?php echo Display::display_icon('edit.png', get_lang('Edit'),'',22); ?>
	                                    </a>
                            	<?php } ?>	                                    
	                            
	                            <?php if ($key > 0) { ?>
                                    <a href="courses.php?action=<?php echo $action; ?>&amp;move=up&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                            	    <?php echo Display::display_icon('up.png', get_lang('Up'),'',22); ?>
                            	    </a>
                            	<?php } else { ?>
                            		<?php echo Display::display_icon('up_na.png', get_lang('Up'),'',22); ?>
                            	<?php } ?>
	                    
	                            <?php if ($key < $number_of_courses - 1) { ?>
	                                    <a href="courses.php?action=<?php echo $action; ?>&amp;move=down&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
	                                    <?php echo Display::display_icon('down.png', get_lang('Down'),'',22); ?>
	                                    </a>
	                            <?php } else { ?>	
						       		<?php echo Display::display_icon('down_na.png', get_lang('Down'),'',22); ?>
								<?php } ?>  
                            								
	                          </div>
	                          <div style="float:left; margin-right:10px;">
	                            <?php if ($course['status'] != 1) {
	                                    if ($course['unsubscr'] == 1) {
	                            ?>
	                                
									<form action="<?php echo api_get_self(); ?>" method="post" onsubmit="javascript: if (!confirm('<?php echo addslashes(api_htmlentities(get_lang("ConfirmUnsubscribeFromCourse"), ENT_QUOTES, api_get_system_encoding()))?>')) return false">
                                    	<input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
                                    	<input type="hidden" name="unsubscribe" value="<?php echo $course['code']; ?>" />                                    	
                                    	 <button class="a_button orange small" value="<?php echo get_lang('_unsubscribe'); ?>" name="unsub">
	                            	<?php echo get_lang('_unsubscribe'); ?>
	                            </button>
                                    	</form>
                                    	</div>
	                              <?php } else {
	                                    	//echo get_lang('UnsubscribeNotAllowed');
	                                    	//echo Display::url(get_lang('_unsubscribe'), '#', array('class'=>'a_button white small '));
	                                    }
	                            } else {
	                            	//echo get_lang('CourseAdminUnsubscribeNotAllowed');
	                            	//echo Display::url(get_lang('_unsubscribe'), '#', array('class'=>'a_button white small '));
	                            }
	                            $key++;
	                    	}
	                    	echo '</div>';
	                }
	        }
	    }	
	?>
</table>