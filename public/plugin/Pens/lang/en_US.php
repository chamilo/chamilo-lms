<?php

/* For licensing terms, see /license.txt. */

$strings['plugin_title'] = 'PENS';
$strings['plugin_comment'] = 'Provides support for the PENS package exchange standard. Preferred endpoint: /plugin/pens/collect.';

$strings['system_user_id'] = 'PENS system user ID';
$strings['system_user_id_help'] = 'The system-user-id that the authoring tool must send. Collect requests are refused until both credentials are set.';
$strings['system_password'] = 'PENS system password';
$strings['system_password_help'] = 'The system-password that the authoring tool must send.';

$strings['PensAdminTitle'] = 'PENS';
$strings['PensAdminIntro'] = 'This page shows the packages received by the PENS endpoint in Chamilo 2.';
$strings['PensBackToPlugins'] = 'Back to plugins';
$strings['PensNoPackages'] = 'No PENS packages have been received yet.';

$strings['PensId'] = 'ID';
$strings['PensPackageId'] = 'Package ID';
$strings['PensClient'] = 'Client';
$strings['PensType'] = 'Type';
$strings['PensFormat'] = 'Format';
$strings['PensStoredFile'] = 'Stored file';
$strings['PensCreated'] = 'Created';
$strings['PensUpdated'] = 'Updated';
$strings['PensVendorData'] = 'Vendor data';

$strings['PensCurrentBehavior'] = 'Current behavior';
$strings['PensBehaviorReceive'] = 'Receives external PENS collect requests through /plugin/pens/collect.';
$strings['PensBehaviorDownload'] = 'Downloads the remote ZIP package.';
$strings['PensBehaviorStore'] = 'Stores the package in the plugins storage.';
$strings['PensBehaviorPersist'] = 'Saves package metadata in plugin_pens.';
$strings['PensBehaviorCallbacks'] = 'Sends PENS receipt and alert callbacks.';
$strings['PensBehaviorNoImport'] = 'Does not import the package into a course automatically yet.';
