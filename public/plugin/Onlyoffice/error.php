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

const ErrorStatus_UpdateOnlyoffice = 1;
const ErrorStatus_NotSupportedVersion = 2;

$plugin = OnlyofficePlugin::create();

$message = '';
$status = $_GET['status'];
switch ($status) {
    case ErrorStatus_UpdateOnlyoffice:
        $message = 'UpdateOnlyoffice';
        break;
    case ErrorStatus_NotSupportedVersion:
        $message = 'NotSupportedVersion';
        break;
}

Display::addFlash(
    Display::return_message(
        $plugin->get_lang($message),
        'error'
    )
);

Display::display_header();
