<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

require_once __DIR__.'/teachdoc_hub.php';

$plugin_info = teachdoc_hub::create()->get_info();

// The plugin title.
$plugin_info['title'] = 'C-Studio Open eLearning Tools';
$plugin_info['comment'] = 'OeL tools (z p_f)';
$plugin_info['author'] = 'Bâtisseurs Numériques / Damien Renou';

// set the templates that are going to be used
$plugin_info['templates'] = ['template.tpl'];
