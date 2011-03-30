<?php
include_once dirname(__FILE__) . '/../../../global.inc.php';
require_once dirname(__FILE__) . '/search_processor.class.php';
require_once dirname(__FILE__) . '/../IndexableChunk.class.php';

/**
 * Process learning paths before pass it to search listing scripts
 */
class learnpath_processor extends search_processor {
    public $learnpaths = array();

    function learnpath_processor($rows) {
        $this->rows = $rows;
        // group by learning path
        foreach ($rows as $row_id => $row_val) {
            $lp_id = $row_val['xapian_data'][SE_DATA]['lp_id'];
            $lp_item = $row_val['xapian_data'][SE_DATA]['lp_item'];
            $document_id = $row_val['xapian_data'][SE_DATA]['document_id'];
            $courseid = $row_val['courseid'];
            $item = array(
              'courseid' => $courseid,
              'lp_item' => $lp_item,
              'score' => $row_val['score'],
              'row_id' => $row_id,
              );
            $this->learnpaths[$courseid][$lp_id][] = $item;
            $this->learnpaths[$courseid][$lp_id]['total_score'] += $row_val['score'];
            $this->learnpaths[$courseid][$lp_id]['has_document_id'] = is_numeric($document_id);
        }
    }

    public function process() {
        $results = array();
        foreach ($this->learnpaths as $courseid => $learnpaths) {
          $search_show_unlinked_results = (api_get_setting('search_show_unlinked_results') == 'true');
          $course_visible_for_user = api_is_course_visible_for_user(NULL, $courseid);
          // can view course?
          if ($course_visible_for_user || $search_show_unlinked_results) {
            foreach ($learnpaths as $lp_id => $lp) {
              // is visible?
              $visibility = api_get_item_visibility(api_get_course_info($courseid), TOOL_LEARNPATH, $lp_id);
              if($visibility) {
                  list($thumbnail, $image, $name, $author) = $this->get_information($courseid, $lp_id, $lp['has_document_id']);
                  $url = api_get_path(WEB_PATH) . 'main/newscorm/lp_controller.php?cidReq=%s&action=view&lp_id=%s';
                  $url = sprintf($url, $courseid, $lp_id);
                  $result = array(
                      'toolid' => TOOL_LEARNPATH,
                      'score' => $lp['total_score']/(count($lp)-1), // not count total_score array item
                      'url' => $url,
                      'thumbnail' => $thumbnail,
                      'image' => $image,
                      'title' => $name,
                      'author' => $author,
                  );
                  if ($course_visible_for_user) {
                      $results[] = $result;
                  } else { // course not visible for user
                      if ($search_show_unlinked_results) {
                          $result['url'] = '';
                          $results[] = $result;
                      }
                  }
              }
            }
          }
        }

        // get information to sort
        foreach ($results as $key => $row) {
        $score[$key]  = $row['score'];
     }
     // Sort results with score descending
     array_multisort($score, SORT_DESC, $results);
     return $results;
    }

    /**
     * Get learning path information
     */
    private function get_information($course_id, $lp_id, $has_document_id=TRUE) {
        
        $course_information     = api_get_course_info($course_id);
        if (!empty($course_information)) {
            $lpi_table  = Database::get_course_table(TABLE_LP_ITEM, $course_information['db_name']);
            $lp_table   = Database::get_course_table_from_code(TABLE_LP_MAIN, $course_information['db_name']);
            $doc_table  = Database::get_course_table_from_code(TABLE_DOCUMENT, $course_information['db_name']);
            
            $lp_id = Database::escape_string($lp_id);

            if ($has_document_id) {
    	        $sql = "SELECT $lpi_table.id, $lp_table.name, $lp_table.author, $doc_table.path
                    FROM       $lp_table, $lpi_table
                    INNER JOIN $doc_table ON $lpi_table.path = $doc_table.id
                    WHERE      $lpi_table.lp_id = $lp_id
                    AND        $lpi_table.display_order = 1
                    AND        $lp_table.id = $lpi_table.lp_id
                    LIMIT 1";
            }
            else {
    	        $sql = "SELECT $lpi_table.id, $lp_table.name, $lp_table.author
                    FROM       $lp_table, $lpi_table
                    WHERE      $lpi_table.lp_id = $lp_id
                    AND        $lpi_table.display_order = 1
                    AND        $lp_table.id = $lpi_table.lp_id
                    LIMIT 1";
            }
    
            $dk_result = Database::query ($sql);
    
            $path = '';
            $name = '';
            if ($row = Database::fetch_array ($dk_result)) {
                // Get the image path
                $img_location = api_get_path(WEB_COURSE_PATH).api_get_course_path($course_id)."/document/";
                $thumbnail_path = str_replace ('.png.html', '_thumb.png', $row['path']);
                $big_img_path = str_replace ('.png.html', '.png', $row['path']);
                $thumbnail = '';
                if (!empty($thumbnail_path)) {
                  $thumbnail = $img_location . $thumbnail_path;
                }
                $image = '';
                if (!empty($big_img_path)) {
                  $image = $img_location . $big_img_path;
                }
                $name = $row['name'];
            }    
            return array($thumbnail, $image, $name, $row['author']);
        } else {
            return array();
        }
    }
}