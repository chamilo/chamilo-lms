<?php
/* For license terms, see /license.txt */

$plugin_info = ZoomPlugin::create()->get_info();

$plugin_info['commercial_model'] = 'freemium';
$plugin_info['commercial_model_reason'] = 'Zoom API/service account is required; features may depend on paid/freemium plans.';
