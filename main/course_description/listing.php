<?php
/* For licensing terms, see /license.txt */

/**
* Template (view in MVC pattern) used for listing course descriptions  
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.course_description
*/

// protect a course script
api_protect_course_script(true);

// display messages
if ($messages['edit'] || $messages['add']) {
	Display :: display_confirmation_message(get_lang('CourseDescriptionUpdated'));
} else if ($messages['destroy']) {
	Display :: display_confirmation_message(get_lang('CourseDescriptionDeleted'));
}

// display actions menu
if (api_is_allowed_to_edit(null,true)) {
	$categories = array ();
	foreach ($default_description_titles as $id => $title) {
		$categories[$id] = $title;
	}
	$categories[ADD_BLOCK] = get_lang('NewBloc');

	$i=1;
	echo '<div class="actions" style="margin-bottom:30px">';
	ksort($categories);
	foreach ($categories as $id => $title) {
		if ($i==ADD_BLOCK) {
			echo '<a href="index.php?'.api_get_cidreq().'&action=add">'.Display::return_icon($default_description_icon[$id], $title,'',ICON_SIZE_MEDIUM).'</a>';
			break;
		} else {
			echo '<a href="index.php?action=edit&'.api_get_cidreq().'&description_type='.$id.'">'.Display::return_icon($default_description_icon[$id], $title,'',ICON_SIZE_MEDIUM).'</a>';
			$i++;
		}
	}
	echo '</div>';
}

// display course description list
if ($history) {
	echo '<div><table width="100%"><tr><td><h3>'.get_lang('ThematicAdvanceHistory').'</h3></td><td align="right"><a href="index.php?action=listing">'.Display::return_icon('info.png',get_lang('BackToCourseDesriptionList'),array('style'=>'vertical-align:middle;'),ICON_SIZE_SMALL).' '.get_lang('BackToCourseDesriptionList').'</a></td></tr></table></div>';
}
$user_info = api_get_user_info();

if (isset($descriptions) && count($descriptions) > 0) {
	foreach ($descriptions as $id => $description) {
		echo '<div class="sectiontitle">';
		
		if (api_is_allowed_to_edit(null,true) && !$history) {
			if (api_get_session_id() == $description['session_id']) {
                $description['title'] = $description['title'].' '.api_get_session_image(api_get_session_id(), $user_info['status']);
                
				//delete
				echo '<a href="'.api_get_self().'?id='.$description['id'].'&cidReq='.api_get_course_id().'&id_session='.$description['session_id'].'&action=delete&description_type='.$description['description_type'].'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;">';
				echo Display::return_icon('delete.png', get_lang('Delete'), array('style' => 'vertical-align:middle;float:right;'),ICON_SIZE_SMALL);
				echo '</a> ';
                
                //edit
                echo '<a href="'.api_get_self().'?id='.$description['id'].'&cidReq='.api_get_course_id().'&id_session='.$description['session_id'].'&action=edit&description_type='.$description['description_type'].'">';
                echo Display::return_icon('edit.png', get_lang('Edit'), array('style' => 'vertical-align:middle;float:right; padding-right:4px;'),ICON_SIZE_SMALL);                	
                echo '</a> ';
			} else {                
                echo Display::return_icon('edit_na.png', get_lang('EditionNotAvailableFromSession'), array('style' => 'vertical-align:middle;float:right;'),ICON_SIZE_SMALL);
                
            }
		}
		
		echo $description['title'];
		echo '</div>';
		echo '<div class="sectioncomment">';
		echo text_filter($description['content']);
		echo '</div>';
	}
} else {
	echo '<em>'.get_lang('ThisCourseDescriptionIsEmpty').'</em>';
}