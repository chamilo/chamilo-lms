<?php

require_once dirname(__FILE__) . '/ChamiloRequirements.php';
require_once dirname(__FILE__) . '/../vendor/autoload.php';

$requirements = new ChamiloRequirements();
$requirementNeeded = true;
foreach ($requirements->getChamiloRequirements() as $requirement) {
    /** @var Requirement $requirement */
    if (!$requirement->isFulfilled()) {
        echo $requirement->getTestMessage() . "\n";
    }
}
