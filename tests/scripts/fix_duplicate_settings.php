<?php
/* For license terms, see /license.txt */
/**
 * Remove the current_settings duplicates from migration 1.9.x to 1.10.x
 */

//exit;

require_once __DIR__.'/../../main/inc/global.inc.php';

//api_protect_admin_script();

$entityManager = Database::getManager();
$settingsCurrentRepo = $entityManager->getRepository('ChamiloCoreBundle:SettingsCurrent');
$settingsOptionsRepo = $entityManager->getRepository('ChamiloCoreBundle:SettingsOptions');

$settingsCurrent = $settingsCurrentRepo->findBy([], ['id' => 'ASC']);
$cleanList = [];
$duplicatedCurrent = [];

foreach ($settingsCurrent as $settingCurrent) {
    $key = vsprintf(
        "%s-%s-%s",
        [
            $settingCurrent->getVariable(),
            $settingCurrent->getSubkey(),
            $settingCurrent->getAccessUrl()
        ]
    );

    if (!array_key_exists($key, $cleanList)) {
        $cleanList[$key] = $settingCurrent;

        continue;
    }

    $duplicatedCurrent[] = $settingCurrent;
}

$settingsOptions = $settingsOptionsRepo->findBy([], ['id' => 'ASC']);
$cleanList = [];
$duplicatedOptions = [];

foreach ($settingsOptions as $settingOption) {
    $key = vsprintf(
        "%s-%s",
        [
            $settingOption->getVariable(),
            $settingOption->getValue()
        ]
    );

    if (!array_key_exists($key, $cleanList)) {
        $cleanList[$key] = $settingOption;

        continue;
    }

    $duplicatedOptions[] = $settingOption;
}

// View
echo "<br>Removing the settings_current:<br>" . PHP_EOL;

if (empty($duplicatedCurrent)) {
    echo "No results";
} else {
    foreach ($duplicatedCurrent as $settingCurrent) {
        echo vsprintf(
            "variable = '%s' subkey = '%s' access_url = '%s'<br>" . PHP_EOL,
            [$settingCurrent->getVariable(), $settingCurrent->getSubkey(), $settingCurrent->getAccessUrl()]
        );

        $entityManager->remove($settingCurrent);
    }
}

echo "<br>Removing the settings_options:<br>" . PHP_EOL;

if (empty($duplicatedOptions)) {
    echo "No results";
} else {
    foreach ($duplicatedOptions as $settingOption) {
        echo vsprintf(
            "variable = '%s' value = '%s'<br>" . PHP_EOL,
            [$settingOption->getVariable(), $settingOption->getValue()]
        );

        $entityManager->remove($settingOption);
    }
}

$entityManager->flush();
