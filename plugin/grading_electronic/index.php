<?php
/* For licensing terms, see /license.txt */

if (strpos($_SERVER['SCRIPT_NAME'], 'gradebook/index.php') === false) {
    return;
}

$gradingElectronic = GradingElectronicPlugin::create();

if (!$gradingElectronic->isAllowed()) {
    return;
}

$_template['show'] = true;
$_template['plugin_title'] = $gradingElectronic->get_title();
$_template['form'] = $gradingElectronic->getForm();
