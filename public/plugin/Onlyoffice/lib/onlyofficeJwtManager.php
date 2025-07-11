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
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Onlyoffice\DocsIntegrationSdk\Manager\Security\JwtManager;

class OnlyofficeJwtManager extends JwtManager
{
    public function __construct($settingsManager)
    {
        parent::__construct($settingsManager);
    }

    public function encode($payload, $key, $algorithm = 'HS256')
    {
        return JWT::encode($payload, $key, $algorithm);
    }

    public function decode($token, $key, $algorithm = 'HS256')
    {
        $payload = JWT::decode($token, new Key($key, $algorithm));

        return $payload;
    }

    public function getHash($object)
    {
        return $this->encode($object, api_get_security_key());
    }
}
