<?php
/* For licensing terms, see /license.txt */

/**
* Template (view in MVC pattern) used for displaying blocks for dashboard  
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.dashboard
*/

// protect script
api_block_anonymous_users();

// menu actions for dashboard views
$views = array('blocks', 'list');
//$dashboard_view = 'blocks';
if(isset($_GET['view']) && in_array($_GET['view'], $views)){
	$dashboard_view = $_GET['view'];
}

if($dashboard_view == 'list') {
	$link_blocks_view = '<a href="'.api_get_self().'?view=blocks">'.Display::return_icon('blocks.png',get_lang('DashboardBlocks'),'','32').'</a>';
	
} else {
	$link_list_view = '<a href="'.api_get_self().'?view=list">'.Display::return_icon('edit.png',get_lang('EditBlocks'),'','32').'</a>';
}

$configuration_link = '';
if (api_is_platform_admin()) {
	$configuration_link = '<a href="'.api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins">'
	.Display::return_icon('settings.png',get_lang('ConfigureDashboardPlugin'),'','32').'</a>';
}

echo '<div class="actions">';
echo $link_blocks_view.$link_list_view.$configuration_link;
echo '</div>';

// block dashboard view
if($dashboard_view == 'blocks') {
	
	if (isset($msg)) {		
		//Display::display_confirmation_message(get_lang('BlocksHaveBeenUpdatedSuccessfully'));		
	}

	if (count($blocks) > 0) {
		$columns = array();
		// group content html by number of column
		if (is_array($blocks)) {	
			$tmp_columns = array();	
			foreach ($blocks as $block) {		
				$tmp_columns[] = $block['column'];		
				if (in_array($block['column'], $tmp_columns)) {
					$columns['column_'.$block['column']][] = $block['content_html'];
				}						
			}		
		}
		
		echo '<div id="columns">';
		if (count($columns) > 0) {
			$columns_name = array_keys($columns);
			// blocks for column 1
			if (in_array('column_1',$columns_name)) {		
				echo '<ul id="column1" class="column">'; 
					foreach ($columns['column_1'] as $content) {
						echo $content; 
					}
				echo '</ul>';		
			} else {
				echo '<ul id="column1" class="column">';
				echo '&nbsp;';
				echo '</ul>';		
			}
			// blocks for column 2
			if (in_array('column_2',$columns_name)) {
				// blocks for column 1
				echo '<ul id="column2" class="column">'; 
					foreach ($columns['column_2'] as $content) {
						echo $content; 
					}
				echo '</ul>';		
			} else {
				echo '<ul id="column2" class="column">';
				echo '&nbsp;';
				echo '</ul>';		
			}		
		}
		echo '</div>';
	} else {		
		echo '<div style="margin-top:20px;">'.get_lang('YouHaveNotEnabledBlocks').'</div>';		
	}

} else {
	// block dashboard list
	if (isset($success)) {		
		Display::display_confirmation_message(get_lang('BlocksHaveBeenUpdatedSuccessfully'));		
	}	
	$user_id = api_get_user_id();
	DashboardManager::display_user_dashboard_list($user_id);	
}