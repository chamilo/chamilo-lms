<?php
/**
 * (c) Copyright Ascensio System SIA 2025.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/lib/onlyofficeSettingsFormBuilder.php';
require_once __DIR__.'/lib/onlyofficeAppSettings.php';

/**
 * @author Asensio System SIA
 */
$plugin = OnlyofficePlugin::create();
$appSettings = new OnlyofficeAppsettings($plugin);
$plugin_info = $plugin->get_info();
$plugin_info['settings_form'] = OnlyofficeSettingsFormBuilder::buildSettingsForm($appSettings);
if ($plugin_info['settings_form']->validate()) {
    $plugin = OnlyofficeSettingsFormBuilder::validateSettingsForm($appSettings);
}
