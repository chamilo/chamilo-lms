<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 *
 * @package chamilo.library.hook
 */

/**
 * Interface HookAdminBlockEventInterface.
 */
interface HookAdminBlockEventInterface extends HookEventInterface
{
    /**
     * @param int $type
     *
     * @return int
     */
    public function notifyAdminBlock($type);
}
