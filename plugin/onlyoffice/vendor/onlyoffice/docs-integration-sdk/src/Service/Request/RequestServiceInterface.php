<?php

namespace Onlyoffice\DocsIntegrationSdk\Service\Request;

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
 * Interface DocumentService.
 *
 * @package Onlyoffice\DocsIntegrationSdk\Service\Request
 */

interface RequestServiceInterface
{

   /**
    * Returns url of file for convert
    *
    * @throws Exception If the processing fails unexpectedly.
    * @return string
    */
    public function getFileUrlForConvert();

   /**
    * Returns response body as string
    *
    * @param string $url Url for request.
    * @param string $method Method of request.
    * @param array $opts Options of request (headers, body).
    * @throws Exception If the processing fails unexpectedly.
    * @return string
    */
    public function request(string $url, string $method, array $opts);

   /**
    * Returns error text by code from converting service.
    *
    * @param int $errorCode Code of error (See ConvertResponseError Util).
    * @throws Exception If the processing fails unexpectedly.
    * @return string
    */
    public function processConvServResponceError(int $errorCode);

   /**
    * Returns error text by code from command service.
    *
    * @param int $errorCode Code of error (See CommandResponseError Util).
    * @throws Exception If the processing fails unexpectedly.
    * @return string
    */
    public function processCommandServResponceError(int $errorCode);

   /**
    * Request health status of Document Server.
    *
    * @throws Exception If the processing fails unexpectedly.
    * @return bool
    */
    public function healthcheckRequest();

   /**
      * Request for conversion to a service. Returns response as array.
      *
      * @param string $documentUri - Uri for the document to convert
      * @param string $fromExtension - Document extension
      * @param string $toExtension - Extension to which to convert
      * @param string $documentRevisionId - Key for caching on service
      * @param bool - $isAsync - Perform conversions asynchronously
      * @param string $region - Region value
      * @throws Exception If the processing fails unexpectedly.
      * @return array
   */
    public function sendRequestToConvertService(
        string $documentUri,
        string $fromExtension,
        string $toExtension,
        string $documentRevisionId,
        bool $isAsync,
        string $region
    );

   /**
      * The method is to convert the file to the required format and return the result url.
      *
      * @param string $documentUri - Uri for the document to convert
      * @param string $fromExtension - Document extension
      * @param string $toExtension - Extension to which to convert
      * @param string $documentRevisionId - Key for caching on service
      * @param string $region - Region value
      * @throws Exception If the processing fails unexpectedly.
      * @return string
   */
    public function getConvertedUri(
        string $documentUri,
        string $fromExtension,
        string $toExtension,
        string $documentRevisionId,
        string $region
    );

   /**
    * Request health status of Document Server.
    *
    * @param string $method - type of command
    * @throws Exception If the processing fails unexpectedly.
    * @return array
    */
    public function commandRequest(string $method);

   /**
    * Checking document service location
    *
    * @throws Exception If the processing fails unexpectedly.
    * @return array
    */
    public function checkDocServiceUrl();
}
