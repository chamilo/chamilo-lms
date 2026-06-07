<?php

/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Resubscription';
$strings['plugin_comment'] = 'Limit session resubscriptions.';

$strings['resubscription_limit'] = 'Resubscription limit';
$strings['resubscription_limit_help'] = 'Choose how long a learner must wait before subscribing again to another session that contains a course already followed recently.';
$strings['CalendarYear'] = 'Calendar year';
$strings['NaturalYear'] = 'Natural year';
$strings['CanResubscribeFromX'] = 'Subscription available from %s.';
$strings['UserCanResubscribeFromX'] = '%s: subscription available from %s.';
$strings['PluginUsageHelp'] = 'This plugin listens to the session resubscription event and blocks repeated subscription attempts when the target session contains a course already followed within the configured period.';
