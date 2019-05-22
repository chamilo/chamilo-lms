<?php
/* For licensing terms, see /license.txt */

/**
 * Suggest words to search.
 *
 * @package chamilo.search
 */
require_once __DIR__.'/../inc/global.inc.php';

function get_suggestions_from_search_engine($q)
{
    global $charset;

    $json = [];
    $table_sfv = Database::get_main_table(TABLE_MAIN_SPECIFIC_FIELD_VALUES);
    $q = Database::escape_string($q);
    $cid = api_get_course_id();
    $sql_add = '';
    if ($cid != -1) {
        $sql_add = " AND course_code = '".$cid."' ";
    }
    $sql = "SELECT * FROM $table_sfv where value LIKE '%$q%'".$sql_add."
            ORDER BY course_code, tool_id, ref_id, field_id";
    $sql_result = Database::query($sql);
    $data = [];
    $i = 0;
    while ($row = Database::fetch_array($sql_result)) {
        $json[] = [
            'id' => api_convert_encoding($row['value'], 'UTF-8', $charset),
            'value' => api_convert_encoding($row['value'], 'UTF-8', $charset),
            'label' => api_convert_encoding($row['value'], 'UTF-8', $charset),
        ];

        if ($i < 20) {
            $data[$row['course_code']][$row['tool_id']][$row['ref_id']] = 1;
        }
        $i++;
    }
    // now that we have all the values corresponding to this search, we want to
    // make sure we get all the associated values that could match this one
    // initial value...
    $more_sugg = [];
    foreach ($data as $cc => $course_id) {
        foreach ($course_id as $ti => $item_tool_id) {
            foreach ($item_tool_id as $ri => $item_ref_id) {
                //natsort($item_ref_id);
                $output = [];
                $field_val = [];
                $sql2 = "SELECT * FROM $table_sfv
                         WHERE course_code = '$cc' AND tool_id = '$ti' AND ref_id = '$ri'
                         ORDER BY field_id";
                $res2 = Database::query($sql2);
                // TODO this code doesn't manage multiple terms in one same field just yet (should duplicate results in this case)
                $field_id = 0;
                while ($row2 = Database::fetch_array($res2)) {
                    //TODO : this code is not perfect yet. It overrides the
                    // first match set, so having 1:Yannick,Julio;2:Rectum;3:LASER
                    // will actually never return: Yannick - Rectum - LASER
                    // because it is overwriteen by Julio - Rectum - LASER
                    // We should have recursivity here to avoid this problem!
                    //Store the new set of results (only one per combination
                    // of all fields)
                    $field_val[$row2['field_id']] = $row2['value'];
                    $current_field_val = '';
                    foreach ($field_val as $id => $val) {
                        $current_field_val .= $val.' - ';
                    }
                    //Check whether we have a field repetition or not. Results
                    // have been ordered by field_id, so we should catch them
                    // all here
                    if ($field_id == $row2['field_id']) {
                        //We found the same field id twice, split the output
                        // array to allow for two sets of results (copy all
                        // existing array elements into copies and update the
                        // copies) eg. Yannick - Car - Driving in $output[1]
                        // will create a copy as Yannick - Car - Speed
                        // in $output[3]
                        $c = count($output);
                        for ($i = 0; $i < $c; $i++) {
                            $output[($c + $i)] = $current_field_val;
                        }
                    } else {
                        //no identical field id, continue as usual
                        $c = count($output);
                        if ($c == 0) {
                            $output[] = $row2['value'].' - ';
                        } else {
                            foreach ($output as $i => $out) {
                                //use the latest combination of fields
                                $output[$i] .= $row2['value'].' - ';
                            }
                        }
                        $field_id = $row2['field_id'];
                    }
                }
                foreach ($output as $i => $out) {
                    if (api_stristr($out, $q) === false) {
                        continue;
                    }
                    $s = api_convert_encoding(substr($out, 0, -3), 'UTF-8', $charset);
                    if (!in_array($s, $more_sugg)) {
                        $more_sugg[] = $s;
                        $json[] = [
                            'id' => $s,
                            'value' => $s,
                            'label' => $s,
                        ];
                    }
                }
            }
        }
    }

    echo json_encode($json);
}

if (isset($_GET['term'])) {
    $q = strtolower($_GET['term']);
    if (!$q) {
        return;
    }

    get_suggestions_from_search_engine($q);
}
