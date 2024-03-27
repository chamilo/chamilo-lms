<?php
/* For licensing terms, see /license.txt */

/**
 * Interface for all displayable items in the gradebook.
 *
 * @author Bert Steppé
 */
interface GradebookItem
{
    public function get_item_type();

    public function get_id();

    public function get_name();

    public function get_description();

    public function get_course_code();

    public function get_weight();

    public function get_date();

    public function is_visible();

    public function get_icon_name();

    public function getStudentList();

    public function setStudentList($list);

    public function calc_score(?int $studentId = null, ?string $type = null);
}
