<?php

namespace Onlyoffice\DocsIntegrationSdk\Manager\Settings;

/**
 *
 * (c) Copyright Ascensio System SIA 2024
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
 *
 */


/**
 * Interface Settings Manager.
 *
 * @package Onlyoffice\DocsIntegrationSdk\Manager\Settings
 */

interface SettingsManagerInterface
{

   /**
    * Get the setting value by setting name.
    *
    * @param string $settingName Name of setting.
    * @throws Exception If the processing fails unexpectedly.
    */
    public function getSetting($settingName);

   /**
    * Set the setting value.
    *
    * @param string $settingName Name of setting.
    * @param $value Value of setting.
    * @param bool $createSetting If True, then create a new setting with the value.
    * @throws Exception If the processing fails unexpectedly.
    */
    public function setSetting($settingName, $value, $createSetting);

   /**
   * Get status of demo server
   *
   * @return bool
    */
    public function useDemo();

   /**
   * Get the data for demo connection
   *
   * @return array
    */
    public function getDemoData();

   /**
   * Switch on demo server
   *
   * @param bool $value - select demo
   *
   * @return bool
   */
    public function selectDemo($value);

   /**
   * Get the document service address from the application configuration
   *
   * @return string
   */
    public function getDocumentServerUrl();

   /**
   * Get the document server API URL from the application configuration
   *
   * @return string
   */
    public function getDocumentServerApiUrl();

   /**
   * Get the preloader URL from the application configuration
   *
   * @return string
   */
    public function getDocumentServerPreloaderUrl();

   /**
   * Get the healthcheck URL from the application configuration
   *
   * @return string
   */
    public function getDocumentServerHealthcheckUrl();

   /**
   * Get the convert service URL from the application configuration
   *
   * @return string
   */
    public function getConvertServiceUrl();

   /**
   * Get the command service URL from the application configuration
   *
   * @return string
   */
    public function getCommandServiceUrl();

   /**
   * Get the JWT Header
   *
   * @return string
   */
    public function getJwtHeader();

   /**
   * Get the JWT Key
   *
   * @return string
   */
    public function getJwtKey();

   /**
   * Get the JWT prefix
   *
   * @return string
   */
    public function getJwtPrefix();

   /**
   * Get the JWT Leeway
   *
   * @return string
   */
    public function getJwtLeeway();

   /**
   * Checks whether the setting to ignore SSL certificate is enabled.
   *
   * @return bool True if the setting to ignore SSL certificate is enabled.
   */
    public function isIgnoreSSL();
}
