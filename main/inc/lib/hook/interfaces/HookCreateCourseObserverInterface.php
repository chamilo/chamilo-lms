<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 *
 * @package chamilo.library.hook
 */

/**
 * Interface CreateUserHookInterface.
 */
interface HookCreateCourseObserverInterface extends HookObserverInterface
{
    /**
     * @return int
     */
    public function hookCreateCourse(HookCreateCourseEventInterface $hook);
}
