<?php
/* For license terms, see /license.txt */
/**
 * Errors management for the Buy Courses plugin - Redirects to course_catalog.php.
 */
/**
 * Config.
 */
unset($_SESSION['bc_sale_id']);

header('Location: course_catalog.php');
exit;
