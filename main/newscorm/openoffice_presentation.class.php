<?php
/* For licensing terms, see /license.txt */

/**
 * Defines the OpenofficeDocument class, which is meant as a conversion
 * tool from Office presentations (.ppt, .sxi, .odp, .pptx) to
 * learning paths
 * @package chamilo.learnpath
 * @author  Eric Marguin <eric.marguin@dokeos.com>
 * @license GNU/GPL
 */

/**
 * Defines the "OpenofficePresentation" child of class "OpenofficeDocument"
 */
require_once 'openoffice_document.class.php';
if (api_get_setting('search_enabled') == 'true') {
    require_once api_get_path(LIBRARY_PATH).'search/DokeosIndexer.class.php';
    require_once api_get_path(LIBRARY_PATH).'search/IndexableChunk.class.php';
    require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
}

class OpenofficePresentation extends OpenofficeDocument {

    public $take_slide_name;

    public function __construct($take_slide_name = false, $course_code = null, $resource_id = null, $user_id = null) {
        $this -> take_slide_name = $take_slide_name;
        parent::__construct($course_code, $resource_id, $user_id);
    }


    public function make_lp($files = array()) {

        global $_course;

        $previous = 0;
        $i = 0;

        if (!is_dir($this->base_work_dir.$this->created_dir))
            return false;


        foreach ($files as $file) {

            list($slide_name, $file_name, $slide_body) = explode('||', $file); // '||' is used as separator between fields: slide name (with accents) || file name (without accents) || all slide text (to be indexed).

            // Filename is utf8 encoded, but when we decode, some chars are not translated (like quote &rsquo;).
            // so we remove these chars by translating it in htmlentities and the reconvert it in want charset.
            $slide_name = api_htmlentities($slide_name, ENT_COMPAT, $this->original_charset);
            $slide_name = str_replace('&rsquo;', '\'', $slide_name);
            $slide_name = api_convert_encoding($slide_name, api_get_system_encoding(), $this->original_charset);
            $slide_name = api_html_entity_decode($slide_name, ENT_COMPAT, api_get_system_encoding());

            if ($this->take_slide_name === true) {
                $slide_name = str_replace('_', ' ', $slide_name);
                $slide_name = api_ucfirst($slide_name);
            } else {
                $slide_name = 'slide'.str_repeat('0', 2 - strlen($i)).$i;
            }

            $i++;
            // Add the png to documents.
            $document_id = add_document($_course, $this->created_dir.'/'.urlencode($file_name), 'file', filesize($this->base_work_dir.$this->created_dir.'/'.$file_name), $slide_name);
            api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentAdded', api_get_user_id(), 0, 0, null, null, api_get_session_id());

            // Generating the thumbnail.
            $image = $this->base_work_dir.$this->created_dir .'/'. $file_name;

            $pattern = '/(\w+)\.png$/';
            $replacement = '${1}_thumb.png';
            $thumb_name = preg_replace($pattern, $replacement, $file_name);
            
            // Calculate thumbnail size.
            $image_size = api_getimagesize($image);
            $width  = $image_size['width'];
            $height = $image_size['height'];
            
            $thumb_width = 300;
            $thumb_height = floor($height * ($thumb_width / $width));
  
            $my_new_image = Image($image);
            $my_new_image->resize($thumb_width, $thumb_height);
            $my_new_image->send_image($this->base_work_dir.$this->created_dir .'/'. $thumb_name, -1, 'png');
  
            // Adding the thumbnail to documents.
            $document_id_thumb = add_document($_course, $this->created_dir.'/'.urlencode($thumb_name), 'file', filesize($this->base_work_dir.$this->created_dir.'/'.$thumb_name), $slide_name);
            api_item_property_update($_course, TOOL_THUMBNAIL, $document_id_thumb, 'DocumentAdded', api_get_user_id(), 0, 0);

            // Create an html file.
            $html_file = $file_name.'.html';
            $fp = fopen($this->base_work_dir.$this->created_dir.'/'.$html_file, 'w+');

            $slide_src = api_get_path(REL_COURSE_PATH).$_course['path'].'/document/'.$this->created_dir.'/'.utf8_encode($file_name);
            $slide_src = str_replace('//', '/', $slide_src);
            fwrite($fp,
'<html>
    <head>
    </head>
    <body>
        <img src="'.$slide_src.'" />
    </body>
</html>');  // This indentation is to make the generated html files to look well.

            fclose($fp);
            $document_id = add_document($_course,$this->created_dir.'/'.urlencode($html_file), 'file', filesize($this->base_work_dir.$this->created_dir.'/'.$html_file), $slide_name);
            if ($document_id) {

                // Put the document in item_property update.
                api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentAdded', api_get_user_id(), 0, 0, null, null, api_get_session_id());

                $previous = $this->add_item(0, $previous, 'document', $document_id, $slide_name, '');
                if ($this->first_item == 0) {
                    $this->first_item = $previous;
                }
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
                    $slide_body = $all_specific_terms .' '. $slide_body;
                    $ic_slide->addValue('content', $slide_body);
                    /* FIXME:  cidReq:lp_id:doc_id al indexar  */
                    // Add a comment to say terms separated by commas.
                    $courseid = api_get_course_id();
                    $ic_slide->addCourseId($courseid);
                    $ic_slide->addToolId(TOOL_LEARNPATH);
                    $lp_id = $this->lp_id;
                    $xapian_data = array(
                        SE_COURSE_ID => $courseid,
                        SE_TOOL_ID => TOOL_LEARNPATH,
                        SE_DATA => array('lp_id' => $lp_id, 'lp_item' => $previous, 'document_id' => $document_id),
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

    function add_command_parameters() {

        if (empty($this->slide_width) || empty($this->slide_height))
            list($this->slide_width, $this->slide_height) = explode('x', api_get_setting('service_ppt2lp', 'size'));
        return ' -w '.$this->slide_width.' -h '.$this->slide_height.' -d oogie "'.$this->base_work_dir.'/'.$this->file_path.'"  "'.$this->base_work_dir.$this->created_dir.'.html"';

    }

    function set_slide_size($width, $height) {
        $this->slide_width = $width;
        $this->slide_height = $height;
    }

    function add_docs_to_visio ($files = array()) {

        global $_course;
        /* Add Files */

        foreach ($files as $file) {

            list($slide_name,$file_name) = explode('||',$file); // '||' is used as separator between slide name (with accents) and file name (without accents).
            $slide_name = api_htmlentities($slide_name, ENT_COMPAT, $this->original_charset);
            $slide_name = str_replace('&rsquo;', '\'', $slide_name);
            $slide_name = api_convert_encoding($slide_name, api_get_system_encoding(), $this->original_charset);
            $slide_name = api_html_entity_decode($slide_name, ENT_COMPAT, api_get_system_encoding());

            $did = add_document($_course, $this->created_dir.'/'.urlencode($file_name), 'file', filesize($this->base_work_dir.$this->created_dir.'/'.$file_name), $slide_name);
            if ($did)
                api_item_property_update($_course, TOOL_DOCUMENT, $did, 'DocumentAdded', $_SESSION['_uid'], 0, null, null, null, api_get_session_id());

        }

    }

}
