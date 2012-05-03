<?php
/* For licensing terms, see /license.txt */
/* 
 * @author Julio Montoya <gugli100@gmail.com>
 *  @todo better organization of the class, methods and variables 
 * 
 **/

require_once api_get_path(LIBRARY_PATH).'course_home.lib.php';
require_once api_get_path(LIBRARY_PATH).'banner.lib.php';
require_once api_get_path(LIBRARY_PATH).'plugin.lib.php';
require_once api_get_path(LIBRARY_PATH).'symfony/Twig/Autoloader.php';

//class Template extends Smarty {
class Template {
	
	var $style = 'default'; //see the template folder 
    var $preview_theme = null; 
    var $theme; // the chamilo theme public_admin, chamilo, chamilo_red, etc
    var $title =  null;
	var $show_header;
	var $show_footer;
    var $help;
    var $menu_navigation = array(); //Used in the userportal.lib.php function: return_navigation_course_links()
    var $show_learnpath = false; // This is a learnpath section or not?
    var $plugin = null;
    var $course_id = null;
    var $user_is_logged_in = false;
    var $twig = null;
    
    var $params = array();
	
	function __construct($title = '', $show_header = true, $show_footer = true, $show_learnpath = false) {
                
        //Twig settings        
        Twig_Autoloader::register();
        
        $template_paths = array(
                api_get_path(SYS_CODE_PATH).'template', //template folder
                api_get_path(SYS_PLUGIN_PATH) //plugin folder
        );
        
        $loader = new Twig_Loader_Filesystem($template_paths);
        
        $this->twig = new Twig_Environment($loader, array(
            //'cache' => api_get_path(SYS_ARCHIVE_PATH), //path to the cache folder
            'autoescape' => false,
            //'debug'      => true,
            //'auto_reload' => true
            //'optimizations' => 0 // turn on optimizations with -1
        ));
               
        $this->twig->addFilter('get_lang',       new Twig_Filter_Function('get_lang'));
        $this->twig->addFilter('get_path',       new Twig_Filter_Function('api_get_path'));
        $this->twig->addFilter('get_setting',    new Twig_Filter_Function('api_get_setting'));
        $this->twig->addFilter('var_dump',       new Twig_Filter_Function('var_dump'));        
        $this->twig->addFilter('return_message', new Twig_Filter_Function('Display::return_message_and_translate'));
        
        /*
        $lexer = new Twig_Lexer($this->twig, array(
            //'tag_comment'  => array('{*', '*}'),
            //'tag_comment'  => array('{#', '#}'),
            //'tag_block'    => array('{', '}'),
            //'tag_variable' => array('{$', '}'),
        ));        
        $this->twig->setLexer($lexer);*/
   
        //Page title
		$this->title = $title;        
		$this->show_learnpath = $show_learnpath;
        		
		//Setting system variables
		$this->set_system_parameters();	
		
		//Setting user variables 
		$this->set_user_parameters();
        
        //Setting course variables 
		$this->set_course_parameters();    
		
		//header and footer are showed by default
		$this->set_footer($show_footer);        
		$this->set_header($show_header);
				
		$this->set_header_parameters();
		$this->set_footer_parameters();
        
		$this->assign('style', $this->style);
        
        //Chamilo plugins
        if ($this->show_header) {
            
            $this->plugin = new AppPlugin();
            
            //1. Showing installed plugins in regions
            $plugin_regions = $this->plugin->get_plugin_regions();                  
            foreach ($plugin_regions as $region) {
                $this->set_plugin_region($region);
            }  
            
            //2. Loading the course plugin info
            global $course_plugin;           
            if (isset($course_plugin) && !empty($course_plugin) && !empty($this->course_id)) {                
                //Load plugin get_langs
                $this->plugin->load_plugin_lang_variables($course_plugin);                
            }
        }
	}
    
