<?php
/* For licensing terms, see /license.txt */

/**
 * Defines the AICC class, which is meant to contain the aicc items (nuclear elements)
 * @package chamilo.learnpath
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 * @license	GNU/GPL
 */
/**
 * Code
 */
require_once 'aiccItem.class.php';
//require_once 'aiccMetadata.class.php';
//require_once 'aiccOrganization.class.php';
require_once 'aiccResource.class.php';
require_once 'aiccBlock.class.php';
/**
 * Class
 * @package chamilo.learnpath
 */
class aicc extends learnpath {

    public $config = array();
    public $config_basename = '';	// The configuration files might be multiple and might have
                                    // funny names. We need to keep the name of that file while we
                                    // install the content.
    public $config_files = array();
    public $config_exts = array(
        'crs' => 0, // Course description file (mandatory)
        'au'  => 0, // Assignable Unit file (mandatory)
        'des' => 0, // Descriptor file (mandatory)
        'cst' => 0, // Course structure file (mandatory)
        'ore' => 0, // Objectives relationshops file (optional)
        'pre' => 0, // Prerequisites file (optional)
        'cmp' => 0  // Completion Requirements file (optional)
    );
    public $aulist = array();
    public $au_order_list = array();
    public $au_order_list_new_id = array();
    public $deslist = array();
    public $cstlist = array();
    public $orelist = array();

    public $subdir = '';	// Path between the scorm/ directory and the config files e.g. maritime_nav/maritime_nav. This is the path that will be used in the lp_path when importing a package.
    public $zipname = '';	// Keeps the zipfile safe for the object's life so that we can use it if there is no title available.
    public $lastzipnameindex = 0;	// Keeps an index of the number of uses of the zipname so far.
    public $config_encoding = 'ISO-8859-1';
    public $debug = 0;

    /**
     * Class constructor. Based on the parent constructor.
     * @param	string	Course code
     * @param	integer	Learnpath ID in DB
     * @param	integer	User ID
     */
    public function __construct($course_code = null, $resource_id = null, $user_id = null) {
        if ($this->debug > 0) { error_log('In aicc::aicc()', 0); }
        if (!empty($course_code) && !empty($resource_id) && !empty($user_id)) {
            parent::__construct($course_code, $resource_id, $user_id);
        } else {
            //do nothing but still build the aicc object
        }
    }

    /**
     * Opens a resource
     * @param	integer	Database ID of the resource
     */
    public function open($id) {
        if ($this->debug > 0) { error_log('In aicc::open()', 0); }
        // Redefine parent method.
    }

