<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/Mobidico.php';

$plugin_info = Mobidico::create()->get_info();

$plugin_info['commercial_model'] = 'commercial_service';
$plugin_info['commercial_model_reason'] = 'External Mobidico service/API key is required for the plugin main flow.';
