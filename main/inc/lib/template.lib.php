<?php
/* For licensing terms, see /license.txt */

// Load Smarty library
require_once api_get_path(LIBRARY_PATH).'smarty/Smarty.class.php';

class Template extends Smarty {
	
	var $style = 'experimental'; //see the template folder 
	
	function __construct($title = '') {
		$this->title = $title;		
		
		$this->template_dir 	= api_get_path(SYS_CODE_PATH).'template/';	
		$this->compile_dir  	= api_get_path(SYS_ARCHIVE_PATH); 	
		//@todo check this config fir
		$this->config_dir   	= api_get_path(SYS_ARCHIVE_PATH);	// main/inc/conf/config?
		$this->cache_dir    	= api_get_path(SYS_ARCHIVE_PATH);			
		$this->plugins_dir		= api_get_path(LIBRARY_PATH).'smarty/plugins';		
		
		$this->caching 			= true;
		$this->cache_lifetime 	= Smarty::CACHING_OFF; // no caching
		//$this->cache_lifetime 	= 120;
		
		$this->set_system_parameters();
		
		$this->set_user_parameters();
		
		$this->set_header_parameters();		
		
		$this->set_footer_parameters();	
		
		//Creating a Smarty modifier - Now we can call the get_lang from a template!!! Just use {"MyString"|get_lang} 
		$this->registerPlugin("modifier","get_lang", "get_lang");
		
		//To load a smarty plugin				
		//$this->loadPlugin('smarty_function_get_lang');

		//$this->caching = Smarty::CACHING_LIFETIME_CURRENT;				
		$this->assign('style', $this->style);		
	}
		
	function get_template($name) {
		return $this->style.'/'.$name;
	}	
	
	private function set_user_parameters() {		
		if (api_get_user_id()) {
			$user_info = api_get_user_info();
			//$this->assign('user_info', $user_info);
			$this->assign('_u', $user_info);
		}
	}	
	
	private function set_system_parameters() {
		global $_configuration;
		
		//Setting app paths		
		$_p = array('web' 			=> api_get_path(WEB_PATH),
					'web_course'	=> api_get_path(WEB_COURSE_PATH),
					'web_main' 		=> api_get_path(WEB_CODE_PATH),
					
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
		global $httpHeadXtra, $htmlHeadXtra, $_course, $_user, $clarolineRepositoryWeb, $text_dir, $plugins, $_user, 
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
		$this->assign('text_direction',	 api_get_text_direction());		

	
		//@todo add this
		/*<link rel="top" href="<?php echo api_get_path(WEB_PATH); ?>index.php" title="" />
		<link rel="courses" href="<?php echo api_get_path(WEB_CODE_PATH); ?>auth/courses.php" title="<?php echo api_htmlentities(get_lang('OtherCourses'), ENT_QUOTES); ?>" />
		<link rel="profil" href="<?php echo api_get_path(WEB_CODE_PATH); ?>auth/profile.php" title="<?php echo api_htmlentities(get_lang('ModifyProfile'), ENT_QUOTES); ?>" />
		<link href="http://www.chamilo.org/documentation.php" rel="Help" />
		<link href="http://www.chamilo.org/team.php" rel="Author" />
		<link href="http://www.chamilo.org" rel="Copyright" />
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
		<meta name="Generator" content="<?php echo $_configuration['software_name'].' '.substr($_configuration['system_version'],0,1);?>" />
		*/
		
		$this->assign('style_print', $style_print);
		
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
			// if single database, don't pefix table names with the main database name in SQL queries
			// (ex. SELECT * FROM table)
			// if multiple database, prefix table names with the course database name in SQL queries (or no prefix if the table is in
			// the main database)
			// (ex. SELECT * FROM table_from_main_db  -  SELECT * FROM courseDB.table_from_course_db)
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
		
		header('Content-Type: text/html; charset='.api_get_system_encoding());
		header('X-Powered-By: '.$_configuration['software_name'].' '.substr($_configuration['system_version'],0,1));
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
		
		$administrator_data = get_lang('Manager'). ' : '. Display::encrypted_mailto_link(api_get_setting('emailAdministrator'), api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))); 
		$this->assign('administrator_name', $administrator_data);
		
		$stats = '';
	
		$this->assign('execution_stats', $stats);		
	}
}