    /**
     * Parses a set of AICC config files and puts everything into the $config array
     * @param	string	Path to the config files dir on the system. If not defined, uses the base path of the course's scorm dir
     * @return	array	Structured array representing the config files' contents
     */
    function parse_config_files($dir = '') {
        if ($this->debug > 0) {error_log('New LP - In aicc::parse_config_files('.$dir.')', 0); }
        if (empty($dir)) {
            // Get the path of the AICC config files dir.
            $dir = $this->subdir;
        }
        if (is_dir($dir) && is_readable($dir)) {
            // Now go through all the config files one by one and parse everything into AICC objects.

            // The basename for the config files is stored in $this->config_basename.

            // Parse the Course Description File (.crs) - ini-type.
            $crs_file = $dir.'/'.$this->config_files['crs'];
            $crs_params = $this->parse_ini_file_quotes_safe($crs_file);
            //echo '<pre>crs:'.print_r($crs_params, true).'</pre>';
            if ($this->debug > 1) { error_log('New LP - In aicc::parse_config_files() - '.$crs_file.' has been parsed', 0); }

            // CRS distribute crs params into the aicc object.
            if (!empty($crs_params['course']['course_creator'])) {
                $this->course_creator = Database::escape_string($crs_params['course']['course_creator']);
            }
            if (!empty($crs_params['course']['course_id'])) {
                $this->course_id = Database::escape_string($crs_params['course']['course_id']);
            }
            if (!empty($crs_params['course']['course_system'])) {
                $this->course_system = $crs_params['course']['course_system'];
            }
            if (!empty($crs_params['course']['course_title'])) {
                $this->course_title = Database::escape_string($crs_params['course']['course_title']);
            }
            if (!empty($crs_params['course']['course_level'])) {
                $this->course_level = $crs_params['course']['course_level'];
            }
            if (!empty($crs_params['course']['max_fields_cst'])) {
                $this->course_max_fields_cst = $crs_params['course']['max_fields_cst'];
            }
            if (!empty($crs_params['course']['max_fields_ort'])) {
                $this->course_max_fields_ort = $crs_params['course']['max_fields_ort'];
            }
            if (!empty($crs_params['course']['total_aus'])) {
                $this->course_total_aus = $crs_params['course']['total_aus'];
            }
            if (!empty($crs_params['course']['total_blocks'])) {
                $this->course_total_blocks = $crs_params['course']['total_blocks'];
            }
            if (!empty($crs_params['course']['total_objectives'])) {
                $this->course_total_objectives = $crs_params['course']['total_objectives'];
            }
            if (!empty($crs_params['course']['total_complex_objectives'])) {
                $this->course_total_complex_objectives = $crs_params['course']['total_complex_objectives'];
            }
            if (!empty($crs_params['course']['version'])) {
                $this->course_version = $crs_params['course']['version'];
            }
            if (!empty($crs_params['course_description'])) {
                $this->course_description = Database::escape_string($crs_params['course_description']);
            }

            // Parse the Descriptor File (.des) - csv-type.
            $des_file = $dir.'/'.$this->config_files['des'];
            $des_params = $this->parse_csv_file($des_file);
            //echo '<pre>des:'.print_r($des_params, true).'</pre>';
            if ($this->debug > 1) { error_log('New LP - In aicc::parse_config_files() - '.$des_file.' has been parsed', 0); }
            // Distribute des params into the aicc object.
            foreach ($des_params as $des){
                // One AU in AICC is equivalent to one SCO in SCORM (scormItem class).
                $oDes = new aiccResource('config', $des);
                $this->deslist[$oDes->identifier] = $oDes;
            }

            // Parse the Assignable Unit File (.au) - csv-type.
            $au_file = $dir.'/'.$this->config_files['au'];
            $au_params = $this->parse_csv_file($au_file);
            //echo '<pre>au:'.print_r($au_params, true).'</pre>';
            if ($this->debug > 1) { error_log('New LP - In aicc::parse_config_files() - '.$au_file.' has been parsed', 0); }
            // Distribute au params into the aicc object.
            foreach ($au_params as $au) {
                $oAu = new aiccItem('config', $au);
                $this->aulist[$oAu->identifier] = $oAu;
                $this->au_order_list[] = $oAu->identifier;
            }

            // Parse the Course Structure File (.cst) - csv-type.
            $cst_file = $dir.'/'.$this->config_files['cst'];
            $cst_params = $this->parse_csv_file($cst_file, ',', '"', true);
            //echo '<pre>cst:'.print_r($cst_params, true).'</pre>';
            if ($this->debug > 1) { error_log('New LP - In aicc::parse_config_files() - '.$cst_file.' has been parsed', 0); }
            // Distribute cst params into the aicc object.
            foreach ($cst_params as $cst) {
                $oCst = new aiccBlock('config', $cst);
                $this->cstlist[$oCst->identifier] = $oCst;
            }

            // Parse the Objectives Relationships File (.ore) - csv-type - if exists.
            // TODO: Implement these objectives. For now they're just parsed.
            if (!empty($this->config_files['ore'])) {
                $ore_file = $dir.'/'.$this->config_files['ore'];
                $ore_params = $this->parse_csv_file($ore_file, ',', '"', true);
                //echo '<pre>ore:'.print_r($ore_params,true).'</pre>';
                if ($this->debug > 1) { error_log('New LP - In aicc::parse_config_files() - '.$ore_file.' has been parsed', 0); }
                // Distribute ore params into the aicc object.
                foreach ($ore_params as $ore) {
                    $oOre = new aiccObjective('config', $ore);
                    $this->orelist[$oOre->identifier] = $oOre;
                }
            }

            // Parse the Prerequisites File (.pre) - csv-type - if exists.
            if (!empty($this->config_files['pre'])) {
                $pre_file = $dir.'/'.$this->config_files['pre'];
                $pre_params = $this->parse_csv_file($pre_file);
                //echo '<pre>pre:'.print_r($pre_params, true).'</pre>';
                if ($this->debug > 1) { error_log('New LP - In aicc::parse_config_files() - '.$pre_file.' has been parsed', 0); }
                // Distribute pre params into the aicc object.
                foreach ($pre_params as $pre) {
                    // Place a constraint on the corresponding block or AU.
                    if (in_array(api_strtolower($pre['structure_element']), array_keys($this->cstlist))) {
                        // If this references a block element:
                        $this->cstlist[api_strtolower($pre['structure_element'])]->prereq_string = api_strtolower($pre['prerequisite']);
                    }
                    if (in_array(api_strtolower($pre['structure_element']), array_keys($this->aulist))) {
                        // If this references a block element:
                        $this->aulist[api_strtolower($pre['structure_element'])]->prereq_string = api_strtolower($pre['prerequisite']);
                    }
                }
            }

            // Parse the Completion Requirements File (.cmp) - csv-type - if exists.
            // TODO: Implement this set of requirements (needs database changes).
            if (!empty($this->config_files['cmp'])) {
                $cmp_file = $dir.'/'.$this->config_files['cmp'];
                $cmp_params = $this->parse_csv_file($cmp_file);
                //echo '<pre>cmp:'.print_r($cmp_params, true).'</pre>';
                if ($this->debug > 1) { error_log('New LP - In aicc::parse_config_files() - '.$cmp_file.' has been parsed', 0); }
                // Distribute cmp params into the aicc object.
                foreach ($cmp_params as $cmp) {
                    //$oCmp = new aiccCompletionRequirements('config', $cmp);
                    //$this->cmplist[$oCmp->identifier] =& $oCmp;
                }
            }
        }
        return $this->config;
    }

