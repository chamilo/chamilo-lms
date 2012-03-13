<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains a class used like library, provides functions for dashboard.
 * @author Christian Fasanando <christian1827@gmail.com>
 * @package chamilo.dashboard
 */
/**
 * Code
 */
// required files
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
/**
 * DashboardManager can be used to manage dashboard
 * @package chamilo.dashboard
 */
class DashboardManager {
	/**
	 * contructor
	 */
	private function __construct() {}

    /**
	 * This function allows easy activating and inactivating of dashboard plugins
	 * @return void
	*/
	public static function handle_dashboard_plugins() {

		$table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

		/* We scan the plugin directory. Each folder is a potential plugin. */
		$dashboard_pluginpath = api_get_path(SYS_PLUGIN_PATH).'dashboard/';
		$possibleplugins = self::get_posible_dashboard_plugins_path();

		$table_cols = array('name', 'version', 'description');		
        echo '<div class="page-header"><h2>'.get_lang('DashboardPlugins').'</h2></div>';
		echo '<form name="plugins" method="post" action="'.api_get_self().'?category='.Security::remove_XSS($_GET['category']).'">';
		echo '<table class="data_table">';
		echo '<tr>';
		echo '<th width="50px">'.get_lang('Enabled').'</th>';
		echo '<th width="250px">'.get_lang('Name').'</th>';
		echo '<th width="100px">'.get_lang('Version').'</th>';
		echo '<th>'.get_lang('Description').'</th>';
		echo '</tr>';

		$disabled_blocks_data = self::get_block_data_without_plugin();

		// We display all the possible enabled or disabled plugins
		foreach ($possibleplugins as $testplugin) {
			$plugin_info_file = $dashboard_pluginpath.$testplugin."/$testplugin.info";			
			$plugin_info = array();
			if (file_exists($plugin_info_file) && is_readable($plugin_info_file)) {
				$plugin_info = parse_info_file($plugin_info_file);
    
    			// change index to lower case
    			$plugin_info = array_change_key_case($plugin_info);
    
    			echo '<tr>';
    				self::display_dashboard_plugin_checkboxes($testplugin);
    				for ($i = 0 ; $i < count($table_cols); $i++) {
    					if (isset($plugin_info[strtolower($table_cols[$i])])) {
    			    		echo '<td>';
    			    			echo $plugin_info[$table_cols[$i]];
    			    		echo '</td>';
    			    	} else {
    			    		echo '<td></td>';
    			    	}
    				}
    			echo '</tr>';
            } else {
                echo Display::tag('tr',  Display::tag('td', get_lang('CheckFilePermissions').' '.Security::remove_XSS($plugin_info_file) , array('colspan'=>'3')));
            }
		}
		
		// display all disabled block data
		if (count($disabled_blocks_data) > 0) {
			foreach ($disabled_blocks_data as $disabled_block) {
				echo '<tr style="background-color:#eee">';
				echo '<td><center><input type="checkbox" name="disabled_block" value="true" checked disabled /></center>';
				for ($j = 0 ; $j < count($table_cols); $j++) {
					if (isset($disabled_block[strtolower($table_cols[$j])])) {
						if ($j == 2) {
							echo '<td>';
			    				echo '<font color="#aaa">'.$disabled_block[$table_cols[$j]].'</font><br />';
			    				echo '<font color="red">'.get_lang('ThisPluginHasbeenDeletedFromDashboardPluginDirectory').'</font>';
			    			echo '</td>';
						} else {
							echo '<td>';
			    				echo '<font color="#aaa">'.$disabled_block[$table_cols[$j]].'</font>';
			    			echo '</td>';
						}
			    	} else {
			    		echo '<td>&nbsp;</td>';
			    	}
				}
				echo '</tr>';
			}
		}
		
		echo '</table>';
		echo '<br />';
		echo '<button class="save" type="submit" name="submit_dashboard_plugins" value="'.get_lang('EnableDashboardPlugins').'">'.get_lang('EnableDashboardPlugins').'</button></form>';
	}

	/**
	 * display checkboxes for dashboard plugin list
	 * @param string  plugin path
	 * @return void
	 */
	public static function display_dashboard_plugin_checkboxes($plugin_path) {

		$tbl_block = Database::get_main_table(TABLE_MAIN_BLOCK);
		$plugin_path = Database::escape_string($plugin_path);

		$sql = "SELECT * FROM $tbl_block WHERE path = '$plugin_path' AND active = 1";
		$rs  = Database::query($sql);

		$checked = '';
		if (Database::num_rows($rs) > 0) {
			$checked = "checked";
		}

		echo "<td align=\"center\">";
			echo '<input type="checkbox" name="'.$plugin_path.'" value="true" '.$checked.'/>';
		echo "</td>";
	}

