<?php

$_template['show_message'] = false;

if (api_is_anonymous()) {
    $_template['show_message'] = true;
    // the default title label
    $label = "Connexion hors compte universitaire";
    if (!empty($plugin_info['settings']['formLogin_hide_unhide_label'])) {
        $label = api_htmlentities($plugin_info['settings']['formLogin_hide_unhide_label']);
    }
    $_template['label'] = $label;
}