    /**
     * Import the aicc object (as a result from the parse_config_files function) into the database structure
     * @param	string	Unique course code
     * @return	bool	Returns -1 on error
     */
    function import_aicc($course_code) {
        $course_id = api_get_course_int_id();
        
        if ($this->debug > 0) { error_log('New LP - In aicc::import_aicc('.$course_code.')', 0); }
        // Get table names.
        $new_lp = 'lp';
        $new_lp_item = 'lp_item';

        // The previous method wasn't safe to get the database name, so do it manually with the course_code.
        $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." WHERE code='$course_code'";
        $res = Database::query($sql);
        if (Database::num_rows($res) < 1) { error_log('New LP - Database for '.$course_code.' not found '.__FILE__.' '.__LINE__, 0); return -1; }
        $row = Database::fetch_array($res);
                
        $new_lp = Database::get_course_table(TABLE_LP_MAIN);
        $new_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $get_max = "SELECT MAX(display_order) FROM $new_lp WHERE c_id = $course_id";
        $res_max = Database::query($get_max);
        if (Database::num_rows($res_max) < 1) {
            $dsp = 1;
        } else {
            $row = Database::fetch_array($res_max);
            $dsp = $row[0] + 1;
        }

        $this->config_encoding = "ISO-8859-1"; // TODO: We may apply detection for this value, see the function api_detect_encoding().

        $sql = "INSERT INTO $new_lp (c_id, lp_type, name, ref, description, path, force_commit, default_view_mod, default_encoding, js_lib, content_maker,display_order)" .
                "VALUES " .
                "($course_id, 3, '".$this->course_title."', '".$this->course_id."','".$this->course_description."'," .
                "'".$this->subdir."', 0, 'embedded', '".$this->config_encoding."'," .
                "'aicc_api.php','".$this->course_creator."',$dsp)";
        if ($this->debug > 2) { error_log('New LP - In import_aicc(), inserting path: '. $sql, 0); }
        $res = Database::query($sql);
        $lp_id = Database::insert_id();
        $this->lp_id = $lp_id;
        api_item_property_update(api_get_course_info($course_code), TOOL_LEARNPATH, $this->lp_id, 'LearnpathAdded', api_get_user_id());
        api_item_property_update(api_get_course_info($course_code), TOOL_LEARNPATH, $this->lp_id, 'visible', api_get_user_id());

        $previous = 0;
        foreach ($this->aulist as $identifier => $dummy) {
            $oAu =& $this->aulist[$identifier];
            //echo "Item ".$oAu->identifier;
            $field_add = '';
            $value_add = '';
            if (!empty($oAu->masteryscore)) {
                $field_add = 'mastery_score, ';
                $value_add = $oAu->masteryscore.',';
            }
            $title = $oAu->identifier;
            if (is_object($this->deslist[$identifier])) {
                $title = $this->deslist[$identifier]->title;
            }
            $path = $oAu->path;
            //$max_score = $oAu->max_score // TODO: Check if special constraint exists for this item.
            //$min_score = $oAu->min_score // TODO: Check if special constraint exists for this item.
            $parent = 0; // TODO: Deal with the parent.
            $previous = 0;
            $prereq = $oAu->prereq_string;
            //$previous = (!empty($this->au_order_list_new_id[x]) ? $this->au_order_list_new_id[x] : 0); // TODO: Deal with the previous.
            $sql_item = "INSERT INTO $new_lp_item (c_id, lp_id,item_type,ref,title, path,min_score,max_score, $field_add parent_item_id,previous_item_id,next_item_id, prerequisite,display_order) " .
                    "VALUES " .
                    "($course_id, $lp_id, 'au','".$oAu->identifier."','".$title."'," .
                    "'$path',0,100, $value_add" .
                    "$parent, $previous, 0, " .
                    "'$prereq', 0" .
                    ")";
            $res_item = Database::query($sql_item);
            if ($this->debug > 1) { error_log('New LP - In aicc::import_aicc() - inserting item : '.$sql_item.' : '.Database::error(), 0); }
            $item_id = Database::insert_id();
            // Now update previous item to change next_item_id.
            if ($previous != 0) {
                $upd = "UPDATE $new_lp_item SET next_item_id = $item_id WHERE c_id = $course_id AND id = $previous";
                $upd_res = Database::query($upd);
                // Update the previous item id.
            }
            $previous = $item_id;
        }
    }

     /**
     * Intermediate to import_package only to allow import from local zip files
     * @param	string	Path to the zip file, from the dokeos sys root
     * @param	string	Current path (optional)
     * @return string	Absolute path to the AICC description files or empty string on error
     */
    function import_local_package($file_path, $current_dir = '') {
        // TODO: Prepare info as given by the $_FILES[''] vector.
        $file_info = array();
        $file_info['tmp_name'] = $file_path;
        $file_info['name'] = basename($file_path);
        // Call the normal import_package function.
        return $this->import_package($file_info, $current_dir);
    }