    function set_help($help_input = null) {        
        if (!empty($help_input)) {
            $help = $help_input;
        } else {
            $help = $this->help;
        }
        
        $help_content = '';
        if (api_get_setting('enable_help_link') == 'true') { 
    		if (!empty($help)) {
    			$help = Security::remove_XSS($help);			
    		    $help_content  = '<li class="help">';                   
    		    $help_content .= '<a href="'.api_get_path(WEB_CODE_PATH).'help/help.php?open='.$help.'&height=400&width=600" class="ajax" title="'.get_lang('Help').'">';
    		    $help_content .= '<img src="'.api_get_path(WEB_IMG_PATH).'help.large.png" alt="'.get_lang('Help').'" title="'.get_lang('Help').'" />';
    		    $help_content .= '</a></li>';		
		  }
        }
		$this->assign('help_content', $help_content);
    }

    /*
     * Use template system to parse the actions menu
     * @todo finish it!
     * */
    function set_actions($actions) {
        $action_string = '';
        if (!empty($actions)) {
            foreach($actions as $action) { 
                $action_string .= $action;
            }
        }
        $this->assign('actions', $actions);        
    }

	/**
	 * Shortcut to display a 1 col layout (index.php)
	 * */
	function display_one_col_template() {		
		$tpl = $this->get_template('layout/layout_1_col.tpl');
		$this->display($tpl);
	}
	
