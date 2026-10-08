<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

$plugin = TeamsPlugin::create();
if (!$plugin->isEnabledForCurrentAccessUrl()) {
    api_not_allowed(true);
}

$scope = null;
if ($plugin->isPersonalConferenceEnabled()) {
    $scope = 'personal';
} elseif ($plugin->isGlobalConferenceEnabled()) {
    $scope = 'global';
}

if (null === $scope) {
    api_not_allowed(true);
}

api_location(api_get_path(WEB_PATH).'conference/teams?scope='.$scope);