    /**
     * Imports a zip file (presumably AICC) into the Chamilo structure
     * @param	string	Zip file info as given by $_FILES['userFile']
     * @return	string	Absolute path to the AICC config files directory or empty string on error
     */
    function import_package($zip_file_info, $current_dir = '') {
        if ($this->debug > 0) { error_log('In aicc::import_package('.print_r($zip_file_info, true).',"'.$current_dir.'") method', 0); }
        //ini_set('error_log', 'E_ALL');
        $maxFilledSpace = 1000000000;
        $zip_file_path = $zip_file_info['tmp_name'];
        $zip_file_name = $zip_file_info['name'];

        if ($this->debug > 0) { error_log('New LP - aicc::import_package() - Zip file path = '.$zip_file_path.', zip file name = '.$zip_file_name, 0); }
        $course_rel_dir  = api_get_course_path().'/scorm'; // Scorm dir web path starting from /courses
        $course_sys_dir = api_get_path(SYS_COURSE_PATH).$course_rel_dir; // The absolute system path of this course.
        $current_dir = replace_dangerous_char(trim($current_dir), 'strict'); // Current dir we are in, inside scorm/
        if ($this->debug > 0) { error_log('New LP - aicc::import_package() - Current_dir = '.$current_dir, 0); }

         //$uploaded_filename = $_FILES['userFile']['name'];
        // Get the name of the zip file without the extension.
        if ($this->debug > 0) { error_log('New LP - aicc::import_package() - Received zip file name: '.$zip_file_path, 0); }
        $file_info = pathinfo($zip_file_name);
        $filename = $file_info['basename'];
        $extension = $file_info['extension'];
        $file_base_name = str_replace('.'.$extension, '', $filename); // Filename without its extension.
        $this->zipname = $file_base_name; // Save for later in case we don't have a title.

        if ($this->debug > 0) { error_log('New LP - aicc::import_package() - Base file name is : '.$file_base_name, 0); }
        $new_dir = replace_dangerous_char(trim($file_base_name),'strict');
        $this->subdir = $new_dir;
        if($this->debug > 0) { error_log('New LP - aicc::import_package() - Subdir is first set to : '.$this->subdir, 0); }

        /*
        if (check_name_exist($course_sys_dir.$current_dir.'/'.$new_dir)) {
            $dialogBox = get_lang('FileExists');
            $stopping_error = true;
        }
        */
        $zipFile = new PclZip($zip_file_path);

        // Check the zip content (real size and file extension).

        $zipContentArray = $zipFile->listContent();

        $package_type = ''; // The type of the package. Should be 'aicc' after the next few lines.
        $package = ''; // The basename of the config files (if 'courses.crs' => 'courses').
        $at_root = false; // Check if the config files are at zip root.
        $config_dir = ''; // The directory in which the config files are. May remain empty.
        $files_found = array();
        $subdir_isset = false;
        // The following loop should be stopped as soon as we found the right config files (.crs, .au, .des and .cst).
        foreach ($zipContentArray as $thisContent) {
            if (preg_match('~.(php.*|phtml)$~i', $thisContent['filename'])) {
                // If a php file is found, do not authorize (security risk).
                if ($this->debug > 1) {error_log('New LP - aicc::import_package() - Found unauthorized file: '.$thisContent['filename'], 0); }
                return api_failure::set_failure('php_file_in_zip_file');
            } elseif (preg_match('?.*/aicc/$?', $thisContent['filename'])) {
                // If a directory named 'aicc' is found, package type = aicc, but continue,
                // because we need to find the right AICC files;
                if ($this->debug > 1) { error_log('New LP - aicc::import_package() - Found aicc directory: '.$thisContent['filename'], 0); }
                $package_type = 'aicc';
            } else {
                // else, look for one of the files we're searching for (something.crs case insensitive).
                $res = array();
                if (preg_match('?^(.*)\.(crs|au|des|cst|ore|pre|cmp)$?i', $thisContent['filename'], $res)) {
                    if ($this->debug > 1) { error_log('New LP - aicc::import_package() - Found AICC config file: '.$thisContent['filename'].'. Now splitting: '.$res[1].' and '.$res[2], 0); }
                    if ($thisContent['filename'] == basename($thisContent['filename'])) {
                        if ($this->debug > 2) { error_log('New LP - aicc::import_package() - '.$thisContent['filename'].' is at root level', 0); }
                        $at_root = true;
                        if (!is_array($files_found[$res[1]])) {
                            $files_found[$res[1]] = $this->config_exts; // Initialise list of expected extensions (defined in class definition).
                        }
                        $files_found[$res[1]][api_strtolower($res[2])] = $thisContent['filename'];
                        $subdir_isset = true;
                    } else {
                        if (!$subdir_isset) {
                            if (preg_match('?^.*/aicc$?i',dirname($thisContent['filename']))) {
                                //echo "Cutting subdir<br/>";
                                $this->subdir .= '/'.substr(dirname($thisContent['filename']), 0, -5);
                            } else {
                                //echo "Not cutting subdir<br/>";
                                $this->subdir .= '/'.dirname($thisContent['filename']);
                            }
                            $subdir_isset = true;
                        }
                        if ($this->debug > 2) { error_log('New LP - aicc::import_package() - '.$thisContent['filename'].' is not at root level - recording subdir '.$this->subdir, 0); }
                        $config_dir = dirname($thisContent['filename']); // Just the relative directory inside scorm/
                        if (!is_array($files_found[basename($res[1])])) {
                            $files_found[basename($res[1])] = $this->config_exts;
                        }
                        $files_found[basename($res[1])][api_strtolower($res[2])] = basename($thisContent['filename']);
                    }
                    $package_type = 'aicc';
                } else {
                    if ($this->debug > 3) { error_log('New LP - aicc::import_package() - File '.$thisContent['filename'].' didnt match any check', 0); }
                }
            }
            $realFileSize += $thisContent['size'];
        }
        if ($this->debug > 2) { error_log('New LP - aicc::import_package() - $files_found: '.print_r($files_found, true), 0); }
        if ($this->debug > 1) { error_log('New LP - aicc::import_package() - Package type is now '.$package_type, 0); }
        $mandatory = false;
        foreach ($files_found as $file_name => $file_exts) {
            $temp = (
                !empty($files_found[$file_name]['crs'])
                AND !empty($files_found[$file_name]['au'])
                AND !empty($files_found[$file_name]['des'])
                AND !empty($files_found[$file_name]['cst'])
            );
            if ($temp) {
                if ($this->debug > 1) { error_log('New LP - aicc::import_package() - Found all config files for '.$file_name, 0); }
                $mandatory = true;
                $package = $file_name;
                // Store base config file name for reuse in parse_config_files().
                $this->config_basename = $file_name;
                // Store filenames for reuse in parse_config_files().
                $this->config_files = $files_found[$file_name];
                // Get out, we only want one config files set.
                break;
            }
        }

        if ($package_type == '' || !$mandatory)
        // && defined('CHECK_FOR_AICC') && CHECK_FOR_AICC)
        {
            return api_failure::set_failure('not_aicc_content');
        }

        if (!enough_size($realFileSize, $course_sys_dir, $maxFilledSpace)) {
            return api_failure::set_failure('not_enough_space');
        }

        // It happens on Linux that $new_dir sometimes doesn't start with '/'
        if ($new_dir[0] != '/') {
            $new_dir = '/'.$new_dir;
        }
        // Cut trailing slash.
        if ($new_dir[strlen($new_dir) - 1] == '/') {
            $new_dir = substr($new_dir, 0, -1);
        }

        /* Uncompressing phase */

        /*
            We need to process each individual file in the zip archive to
            - add it to the database
            - parse & change relative html links
            - make sure the filenames are secure (filter funny characters or php extensions)
        */
        if (is_dir($course_sys_dir.$new_dir) OR @mkdir($course_sys_dir.$new_dir, api_get_permissions_for_new_directories())) {
            // PHP method - slower...
            if ($this->debug >= 1) { error_log('New LP - Changing dir to '.$course_sys_dir.$new_dir, 0); }
            $saved_dir = getcwd();
            chdir($course_sys_dir.$new_dir);
            $unzippingState = $zipFile->extract();
            for ($j = 0; $j < count($unzippingState); $j++) {
                $state = $unzippingState[$j];

                // TODO: Fix relative links in html files (?)
                $extension = strrchr($state["stored_filename"], '.');
                //if ($this->debug > 1) { error_log('New LP - found extension '.$extension.' in '.$state['stored_filename'], 0); }

            }

            if (!empty($new_dir)) {
                $new_dir = $new_dir.'/';
            }
            // Rename files, for example with \\ in it.
            if ($dir = @opendir($course_sys_dir.$new_dir)) {
                if ($this->debug == 1) { error_log('New LP - Opened dir '.$course_sys_dir.$new_dir, 0); }
                while ($file = readdir($dir)) {
                    if ($file != '.' && $file != '..') {
                        $filetype = 'file';

                        if (is_dir($course_sys_dir.$new_dir.$file)) $filetype = 'folder';

                        // TODO: RENAMING FILES CAN BE VERY DANGEROUS AICC-WISE, avoid that as much as possible!
                        //$safe_file = replace_dangerous_char($file, 'strict');
                        $find_str = array('\\', '.php', '.phtml');
                        $repl_str = array('/',  '.txt', '.txt');
                        $safe_file = str_replace($find_str, $repl_str, $file);

                        if ($safe_file != $file) {
                            //@rename($course_sys_dir.$new_dir, $course_sys_dir.'/'.$safe_file);
                            $mydir = dirname($course_sys_dir.$new_dir.$safe_file);
                            if (!is_dir($mydir)) {
                                $mysubdirs = split('/', $mydir);
                                $mybasedir = '/';
                                foreach ($mysubdirs as $mysubdir) {
                                    if (!empty($mysubdir)) {
                                        $mybasedir = $mybasedir.$mysubdir.'/';
                                        if (!is_dir($mybasedir)) {
                                            @mkdir($mybasedir, api_get_permissions_for_new_directories());
                                            if ($this->debug == 1) { error_log('New LP - Dir '.$mybasedir.' doesnt exist. Creating.', 0); }
                                        }
                                    }
                                }
                            }
                            @rename($course_sys_dir.$new_dir.$file, $course_sys_dir.$new_dir.$safe_file);
                            if ($this->debug == 1) { error_log('New LP - Renaming '.$course_sys_dir.$new_dir.$file.' to '.$course_sys_dir.$new_dir.$safe_file, 0); }
                        }
                        //set_default_settings($course_sys_dir, $safe_file, $filetype);
                    }
                }

                closedir($dir);
                chdir($saved_dir);
            }
        } else {
            return '';
        }
        return $course_sys_dir.$new_dir.$config_dir;
    }