	/**
	* Shortcut to display a 2 col layout (userportal.php)
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
     * Displays an empty template
     */
    function display_no_layout_template() {     
        $tpl = $this->get_template('layout/no_layout.tpl');
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
	 * @param bool true if we show the header
	 */
	function set_header($status) {        
		$this->show_header = $status;
		$this->assign('show_header', $status);
        
        //Toolbar                
        $show_admin_toolbar = api_get_setting('show_admin_toolbar');
        $show_toolbar = 0;
        
        switch($show_admin_toolbar) {
            case 'do_not_show':                
                break;
            case 'show_to_admin':
                if (api_is_platform_admin()) {
                    $show_toolbar = 1;
                }
                break;    
            case 'show_to_admin_and_teachers':
                if (api_is_platform_admin() || api_is_allowed_to_edit()) {
                    $show_toolbar = 1;
                }
                break;
            case 'show_to_all':
                $show_toolbar = 1;
                break;                               
        }        
        $this->assign('show_toolbar', $show_toolbar);
        
        //Only if course is available        
        if (!empty($this->course_id) && $this->user_is_logged_in) {        
            
            if (api_get_setting('show_toolshortcuts') != 'false') {
                //Course toolbar
                $course_tool = CourseHome::show_navigation_tool_shortcuts();                
                $this->assign('show_course_shortcut', $course_tool);
            }

            if (api_get_setting('show_navigation_menu') != 'false') {
                //Course toolbar
                $course_tool = CourseHome::show_navigation_menu();
                $this->assign('show_course_navigation_menu', $course_tool);
            }
        }        
	}
		
	function get_template($name) {
		return $this->style.'/'.$name;
    }	
    
    /* Set course parameters */
    private function set_course_parameters() {                        
        //Setting course id
        $course_id = api_get_course_int_id();
        $this->course_id = $course_id;
    }
	
    /* Set user parameters */
	private function set_user_parameters() {
		$user_info = array();
		$user_info['logged'] = 0;
        $this->user_is_logged_in = false;
		if (api_get_user_id() && !api_is_anonymous()) {
			$user_info = api_get_user_info();			
			$user_info['logged'] = 1;
            
            $user_info['is_admin'] = 0;
            if (api_is_platform_admin()) {
                $user_info['is_admin'] = 1;    
            }
            
			$user_info['messages_count'] = MessageManager::get_new_messages();
            $this->user_is_logged_in = true;
		}		
        //Setting the $_u array that could be use in any template 
		$this->assign('_u', $user_info);         
	}	
	
    /* Set system parameters */
	private function set_system_parameters() {
		global $_configuration;
		
		//Setting app paths		
		$_p = array('web' 			=> api_get_path(WEB_PATH),
					'web_course'	=> api_get_path(WEB_COURSE_PATH),
					'web_main' 		=> api_get_path(WEB_CODE_PATH),
					'web_ajax' 		=> api_get_path(WEB_AJAX_PATH),
                    'web_img' 		=> api_get_path(WEB_IMG_PATH),
                    'web_plugin'    => api_get_path(WEB_PLUGIN_PATH),
                    'web_lib' 		=> api_get_path(WEB_LIBRARY_PATH),
					);
		$this->assign('_p', $_p);
		
		//Here we can add system parameters that can be use in any template
		$_s = array(
				'software_name' 	=> $_configuration['software_name'],
				'system_version' 	=> $_configuration['system_version'],
				'site_name'			=> api_get_setting('siteName'),
				'institution'		=> api_get_setting('Institution')
		);
		$this->assign('_s', $_s);	
	}
    
    function set_theme() {
        //$platform_theme = api_get_setting('stylesheets');
		$this->theme = api_get_visual_theme();
        
        if (!empty($this->preview_theme)) {
            $this->theme = $this->preview_theme;    
        }
		
		//Base CSS
		$style_html = '@import "'.api_get_path(WEB_CSS_PATH).'base.css";'."\n";        
		//Default theme CSS
		$style_html .= '@import "'.api_get_path(WEB_CSS_PATH).$this->theme.'/default.css";'."\n";
        
		//Course theme CSS
		$style_html .= '@import "'.api_get_path(WEB_CSS_PATH).$this->theme.'/course.css";'."\n";
        
        $navigator_info = api_get_navigator();
        
		if ($navigator_info['name']=='Internet Explorer' &&  $navigator_info['version']=='6') {
			$style_html .= 'img, div { behavior: url('.api_get_path(WEB_LIBRARY_PATH).'javascript/iepngfix/iepngfix.htc) } '."\n";
		}
        
        $style_html .= '@import "'.api_get_path(WEB_CSS_PATH).'bootstrap-responsive.css";'."\n";        
        $style_html .= '@import "'.api_get_path(WEB_CSS_PATH).'responsive.css";'."\n";   
		
		$this->assign('css_style', $style_html);
		
		$style_print = '@import "'.api_get_path(WEB_CSS_PATH).$this->theme.'/print.css";'."\n";
        
		$this->assign('css_style_print', $style_print);        
        
        // Logo
		$logo = return_logo($this->theme);
		$this->assign('logo', $logo);
    }
    
	private function set_header_parameters() {
        $help       = $this->help;
		$nameTools  = $this->title;
        
		global $lp_theme_css, $mycoursetheme, $user_theme;
		global $httpHeadXtra, $htmlHeadXtra, $_course, $text_dir,
				$interbreadcrumb, $charset, $language_file, $noPHP_SELF;		
		        
        $navigation            = return_navigation_array();        
        $this->menu_navigation = $navigation['menu_navigation'];
         
		global $_configuration;
		
		$this->assign('system_charset', api_get_system_encoding());
			
		if (isset($httpHeadXtra) && $httpHeadXtra) {
			foreach ($httpHeadXtra as & $thisHttpHead) {
				header($thisHttpHead);
			}
		}
		
        $this->assign('online_button',  Security::remove_XSS(Display::return_icon('online.png')));
		$this->assign('offline_button', Security::remove_XSS(Display::return_icon('offline.png')));
   	
		// Get language iso-code for this page - ignore errors				
		$this->assign('document_language', api_get_language_isocode());
		
		$course_title = $_course['name'];
        
        $title_list = array();
        
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
        
        //Setting the theme
        $this->set_theme();        
        
		//Extra JS files
		
		$js_files = array(
            'modernizr.js',
			'jquery.min.js',
			'chosen/chosen.jquery.min.js',
			'thickbox.js',			
			'dtree/dtree.js',
			'email_links.lib.js.php',
			'bootstrap/bootstrap.js',
		);
        
		if (api_get_setting('allow_global_chat') == 'true') {            
            if (!api_is_anonymous()) {
                //Do not include the global chat in LP
                if ($this->show_learnpath == false) {
                    $js_files[] = 'chat/js/chat.js';
                }
            }
		}
		
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
        		
		//Extra CSS files
		
		$css_files = array (
			api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css',
			api_get_path(WEB_LIBRARY_PATH).'javascript/chosen/chosen.css',
			api_get_path(WEB_LIBRARY_PATH).'javascript/dtree/dtree.css',
		);
		
		if ($this->show_learnpath) {
			$css_files[] = api_get_path(WEB_CSS_PATH).$this->theme.'/learnpath.css';
            $css_files[] = api_get_path(WEB_CSS_PATH).$this->theme.'/scorm.css';
		}
		
		if (api_get_setting('allow_global_chat') == 'true') {
			$css_files[] = api_get_path(WEB_LIBRARY_PATH).'javascript/chat/css/chat.css';
		}
		
		$css_file_to_string = '';
		foreach ($css_files  as $css_file) {
			$css_file_to_string .= api_get_css($css_file);
		}
        
        // @todo move this somewhere else. Special fix when using tablets in order to see the text near icons
        if (SHOW_TEXT_NEAR_ICONS == true) {
            //hack in order to fix the actions buttons
            $css_file_to_string .= '<style>                
                .td_actions a {
                    float:left;
                    width:100%;
                }                
                .forum_message_left a {
                    float:left;
                    width:100%;
                }
                </style>';
        }
	
		global $this_section;        
        
		$this->assign('css_file_to_string', $css_file_to_string);
		$this->assign('js_file_to_string',  $js_file_to_string);		
		$this->assign('text_direction',	    api_get_text_direction());					
		$this->assign('section_name',       'section-'.$this_section);        
        
        //Adding jquery ui by default
        $extra_headers = api_get_jquery_ui_js();
		
		//$extra_headers = '';		
		if (isset($htmlHeadXtra) && $htmlHeadXtra) {
		    foreach ($htmlHeadXtra as & $this_html_head) {
		        $extra_headers .= $this_html_head."\n";
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
                $icon_real_homep = api_get_path(SYS_PATH).'home/'.$clean_url;

		        //we create the new dir for the new sites
		        if (is_file($icon_real_homep.'favicon.ico')) {
		            $favico = '<link rel="shortcut icon" href="'.$homep.'favicon.ico" type="image/x-icon" />';
		        }
		    }
		}
        
		$this->assign('favico', $favico);
        
        $this->set_help();
        
        //@todo move this in the template
		$bug_notification_link = '';
		if (api_get_setting('show_link_bug_notification') == 'true' && $this->user_is_logged_in) {
			$bug_notification_link = '<li class="report">
		        						<a href="http://support.chamilo.org/projects/chamilo-18/wiki/How_to_report_bugs" target="_blank">
		        						<img src="'.api_get_path(WEB_IMG_PATH).'bug.large.png" style="vertical-align: middle;" alt="'.get_lang('ReportABug').'" title="'.get_lang('ReportABug').'"/></a>
		    						  </li>';
		}
		
		$this->assign('bug_notification_link', $bug_notification_link);
		
		$notification   = return_notification_menu();
		$menu           = return_menu();        
		$breadcrumb     = return_breadcrumb($interbreadcrumb, $language_file, $nameTools);
		
		$this->assign('notification_menu', $notification);
		$this->assign('menu', $menu);        
		$this->assign('breadcrumb', $breadcrumb);
		
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
		global $_configuration;   
        
        //Show admin data
		//$this->assign('show_administrator_data', api_get_setting('show_administrator_data'));
        
        if (api_get_setting('show_administrator_data') == 'true') {
             //Administrator name
            $administrator_data = get_lang('Manager'). ' : '. Display::encrypted_mailto_link(api_get_setting('emailAdministrator'), api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))); 
            $this->assign('administrator_name', $administrator_data);
        }
        
