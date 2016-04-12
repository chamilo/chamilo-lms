<?php

// propagate in all known vchamilos a setting
if ($action == 'syncall') {
    $vchamilos = $DB->get_records('vchamilo', array());

    $keys = array_keys($_REQUEST);
    $selection = preg_grep('/sel_.*/', $keys);

    foreach($selection as $selkey) {
        $settingid = str_replace('sel_', '', $selkey);

        if (!is_numeric($settingid)) continue;

        $value = $_REQUEST[$selkey];
        $setting = $DB->get_record('settings_current', array('id' => $settingid));
        $params = array('variable' => $setting->variable, 'subkey' => $setting->subkey, 'category' => $setting->category, 'access_url' => $setting->access_url);
        foreach($vchamilos as $vcid => $chm) {
            $DB->set_field('settings_current', 'selected_value', $value, $params, 'id', $chm->main_database);
        }
    }
}

if ($action == 'syncthis') {
    $settingid = $_GET['settingid'];
    $vchamilos = $DB->get_records('vchamilo', array());

    if (is_numeric($settingid)) {
        $delifempty = @$_REQUEST['del'];
        $value = $_REQUEST['value'];
        // Getting the local setting record.
        $setting = $DB->get_record('settings_current', array('id' => $settingid));
        $params = array('variable' => $setting->variable, 'subkey' => $setting->subkey, 'category' => $setting->category, 'access_url' => $setting->access_url);
        $errors = '';
        foreach ($vchamilos as $vcid => $chm) {
            if ($delifempty && empty($value)) {
                $res = $DB->delete_records('settings_current', $params, $chm->main_database);
                $case = "delete";
            } else {
                if ($remotesetting = $DB->get_record('settings_current', array('variable' => $setting->variable, 'subkey' => $setting->subkey), '*', $chm->main_database)) {
                    $value = str_replace("'", "''", $value); // Mysql protection
                    $res = $DB->set_field('settings_current', 'selected_value', $value, $params, 'id', $chm->main_database);
                    $case = "update";
                } else {
                    $remotesetting = $setting;
                    $remotesetting->selected_value = str_replace("'", "''", $value);
                    unset($remotesetting->id);
                    $res = $DB->insert_record('settings_current', $remotesetting, $chm->main_database);
                    $case = "insert";
                }
            }
            if (!$res) {
                $errors .= "Set Field Error in $chm->sitename for case $case<br/>\n";
            }
        }
        return $errors;
    } else {
        return "Bad ID. Non numeric";
    }
}

return 0;