<?php
/* For licensing terms, see /license.txt */

// Load Smarty library
require_once api_get_path(LIBRARY_PATH).'smarty/Smarty.class.php';

class Template extends Smarty {
	
	var $style = 'default'; //see the template folder 
	var $show_header;
	var $show_footer;
	
	function __construct($title = '', $show_header = true, $show_footer = true) {
		$this->title = $title;
		
		//Smarty 3 configuration
		$this->setPluginsDir(api_get_path(LIBRARY_PATH).'smarty/plugins');
		$this->setCacheDir(api_get_path(SYS_ARCHIVE_PATH));
		$this->setCompileDir(api_get_path(SYS_ARCHIVE_PATH));
		$this->setTemplateDir(api_get_path(SYS_CODE_PATH).'template/');
		$this->setConfigDir(api_get_path(SYS_ARCHIVE_PATH));
		
		//Caching settings
		$this->caching 			= true;
		//$this->caching = Smarty::CACHING_LIFETIME_CURRENT;		
		$this->cache_lifetime 	= Smarty::CACHING_OFF; // no caching
		//$this->cache_lifetime 	= 120;
		
		//By default we show the header and footer
		$this->set_footer($show_footer);
		$this->set_header($show_header);
		
		$this->set_system_parameters();		
		$this->set_user_parameters();
		
		//Creating a Smarty modifier - Now we can call the get_lang from a template!!! Just use {"MyString"|get_lang} 
		$this->registerPlugin("modifier","get_lang", "get_lang");
		$this->registerPlugin("modifier","get_path", "api_get_path");
		$this->registerPlugin("modifier","get_setting", "api_get_setting");
		
		//To load a smarty plugin				
		//$this->loadPlugin('smarty_function_get_lang');
		//$this->testInstall();			
		$this->set_header_parameters();
		$this->set_footer_parameters();		
		$this->assign('style', $this->style);
	}
		
	/**
	 * Shortcut to display a 1 col layout
	 * */
	function display_one_col_template() {		
		$tpl = $this->get_template('layout/layout_1_col.tpl');
		$this->display($tpl);
	}
	
	/**
	* Shortcut to display a 2 col layout
	* */	
	function display_two_col_template() {		
		$tpl = $this->get_template('layout/layout_2_col.tpl');
		$this->display($tpl);
	}
	
	/**
	 * Displays an empty template
	 */
	function display_blank_template() {		
		$tpl = $this->get_template('layout/blank.tpl');
		$this->display($tpl);
	}
	
	/**	  
	 * Sets the footer visibility 
	 * @param bool true if we show the footer
	 */
	function set_footer($status) {
		$this->show_footer = $status;
		$this->assign('show_footer', $status);
	}
	/**
	 * Sets the header visibility
	 * 
	 */
	function set_header($status) {
		$this->show_header = $status;
		$this->assign('show_header', $status);	
	}
		
	function get_template($name) {
		return $this->style.'/'.$name;
	}	
	
	private function set_user_parameters() {
		$user_info = array();
		$user_info['logged'] = 0;
		if (api_get_user_id() && !api_is_anonymous()) {
			$user_info = api_get_user_info();			
			$user_info['logged'] = 1;
			$user_info['messages_count'] = MessageManager::get_new_messages();
		}		
		$this->assign('_u', $user_info);
	}	
	
	private function set_system_parameters() {
		global $_configuration;
		
		//Setting app paths		
		$_p = array('web' 			=> api_get_path(WEB_PATH),
					'web_course'	=> api_get_path(WEB_COURSE_PATH),
					'web_main' 		=> api_get_path(WEB_CODE_PATH),
					'web_ajax' 		=> api_get_path(WEB_AJAX_PATH),
					
					);
		$this->assign('_p', $_p);
		
		//Here we can add system parameters that can be use in any template
		$_s = array(
				'software_name' 	=> $_configuration['software_name'],
				'system_version' 	=> $_configuration['system_version'],
				'site_name'			=> api_get_setting('siteName'),
				'institution'		=> api_get_setting('Institution'),		
		);
		$this->assign('_s', $_s);		
		
	}