         //Loading footer extra content
        if (!api_is_platform_admin()) {
            $extra_footer = trim(api_get_setting('footer_extra_content'));
            if (!empty($extra_footer)) {				
                $this->assign('footer_extra_content', $extra_footer);
            }
        }       
        
        //Tutor name
        if (api_get_setting('show_tutor_data') == 'true') {
            // Course manager
            $id_course  = api_get_course_id();
            $id_session = api_get_session_id();
            if (isset($id_course) && $id_course != -1) {
                $tutor_data = '';
                if ($id_session != 0) {
                    $coachs_email = CourseManager::get_email_of_tutor_to_session($id_session, $id_course);
                    $email_link = array();
                    foreach ($coachs_email as $coach) {                        
                        $email_link[] = Display::encrypted_mailto_link($coach['email'], $coach['complete_name']);
                    }
                    if (count($coachs_email) > 1) {
                        $tutor_data .= get_lang('Coachs').' : ';
                        $tutor_data .= array_to_string($email_link, USER_SEPARATOR);                        
                    } elseif (count($coachs_email) == 1) {
                        $tutor_data .= get_lang('Coach').' : ';
                        $tutor_data .= array_to_string($email_link, USER_SEPARATOR);
                    } elseif (count($coachs_email) == 0) {
                        $tutor_data .= '';
                    }
                }             
                $this->assign('session_teachers', $tutor_data);                    
            }            
        }
        