	/**
	 * This function allows easy activating and inactivating of plugins and save them inside db
	 * @param array dashboard plugin paths
	 * return int affected rows
	*/
	public static function store_dashboard_plugins($plugin_paths)
	{

		$tbl_block = Database :: get_main_table(TABLE_MAIN_BLOCK);
		$affected_rows = 0;

		// get all plugins path inside plugin directory
		$dashboard_pluginpath = api_get_path(SYS_PLUGIN_PATH).'dashboard/';
		$possibleplugins = self::get_posible_dashboard_plugins_path();

		if (count($possibleplugins) > 0) {
			
			$selected_plugins = array_intersect(array_keys($plugin_paths),$possibleplugins);
			$not_selected_plugins = array_diff($possibleplugins,array_keys($plugin_paths));

			// get blocks id from not selected path			
			$not_selected_blocks_id = array();
			foreach ($not_selected_plugins as $plugin) {
				$block_data = self::get_enabled_dashboard_blocks($plugin);
				if (!empty($block_data[$plugin])) {
					$not_selected_blocks_id[] = $block_data[$plugin]['id'];
				}
			}
						
			/* clean not selected plugins for extra user data and block data */
			// clean from extra user data
			$field_variable = 'dashboard';
			$extra_user_data = UserManager::get_extra_user_data_by_field_variable($field_variable);			
			foreach ($extra_user_data as $key => $user_data) {
				$user_id = $key;
				$user_block_data = self::get_user_block_data($user_id);
				$user_block_id   = array_keys($user_block_data);

				// clean disabled block data
				foreach ($user_block_id as $block_id) {					
					if (in_array($block_id, $not_selected_blocks_id)) {
						unset($user_block_data[$block_id]);
					}
				}
				
				// get columns and blocks id for updating extra user data
				$columns = array();
				$user_blocks_id = array();
				foreach ($user_block_data as $data) {
					$user_blocks_id[$data['block_id']] = true;
					$columns[$data['block_id']] = $data['column'];
				}

				// update extra user blocks data
				$upd_extra_field = self::store_user_blocks($user_id, $user_blocks_id, $columns);
				
			}
			
			// clean from block data
			if (!empty($not_selected_blocks_id)) {
				$sql_check = "SELECT id FROM $tbl_block WHERE id IN(".implode(',',$not_selected_blocks_id).")";
				$rs_check = Database::query($sql_check);
				if (Database::num_rows($rs_check) > 0) {
					$del = "DELETE FROM $tbl_block WHERE id IN(".implode(',',$not_selected_blocks_id).")";
					Database::query($del);
				}
			}

			
			// store selected plugins
			foreach ($selected_plugins as $testplugin) {
				$selected_path = Database::escape_string($testplugin);
				
				// check if the path already stored inside block table for updating or adding it
				$sql = "SELECT path FROM $tbl_block WHERE path = '$selected_path'";
				$rs	 = Database::query($sql);
				if (Database::num_rows($rs) > 0) {
					// update
					$upd = "UPDATE $tbl_block SET active = 1 WHERE path = '$selected_path'";
					Database::query($upd);
				} else {
					// insert
					$plugin_info_file = $dashboard_pluginpath.$testplugin."/$testplugin.info";
					$plugin_info = array();
					if (file_exists($plugin_info_file)) {
						$plugin_info = parse_info_file($plugin_info_file);
					}

					// change keys to lower case
					$plugin_info = array_change_key_case($plugin_info);

					// setting variables
					$plugin_name = $testplugin;
					$plugin_description = '';
					$plugin_controller = '';
					$plugin_path = $testplugin;

					if (isset($plugin_info['name'])) {
						$plugin_name = Database::escape_string($plugin_info['name']);
					}
					if (isset($plugin_info['description'])) {
						$plugin_description = Database::escape_string($plugin_info['description']);
					}
					if (isset($plugin_info['controller'])) {
						$plugin_controller = Database::escape_string($plugin_info['controller']);
					}

					$ins = "INSERT INTO $tbl_block(name, description, path, controller) VALUES ('$plugin_name', '$plugin_description', '$plugin_path', '$plugin_controller')";
					Database::query($ins);
				}
				$affected_rows = Database::affected_rows();
			}	
							
		}
				
		return $affected_rows;
	}

	/**
	 * Get all plugins path inside dashboard directory
	 * @return array name plugins directories
	 */
	public static function get_posible_dashboard_plugins_path() {

		// get all plugins path inside plugin directory
		/* We scan the plugin directory. Each folder is a potential plugin. */
		$possibleplugins = array();
		$dashboard_pluginpath = api_get_path(SYS_PLUGIN_PATH).'dashboard/';
		$handle = @opendir($dashboard_pluginpath);
		while (false !== ($file = readdir($handle)))
		{
			if ($file <> '.' AND $file <> '..' AND is_dir($dashboard_pluginpath.$file))
			{
				$possibleplugins[] = $file;
			}
		}
		@closedir($handle);
		return $possibleplugins;
	}