	private function set_header_parameters($help = null) {
		$nameTools = $this->title;
		global $_plugins, $lp_theme_css, $mycoursetheme, $user_theme, $platform_theme;
		global $httpHeadXtra, $htmlHeadXtra, $_course, $_user, $text_dir, $plugins, $_user, 
				$rootAdminWeb, $_cid, $interbreadcrumb, $charset, $language_file, $noPHP_SELF;		
		global $menu_navigation;
		global $_configuration, $show_learn_path;
		
		$this->assign('system_charset', api_get_system_encoding());
			
		if (isset($httpHeadXtra) && $httpHeadXtra) {
			foreach ($httpHeadXtra as & $thisHttpHead) {
				header($thisHttpHead);
			}
		}
		
		// Get language iso-code for this page - ignore errors		
		
		$this->assign('document_language', api_get_language_isocode());
		
		$course_title = $_course['name'];
		$title_list[] = api_get_setting('Institution');
		$title_list[] = api_get_setting('siteName');
		if (!empty($course_title)) {
			$title_list[] = $course_title;
		}
		if ($nameTools != '') {
			$title_list[] = $nameTools;
		}
		$title_string = '';
		for($i=0; $i<count($title_list);$i++) {
			$title_string .=$title_list[$i];
			if (isset($title_list[$i+1])) {
				$item = trim($title_list[$i+1]);
				if (!empty($item))
				$title_string .=' - ';
			}
		}
		
		$this->assign('title_string', $title_string);
				
		$platform_theme = api_get_setting('stylesheets');
		$my_style 		= api_get_visual_theme();	
		
		$style = '';
		//Base CSS
		$style = '@import "'.api_get_path(WEB_CSS_PATH).'base.css";';
		//Default CSS
		$style .= '@import "'.api_get_path(WEB_CSS_PATH).$my_style.'/default.css";';
		//Course CSS
		$style .= '@import "'.api_get_path(WEB_CSS_PATH).$my_style.'/course.css";';
		
		if ($navigator_info['name']=='Internet Explorer' &&  $navigator_info['version']=='6') {
			$style .= 'img, div { behavior: url('.api_get_path(WEB_LIBRARY_PATH).'javascript/iepngfix/iepngfix.htc) } ';
		}
		
		$this->assign('css_style', $style);
		
		$style_print =  '@import "'.api_get_path(WEB_CSS_PATH).$my_style.'/print.css";';
		$this->assign('css_style_print', $style_print);
		
		$js_files = array(
			'jquery.min.js',
			'chosen/chosen.jquery.min.js',
			'thickbox.js',
			'jquery.menu.js',
			'dtree/dtree.js',
			'email_links.lib.js.php',
			'bootstrap/bootstrap-dropdown.js'
		);
		
		if (api_get_setting('accessibility_font_resize') == 'true') {
			$js_files[] = 'fontresize.js';
		}
		
		if (api_get_setting('include_asciimathml_script') == 'true') {
			$js_files[] = 'asciimath/ASCIIMathML.js';
		}
		
		$js_file_to_string = '';
		
		foreach($js_files as $js_file) {
			$js_file_to_string .= api_get_js($js_file);
		}
		
		$css_files = array (
			api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css',
			api_get_path(WEB_LIBRARY_PATH).'javascript/chosen/chosen.css',
			api_get_path(WEB_LIBRARY_PATH).'javascript/dtree/dtree.css',
		);
		
		if ($show_learn_path) {
			$css_files[] = api_get_path(WEB_CSS_PATH).$my_style.'/learnpath.css';
		}
		
		$css_file_to_string = '';
		foreach($css_files  as $css_file) {
			$css_file_to_string .= api_get_css($css_file);
		}
		
		$this->assign('css_file_to_string', $css_file_to_string);
		$this->assign('js_file_to_string',  $js_file_to_string);		
		$this->assign('text_direction',	 	api_get_text_direction());			
		$this->assign('style_print', 		$style_print);
		
		$extra_headers = '';		
		if (isset($htmlHeadXtra) && $htmlHeadXtra) {
		    foreach ($htmlHeadXtra as & $this_html_head) {
		        $extra_headers .= $this_html_head;
		    }
		}
		$this->assign('extra_headers', $extra_headers);	
	
		$favico = '<link rel="shortcut icon" href="'.api_get_path(WEB_PATH).'favicon.ico" type="image/x-icon" />';
		if (isset($_configuration['multiple_access_urls']) && $_configuration['multiple_access_urls']) {
		    $access_url_id = api_get_current_access_url_id();
		    if ($access_url_id != -1) {
		        $url_info = api_get_access_url($access_url_id);
		        $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
		        $clean_url = replace_dangerous_char($url);
		        $clean_url = str_replace('/', '-', $clean_url);
		        $clean_url .= '/';
		        $homep            = api_get_path(REL_PATH).'home/'.$clean_url; //homep for Home Path               
		        //we create the new dir for the new sites
		        if (is_file($homep.'favicon.ico')) {
		            $favico = '<link rel="shortcut icon" href="'.$homep.'favicon.ico" type="image/x-icon" />';
		        }
		    }
		}
		$this->assign('favico', $favico);
		
		//old banner.inc.php
		
		require_once api_get_path(LIBRARY_PATH).'banner.lib.php';
		
		global $my_session_id;
		$session_id     = api_get_session_id();
		$session_name   = api_get_session_name($my_session_id);
		
		$help_content = '';
		if (!empty($help)) {
			$help = Security::remove_XSS($help);			
		    $help_content  = '<li class="help">';                   
		    $help_content .= '<a href="'.api_get_path(WEB_CODE_PATH).'help/help.php?open='.$help.'&height=400&width=600" class="thickbox" title="'.get_lang('Help').'">';
		    $help_content .= '<img src="'.api_get_path(WEB_IMG_PATH).'help.large.png" alt="'.get_lang('Help').'" title="'.get_lang('Help').'" />';
		    $help_content .= '</a></li>';		
		}

		$this->assign('help_content', $help_content);
		$bug_notification_link = '';
		if (api_get_setting('show_link_bug_notification') == 'true') {
			$bug_notification_link = '<li class="report">
		        						<a href="http://support.chamilo.org/projects/chamilo-18/wiki/How_to_report_bugs" target="_blank">
		        						<img src="'.api_get_path(WEB_IMG_PATH).'bug.large.png" style="vertical-align: middle;" alt="'.get_lang('ReportABug').'" title="'.get_lang('ReportABug').'"/></a>
		    						  </li>';
		}
		
		$this->assign('bug_notification_link', $bug_notification_link);
		
		if (isset($database_connection)) {
			// connect to the main database.
			Database::select_db($_configuration['main_database'], $database_connection);
		}
		
		ob_start();
		show_header_1($language_file, $nameTools);
		$header1 = ob_get_contents();
		ob_clean();
		
		ob_start();
		show_header_2();
		$header2 = ob_get_contents();
		ob_clean();
		
		ob_start();
		$menu_navigation = show_header_3();
		$header3 = ob_get_contents();
		ob_clean();
		
		$header4 = show_header_4($interbreadcrumb, $language_file, $nameTools);
		
		$this->assign('header1', $header1);
		$this->assign('header2', $header2);
		$this->assign('header3', $header3);
		$this->assign('header4', $header4);
		
		if (!api_is_platform_admin()) {
			$extra_header = trim(api_get_setting('header_extra_content'));
			if (!empty($extra_header)) {				
				$this->assign('header_extra_content', $extra_header);
			}		
		}
		
		if ($this->show_header == 1) {
			header('Content-Type: text/html; charset='.api_get_system_encoding());
			header('X-Powered-By: '.$_configuration['software_name'].' '.substr($_configuration['system_version'],0,1));
		}
	}

	private function set_footer_parameters() {
		//Footer plugin
		global $_plugins, $_configuration;
		ob_start();
		api_plugin('footer');
		$plugin_footer = ob_get_contents();
		ob_clean();
		$this->assign('plugin_footer', $plugin_footer);
		
		$this->assign('show_administrator_data', api_get_setting('show_administrator_data'));
		
		//$platform = get_lang('Platform').' <a href="'.$_configuration['software_url'].'" target="_blank">'.$_configuration['software_name'].' '.$_configuration['system_version'].'</a> &copy; '.date('Y');		
		//$this->assign('platform_name', $platform);

		if (!api_is_platform_admin()) {
			$extra_footer = trim(api_get_setting('footer_extra_content'));
			if (!empty($extra_footer)) {				
				$this->assign('footer_extra_content', $extra_footer);
			}		
		}
		
		$administrator_data = get_lang('Manager'). ' : '. Display::encrypted_mailto_link(api_get_setting('emailAdministrator'), api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))); 
		$this->assign('administrator_name', $administrator_data);
		
		$stats = '';
	
		$this->assign('execution_stats', $stats);		
	}
}
