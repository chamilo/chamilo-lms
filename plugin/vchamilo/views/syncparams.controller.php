<?php
/* For licensing terms, see /license.txt */

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

api_protect_admin_script();

$sql = "SELECT * FROM vchamilo";
$result = Database::query($sql);
$vchamilos = Database::store_result($result, 'ASSOC');

// propagate in all known vchamilos a setting
switch ($action) {
    case 'syncall':
        exit;
        $keys = array_keys($_REQUEST);
        $selection = preg_grep('/sel_.*/', $keys);

        foreach ($selection as $selkey) {
            $settingId = str_replace('sel_', '', $selkey);

            if (!is_numeric($settingId)) {
                continue;
            }

            $value = $_REQUEST[$selkey];
            $setting = api_get_settings_params_simple(['id = ?' => $settingId]);

            $params = [
                'title' => $setting['title'],
                'variable' => $setting['variable'],
                'subkey' => $setting['subkey'],
                'category' => $setting['category'],
                'access_url' => $setting['access_url'],
            ];

            foreach ($vchamilos as $chm) {
                $table = $chm['main_database'].".settings_current ";
                $sql = " SELECT * FROM $table
                     WHERE
                        variable = '{{$setting['variable']}}' AND
                        access_url = '{$setting['access_url']}'
                    ";
                $result = Database::query($sql);

                if (Database::num_rows($result)) {
                    Database::update($table, ['selected_Value' => $value, ['id' => $settingId]]);
                }
            }
        }
        break;
    case 'syncthis':
        $settingId = isset($_GET['settingid']) ? (int) $_GET['settingid'] : 0;

        if ($settingId) {
            $deleteIfEmpty = $_REQUEST['del'] ?? '';
            $value = $_REQUEST['value'];
            // Getting the local setting record.
            $setting = api_get_settings_params_simple(['id = ?' => $settingId]);
            if (empty($setting)) {
                return 0;
            }

            $params = [
                'access_url_changeable' => $setting['access_url_changeable'],
                'title' => $setting['title'],
                'variable' => $setting['variable'],
                'subkey' => $setting['subkey'],
                'category' => $setting['category'],
                'type' => $setting['type'],
                'comment' => $setting['comment'],
                'access_url' => $setting['access_url'],
            ];

            $errors = '';
            foreach ($vchamilos as $instance) {
                $table = 'settings_current';
                $config = new Configuration();
                $connectionParams = [
                    'dbname' => $instance['main_database'],
                    'user' => $instance['db_user'],
                    'password' => $instance['db_password'],
                    'host' => $instance['db_host'],
                    'driver' => 'pdo_mysql',
                ];
                try {
                    $connection = DriverManager::getConnection($connectionParams, $config);

                    $variable = $setting['variable'];
                    $subKey = $setting['subkey'];
                    $category = $setting['category'];
                    $accessUrl = $setting['access_url'];

                    if ($deleteIfEmpty && empty($value)) {
                        $connection->delete($table, ['selected_value' => $value, 'variable' => $variable, 'access_url' => $accessUrl]);
                        $case = 'delete';
                    } else {
                        $sql = "SELECT * FROM $table
                                WHERE
                                    variable = '$variable' AND
                                    access_url = '$accessUrl'
                                ";
                        $result = $connection->fetchAllAssociative($sql);

                        if (!empty($result)) {
                            //$sql = "UPDATE $table SET selected_value = '$value' WHERE id = $settingId";
                            $criteria = ['variable' => $variable];
                            if (!empty($subKey)) {
                                $criteria['subkey'] = $subKey;
                            }
                            if (!empty($category)) {
                                $criteria['category'] = $category;
                            }
                            $connection->update($table, ['selected_value' => $value], $criteria);
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
        break;
}

return 0;