        if (api_get_setting('show_teacher_data') == 'true') {     
            // course manager
            $id_course = api_get_course_id();
            if (isset($id_course) && $id_course != -1) {
                $teacher_data = '';
                $mail = CourseManager::get_emails_of_tutors_to_course($id_course);
                if (!empty($mail)) {                                         
                    $teachers_parsed = array();
                    foreach ($mail as $value) {
                        foreach ($value as $email => $name) {
                            $teachers_parsed[] = Display::encrypted_mailto_link($email, $name);
                        }
                    }
                    $label = get_lang('Teacher');
                    if (count($mail) > 1) { 
                        $label = get_lang('Teachers');    
                    }
                    $teacher_data .= $label.' : '.array_to_string($teachers_parsed, USER_SEPARATOR);                
                }                
                $this->assign('teachers', $teacher_data);     
            }
        }
		
		/*$stats = '';	
		$this->assign('execution_stats', $stats);		*/
	}
    
    function show_header_template() {        
		$tpl = $this->get_template('layout/show_header.tpl');        
		$this->display($tpl);
    }
    
    function show_footer_template() {
        $tpl = $this->get_template('layout/show_footer.tpl');
		$this->display($tpl);	
    }
    
    /* Sets the plugin content in a template variable */
    function set_plugin_region($plugin_region) {
        if (!empty($plugin_region)) {          
            $content = $this->plugin->load_region($plugin_region, $this);    
            if (!empty($content)) {
                //Assigning the plugin with the template
                $this->assign('plugin_'.$plugin_region, $content);                
            }
        }
        return null;
    }
    
    public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null, $display = false, $merge_tpl_vars = true, $no_output_filter = false) {
        //parent::fetch($template, $cache_id, $compile_id, $parent, $display, $merge_tpl_vars , $no_output_filter);
        //parent::fetch($template, $cache_id, $compile_id, $parent, $display, $merge_tpl_vars , $no_output_filter);        
        $template = $this->twig->loadTemplate($template);    
        return $template->render($this->params);
    }
    
    public function assign($tpl_var, $value = null, $nocache = false) {
        //parent::assign($tpl_var, $value, $nocache);
        $this->params[$tpl_var] = $value;
    }
    
    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null) {
        //parent::display($template, $cache_id, $compile_id, $parent);             
        echo $this->twig->render($template, $this->params);
    }
}