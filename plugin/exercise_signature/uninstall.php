<?php

/* For license terms, see /license.txt */

if (!api_is_platform_admin()) {
    die('You must have admin permissions to uninstall plugins');
}

ExerciseSignaturePlugin::create()->uninstall();
