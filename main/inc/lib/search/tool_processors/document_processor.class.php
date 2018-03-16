<?php
/* For licensing terms, see /license.txt */

/**
 * Process documents before pass it to search listing scripts.
 *
 * @package chamilo.include.search
 */
class document_processor extends search_processor
{
    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function process()
    {
        $results = [];
        foreach ($this->rows as $row_val) {
            $search_show_unlinked_results = (api_get_setting('search_show_unlinked_results') == 'true');
            $course_visible_for_user = api_is_course_visible_for_user(null, $row_val['courseid']);
            // can view course?
            if ($course_visible_for_user || $search_show_unlinked_results) {
                // is visible?
                $visibility = api_get_item_visibility(api_get_course_info($row_val['courseid']), TOOL_DOCUMENT, $row_val['xapian_data'][SE_DATA]['doc_id']);
                if ($visibility) {
                    list($thumbnail, $image, $name, $author, $url) = $this->get_information($row_val['courseid'], $row_val['xapian_data'][SE_DATA]['doc_id']);
                    $result = [
                        'toolid' => TOOL_DOCUMENT,
                        'score' => $row_val['score'],
                        'url' => $url,
                        'thumbnail' => $thumbnail,
                        'image' => $image,
                        'title' => $name,
                        'author' => $author,
                    ];
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

        // get information to sort
        foreach ($results as $key => $row) {
            $score[$key] = $row['score'];
        }

        // Sort results with score descending
        array_multisort($score, SORT_DESC, $results);

        return $results;
    }

    /**
     * Get document information.
     */
    private function get_information($course_id, $doc_id)
    {
        $course_information = api_get_course_info($course_id);
        $course_id = $course_information['real_id'];
        $course_path = $course_information['path'];
        if (!empty($course_information)) {
            $item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
            $doc_table = Database::get_course_table(TABLE_DOCUMENT);

            $doc_id = intval($doc_id);
            $sql = "SELECT * FROM       $doc_table
                    WHERE      $doc_table.id = $doc_id AND c_id = $course_id
                    LIMIT 1";
            $dk_result = Database::query($sql);

            $sql = "SELECT insert_user_id FROM       $item_property_table
                    WHERE   ref = $doc_id AND tool = '".TOOL_DOCUMENT."' AND c_id = $course_id
                    LIMIT 1";
            $name = '';
            if ($row = Database::fetch_array($dk_result)) {
                $name = $row['title'];
                $url = api_get_path(WEB_COURSE_PATH).'%s/document%s';
                $url = sprintf($url, $course_path, $row['path']);
                // Get the image path
                $icon = choose_image(basename($row['path']));
                $thumbnail = Display::returnIconPath($icon);
                $image = $thumbnail;
                //FIXME: use big images
                // get author
                $author = '';
                $item_result = Database::query($sql);
                if ($row = Database::fetch_array($item_result)) {
                    $user_data = api_get_user_info($row['insert_user_id']);
                    $author = api_get_person_name($user_data['firstName'], $user_data['lastName']);
                }
            }

            return [$thumbnail, $image, $name, $author, $url]; // FIXME: is it posible to get an author here?
        } else {
            return [];
        }
    }
}
