<?php
/* For licensing terms, see /license.txt */

require_once api_get_path(SYS_PATH).'main/inc/lib/kses-0.2.2/kses.php';

/**
 * QuickForm rule to check a html.
 */
class HTML_QuickForm_Rule_HTML extends HTML_QuickForm_Rule
{
    /**
     * Function to validate HTML.
     *
     * @see HTML_QuickForm_Rule
     *
     * @param string $html
     *
     * @return bool True if html is valid
     */
    public function validate($html, $mode = NO_HTML)
    {
        $allowed_tags = self::get_allowed_tags($mode, $fullpage);
        $cleaned_html = kses($html, $allowed_tags);

        return $html == $cleaned_html;
    }

    /**
     * Get allowed tags.
     *
     * @param int $mode NO_HTML, STUDENT_HTML, TEACHER_HTML,
     *                  STUDENT_HTML_FULLPAGE or TEACHER_HTML_FULLPAGE
     */
    public static function get_allowed_tags($mode)
    {
        // Include the allowed tags.
        //include __DIR__.'/allowed_tags.inc.php';
        global $allowed_tags_student, $allowed_tags_student_full_page, $allowed_tags_teacher, $allowed_tags_teacher_full_page;
        switch ($mode) {
            case NO_HTML:
                return [];
                break;
            case STUDENT_HTML:
                return $allowed_tags_student;
                break;
            case STUDENT_HTML_FULLPAGE:
                return array_merge($allowed_tags_student, $allowed_tags_student_full_page);
                break;
            case TEACHER_HTML:
                return $allowed_tags_teacher;
                break;
            case TEACHER_HTML_FULLPAGE:
                return array_merge($allowed_tags_teacher, $allowed_tags_teacher_full_page);
                break;
            default:
                return [];
                break;
        }
    }
}
