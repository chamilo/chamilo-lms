<?php
/* For licensing terms, see /license.txt */

/**
 * Manage the course extra fields
 * @package chamilo.library
 */
/**
 * Manage the course extra fields
 *
 * Add the extra fields to the form excluding the Special Course Field
 */
class CourseField extends ExtraField
{

    /**
     * Special Course extra field
     */
    const SPECIAL_COURSE_FIELD = 'special_course';

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct('course');
    }
}
