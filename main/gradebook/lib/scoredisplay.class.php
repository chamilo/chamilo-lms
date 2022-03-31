<?php

/* For licensing terms, see /license.txt */

/**
 * Class ScoreDisplay
 * Display scores according to the settings made by the platform admin.
 * This class works as a singleton: call instance() to retrieve an object.
 *
 * @author Bert Steppé
 */
class ScoreDisplay
{
    private $coloring_enabled;
    private $color_split_value;
    private $custom_enabled;
    private $upperlimit_included;
    private $custom_display;
    private $custom_display_conv;

    /**
     * Protected constructor - call instance() to instantiate.
     *
     * @param int $category_id
     */
    public function __construct($category_id = 0)
    {
        if (!empty($category_id)) {
            $this->category_id = $category_id;
        }

        // Loading portal settings + using standard functions.
        $value = api_get_setting('gradebook_score_display_coloring');
        $value = $value['my_display_coloring'];

        // Setting coloring.
        $this->coloring_enabled = $value === 'true' ? true : false;

        if ($this->coloring_enabled) {
            $value = api_get_setting('gradebook_score_display_colorsplit');
            if (isset($value)) {
                $this->color_split_value = $value;
            }
        }

        // Setting custom enabled
        $value = api_get_setting('gradebook_score_display_custom');
        $value = $value['my_display_custom'];
        $this->custom_enabled = $value === 'true' ? true : false;

        if ($this->custom_enabled) {
            $params = ['category = ?' => ['Gradebook']];
            $displays = api_get_settings_params($params);
            $portal_displays = [];
            if (!empty($displays)) {
                foreach ($displays as $display) {
                    $data = explode('::', $display['selected_value']);
                    if (empty($data[1])) {
                        $data[1] = '';
                    }
                    $portal_displays[$data[0]] = [
                        'score' => $data[0],
                        'display' => $data[1],
                    ];
                }
                sort($portal_displays);
            }
            $this->custom_display = $portal_displays;
            if (count($this->custom_display) > 0) {
                $value = api_get_setting('gradebook_score_display_upperlimit');
                $value = $value['my_display_upperlimit'];
                $this->upperlimit_included = $value === 'true' ? true : false;
                $this->custom_display_conv = $this->convert_displays($this->custom_display);
            }
        }

        // If teachers can override the portal parameters
        if (api_get_setting('teachers_can_change_score_settings') === 'true') {
            //Load course settings
            if ($this->custom_enabled) {
                $this->custom_display = $this->get_custom_displays();
                if (count($this->custom_display) > 0) {
                    $this->custom_display_conv = $this->convert_displays($this->custom_display);
                }
            }

            if ($this->coloring_enabled) {
                $this->color_split_value = $this->get_score_color_percent();
            }
        }
    }

