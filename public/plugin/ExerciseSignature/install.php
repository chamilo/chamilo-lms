<?php

/* For license terms, see /license.txt */

if (!api_is_platform_admin()) {
    exit('You must have admin permissions to install plugins');
}

ExerciseSignaturePlugin::create()->install();
