<?php

/* For licensing terms, see /license.txt */

// Check extra_field remedialcourselist and advancedCourseList

if (!api_is_platform_admin()) {
    exit('You must have admin permissions to install plugins');
}

RemedialCoursePlugin::create()->install();