	/**
	 * Get all blocks data without plugin directory
	 * @return array Block data
	 */
	public static function get_block_data_without_plugin() {

		$tbl_block = Database :: get_main_table(TABLE_MAIN_BLOCK);
		$possibleplugins = self::get_posible_dashboard_plugins_path();

		// We check if plugin exists inside directory for updating active field
		$sql = "SELECT * FROM $tbl_block";
		$rs = Database::query($sql);
		if (Database::num_rows($rs) > 0){
			while ($row = Database::fetch_array($rs)) {
				$path = $row['path'];
				if (!in_array($row['path'],$possibleplugins)) {
						$active = 0;
				} else {
						$active = 1;
				}
				// update active
				$upd = "UPDATE $tbl_block SET active = '$active' WHERE path = '".$row['path']."'";
				Database::query($upd);
			}
		}

		// get disabled block data
		$block_data = array();
		$sql = "SELECT * FROM $tbl_block WHERE active = 0";
		$rs_block = Database::query($sql);
		if (Database::num_rows($rs_block) > 0) {
			while ($row_block = Database::fetch_array($rs_block)) {
				$block_data[] = $row_block;
			}
		}
		return $block_data;

	}

	/**
	 * get data about enabled dashboard block (stored insise block table)
	 * @param  string	plugin path
	 * @return array 	data
	 */
	public static function get_enabled_dashboard_blocks($path = '') {
		$tbl_block = Database :: get_main_table(TABLE_MAIN_BLOCK);
		$condition_path = '';
		if (!empty($path)) {
			$path = Database::escape_string($path);
			$condition_path = ' AND path = "'.$path.'" ';
		}

		$sql = "SELECT * FROM $tbl_block WHERE active = 1 $condition_path ";
		$rs  = Database::query($sql);
		$block_data = array();
		if (Database::num_rows($rs) > 0) {
			while ($row = Database::fetch_array($rs)) {
				$block_data[$row['path']] = $row;
			}
		}
		return $block_data;
	}

	/**
	 * display user dashboard list
	 * @param int  User id
	 * @return void
	 */
	public static function display_user_dashboard_list($user_id) {

		$block_data_without_plugin = self::get_block_data_without_plugin();
		$enabled_dashboard_plugins = self::get_enabled_dashboard_blocks();
		$user_block_data = self::get_user_block_data($user_id);

		if (count($enabled_dashboard_plugins) > 0) {
			echo '<div style="margin-top:20px">';
			echo '<div><strong>'.get_lang('SelectBlockForDisplayingInsideBlocksDashboardView').'</strong></div><br />';
			echo '<form name="dashboard_list" method="post" action="index.php?action=store_user_block">';
			echo '<table class="data_table">';
			echo '<tr>';
			echo '<th width="5%">';
			echo get_lang('Enabled');
			echo '</th>';
			echo '<th width="30%">';
			echo get_lang('Name');
			echo '</th>';
			echo '<th width="40%">';
			echo get_lang('Description');
			echo '</th>';
			echo '<th>';
			echo get_lang('ColumnPosition');
			echo '</th>';
			echo '</tr>';

			// We display all enabled plugins and the checkboxes
			foreach ($enabled_dashboard_plugins as $block) {
				
				$path = $block['path'];
				$controller_class = $block['controller'];
				$filename_controller = $path.'.class.php';			
				$dashboard_plugin_path = api_get_path(SYS_PLUGIN_PATH).'dashboard/'.$path.'/';	
				require_once $dashboard_plugin_path.$filename_controller;
				if (class_exists($controller_class)) {
				    $obj_block = new $controller_class($user_id);    				
    				
    				// check if user is allowed to see the block
    				if (method_exists($obj_block, 'is_block_visible_for_user')) {					
    					$is_block_visible_for_user = $obj_block->is_block_visible_for_user($user_id);					
    					if (!$is_block_visible_for_user) continue;
    				}
    				
    				echo '<tr>';
                    // checkboxes
                    self::display_user_dashboard_list_checkboxes($user_id, $block['id']);
                    echo '<td>'.$block['name'].'</td>';
                    echo '<td>'.$block['description'].'</td>';
                    echo '<td><center>
                            <select name="columns['.$block['id'].']">
                            <option value="1" '.($user_block_data[$block['id']]['column']==1?'selected':'').' >1</option>
                            <option value="2" '.($user_block_data[$block['id']]['column']==2?'selected':'').' >2</option>
                            </select></center>
                          </td>';
                    echo '</tr>';    				
				} else {
			         echo Display::tag('tr',  Display::tag('td', get_lang('Error').' '.$controller_class, array('colspan'=>'3')));	    
				}				
			}

			echo '</table>';
			echo '<br />';
			echo '<button class="save" type="submit" name="submit_dashboard_list" value="'.get_lang('EnableDashboardBlock').'">'.get_lang('EnableDashboardBlock').'</button></form>';
			echo '</div>';
		} else {
			echo '<div style="margin-top:20px">'.get_lang('ThereAreNoEnabledDashboardPlugins').'</div>';
			if (api_is_platform_admin()) {
				echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins">'.get_lang('ConfigureDashboardPlugin').'</a>';	
			}
		}
	}