    /**
     * Sets the proximity setting in the database
     * @param	string	Proximity setting
     */
    function set_proximity($proxy = '') {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) { error_log('In aicc::set_proximity('.$proxy.') method', 0); }
        $lp = $this->get_id();
        if ($lp != 0) {
            $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
            $sql = "UPDATE $tbl_lp SET content_local = '$proxy' WHERE c_id = ".$course_id." id = ".$lp;
            $res = Database::query($sql);
            return $res;
        } else {
            return false;
        }
    }

    /**
     * Sets the theme setting in the database
     * @param	string	Theme setting
     */
    function set_theme($theme = '') {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) { error_log('In aicc::set_theme('.$theme.') method', 0); }
        $lp = $this->get_id();
        if ($lp != 0) {
            $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
            $sql = "UPDATE $tbl_lp SET theme = '$theme' WHERE c_id = ".$course_id." id = ".$lp;
            $res = Database::query($sql);
            return $res;
        } else {
            return false;
        }
    }

    /**
     * Sets the image LP in the database
     * @param	string	Theme setting
     */
    function set_preview_image($preview_image = '') {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {error_log('In aicc::set_preview_image('.$preview_image.') method', 0); }
        $lp = $this->get_id();
        if ($lp != 0) {
            $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
            $sql = "UPDATE $tbl_lp SET preview_image = '$preview_image' WHERE c_id = ".$course_id." id = ".$lp;
            $res = Database::query($sql);
            return $res;
        } else {
            return false;
        }
    }

    /**
     * Sets the Author LP in the database
     * @param	string	Theme setting
     */
    function set_author($author = '') {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) { error_log('In aicc::set_author('.$author.') method', 0); }
        $lp = $this->get_id();
        if ($lp != 0) {
            $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
            $sql = "UPDATE $tbl_lp SET author = '$author' WHERE c_id = ".$course_id." id = ".$lp;
            $res = Database::query($sql);
            return $res;
        } else {
            return false;
        }
    }

    /**
     * Sets the content maker setting in the database
     * @param	string	Proximity setting
     */
    function set_maker($maker = '') {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) { error_log('In aicc::set_maker method('.$maker.')', 0); }
        $lp = $this->get_id();
        if ($lp != 0) {
            $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
            $sql = "UPDATE $tbl_lp SET content_maker = '$maker' WHERE c_id = ".$course_id." id = ".$lp;
            $res = Database::query($sql);
            return $res;
        } else {
            return false;
        }
    }

    /**
     * Exports the current AICC object's files as a zip. Excerpts taken from learnpath_functions.inc.php::exportpath()
     * @param	integer	Learnpath ID (optional, taken from object context if not defined)
     */
    function export_zip($lp_id = null) {
        if ($this->debug > 0) { error_log('In aicc::export_zip method('.$lp_id.')', 0); }
        if (empty($lp_id)) {
            if (!is_object($this)) {
                return false;
            } else {
                $id = $this->get_id();
                if (empty($id)) {
                    return false;
                } else {
                    $lp_id = $this->get_id();
                }
            }
        }
        //error_log('New LP - in export_zip()', 0);
        // Zip everything that is in the corresponding scorm dir.
        // Write the zip file somewhere (might be too big to return).
        require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
        require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
        require_once api_get_path(LIBRARY_PATH).'document.lib.php';
        require_once api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php';
        require_once 'learnpath_functions.inc.php';
        $course_id = api_get_course_int_id();
        $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
        $_course = Database::get_course_info(api_get_course_id());

        $sql = "SELECT * FROM $tbl_lp WHERE c_id = ".$course_id." id=".$lp_id;
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $LPname = $row['path'];
        $list = split('/', $LPname);
        $LPnamesafe = $list[0];
        //$zipfoldername = '/tmp';
        //$zipfoldername = '../../courses/'.$_course['directory'].'/temp/'.$LPnamesafe;
        $zipfoldername = api_get_path(SYS_COURSE_PATH).$_course['directory'].'/temp/'.$LPnamesafe;
        $scormfoldername = api_get_path(SYS_COURSE_PATH).$_course['directory'].'/scorm/'.$LPnamesafe;
        $zipfilename = $zipfoldername.'/'.$LPnamesafe.'.zip';

        // Get a temporary dir for creating the zip file.

        //error_log('New LP - cleaning dir '.$zipfoldername, 0);
        deldir($zipfoldername); //make sure the temp dir is cleared
        mkdir($zipfoldername, api_get_permissions_for_new_directories());
        //error_log('New LP - made dir '.$zipfoldername, 0);

        // Create zipfile of given directory.
        $zip_folder = new PclZip($zipfilename);
        $zip_folder->create($scormfoldername.'/', PCLZIP_OPT_REMOVE_PATH, $scormfoldername.'/');

        //$zipfilename = '/var/www/dokeos-comp/courses/TEST2/scorm/example_document.html';
        //this file sending implies removing the default mime-type from php.ini
        //DocumentManager :: file_send_for_download($zipfilename, true, $LPnamesafe.".zip");
        DocumentManager :: file_send_for_download($zipfilename, true);

        // Delete the temporary zip file and directory in fileManage.lib.php
        my_delete($zipfilename);
        my_delete($zipfoldername);

        return true;
    }

    /**
     * Gets a resource's path if available, otherwise return empty string
     * @param	string	Resource ID as used in resource array
     * @return string	The resource's path as declared in config file course.crs
     */
    function get_res_path($id) {
        if ($this->debug > 0) { error_log('In aicc::get_res_path('.$id.') method', 0); }
        $path = '';
        if (isset($this->resources[$id])) {
            $oRes =& $this->resources[$id];
            $path = @$oRes->get_path();
        }
        return $path;
    }

    /**
     * Gets a resource's type if available, otherwise return empty string
     * @param	string	Resource ID as used in resource array
     * @return string	The resource's type as declared in the assignable unit (.au) file
     */
    function get_res_type($id) {
        if ($this->debug > 0) { error_log('In aicc::get_res_type('.$id.') method', 0); }
        $type = '';
        if (isset($this->resources[$id])) {
            $oRes =& $this->resources[$id];
            $temptype = $oRes->get_scorm_type();
            if (!empty($temptype)) {
                $type = $temptype;
            }
        }
        return $type;
    }

    /**
     * Gets the default organisation's title
     * @return	string	The organization's title
     */
    function get_title(){
        if ($this->debug > 0) { error_log('In aicc::get_title() method', 0); }
        $title = '';
        if (isset($this->config['organizations']['default'])) {
            $title = $this->organizations[$this->config['organizations']['default']]->get_name();
        } elseif (count($this->organizations) == 1) {
            // This will only get one title but so we don't need to know the index.
            foreach ($this->organizations as $id => $value) {
                $title = $this->organizations[$id]->get_name();
                break;
            }
        }
        return $title;
    }

    /**
     * TODO: Implement this function to restore items data from a set of AICC config files,
     * updating the existing table... This will prove very useful in case initial data
     * from config files were not imported well enough.
     */
    function reimport_aicc() {
        if ($this->debug > 0) { error_log('In aicc::reimport_aicc() method', 0); }
        //query current items list
        //get the identifiers
        //parse the config files
        //match both
        //update DB accordingly
        return true;
    }

    /**
     * Static function to parse AICC ini files.
     * Based on work by sinedeo at gmail dot com published on php.net (parse_ini_file()).
     * @param	string	File path
     * @return	array	Structured array
     */
    function parse_ini_file_quotes_safe($f) {
        $null = '';
        $r = $null;
        $sec = $null;
        $f = @file_get_contents($f);
        $f = api_convert_encoding($f, api_get_system_encoding(), $this->config_encoding);
        $f = preg_split('/\r?\n/', $f);
        for ($i = 0; $i < @count($f); $i++) {
            $newsec = 0;
            $w = @trim($f[$i]);
            if ($w) {
                if ((!$r) or ($sec)) {
                    if ((@substr($w, 0, 1) == '[') and (@substr($w, -1, 1)) == ']') {
                        $sec = @substr($w, 1, @strlen($w) - 2);
                        $newsec = 1;
                    }
                }
                if (!$newsec) {
                    $w = @explode('=', $w);
                    $k = @trim($w[0]);
                    unset($w[0]);
                    $v = @trim(@implode('=', $w));
                    if ((@substr($v, 0, 1) == "\"") and (@substr($v, -1, 1) == "\"")) {
                        $v = @substr($v, 1, @strlen($v) - 2);
                    }
                    if ($sec) {
                        if (api_strtolower($sec) == 'course_description') { // A special case.
                            $r[api_strtolower($sec)] = $k;
                        } else {
                            $r[api_strtolower($sec)][api_strtolower($k)] = $v;
                        }
                    } else {
                        $r[api_strtolower($k)] = $v;
                    }
                }
            }
        }
        return $r;
    }

    /**
     * Static function to parse AICC ini strings.
     * Based on work by sinedeo at gmail dot com published on php.net (parse_ini_file()).
     * @param		string	INI File string
     * @param		array	List of names of sections that should be considered as containing only hard string data (no variables), provided in lower case
     * @return	array	Structured array
     */
    function parse_ini_string_quotes_safe($s, $pure_strings = array()) {
        $null = '';
        $r = $null;
        $sec = $null;
        $s = api_convert_encoding($s, api_get_system_encoding(), $this->config_encoding);
        //$f = split("\r\n", $s);
        $f = preg_split('/\r?\n/', $s);
        for ($i = 0; $i < @count($f); $i++) {
            $newsec = 0;
            $w = @trim($f[$i]);
            if ($w) {
                if ((!$r) or ($sec)) {
                    if ((@substr($w, 0, 1) == '[') and (@substr($w, -1, 1)) == ']') {
                        $sec = @substr($w, 1, @strlen($w) - 2);
                        $pure_data = 0;
                        if (in_array(api_strtolower($sec), $pure_strings)) {
                            // This section can only be considered as pure string data (until the next section).
                            $pure_data = 1;
                            $r[api_strtolower($sec)] = '';
                        }
                        $newsec = 1;
                    }
                }
                if (!$newsec) {
                    $w = @explode('=', $w);
                    $k = @trim($w[0]);
                    unset($w[0]);
                    $v = @trim(@implode('=', $w));
                    if ((@substr($v, 0, 1) == "\"") and (@substr($v, -1, 1) == "\"")) {
                        $v = @substr($v, 1, @strlen($v) - 2);
                    }
                    if ($sec) {
                        if ($pure_data) {
                            $r[api_strtolower($sec)] .= $f[$i];
                        } else {
                            if (api_strtolower($sec) == 'course_description') { // A special case.
                                $r[api_strtolower($sec)] = $k;
                            } else {
                                $r[api_strtolower($sec)][api_strtolower($k)] = $v;
                            }
                        }
                    } else {
                        $r[api_strtolower($k)] = $v;
                    }
                }
            }
        }
        return $r;
    }

    /**
     * Static function that parses CSV files into simple arrays, based on a function
     * by spam at cyber-space dot nl published on php.net (fgetcsv()).
     * @param	string	Filepath
     * @param	string	CSV delimiter
     * @param	string	CSV enclosure
     * @param	boolean	Might one field name happen more than once on the same line? (then split by comma in the values)
     * @return array	Simple structured array
     */
    function parse_csv_file($f, $delim = ',', $enclosure = '"', $multiples = false) {
        $data = @file_get_contents($f);
        $data = api_convert_encoding($data, api_get_system_encoding(), $this->config_encoding);
        $enclosed = false;
        $fldcount = 0;
        $linecount = 0;
        $fldval = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $chr = $data{$i};
            switch ($chr) {
                case $enclosure:
                    if ($enclosed && $data{$i+1} == $enclosure) {
                        $fldval .= $chr;
                        ++$i; // Skip the next character.
                    } else
                        $enclosed = !$enclosed;
                    break;
                case $delim:
                    if (!$enclosed) {
                        $ret_array[$linecount][$fldcount++] = $fldval;
                        $fldval = '';
                    } else
                        $fldval .= $chr;
                    break;
                case "\r":
                    if (!$enclosed&&$data{$i+1} == "\n")
                        continue;
                case "\n":
                    if (!$enclosed) {
                        $ret_array[$linecount++][$fldcount] = $fldval;
                        $fldcount = 0;
                        $fldval = '';
                    } else
                        $fldval .= $chr;
                    break;
                case "\\r":
                    if (!$enclosed&&$data{$i+1} == "\\n")
                        continue;
                case "\\n":
                    if (!$enclosed) {
                        $ret_array[$linecount++][$fldcount] = $fldval;
                        $fldcount = 0;
                        $fldval = '';
                    } else
                        $fldval .= $chr;
                    break;
                default:
                    $fldval .= $chr;
            }
        }
        if ($fldval) {
            $ret_array[$linecount][$fldcount] = $fldval;
        }
        // Transform the array to use the first line as titles.
        $titles = array();
        $ret_ret_array = array();
        foreach ($ret_array as $line_idx => $line) {
            if ($line_idx == 0) {
                $titles = $line;
            } else {
                $ret_ret_array[$line_idx] = array();
                foreach ($line as $idx => $val) {
                    if ($multiples && !empty($ret_ret_array[$line_idx][api_strtolower($titles[$idx])])) {
                        $ret_ret_array[$line_idx][api_strtolower($titles[$idx])] .= ','.$val;
                    } else {
                        $ret_ret_array[$line_idx][api_strtolower($titles[$idx])] = $val;
                    }
                }
            }
        }
        return $ret_ret_array;
    }
}
