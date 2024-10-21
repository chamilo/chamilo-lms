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
use Onlyoffice\DocsIntegrationSdk\Manager\Document\DocumentManager;
use Onlyoffice\DocsIntegrationSdk\Manager\Settings\SettingsManager;
use Onlyoffice\DocsIntegrationSdk\Manager\Security\JwtManager;
use Onlyoffice\DocsIntegrationSdk\Models\ConvertRequest;
use Onlyoffice\DocsIntegrationSdk\Service\Request\RequestServiceInterface;
use Onlyoffice\DocsIntegrationSdk\Service\Request\HttpClientInterface;
use Onlyoffice\DocsIntegrationSdk\Util\CommandResponseError;
use Onlyoffice\DocsIntegrationSdk\Util\CommonError;
use Onlyoffice\DocsIntegrationSdk\Util\ConvertResponseError;

/**
 * Default Document service.
 *
 * @package Onlyoffice\DocsIntegrationSdk\Service\Request
 */

abstract class RequestService implements RequestServiceInterface
{

    /**
     * Minimum supported version of editors
     *
     * @var float
     */
    private const MIN_EDITORS_VERSION = 6.0;

    protected SettingsManager $settingsManager;
    protected JwtManager $jwtManager;

    abstract public function getFileUrlForConvert();

    public function __construct(
        SettingsManager $settingsManager,
        HttpClientInterface $httpClient,
        JwtManager $jwtManager
    ) {
        $this->settingsManager = $settingsManager;
        $this->jwtManager = $jwtManager;
        $this->httpClient = $httpClient;
    }

    /**
    * Request to Document Server
    *
    * @param string $url - request address
    * @param array $method - request method
    * @param array $opts - request options
    *
    * @return string
    */
    public function request($url, $method = "GET", $opts = [])
    {
        if ($this->settingsManager->isIgnoreSSL()) {
            $opts["verify"] = false;
        }

        if (!array_key_exists("timeout", $opts)) {
            $opts["timeout"] = 60;
        }

        $this->httpClient->request($url, $method, $opts);
        if ($this->httpClient->getStatusCode() === 200) {
            return $this->httpClient->getBody();
        }

        return "";
    }

    /**
     * Generate an error code table of convertion
     *
     * @param int $errorCode - Error code
     *
     * @throws Exception
     */
    public function processConvServResponceError($errorCode)
    {
        $errorMessage = '';

        switch ($errorCode) {
            case ConvertResponseError::UNKNOWN:
                $errorMessage = ConvertResponseError::message(ConvertResponseError::UNKNOWN);
                break;
            case ConvertResponseError::TIMEOUT:
                $errorMessage = ConvertResponseError::message(ConvertResponseError::TIMEOUT);
                break;
            case ConvertResponseError::CONVERSION:
                $errorMessage = ConvertResponseError::message(ConvertResponseError::CONVERSION);
                break;
            case ConvertResponseError::DOWNLOADING:
                $errorMessage = ConvertResponseError::message(ConvertResponseError::DOWNLOADING);
                break;
            case ConvertResponseError::PASSWORD:
                $errorMessage = ConvertResponseError::message(ConvertResponseError::PASSWORD);
                break;
            case ConvertResponseError::DATABASE:
                $errorMessage = ConvertResponseError::message(ConvertResponseError::DATABASE);
                break;
            case ConvertResponseError::INPUT:
                $errorMessage = ConvertResponseError::message(ConvertResponseError::INPUT);
                break;
            case ConvertResponseError::TOKEN:
                $errorMessage = ConvertResponseError::message(ConvertResponseError::TOKEN);
                break;
            default:
                $errorMessage = "ErrorCode = " . $errorCode;
                break;
        }

        throw new \Exception($errorMessage);
    }

    /**
     * Generate an error code table of command
     *
     * @param string $errorCode - Error code
     *
     * @throws Exception
     */
    public function processCommandServResponceError($errorCode)
    {
        $errorMessage = "";

        switch ($errorCode) {
            case CommandResponseError::NO:
                return;
            case CommandResponseError::KEY:
                $errorMessage = CommandResponseError::message(CommandResponseError::KEY);
                break;
            case CommandResponseError::CALLBACK_URL:
                $errorMessage = CommandResponseError::message(CommandResponseError::CALLBACK_URL);
                break;
            case CommandResponseError::INTERNAL_SERVER:
                $errorMessage = CommandResponseError::message(CommandResponseError::INTERNAL_SERVER);
                break;
            case CommandResponseError::FORCE_SAVE:
                $errorMessage = CommandResponseError::message(CommandResponseError::FORCE_SAVE);
                break;
            case CommandResponseError::COMMAND:
                $errorMessage = CommandResponseError::message(CommandResponseError::COMMAND);
                break;
            case CommandResponseError::TOKEN:
                $errorMessage = CommandResponseError::message(CommandResponseError::TOKEN);
                break;
            default:
                $errorMessage = "ErrorCode = " . $errorCode;
                break;
        }

        throw new \Exception($errorMessage);
    }

