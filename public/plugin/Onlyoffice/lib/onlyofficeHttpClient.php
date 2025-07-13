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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Onlyoffice\DocsIntegrationSdk\Service\Request\HttpClientInterface;

class OnlyofficeHttpClient implements HttpClientInterface
{
    private $responseStatusCode;
    private $responseBody;

    public function __construct()
    {
        $this->responseStatusCode = null;
        $this->responseBody = null;
    }

    /**
     * Request to Document Server with turn off verification.
     *
     * @param string $url    - request address
     * @param array  $method - request method
     * @param array  $opts   - request options
     */
    public function request($url, $method = 'GET', $opts = [])
    {
        $httpClient = new Client(['base_uri' => $url]);
        try {
            $response = $httpClient->request($method, $url, $opts);
            $this->responseBody = $response->getBody()->getContents();
            $this->responseStatusCode = $response->getStatusCode();
        } catch (RequestException $requestException) {
            throw new Exception($requestException->getMessage());
        }
    }

    /**
     * Get the value of responseStatusCode.
     */
    public function getStatusCode()
    {
        return $this->responseStatusCode;
    }

    /**
     * Get the value of responseBody.
     */
    public function getBody()
    {
        return $this->responseBody;
    }
}
