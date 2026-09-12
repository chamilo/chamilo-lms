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
        try {
            $payload = JWT::decode($token, new Key($key, $algorithm));
        } catch (UnexpectedValueException $e) {
            throw $e;
        } catch (Exception $e) {
            // A malformed token must be rejected like an invalid one instead of
            // ending the request on an uncaught error.
            throw new UnexpectedValueException($e->getMessage(), 0, $e);
        }

        return $payload;
    }

    /**
     * Key used to sign the hashes exchanged between the editor and the callback.
     *
     * It is derived from the platform security key so that the hashes of this
     * plugin cannot be forged from a token issued by another feature, and so
     * that they are not signed with the key itself.
     */
    public static function getSecurityKey(): string
    {
        return hash_hmac('sha256', 'onlyoffice-hash', (string) api_get_security_key());
    }

    public function getHash($object)
    {
        return $this->encode($object, self::getSecurityKey());
    }
}
