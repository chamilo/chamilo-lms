<?php
/* For licensing terms, see /license.txt */

/**
 * Process part of the SCORM sub-process for upload. This script MUST BE included by upload/index.php
 * as it prepares most of the variables needed here.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
$cwdir = getcwd();
$scorm = null;
require_once '../lp/lp_upload.php';

// Reinit current working directory as many functions in upload change it
chdir($cwdir);
header('location: ../lp/lp_controller.php?action=list&'.api_get_cidreq());
exit;
