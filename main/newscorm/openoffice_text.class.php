<?php
/* For licensing terms, see /license.txt */

/**
 * Defines the OpenofficeDocument class, which is meant as a conversion
 * tool from Office text documents (.doc, .sxw, .odt, .docx) to
 * learning paths
 * @package chamilo.learnpath
 * @author  Eric Marguin <eric.marguin@dokeos.com>
 * @license GNU/GPL
 */

/**
 * Defines the "OpenofficeText" child of class "learnpath"
 */
require_once 'openoffice_document.class.php';
if (api_get_setting('search_enabled') == 'true') {
    require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
    require_once api_get_path(LIBRARY_PATH).'search/DokeosIndexer.class.php';
    require_once api_get_path(LIBRARY_PATH).'search/IndexableChunk.class.php';
}
/**
 * @package chamilo.learnpath.OpenofficeDocument
 */
class OpenofficeText extends OpenofficeDocument {

    public $split_steps;

    /**
     * Class constructor. Calls the parent class and initialises the local attribute split_steps
     * @param	boolean	Whether to split steps (true) or make one large page (false)
     * @param	string	Course code
     * @param	integer	Resource ID
     * @param	integer Creator user id
     * @return	void
     */
    function OpenofficeText($split_steps = false, $course_code = null, $resource_id = null, $user_id = null) {
        $this -> split_steps = $split_steps;
        parent::__construct($course_code, $resource_id, $user_id);
    }

    /**
     * Gets html pages and compose them into a learning path
     * @param	array	The files that will compose the generated learning path. Unused so far.
     * @return	boolean	False if file does not exit. Nothing otherwise.
     */
    function make_lp($files = array()) {

        global $_course;
        // We get a content where ||page_break|| indicates where the page is broken.
        if (!file_exists($this->base_work_dir.'/'.$this->created_dir.'/'.$this->file_name.'.html')) { return false; }
        $content = file_get_contents($this->base_work_dir.'/'.$this->created_dir.'/'.$this->file_name.'.html');

        unlink($this->base_work_dir.'/'.$this->file_path);
        unlink($this->base_work_dir.'/'.$this->created_dir.'/'.$this->file_name.'.html');

        // The file is utf8 encoded and it seems to make problems with special quotes.
        // Then we htmlentities that, we replace these quotes and html_entity_decode that in good charset.
        $charset = api_get_system_encoding();
        $content = api_htmlentities($content, ENT_COMPAT, $this->original_charset);
        $content = str_replace('&rsquo;', '\'', $content);
        $content = api_convert_encoding($content, $charset, $this->original_charset);
        $content = str_replace($this->original_charset, $charset, $content);
        $content = api_html_entity_decode($content, ENT_COMPAT, $charset);

        // Set the path to pictures to absolute (so that it can be modified in fckeditor).
        $content = preg_replace("|src=\"([^\"]*)|i", "src=\"".api_get_path(REL_COURSE_PATH).$_course['path'].'/document'.$this->created_dir."/\\1", $content);

        list($header, $body) = explode('<BODY', $content);

        $body = '<BODY'.$body;

        // Remove font-family styles.
        $header = preg_replace("|font\-family[^;]*;|i", '', $header);

        // Chamilo styles.
        $my_style = api_get_setting('stylesheets');
        if (empty($my_style)) { $my_style = 'chamilo'; }
        $style_to_import = "<style type=\"text/css\">\r\n";
        $style_to_import .= '@import "'.api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/default.css";'."\n";        
        $style_to_import .= "</style>\r\n";
        $header = preg_replace("|</head>|i", "\r\n$style_to_import\r\n\\0", $header);

        // Line break before and after picture.
        $header = str_replace('p {', 'p {clear:both;', $header);

        $header = str_replace('absolute', 'relative', $header);

        switch ($this->split_steps) {
            case 'per_page': $this -> dealPerPage($header, $body); break;
            case 'per_chapter': $this -> dealPerChapter($header, $body); break;
        }
    }

