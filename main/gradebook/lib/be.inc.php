<?php
/* For licensing terms, see /license.txt */
/**
 * include this file to have access to all backend classes
 * @author Bert Steppé
 */
/**
 * Code
 */
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
define ('LIMIT', 1000);
require_once 'be/gradebookitem.class.php';
require_once 'be/category.class.php';
require_once 'be/evaluation.class.php';
require_once 'be/result.class.php';
require_once 'be/linkfactory.class.php';	// this contains the include declarations
											// to all link classes