    /**
     * Request health status
     *
     * @throws Exception
     *
     * @return bool
     */
    public function healthcheckRequest() : bool
    {
        $healthcheckUrl = $this->settingsManager->getDocumentServerHealthcheckUrl();
        if (empty($healthcheckUrl)) {
            throw new \Exception(CommonError::message(CommonError::NO_HEALTHCHECK_ENDPOINT));
        }

        $response = $this->request($healthcheckUrl);
        return $response === "true";
    }

    /**
     * Request for conversion to a service
     *
     * @param string $documentUri - Uri for the document to convert
     * @param string $fromExtension - Document extension
     * @param string $toExtension - Extension to which to convert
     * @param string $documentRevisionId - Key for caching on service
     * @param bool - $isAsync - Perform conversions asynchronously
     * @param string $region - Region
     *
     * @throws Exception
     *
     * @return array
     */
    public function sendRequestToConvertService(
        $documentUri,
        $fromExtension,
        $toExtension,
        $documentRevisionId,
        $isAsync,
        $region = null
    ) {
        $urlToConverter = $this->settingsManager->getConvertServiceUrl(true);
        if (empty($urlToConverter)) {
            throw new \Exception(CommonError::message(CommonError::NO_CONVERT_SERVICE_ENDPOINT));
        }

        if (empty($documentRevisionId)) {
            $documentRevisionId = $documentUri;
        }
        $documentRevisionId = DocumentManager::generateRevisionId($documentRevisionId);

        if (empty($fromExtension)) {
            $fromExtension = pathinfo($documentUri)["extension"];
        } else {
            $fromExtension = trim($fromExtension, ".");
        }

        $data = new ConvertRequest;
        $data->setAsync($isAsync);
        $data->setUrl($documentUri);
        $data->setOutputtype(trim($toExtension, "."));
        $data->setFiletype($fromExtension);
        $data->setTitle($documentRevisionId . "." . $fromExtension);
        $data->setKey($documentRevisionId);

        if (!is_null($region)) {
            $data->setRegion($region);
        }

        $opts = [
            "timeout" => "120",
            "headers" => [
                'Content-type' => 'application/json'
            ],
            "body" => json_encode($data)
        ];

        if ($this->jwtManager->isJwtEnabled()) {
            $params = [
                "payload" => json_decode(json_encode($data), true)
            ];
            $token = $this->jwtManager->jwtEncode($params);
            $jwtHeader = $this->settingsManager->getJwtHeader();
            $jwtPrefix = $this->settingsManager->getJwtPrefix();

            if (empty($jwtHeader)) {
                throw new \Exception(CommonError::message(CommonError::NO_JWT_HEADER));
            } elseif (empty($jwtPrefix)) {
                throw new \Exception(CommonError::message(CommonError::NO_JWT_PREFIX));
            }

            $opts["headers"][$jwtHeader] = (string)$jwtPrefix . $token;
            $token = $this->jwtManager->jwtEncode(json_decode(json_encode($data), true));
            $data->setToken($token);
            $opts["body"] = json_encode($data);
        }


        $responseXmlData = $this->request($urlToConverter, "POST", $opts);
        libxml_use_internal_errors(true);

        if (!function_exists("simplexml_load_file")) {
             throw new \Exception(CommonError::message(CommonError::READ_XML));
        }

        $responseData = simplexml_load_string($responseXmlData);
        
        if (!$responseData) {
            $exc = CommonError::message(CommonError::BAD_RESPONSE_XML);
            foreach (libxml_get_errors() as $error) {
                $exc = $exc . PHP_EOL . $error->message;
            }
            throw new \Exception($exc);
        }

        return $responseData;
    }

