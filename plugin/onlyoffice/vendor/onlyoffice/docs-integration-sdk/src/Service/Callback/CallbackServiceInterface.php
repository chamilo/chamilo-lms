<?php

namespace Onlyoffice\DocsIntegrationSdk\Service\Callback;

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
use Onlyoffice\DocsIntegrationSdk\Models\Callback;

interface CallbackServiceInterface
{
   /**
    * Verifies the Callback object.
    *
    * @param Callback $callback Object with the callback handler parameters.
    * @param string $authorizationHeader The authorization header from the callback request.
    * @throws Exception If authorization token is not found.
    * @return Callback
    */
    public function verifyCallback(Callback $callback, string $authorizationHeader);

   /**
    * Starts the callback handler.
    *
    * @param Callback $callback Object with the callback handler parameters.
    * @param string $fileId The file ID.
    * @throws Exception If the processing fails unexpectedly.
    */
    public function processCallback(Callback $callback, string $fileId);

   /**
    * Starts the handler that is called if the callback status is 1 (EDITING).
    *
    * @param Callback $callback Object with the callback handler parameters.
    * @param string $fileId The file ID.
    * @throws Exception If the processing fails unexpectedly.
    */
    public function processTrackerStatusEditing(Callback $callback, string $fileId);

   /**
    * Starts the handler that is called if the callback status is 2 (SAVE).
    *
    * @param Callback $callback Object with the callback handler parameters.
    * @param string $fileId The file ID.
    * @throws Exception If the processing fails unexpectedly.
    */
    public function processTrackerStatusMustsave(Callback $callback, string $fileId);

   /**
    * Starts the handler that is called if the callback status is 3 (SAVE_CORRUPTED).
    *
    * @param Callback $callback Object with the callback handler parameters.
    * @param string $fileId The file ID.
    * @throws Exception If the processing fails unexpectedly.
    */
    public function processTrackerStatusCorrupted(Callback $callback, string $fileId);

   /**
    * Starts the handler that is called if the callback status is 4 (CLOSED).
    *
    * @param Callback $callback Object with the callback handler parameters.
    * @param string $fileId The file ID.
    * @throws Exception If the processing fails unexpectedly.
    */
    public function processTrackerStatusClosed(Callback $callback, string $fileId);

   /**
    * Starts the handler that is called if the callback status is 6 (FORCESAVE).
    *
    * @param Callback $callback Object with the callback handler parameters.
    * @param string $fileId The file ID.
    * @throws Exception If the processing fails unexpectedly.
    */
    public function processTrackerStatusForcesave(Callback $callback, string $fileId);

   /**
    * Starts the handler that is called if the callback status is 7 (FORCESAVE_CORRUPTED).
    *
    * @param Callback $callback Object with the callback handler parameters.
    * @param string $fileId The file ID.
    * @throws Exception If the processing fails unexpectedly.
    */
    public function processTrackerStatusCorruptedForcesave(Callback $callback, string $fileId);
}
