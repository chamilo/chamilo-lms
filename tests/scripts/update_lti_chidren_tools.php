<?php

/* For licensing terms, see /license.txt */

/**
 * Update all children tools created by a LTI tool in courses.
 */

exit;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

require __DIR__.'/../../main/inc/global.inc.php';


// Arguments
$parentLtiToolId = 1;
$visibility = 1;
$image = 'ims_lti.png';

// Processing

$em = Database::getManager();
$tblCTool = Database::get_course_table(TABLE_TOOL_LIST);
/** @var ImsLtiTool $parentLtiTool */
$parentLtiTool = $em->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', $parentLtiToolId);

if (!$parentLtiTool) {
    exit("LTI tool ($parentLtiToolId) not found.".PHP_EOL);
}

echo "Updating children tools for parent {$parentLtiTool->getId()}:".PHP_EOL;

$childrenLtiTools = $parentLtiTool->getChildren();

/** @var ImsLtiTool $childrenLtiTool */
foreach ($childrenLtiTools as $childrenLtiTool) {
    $sql = "UPDATE $tblCTool
        SET visibility = $visibility,
            image = '$image'
        WHERE link = 'ims_lti/start.php?id={$childrenLtiTool->getId()}'
            AND category = 'plugin'";

    Database::query($sql);

    echo "\tLTI tool updated: {$childrenLtiTool->getId()}".PHP_EOL;
}

echo "Done.".PHP_EOL;
