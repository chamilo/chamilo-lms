<?php

namespace Onlyoffice\DocsIntegrationSdk\Manager\Formats;

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
 * Interface Formats.
 *
 * @package Onlyoffice\DocsIntegrationSdk\Manager\Formats
 */

interface FormatsManagerInterface
{
   /**
    * Returns the list of all formats.
    *
    * @return array List of all formats
    */
    public function getFormatsList();

   /**
    * Returns the list of viewable formats.
    *
    * @return array List of viewable formats
    */
    public function getViewableList();

   /**
    * Returns the list of editable formats.
    *
    * @return array List of editable formats
    */
    public function getEditableList();

   /**
    * Returns the list of convertable formats.
    *
    * @return array List of convertable formats
    */
    public function getConvertableList();

   /**
    * Returns the list of fillable formats.
    *
    * @return array List of fillable formats
    */
    public function getFillableList();
}
