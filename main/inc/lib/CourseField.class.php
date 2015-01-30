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

    /**
     * Add elements to a form
     * @param FormValidator $form the form
     * @param string $courseCode The course code
     * @return array The extra data. Otherwise return false
     */
    public function addElements($form, $courseCode = null)
    {
        if (empty($form)) {
            return false;
        }

        $extra_data = false;
        if (!empty($courseCode)) {
            $extra_data = self::get_handler_extra_data($courseCode);

            if ($form) {
                $form->setDefaults($extra_data);
            }
        }

        $extra_fields = $this->get_all(null, 'option_order');

        $specilCourseFieldId = -1;

        foreach ($extra_fields as $id => $extraField) {
            if ($extraField['field_variable'] === self::SPECIAL_COURSE_FIELD) {
                $specilCourseFieldId = $id;
            }
        }

        if (isset($extra_fields[$specilCourseFieldId])) {
            unset($extra_fields[$specilCourseFieldId]);
        }

        $extra = $this->set_extra_fields_in_form(
            $form, $extra_data, $this->type . '_field', false, false, $extra_fields, $courseCode
        );

        return $extra;
    }

}
