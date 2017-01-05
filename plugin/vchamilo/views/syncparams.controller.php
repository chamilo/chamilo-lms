<?php
/* For licensing terms, see /license.txt */

api_protect_admin_script();

$sql = "SELECT * FROM vchamilo";
$result = Database::query($sql);
$vchamilos = Database::store_result($result, 'ASSOC');

// propagate in all known vchamilos a setting
if ($action == 'syncall') {
    exit;
    $keys = array_keys($_REQUEST);
    $selection = preg_grep('/sel_.*/', $keys);

    foreach ($selection as $selkey) {
        $settingId = str_replace('sel_', '', $selkey);

        if (!is_numeric($settingId)) {
            continue;
        }

        $value = $_REQUEST[$selkey];

        $setting = api_get_settings_params_simple(array('id' => $settingId));

        $params = array(
            'title' => $setting['title'],
            'variable' => $setting['variable'],
            'subkey' => $setting['subkey'],
            'category' => $setting['category'],
            'access_url' => $setting['access_url'],
        );

        foreach ($vchamilos as $vcid => $chm) {
            $table = $chm['main_database'].".settings_current ";
            $sql = " SELECT * FROM $table 
                     WHERE 
                        variable = '{{$setting['variable']}}' AND 
                        access_url = '{$setting['access_url']}'
                    ";
            $result = Database::query($sql);

            if (Database::num_rows($result)) {
                $sql = "UPDATE $table SET 
                            selected_value = '$value'
                      WHERE id = $settingId";
                Database::query($sql);
            }
            //$DB->set_field('settings_current', 'selected_value', $value, $params, 'id', $chm->main_database);
        }
    }
}

if ($action == 'syncthis') {
    $settingId = isset($_GET['settingid']) ? $_GET['settingid'] : '';

    if (is_numeric($settingId)) {
        $delifempty = isset($_REQUEST['del']) ? $_REQUEST['del'] : '';
        $value = $_REQUEST['value'];
        // Getting the local setting record.
        $setting = api_get_settings_params_simple(array('id' => $settingId));
        $params = array(
            'access_url_changeable' => $setting['access_url_changeable'],
            'title' => $setting['title'],
            'variable' => $setting['variable'],
            'subkey' => $setting['subkey'],
            'category' => $setting['category'],
            'type' => $setting['type'],
            'comment' => $setting['comment'],
            'access_url' => $setting['access_url'],
        );

        $errors = '';
        foreach ($vchamilos as $vcid => $chm) {
            $table = "settings_current";

            $config = new \Doctrine\DBAL\Configuration();
            $connectionParams = array(
                'dbname' => $chm['main_database'],
                'user' => $chm['db_user'],
                'password' => $chm['db_password'],
                'host' => $chm['db_host'],
                'driver' => 'pdo_mysql',
            );
            $connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
            try {
                if ($delifempty && empty($value)) {
                    $sql = "DELETE FROM $table 
                            WHERE  
                                selected_value = '$value' AND   
                                variable = '{{$setting['variable']}}' AND 
                                access_url = '{$setting['access_url']}'
                    ";
                    $connection->executeQuery($sql);
                    $case = "delete";
                } else {
                    $sql = " SELECT * FROM $table 
                             WHERE 
                                variable = '".$setting['variable']."' AND 
                                access_url = '{$setting['access_url']}'
                            ";
                    $result = $connection->fetchAll($sql);

                    if (!empty($result)) {
                        $sql = "UPDATE $table SET selected_value = '$value' WHERE id = $settingId";
                        $connection->executeQuery($sql);
                    } else {
                        $connection->insert($table, $params);
                    }
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        return $errors;
    } else {
        return "Bad ID. Non numeric";
    }
}

return 0;