    /**
     * Manages chapter splitting
     * @param	string	Chapter header
     * @param	string	Content
     * @return	void
     */
    function dealPerChapter($header, $content) {

        global $_course;

        $content = str_replace('||page_break||', '', $content);

        // Get all the h1.
        preg_match_all("|<h1[^>]*>([^(h1)+]*)</h1>|is", $content, $matches_temp);

        // Empty the fake chapters.
        $new_index = 0;
        for ($i = 0; $i < count($matches_temp[0]); $i++) {

            if (trim($matches_temp[1][$i]) !== '') {
                $matches[0][$new_index] = $matches_temp[0][$i];
                $matches[1][$new_index] = $matches_temp[1][$i];
                $new_index++;
            }

        }

        // Add intro item.
        $intro_content = substr($content, 0, strpos($content, $matches[0][0]));
        $items_to_create[get_lang('Introduction')] = $intro_content;


        for ($i = 0; $i < count($matches[0]); $i++) {

            if (empty($matches[1][$i]))
                continue;

            $content = strstr($content,$matches[0][$i]);
            if ($i + 1 !== count($matches[0])) {
                $chapter_content = substr($content, 0, strpos($content, $matches[0][$i + 1]));
            } else {
                $chapter_content = $content;
            }
            $items_to_create[$matches[1][$i]] = $chapter_content;

        }

        $i = 0;
        foreach ($items_to_create as $item_title => $item_content) {
            $i++;
            $page_content = $this->format_page_content($header, $item_content);

            $html_file = $this->created_dir.'-'.$i.'.html';
            $handle = fopen($this->base_work_dir.$this->created_dir.'/'.$html_file, 'w+');
            fwrite($handle, $page_content);
            fclose($handle);

            $document_id = add_document($_course, $this->created_dir.'/'.$html_file, 'file', filesize($this->base_work_dir.$this->created_dir.'/'.$html_file), $html_file);

            if ($document_id){

                // Put the document in item_property update.
                api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentAdded', $_SESSION['_uid'], 0, 0, null, null, api_get_session_id());

                $infos = pathinfo($this->filepath);
                $slide_name = strip_tags(nl2br($item_title));
                $slide_name = str_replace(array("\r\n", "\r", "\n"), '', $slide_name);
                $slide_name = html_entity_decode($slide_name);
                $previous = learnpath::add_item(0, $previous, 'document', $document_id, $slide_name, '');
                if ($this->first_item == 0) {
                    $this->first_item = $previous;
                }
            }
        }
    }

