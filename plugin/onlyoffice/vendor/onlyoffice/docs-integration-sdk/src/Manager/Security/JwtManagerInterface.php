<?php

namespace Onlyoffice\DocsIntegrationSdk\Manager\Security;

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
 * Interface JwtManager.
 *
 * @package Onlyoffice\DocsIntegrationSdk\Manager\Security
 */

interface JwtManagerInterface
{
    /**
    * Checks is JWT enabled or not.
    *
    * @throws Exception If the processing fails unexpectedly.
    * @return bool True if JWT is enabled.
    */
    public function isJwtEnabled();

    /**
     * Encode a payload object into a token using a secret key
     *
     * @param array $payload
     * @param string $key
     *
     * @return string
     */
    public function jwtEncode($payload, $key);

    /**
     * Create an object from the token
     *
     * @param string $token
     * @param string $securityKey
     *
     * @return array
     */
    public function readHash($token, $securityKey);
}