    /**
     * The method is to convert the file to the required format and return the result url
     *
     * @param string $documentUri - Uri for the document to convert
     * @param string $fromExtension - Document extension
     * @param string $toExtension - Extension to which to convert
     * @param string $documentRevisionId - Key for caching on service
     * @param string $region - Region
     *
     * @return string
     */
    public function getConvertedUri($documentUri, $fromExtension, $toExtension, $documentRevisionId, $region = null)
    {
        $responseFromConvertService = $this->sendRequestToConvertService(
            $documentUri,
            $fromExtension,
            $toExtension,
            $documentRevisionId,
            false,
            $region
        );
        // phpcs:ignore
        $errorElement = $responseFromConvertService->Error;
        if ($errorElement->count() > 0) {
            $this->processConvServResponceError($errorElement);
        }

        // phpcs:ignore
        $isEndConvert = $responseFromConvertService->EndConvert;

        if ($isEndConvert !== null && strtolower($isEndConvert) === "true") {
            // phpcs:ignore
            return is_string($responseFromConvertService->FileUrl) ? $responseFromConvertService->FileUrl : $responseFromConvertService->FileUrl->__toString();
        }

        return "";
    }

    /**
     * Send command
     *
     * @param string $method - type of command
     *
     * @return array
     */
    public function commandRequest($method)
    {
        $urlCommand = $this->settingsManager->getCommandServiceUrl(true);
        if (empty($urlCommand)) {
            throw new \Exception(CommonError::message(CommonError::NO_COMMAND_ENDPOINT));
        }

        $data = [
            "c" => $method
        ];
        $opts = [
            "headers" => [
                "Content-type" => "application/json"
            ],
            "body" => json_encode($data)
        ];

        if ($this->jwtManager->isJwtEnabled()) {
            $params = [
                "payload" => $data
            ];
            $token = $this->jwtManager->jwtEncode($params);
            $jwtHeader = $this->settingsManager->getJwtHeader();
            $jwtPrefix = $this->settingsManager->getJwtPrefix();

            if (empty($jwtHeader)) {
                throw new \Exception(CommonError::message(CommonError::NO_JWT_HEADER));
            } elseif (empty($jwtPrefix)) {
                throw new \Exception(CommonError::message(CommonError::NO_JWT_PREFIX));
            }

            $opts["headers"][$jwtHeader] = $jwtPrefix . $token;
            $token = $this->jwtManager->jwtEncode($data);
            $data["token"] = $token;
            $opts["body"] = json_encode($data);
        }

        $response = $this->request($urlCommand, "post", $opts);

        $data = json_decode($response);
        $this->processCommandServResponceError($data->error);

        return $data;
    }

    /**
     * Checking document service location
     *
     * @return array
     */
    public function checkDocServiceUrl()
    {
        $version = null;
        $documentServerUrl = $this->settingsManager->getDocumentServerUrl();
        if (empty($documentServerUrl)) {
            throw new \Exception(CommonError::message(CommonError::NO_DOCUMENT_SERVER_URL));
        }

        try {
            if ((isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on" || $_SERVER["HTTPS"] == 1)
            || isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] == "https")
            && preg_match('/^http:\/\//i', $documentServerUrl)) {
                throw new \Exception(CommonError::message(CommonError::MIXED_CONTENT));
            }
        } catch (\Exception $e) {
            return [$e->getMessage(), $version];
        }

        try {
            $healthcheckResponse = $this->healthcheckRequest();

            if (!$healthcheckResponse) {
                throw new \Exception(CommonError::message(CommonError::BAD_HEALTHCHECK_STATUS));
            }
        } catch (\Exception $e) {
            return [$e->getMessage(), $version];
        }

        try {
            $commandResponse = $this->commandRequest('version');

            if (empty($commandResponse)) {
                throw new \Exception(CommonError::message(CommonError::BAD_HEALTHCHECK_STATUS));
            }

            $version = $commandResponse->version;
            $versionF = floatval($version);

            if ($versionF > 0.0 && $versionF <= self::MIN_EDITORS_VERSION) {
                throw new \Exception(CommonError::message(CommonError::NOT_SUPPORTED_VERSION));
            }
        } catch (\Exception $e) {
            return [$e->getMessage(), $version];
        }

        try {
            $fileUrl = $this->getFileUrlForConvert();

            if (!empty($fileUrl)) {
                if (!empty($this->settingsManager->getStorageUrl())) {
                    $fileUrl = str_replace(
                        $this->settingsManager->getServerUrl(),
                        $this->settingsManager->getStorageUrl(),
                        $fileUrl
                    );
                }
                $convertedFileUri = $this->getConvertedUri($fileUrl, "docx", "docx", "check_" . rand());
            }
        } catch (\Exception $e) {
            return [$e->getMessage(), $version];
        }

        try {
            $this->request($convertedFileUri);
        } catch (\Exception $e) {
            return [$e->getMessage(), $version];
        }

        return ["", $version];
    }
}