	/**
	 * display checkboxes for user dashboard list
	 * @param int 	User id
	 * @param int	Block id
	 * @return void
	 */
	public static function display_user_dashboard_list_checkboxes($user_id, $block_id) {

		$user_id = intval($user_id);
		$user_block_data = self::get_user_block_data($user_id);
		$enabled_blocks_id = array_keys($user_block_data);

		$checked = '';
		if (in_array($block_id, $enabled_blocks_id)) {
			$checked = "checked";
		}

		echo "<td align=\"center\">";
			echo '<input type="checkbox" name="enabled_blocks['.$block_id.']" value="true" '.$checked.'/>';
		echo "</td>";
	}

	/**
	 * This function store enabled blocks id with its column position (block_id1:colum;block_id2:colum; ...) inside extra user fields
	 * @param int User id
	 * @param array selected blocks
	 * @param array columns position
	 * @return bool
	*/
	public static function store_user_blocks($user_id, $enabled_blocks, $columns) {
		$selected_blocks_id  = array();
		if (is_array($enabled_blocks) && count($enabled_blocks) > 0) {
			$selected_blocks_id = array_keys($enabled_blocks);
		}

		// build data for storing inside extra user field
		$fname = 'dashboard';
		$fvalue = array();
		foreach ($selected_blocks_id as $block_id) {
			$fvalue[] = $block_id.':'.$columns[$block_id];
		}
		$upd_extra_field = UserManager::update_extra_field_value($user_id, $fname, $fvalue);
		return $upd_extra_field;

	}

	/**
	 * This function get user block data (block id with its number of column) from extra user data
	 * @param int  		User id
	 * @return array  	data (block_id,column)
	 */
	public static function get_user_block_data($user_id) {

		$user_id = intval($user_id);
		$field_variable = 'dashboard';
		$extra_user_data = UserManager::get_extra_user_data_by_field($user_id, $field_variable);
		$extra_user_data = explode(';',$extra_user_data[$field_variable]);
		$data = array();
		foreach ($extra_user_data as $extra) {
			$split_extra = explode(':',$extra);
			$block_id = $split_extra[0];
			$column = $split_extra[1];
			$data[$block_id] = array('block_id' => $block_id, 'column' => $column);
		}

		return $data;

	}

	/**
	 * This function update extra user blocks data after closing a dashboard block
	 * @param int 		User id
	 * @param string	plugin path
	 * @return bool
	 */
	public static function close_user_block($user_id, $path) {

		$enabled_dashboard_blocks = self::get_enabled_dashboard_blocks($path);
		$user_block_data = self::get_user_block_data($user_id);

		foreach ($enabled_dashboard_blocks as $enabled_block) {
			unset($user_block_data[$enabled_block['id']]);
		}

		// get columns and blocks id for updating extra user data
		$columns = array();
		$user_blocks_id = array();
		foreach ($user_block_data as $data) {
			$user_blocks_id[$data['block_id']] = true;
			$columns[$data['block_id']] = $data['column'];
		}

		// update extra user blocks data
		$upd_extra_field = self::store_user_blocks($user_id, $user_blocks_id, $columns);

		return $upd_extra_field;

	}

	/**
	 * get links for styles from dashboard plugins
	 * @return string   links
	 */
	public static function get_links_for_styles_from_dashboard_plugins() {

		$possible_paths = self::get_posible_dashboard_plugins_path();
		$enabled_blocks = self::get_enabled_dashboard_blocks();
		$links = '';
		foreach ($enabled_blocks as $block) {
			$path = $block['path'];
			$dashboard_plugin_path = api_get_path(SYS_PLUGIN_PATH).'dashboard/'.$path.'/css/';
			if (is_dir($dashboard_plugin_path)) {
				$handle = @opendir($dashboard_plugin_path);
				while (false !== ($file = readdir($handle))) {
					if ($file <> '.' AND $file <> '..') {
						$src = api_get_path(WEB_PLUGIN_PATH).'dashboard/'.$path.'/css/'.$file;
						$links .= '<link rel="stylesheet" href="'.$src.'" type="text/css" />'.PHP_EOL;
					}
				}
				@closedir($handle);
			}
		}
		return $links;
	}

}
