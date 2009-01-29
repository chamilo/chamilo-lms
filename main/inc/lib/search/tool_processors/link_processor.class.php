<?php
include_once dirname(__FILE__) . '/../../../global.inc.php';
require_once dirname(__FILE__) . '/search_processor.class.php';

/**
 * Process links before pass it to search listing scripts
 */
class link_processor extends search_processor {
	public $links = array();

    function link_processor($rows) {
        $this->rows = $rows;

        // group all links together
        foreach ($rows as $row_id => $row_val) {
            $link_id = $row_val['xapian_data'][SE_DATA]['link_id'];
            $courseid = $row_val['courseid'];
            $item = array(
              'courseid' => $courseid,
              'score' => $row_val['score'],
              'link_id' => $link_id,
              'row_id' => $row_id,
              );
            $this->links[$courseid]['links'][] = $item;
            $this->links[$courseid]['total_score'] += $row_val['score'];
        }

    }

    public function process() {
        $results = array();

        foreach ($this->links as $courseid => $one_course_links) {
	        $course_info = api_get_course_info($courseid);
            $search_show_unlinked_results = (api_get_setting('search_show_unlinked_results') == 'true');
            $course_visible_for_user = api_is_course_visible_for_user(NULL, $courseid);
	        // can view course?
	        if ($course_visible_for_user || $search_show_unlinked_results) {
	        	$result = NULL;
		        foreach ($one_course_links['links'] as $one_link) {
			        // is visible?
			        $visibility = api_get_item_visibility($course_info, TOOL_LINK, $one_link['link_id']);
			        if ($visibility) {
			        	// if one is visible let show the result for a course
			        	// also asume all data of this item like the data of the whole group of links(Ex. author) 
				        list($thumbnail, $image, $name, $author, $url) = $this->get_information($courseid, $one_link['link_id']);
                        if ($search_show_unlinked_results) {
                            if (!$course_visible_for_user) {
                                $url = '';
                            }
                        }
                            $result = array(
	                            'toolid' => TOOL_LINK,
								'score' => $one_course_links['total_score']/(count($one_course_links)-1), // not count total_score array item
								'url' => $url,
								'thumbnail' => $thumbnail,
								'image' => $image,
								'title' => $name,
								'author' => $author,
                            );
				        break;
			        }	          	
		        }
		        if (!is_null($result)) {
		        	$results[] = $result;
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
     * Get document information
     */
    private function get_information($course_id, $link_id) {
        $item_property_table = Database::get_course_table_from_code($course_id, TABLE_ITEM_PROPERTY);

        $sql = "SELECT insert_user_id
          FROM       $item_property_table
          WHERE      ref = $link_id
                     AND tool = '". TOOL_LINK ."'
          LIMIT 1";

        $name = get_lang('Links');
        $url = api_get_path(WEB_PATH) . 'main/link/link.php?cidReq=%s';
        $url = sprintf($url, $course_id);
        // Get the image path
        $thumbnail = api_get_path(WEB_CODE_PATH) .'img/link.gif';
        $image = $thumbnail; //FIXME: use big images
        // get author
        $author = '';
        $item_result = api_sql_query ($sql);
        if ($row = Database::fetch_array ($item_result)) {
	        $user_data = api_get_user_info($row['insert_user_id']);
	        $author = $user_data['firstName'] .' '. $user_data['lastName'];
        }

        return array($thumbnail, $image, $name, $author, $url);
    }
}
?>
