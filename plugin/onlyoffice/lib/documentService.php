<?php
/**
 *
 * (c) Copyright Ascensio System SIA 2023
 *
 * Licensed under the Apache License, Version 2.0 (the 'License');
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

require_once __DIR__ . '/../../../main/inc/global.inc.php';

use \Firebase\JWT\JWT;

class DocumentService {

    /**
     * Plugin object
     *
     * @var OnlyofficePlugin
     * 
     */
    private $plugin;

    /**
     * New settings for check
     *
     * @var array
     * 
     */
    private $newSettings;

    /**
     * DocumentService constructor
     *
     * @param OnlyofficePlugin $plugin - OnlyofficePlugin
     *
     */
    public function __construct($plugin, $newSettings = null) {
        $this->plugin = $plugin;
        $this->newSettings = $newSettings;
    }


    /**
     * Request to Document Server with turn off verification
     *
     * @param string $url - request address
     * @param array $method - request method
     * @param array $opts - request options
     *
     * @return string
     */
    public function request($url, $method = 'GET', $opts = []) {
        if (substr($url, 0, strlen('https')) === 'https') {
            $opts['verify'] = false;
        }
        if (!array_key_exists('timeout', $opts)) {
            $opts['timeout'] = 60;
        }

        $curl_info = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $opts['timeout'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $opts['body'],
            CURLOPT_HTTPHEADER => $opts['headers'],
        ];

        if ($opts == []) {
            unset($curl_info[CURLOPT_POSTFIELDS]);
        }

        $curl = curl_init();
        curl_setopt_array($curl, $curl_info);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    /**
     * Generate an error code table of convertion
     *
     * @param int $errorCode - Error code
     *
     * @throws Exception
     */
    private function processConvServResponceError($errorCode) {
        $errorMessageTemplate = $this->plugin->get_lang('docServiceError');
        $errorMessage = '';

        switch ($errorCode) {
            case -20:
                $errorMessage = $errorMessageTemplate . ': Error encrypt signature';
                break;
            case -8:
                $errorMessage = $errorMessageTemplate . ': Invalid token';
                break;
            case -7:
                $errorMessage = $errorMessageTemplate . ': Error document request';
                break;
            case -6:
                $errorMessage = $errorMessageTemplate . ': Error while accessing the conversion result database';
                break;
            case -5:
                $errorMessage = $errorMessageTemplate . ': Incorrect password';
                break;
            case -4:
                $errorMessage = $errorMessageTemplate . ': Error while downloading the document file to be converted.';
                break;
            case -3:
                $errorMessage = $errorMessageTemplate . ': Conversion error';
                break;
            case -2:
                $errorMessage = $errorMessageTemplate . ': Timeout conversion error';
                break;
            case -1:
                $errorMessage = $errorMessageTemplate . ': Unknown error';
                break;
            case 0:
                break;
            default:
                $errorMessage = $errorMessageTemplate . ': ErrorCode = ' . $errorCode;
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
    private function processCommandServResponceError($errorCode) {
        $errorMessageTemplate = $this->plugin->get_lang('docServiceError');
        $errorMessage = '';

        switch ($errorCode) {
            case 6:
                $errorMessage = $errorMessageTemplate . ': Invalid token';
                break;
            case 5:
                $errorMessage = $errorMessageTemplate . ': Command not correсt';
                break;
            case 3:
                $errorMessage = $errorMessageTemplate . ': Internal server error';
                break;
            case 0:
                return;
            default:
                $errorMessage = $errorMessageTemplate . ': ErrorCode = ' . $errorCode;
                break;
        }

        throw new \Exception($errorMessage);
    }

    /**
     * Create temporary file for convert service testing
     *
     * @return array
     */
    private function createTempFile() {
        $fileUrl = null;
        $fileName = 'convert.docx';
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $baseName = strtolower(pathinfo($fileName, PATHINFO_FILENAME));
        $templatePath = TemplateManager::getEmptyTemplate($fileExt);
        $folderPath = api_get_path(SYS_PLUGIN_PATH).$this->plugin->getPluginName();
        $filePath = $folderPath . '/' . $fileName;

        if ($fp = @fopen($filePath, 'w')) {
            $content = file_get_contents($templatePath);
            fputs($fp, $content);
            fclose($fp);
            chmod($filePath, api_get_permissions_for_new_files());
            $fileUrl = api_get_path(WEB_PLUGIN_PATH).$this->plugin->getPluginName().'/'.$fileName;
        }

        return [
            'fileUrl' => $fileUrl,
            'filePath' => $filePath
        ];
    }
     

    /**
     * Request for conversion to a service
     *
     * @param string $document_uri - Uri for the document to convert
     * @param string $from_extension - Document extension
     * @param string $to_extension - Extension to which to convert
     * @param string $document_revision_id - Key for caching on service
     * @param bool - $is_async - Perform conversions asynchronously
     * @param string $region - Region
     * 
     * @throws Exception
     *
     * @return array
     */
    public function sendRequestToConvertService($document_uri, $from_extension, $to_extension, $document_revision_id, $is_async, $region = null) {
        if (!empty($this->getValue('document_server_internal'))) {
            $documentServerUrl = $this->getValue('document_server_internal');
        } else {
            $documentServerUrl = $this->getValue('document_server_url');
        }

        if (empty($documentServerUrl)) {
            throw new \Exception($this->plugin->get_lang('pluginIsNotConfigured'));
        }

        $urlToConverter = $documentServerUrl . 'ConvertService.ashx';

        if (empty($document_revision_id)) {
            $document_revision_id = $document_uri;
        }

        $document_revision_id = FileUtility::GenerateRevisionId($document_revision_id);

        if (empty($from_extension)) {
            $from_extension = pathinfo($document_uri)['extension'];
        } else {
            $from_extension = trim($from_extension, '.');
        }

        $data = [
            'async' => $is_async,
            'url' => $document_uri,
            'outputtype' => trim($to_extension, '.'),
            'filetype' => $from_extension,
            'title' => $document_revision_id . '.' . $from_extension,
            'key' => $document_revision_id
        ];

        if (!is_null($region)) {
            $data['region'] = $region;
        }

        $opts = [
            'timeout' => '120',
            'headers' => [
                'Content-type' => 'application/json'
            ],
            'body' => json_encode($data)
        ];

        if (!empty($this->getValue('jwt_secret'))) {
            $params = [
                'payload' => $data
            ];
            $token = JWT::encode($params, $this->getValue('jwt_secret'), 'HS256');
            $opts['headers'][$this->getValue('jwt_header')] = 'Bearer ' . $token;
            $token = JWT::encode($data, $this->getValue('jwt_secret'), 'HS256');
            $data['token'] = $token;
            $opts['body'] = json_encode($data);
        }

        $response_xml_data = $this->request($urlToConverter, 'POST', $opts);
        libxml_use_internal_errors(true);

        if (!function_exists('simplexml_load_file')) {
             throw new \Exception($this->plugin->get_lang('cantReadXml'));
        }

        $response_data = simplexml_load_string($response_xml_data);
        
        if (!$response_data) {
            $exc = $this->plugin->get_lang('badResponseErrors');
            foreach(libxml_get_errors() as $error) {
                $exc = $exc . '\t' . $error->message;
            }
            throw new \Exception ($exc);
        }

        return $response_data;
    }

    /**
     * Request health status
     *
     * @throws Exception
     * 
     * @return bool
     */
    public function healthcheckRequest() {
        if (!empty($this->getValue('document_server_internal'))) {
            $documentServerUrl = $this->getValue('document_server_internal');
        } else {
            $documentServerUrl = $this->getValue('document_server_url');
        }

        if (empty($documentServerUrl)) {
            throw new \Exception($this->plugin->get_lang('appIsNotConfigured'));
        }

        $urlHealthcheck = $documentServerUrl . 'healthcheck';
        $response = $this->request($urlHealthcheck);
        return $response === 'true';
    }

    /**
     * The method is to convert the file to the required format and return the result url
     *
     * @param string $document_uri - Uri for the document to convert
     * @param string $from_extension - Document extension
     * @param string $to_extension - Extension to which to convert
     * @param string $document_revision_id - Key for caching on service
     * @param string $region - Region
     *
     * @return string
     */
    public function getConvertedUri($document_uri, $from_extension, $to_extension, $document_revision_id, $region = null) {
        $responceFromConvertService = $this->sendRequestToConvertService($document_uri, $from_extension, $to_extension, $document_revision_id, false, $region);
        $errorElement = $responceFromConvertService->Error;
        if ($errorElement->count() > 0) {
            $this->processConvServResponceError($errorElement . '');
        }

        $isEndConvert = $responceFromConvertService->EndConvert;

        if ($isEndConvert !== null && strtolower($isEndConvert) === 'true') {
            return $responceFromConvertService->FileUrl;
        }

        return '';
    }

    /**
     * Send command
     *
     * @param string $method - type of command
     *
     * @return array
     */
    public function commandRequest($method) {
        //$documentServerUrl = $this->plugin->getDocumentServerInternalUrl();
        if (!empty($this->getValue('document_server_internal'))) {
            $documentServerUrl = $this->getValue('document_server_internal');
        } else {
            $documentServerUrl = $this->getValue('document_server_url');
        }
        

        if (empty($documentServerUrl)) {
            throw new \Exception($this->plugin->get_lang('cantReadXml'));
        }

        $urlCommand = $documentServerUrl . 'coauthoring/CommandService.ashx';
        $data = [
            'c' => $method
        ];
        $opts = [
            'headers' => [
                'Content-type' => 'application/json'
            ],
            'body' => json_encode($data)
        ];

        if (!empty($this->getValue('jwt_secret'))) {
            $params = [
                'payload' => $data
            ];
            $token = JWT::encode($params, $this->getValue('jwt_secret'), 'HS256');
            $opts['headers'][$this->getValue('jwt_header')] = 'Bearer ' . $token;

            $token = JWT::encode($data, $this->getValue('jwt_secret'), 'HS256');
            $data['token'] = $token;
            $opts['body'] = json_encode($data);
        }

        $response = $this->request($urlCommand, 'POST', $opts);
        $data = json_decode($response);
        $this->processCommandServResponceError($data->error);

        return $data;
    }

    /**
     * Checking document service location
     *
     * @return array
     */
    public function checkDocServiceUrl() {
        $version = null;
        try {
            if (preg_match('/^https:\/\//i', api_get_path(WEB_PATH))
                && preg_match('/^http:\/\//i', $this->getValue('document_server_url'))) {
                throw new \Exception($this->plugin->get_lang('mixedContent'));
            }
        } catch (\Exception $e) {
            return [$e->getMessage(), $version];
        }

        try {
            $healthcheckResponse = $this->healthcheckRequest();

            if (!$healthcheckResponse) {
                throw new \Exception($this->plugin->get_lang('badHealthcheckStatus'));
            }
        } catch (\Exception $e) {
            return [$e->getMessage(), $version];
        }

        try {
            $commandResponse = $this->commandRequest('version');

            if (empty($commandResponse)) {
                throw new \Exception($this->plugin->get_lang('errorOccuredDocService'));
            }

            $version = $commandResponse->version;
            $versionF = floatval($version);

            if ($versionF > 0.0 && $versionF <= 6.0) {
                throw new \Exception($this->plugin->get_lang('notSupportedVersion'));
            }
        } catch (\Exception $e) {
            return [$e->getMessage(), $version];
        }

        $convertedFileUri = null;

        try {
            $emptyFile = $this->createTempFile();

            if ($emptyFile['fileUrl'] !== null) {
                if (!empty($this->getValue('storage_url'))) {
                    $emptyFile['fileUrl'] = str_replace(api_get_path(WEB_PATH), $this->getValue('storage_url'), $emptyFile['fileUrl']);
                   }
                $convertedFileUri = $this->getConvertedUri($emptyFile['fileUrl'], 'docx', 'docx', 'check_' . rand());
            }
            
            unlink($emptyFile['filePath']);
        } catch (\Exception $e) {
            if (isset($emptyFile['filePath'])) {
                unlink($emptyFile['filePath']);
            }
            return [$e->getMessage(), $version];
        }

        try {
            $this->request($convertedFileUri);
        } catch (\Exception $e) {
            return [$e->getMessage(), $version];
        }

        return ['', $version];
    }

    /**
     * Get setting value (from data base or submited form)
     *
     * @return string
     */
    private function getValue($value) {
        $result = null;

        if (!isset($this->newSettings)) {
            switch ($value) {
                case 'document_server_url':
                    $result = $this->plugin->getDocumentServerUrl();
                    break;
                case 'jwt_secret':
                    $result = $this->plugin->getDocumentServerSecret();
                    break;
                case 'jwt_header':
                    $result = $this->plugin->getJwtHeader();
                    break;
                case 'document_server_internal':
                    $result = $this->plugin->getDocumentServerInternalUrl();
                    break;
                case 'storage_url':
                    $result = $this->plugin->getStorageUrl();
                    break;
                default:
            }
        } else {
            $result = isset($this->newSettings[$value]) ? (string)$this->newSettings[$value] : null;
            if ($value !== 'jwt_secret' && $value !== 'jwt_header') {
                if ($result !== null && $result !== "/") {
                    $result = rtrim($result, "/");
                    if (strlen($result) > 0) {
                        $result = $result . "/";
                    }
                }
            } else if ($value === 'jwt_header' && empty($this->newSettings[$value])) {
                $result = 'Authorization';
            }
        }
        return $result;
    }

}