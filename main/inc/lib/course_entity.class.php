<?php

/**
 * Description of course_entity
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class CourseEntity extends Entity
{

    public function __construct()
    {
        $this->defaults('c_id', self::current_course()->get_id());
        $this->defaults('session_id', api_get_session_id());
    }

}