    /**
     * Manages page splitting
     * @param	string	Page header
     * @param	string	Page body
     * @return	void
     */
    function dealPerPage($header, $body) {
        global $_course;
        // Split document to pages.
        $pages = explode('||page_break||', $body);

        $first_item = 0;

        foreach ($pages as $key => $page_content) {
            // For every pages, we create a new file.

            $key += 1;

            $page_content = $this->format_page_content($header, $page_content, $this->base_work_dir.$this->created_dir);
            $html_file = $this->created_dir.'-'.$key.'.html';
            $handle = fopen($this->base_work_dir.$this->created_dir.'/'.$html_file, 'w+');
            fwrite($handle, $page_content);
            fclose($handle);

            $document_id = add_document($_course, $this->created_dir.$html_file, 'file', filesize($this->base_work_dir.$this->created_dir.$html_file), $html_file);

            $slide_name = '';

            if ($document_id) {

                // Put the document in item_property update.
                api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentAdded', $_SESSION['_uid'], 0, 0, null, null, api_get_session_id());

                $infos = pathinfo($this->filepath);
                $slide_name = 'Page '.str_repeat('0', 2 - strlen($key)).$key;
                $previous = learnpath::add_item(0, $previous, 'document', $document_id, $slide_name, '');
                if ($this->first_item == 0) {
                    $this->first_item = $previous;
                }
                // Code for text indexing.
                if (api_get_setting('search_enabled') == 'true') {
                    if (isset($_POST['index_document']) && $_POST['index_document']) {
                        //Display::display_normal_message(print_r($_POST));
                        $di = new DokeosIndexer();
                        isset($_POST['language']) ? $lang = Database::escape_string($_POST['language']) : $lang = 'english';
                        $di->connectDb(NULL, NULL, $lang);
                        $ic_slide = new IndexableChunk();
                        $ic_slide->addValue('title', $slide_name);
                        $specific_fields = get_specific_field_list();
                        $all_specific_terms = '';
                        foreach ($specific_fields as $specific_field) {
                            if (isset($_REQUEST[$specific_field['code']])) {
                                $sterms = trim($_REQUEST[$specific_field['code']]);
                                $all_specific_terms .= ' '. $sterms;
                                if (!empty($sterms)) {
                                    $sterms = explode(',', $sterms);
                                    foreach ($sterms as $sterm) {
                                        $ic_slide->addTerm(trim($sterm), $specific_field['code']);
                                    }
                                }
                            }
                        }
                        $page_content = $all_specific_terms .' '. $page_content;
                        $ic_slide->addValue('content', $page_content);
                        // Add a comment to say terms separated by commas.
                        $courseid=api_get_course_id();
                        $ic_slide->addCourseId($courseid);
                        $ic_slide->addToolId(TOOL_LEARNPATH);
                        $lp_id = $this->lp_id;
                        $xapian_data = array(
                            SE_COURSE_ID => $courseid,
                            SE_TOOL_ID => TOOL_LEARNPATH,
                            SE_DATA => array('lp_id' => $lp_id, 'lp_item'=> $previous, 'document_id' => $document_id),
                            SE_USER => (int)api_get_user_id(),
                        );
                        $ic_slide->xapian_data = serialize($xapian_data);
                        $di->addChunk($ic_slide);
                        // Index and return search engine document id.
                        $did = $di->index();
                        if ($did) {
                            // Save it to db.
                            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
                            $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, ref_id_second_level, search_did)
                                    VALUES (NULL , \'%s\', \'%s\', %s, %s, %s)';
                            $sql = sprintf($sql, $tbl_se_ref, api_get_course_id(), TOOL_LEARNPATH, $lp_id, $previous, $did);
                            Database::query($sql);
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns additional Java command parameters
     * @return	string	The additional parameters to be used in the Java call
     */
    function add_command_parameters(){
        return ' -d woogie "'.$this->base_work_dir.'/'.$this->file_path.'"  "'.$this->base_work_dir.$this->created_dir.'/'.$this->file_name.'.html"';
    }

    /**
     * Formats a page content by reorganising the HTML code a little
     * @param	string	Page header
     * @param	string	Page content
     * @return	string	Formatted page content
     */
    function format_page_content($header, $content) {
        // Limit the width of the doc.
        list($max_width, $max_height) = explode('x',api_get_setting('service_ppt2lp','size'));

        $content = preg_replace("|<body[^>]*>|i", "\\0\r\n<div style=\"width:".$max_width."\">", $content, -1, $count);
        if ($count < 1) {
            $content = '<body><div style="width:'.$max_width.'">'.$content;
        }

        $content = preg_replace('|</body>|i', '</div>\\0', $content, -1, $count);
        if ($count < 1) {
            $content = $content.'</div></body>';
        }

        // Add the headers.
        $content = $header.$content;

        // Resize all the picture to the max_width-10
        preg_match_all("|<img[^src]*src=\"([^\"]*)\"[^>]*>|i", $content, $images);

        foreach ($images[1] as $key => $image) {
            // Check if the <img tag soon has a width attribute.
            $defined_width = preg_match("|width=([^\s]*)|i", $images[0][$key], $img_width);
            $img_width = $img_width[1];
            if (!$defined_width) {

                list($img_width, $img_height, $type) = getimagesize($this->base_work_dir.$this->created_dir.'/'.$image);

                $new_width = $max_width - 10;
                if ($img_width > $new_width) {
                    $picture_resized = str_ireplace('<img', '<img width="'.$new_width.'" ', $images[0][$key]);
                    $content = str_replace($images[0][$key], $picture_resized, $content);
                }

            } elseif ($img_width > $max_width - 10) {
                $picture_resized = str_ireplace('width='.$img_width, 'width="'.($max_width-10).'"', $images[0][$key]);
                $content = str_replace($images[0][$key], $picture_resized, $content);
            }
        }

        return $content;
    }

    /**
     * Add documents to the visioconference (to be implemented)
     */
    function add_docs_to_visio() {

    }
}