    /**
     * Get the instance of this class.
     *
     * @param int $categoryId
     *
     * @return ScoreDisplay
     */
    public static function instance($categoryId = 0)
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new ScoreDisplay($categoryId);
        }

        return $instance;
    }

    /**
     * Compare the custom display of 2 scores, can be useful in sorting.
     */
    public static function compare_scores_by_custom_display($score1, $score2)
    {
        if (!isset($score1)) {
            return isset($score2) ? 1 : 0;
        }

        if (!isset($score2)) {
            return -1;
        }

        $scoreDisplay = self::instance();
        $custom1 = $scoreDisplay->display_custom($score1);
        $custom2 = $scoreDisplay->display_custom($score2);
        if ($custom1 == $custom2) {
            return 0;
        }

        return ($score1[0] / $score1[1]) < ($score2[0] / $score2[1]) ? -1 : 1;
    }

    /**
     * Is coloring enabled ?
     */
    public function is_coloring_enabled()
    {
        return $this->coloring_enabled;
    }

    /**
     * Is custom score display enabled ?
     */
    public function is_custom()
    {
        return $this->custom_enabled;
    }

    /**
     * Is upperlimit included ?
     */
    public function is_upperlimit_included()
    {
        return $this->upperlimit_included;
    }

    /**
     * If custom score display is enabled, this will return the current settings.
     * See also updateCustomScoreDisplaySettings.
     *
     * @return array current settings (or null if feature not enabled)
     */
    public function get_custom_score_display_settings()
    {
        return $this->custom_display;
    }

    /**
     * If coloring is enabled, scores below this value will be displayed in red.
     *
     * @return int color split value, in percent (or null if feature not enabled)
     */
    public function get_color_split_value()
    {
        return $this->color_split_value;
    }

    /**
     * Update custom score display settings.
     *
     * @param array $displays 2-dimensional array - every sub array must have keys (score, display)
     * @param int   score color percent (optional)
     * @param int   gradebook category id (optional)
     */
    public function updateCustomScoreDisplaySettings(
        $displays,
        $scorecolpercent = 0,
        $category_id = null
    ) {
        $this->custom_display = $displays;
        $this->custom_display_conv = $this->convert_displays($this->custom_display);
        if (isset($category_id)) {
            $category_id = (int) $category_id;
        } else {
            $category_id = $this->get_current_gradebook_category_id();
        }

        // remove previous settings
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_SCORE_DISPLAY);
        $sql = 'DELETE FROM '.$table.' WHERE category_id = '.$category_id;
        Database::query($sql);

        // add new settings
        foreach ($displays as $display) {
            $params = [
                'score' => $display['score'],
                'display' => $display['display'],
                'category_id' => $category_id,
                'score_color_percent' => $scorecolpercent,
            ];
            Database::insert($table, $params);
        }
    }

    /**
     * @param int $category_id
     *
     * @return false|null
     */
    public function insert_defaults($category_id)
    {
        if (empty($category_id)) {
            return false;
        }

        //Get this from DB settings
        $display = [
            50 => get_lang('GradebookFailed'),
            60 => get_lang('GradebookPoor'),
            70 => get_lang('GradebookFair'),
            80 => get_lang('GradebookGood'),
            90 => get_lang('GradebookOutstanding'),
            100 => get_lang('GradebookExcellent'),
        ];

        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_SCORE_DISPLAY);
        foreach ($display as $value => $text) {
            $params = [
                'score' => $value,
                'display' => $text,
                'category_id' => $category_id,
                'score_color_percent' => 0,
            ];
            Database::insert($table, $params);
        }
    }

    /**
     * @return int
     */
    public function get_number_decimals()
    {
        $number_decimals = api_get_setting('gradebook_number_decimals');
        if (!isset($number_decimals)) {
            $number_decimals = 0;
        }

        return $number_decimals;
    }

    /**
     * Formats a number depending of the number of decimals.
     *
     * @param float  $score
     * @param bool   $ignoreDecimals
     * @param string $decimalSeparator
     * @param string $thousandSeparator
     * @param bool   $removeEmptyDecimals Converts 100.00 to 100, 53.00 to 53
     *
     * @return float the score formatted
     */
    public function format_score(
        $score,
        $ignoreDecimals = false,
        $decimalSeparator = '.',
        $thousandSeparator = ',',
        $removeEmptyDecimals = false
    ) {
        $decimals = $this->get_number_decimals();
        if ($ignoreDecimals) {
            $decimals = 0;
        }

        if ($removeEmptyDecimals) {
            if ($score && self::hasEmptyDecimals($score)) {
                $score = round($score);
                $decimals = 0;
            }
        }

        return api_number_format($score, $decimals, $decimalSeparator, $thousandSeparator);
    }

    public static function hasEmptyDecimals($score)
    {
        $hasEmptyDecimals = false;
        if (is_float($score)) {
            $check = fmod($score, 1);
            if (0 === bccomp(0, $check)) {
                $score = round($score);
                $hasEmptyDecimals = true;
            }
        }
        if (is_int($score) || is_string($score)) {
            $score = (float) $score;
            $check = fmod($score, 1);
            if (0 === bccomp(0, $check)) {
                $hasEmptyDecimals = true;
            }
        }

        return $hasEmptyDecimals;
    }

    /**
     * Display a score according to the current settings.
     *
     * @param array $score               data structure, as returned by the calc_score functions
     * @param int   $type                one of the following constants:
     *                                   SCORE_DIV, SCORE_PERCENT, SCORE_DIV_PERCENT, SCORE_AVERAGE
     *                                   (ignored for student's view if custom score display is enabled)
     * @param int   $what                one of the following constants:
     *                                   SCORE_BOTH, SCORE_ONLY_DEFAULT, SCORE_ONLY_CUSTOM (default: SCORE_BOTH)
     *                                   (only taken into account if custom score display is enabled and for course/platform admin)
     * @param bool  $disableColor
     * @param bool  $ignoreDecimals
     * @param bool  $removeEmptyDecimals Replaces 100.00 to 100
     *
     * @return string
     */
    public function display_score(
        $score,
        $type = SCORE_DIV_PERCENT,
        $what = SCORE_BOTH,
        $disableColor = false,
        $ignoreDecimals = false,
        $removeEmptyDecimals = false
    ) {
        // No score available.
        if (!is_array($score)) {
            return '-';
        }

        $my_score = $score == 0 ? [] : $score;
        switch ($type) {
            case SCORE_BAR:
                $percentage = $my_score[0] / $my_score[1] * 100;

                return Display::bar_progress($percentage);
                break;
            case SCORE_NUMERIC:
                $percentage = $my_score[0] / $my_score[1] * 100;

                return round($percentage);
                break;
        }

        if (false === $disableColor && $this->custom_enabled && isset($this->custom_display_conv)) {
            $display = $this->displayDefault($my_score, $type, $ignoreDecimals, $removeEmptyDecimals, true);
        } else {
            // if no custom display set, use default display
            $display = $this->displayDefault($my_score, $type, $ignoreDecimals, $removeEmptyDecimals);
        }

        if ($this->coloring_enabled && false === $disableColor) {
            $denom = isset($score[1]) && !empty($score[1]) && $score[1] > 0 ? $score[1] : 1;
            $scoreCleaned = isset($score[0]) ? $score[0] : 0;
            if (($scoreCleaned / $denom) < ($this->color_split_value / 100)) {
                $display = Display::tag(
                    'font',
                    $display,
                    ['color' => 'red']
                );
            }
        }

        return $display;
    }

    /**
     * Depends on the teacher's configuration of thresholds. i.e. [0 50] "Bad", [50:100] "Good".
     *
     * @param array $score
     *
     * @return string
     */
    public function display_custom($score)
    {
        if (empty($score)) {
            return null;
        }

        $denom = $score[1] == 0 ? 1 : $score[1];
        $scaledscore = $score[0] / $denom;

        if ($this->upperlimit_included) {
            foreach ($this->custom_display_conv as $displayitem) {
                if ($scaledscore <= $displayitem['score']) {
                    return $displayitem['display'];
                }
            }
        } else {
            if (!empty($this->custom_display_conv)) {
                foreach ($this->custom_display_conv as $displayitem) {
                    if ($scaledscore < $displayitem['score'] || $displayitem['score'] == 1) {
                        return $displayitem['display'];
                    }
                }
            }
        }
    }

    /**
     * Get current gradebook category id.
     *
     * @return int Category id
     */
    private function get_current_gradebook_category_id()
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $courseCode = api_get_course_id();
        $sessionId = api_get_session_id();
        $sessionCondition = api_get_session_condition($sessionId, true);

        $sql = "SELECT id FROM $table
                WHERE course_code = '$courseCode'  $sessionCondition";
        $rs = Database::query($sql);
        $categoryId = 0;
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_row($rs);
            $categoryId = $row[0];
        }

        return $categoryId;
    }

    /**
     * @param array $score
     * @param int   $type
     * @param bool  $ignoreDecimals
     * @param bool  $removeEmptyDecimals
     * @param bool  $addScoreLabel
     *
     * @return string
     */
    private function displayDefault($score, $type, $ignoreDecimals = false, $removeEmptyDecimals = false, $addScoreLabel = false)
    {
        $scoreLabel = '';
        if ($addScoreLabel) {
            $scoreLabel = $this->display_custom($score);
            if (!empty($scoreLabel)) {
                $scoreLabel = ' - '.$scoreLabel;
            }
        }

        switch ($type) {
            case SCORE_DIV:                            // X / Y
                return $this->display_as_div($score, $ignoreDecimals, $removeEmptyDecimals).$scoreLabel;
            case SCORE_PERCENT:                        // XX %
                return $this->display_as_percent($score).$scoreLabel;
            case SCORE_DIV_PERCENT:                    // X / Y (XX %)
                //return $this->display_as_div($score).' ('.$this->display_as_percent($score).')';
                // 2020-10 Changed to XX % (X / Y)
                return $this->display_as_percent($score).' ('.$this->display_as_div($score).')'.$scoreLabel;
            case SCORE_AVERAGE:                        // XX %
                return $this->display_as_percent($score).$scoreLabel;
            case SCORE_DECIMAL:                        // 0.50  (X/Y)
                return $this->display_as_decimal($score).$scoreLabel;
            case SCORE_DIV_PERCENT_WITH_CUSTOM:        // X / Y (XX %) - Good!
                $div = $this->display_as_div($score, false, $removeEmptyDecimals);

                return
                    $this->display_as_percent($score).PHP_EOL
                    .Display::div("($div)", ['class' => 'small'])
                    .$scoreLabel;
            case SCORE_DIV_SIMPLE_WITH_CUSTOM:         // X - Good!
                return $this->display_simple_score($score).$scoreLabel;
                break;
            case SCORE_DIV_SIMPLE_WITH_CUSTOM_LETTERS:
                $score = $this->display_simple_score($score);
                // Needs sudo apt-get install php5-intl
                if (class_exists('NumberFormatter')) {
                    $iso = api_get_language_isocode();
                    $f = new NumberFormatter($iso, NumberFormatter::SPELLOUT);
                    $letters = $f->format($score);
                    $letters = api_strtoupper($letters);
                    $letters = " ($letters) ";
                }

                return $score.$letters.$scoreLabel;
            case SCORE_CUSTOM: // Good!
                return $this->display_custom($score);
            case SCORE_SIMPLE:
                if (!isset($score[0])) {
                    $score[0] = 0;
                }

                return $this->format_score($score[0], $ignoreDecimals).$scoreLabel;
        }
    }

    /**
     * @param array $score
     *
     * @return float|string
     */
    private function display_simple_score($score)
    {
        if (isset($score[0])) {
            return $this->format_score($score[0]);
        }

        return '';
    }

    /**
     * Returns "1" for array("100", "100").
     *
     * @param array $score
     *
     * @return float
     */
    private function display_as_decimal($score)
    {
        $score_denom = $score[1] == 0 ? 1 : $score[1];

        return $this->format_score($score[0] / $score_denom);
    }

    /**
     * Returns "100 %" for array("100", "100").
     */
    private function display_as_percent($score)
    {
        if (empty($score)) {
            return null;
        }
        $scoreDenom = $score[1] == 0 ? 1 : $score[1];

        return $this->format_score($score[0] / $scoreDenom * 100).' %';
    }

    /**
     * Returns 10.00 / 10.00 for array("100", "100").
     *
     * @param array $score
     * @param bool  $ignoreDecimals
     * @param bool  $removeEmptyDecimals
     *
     * @return string
     */
    private function display_as_div($score, $ignoreDecimals = false, $removeEmptyDecimals = false)
    {
        if ($score == 1) {
            return '0 / 0';
        }

        if (empty($score)) {
            return '0 / 0';
        }

        $score[0] = isset($score[0]) ? $this->format_score($score[0], $ignoreDecimals) : 0;
        $score[1] = isset($score[1]) ? $this->format_score(
            $score[1],
            $ignoreDecimals,
            '.',
            ',',
            $removeEmptyDecimals
        ) : 0;

        return $score[0].' / '.$score[1];
    }

    /**
     * Get score color percent by category.
     *
     * @param   int Gradebook category id
     *
     * @return int Score
     */
    private function get_score_color_percent($category_id = null)
    {
        $tbl_display = Database::get_main_table(TABLE_MAIN_GRADEBOOK_SCORE_DISPLAY);
        if (isset($category_id)) {
            $category_id = (int) $category_id;
        } else {
            $category_id = $this->get_current_gradebook_category_id();
        }

        $sql = 'SELECT score_color_percent FROM '.$tbl_display.'
                WHERE category_id = '.$category_id.'
                LIMIT 1';
        $result = Database::query($sql);
        $score = 0;
        if (Database::num_rows($result) > 0) {
            $row = Database::fetch_row($result);
            $score = $row[0];
        }

        return $score;
    }

    /**
     * Get current custom score display settings.
     *
     * @param   int     Gradebook category id
     *
     * @return array 2-dimensional array every element contains 3 subelements (id, score, display)
     */
    private function get_custom_displays($category_id = null)
    {
        $tbl_display = Database::get_main_table(TABLE_MAIN_GRADEBOOK_SCORE_DISPLAY);
        if (isset($category_id)) {
            $category_id = (int) $category_id;
        } else {
            $category_id = $this->get_current_gradebook_category_id();
        }
        $sql = 'SELECT * FROM '.$tbl_display.'
                WHERE category_id = '.$category_id.'
                ORDER BY score';
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * Convert display settings to internally used values.
     */
    private function convert_displays($custom_display)
    {
        if (isset($custom_display)) {
            // get highest score entry, and copy each element to a new array
            $converted = [];
            $highest = 0;
            foreach ($custom_display as $element) {
                if ($element['score'] > $highest) {
                    $highest = $element['score'];
                }
                $converted[] = $element;
            }
            // sort the new array (ascending)
            usort($converted, ['ScoreDisplay', 'sort_display']);

            // adjust each score in such a way that
            // each score is scaled between 0 and 1
            // the highest score in this array will be equal to 1
            $converted2 = [];
            foreach ($converted as $element) {
                $newelement = [];
                if (isset($highest) && !empty($highest) && $highest > 0) {
                    $newelement['score'] = $element['score'] / $highest;
                } else {
                    $newelement['score'] = 0;
                }
                $newelement['display'] = $element['display'];
                $converted2[] = $newelement;
            }

            return $converted2;
        }

        return null;
    }

    /**
     * @param array $item1
     * @param array $item2
     *
     * @return int
     */
    private function sort_display($item1, $item2)
    {
        if ($item1['score'] === $item2['score']) {
            return 0;
        } else {
            return $item1['score'] < $item2['score'] ? -1 : 1;
        }
    }
}
