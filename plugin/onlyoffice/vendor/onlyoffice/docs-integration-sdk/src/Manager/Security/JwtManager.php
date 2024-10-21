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
use Onlyoffice\DocsIntegrationSdk\Manager\Settings\SettingsManager;
use Onlyoffice\DocsIntegrationSdk\Manager\Security\JwtManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Default JWT Manager.
 *
 * @package Onlyoffice\DocsIntegrationSdk\Manager\Security
 */

abstract class JwtManager implements JwtManagerInterface
{

    private SettingsManager $settingsManager;

    public function __construct(SettingsManager $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    abstract public function encode($payload, $key, $algorithm = "HS256");
    abstract public function decode($token, $key, $algorithm = "HS256");

    /**
     * Check if a secret key to generate token exists or not.
     *
     * @return bool
     */
    public function isJwtEnabled()
    {
        return !empty($this->settingsManager->getJwtKey());
    }

    /**
     * Encode a payload object into a token using a secret key
     *
     * @param array $payload
     *
     * @return string
     */
    public function jwtEncode($payload, $key = null)
    {
        if (empty($key)) {
            $key = $this->settingsManager->getJwtKey();
        }
        return $this->encode($payload, $key);
    }

    /**
     * Decode a token into a payload object using a secret key
     *
     * @param string $token
     *
     * @return string
     */
    public function jwtDecode($token)
    {
        try {
            $payload = $this->decode($token, $this->settingsManager->getJwtKey());
        } catch (\UnexpectedValueException $e) {
            $payload = "";
        }
        return $payload;
    }

    /**
     * Create an object from the token
     *
     * @param string $token - token
     *
     * @return array
     */
    public function readHash($token, $securityKey)
    {
        $result = null;
        $error = null;
        if ($token === null) {
            return [$result, "Token is empty"];
        }
        try {
            $result = $this->decode($token, $securityKey);
        } catch (\UnexpectedValueException $e) {
            $error = $e->getMessage();
        }
        return [$result, $error];
    }
}
