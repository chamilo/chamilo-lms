<?php

namespace Onlyoffice\DocsIntegrationSdk\Manager\Settings;

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
use Onlyoffice\DocsIntegrationSdk\Manager\Settings\SettingsManagerInterface;
use Onlyoffice\DocsIntegrationSdk\Util\EnvUtil;
use Dotenv\Dotenv;

/**
 * Default Settings Manager.
 *
 * @package Onlyoffice\DocsIntegrationSdk\Manager\Settings
 */

abstract class SettingsManager implements SettingsManagerInterface
{

    abstract public function getServerUrl();

    abstract public function getSetting($settingName);

    abstract public function setSetting($settingName, $value, $createSetting = false);

    /**
     * The settings key for the demo server
     *
     * @var string
     */
    protected $useDemoName = "demo";

    /**
     * The settings key for the document server address
     *
     * @var string
     */
    protected $documentServerUrl = "documentServerUrl";

    /**
     * The config key for the document server address available from storage
     *
     * @var string
     */
    protected $documentServerInternalUrl = "documentServerInternalUrl";

    /**
     * The config key for the storage url
     *
     * @var string
     */
    protected $storageUrl = "storageUrl";

    /**
     * The config key for JWT header
     *
     * @var string
     */
    protected $jwtHeader = "jwtHeader";

    /**
     * The config key for JWT secret key
     *
     * @var string
     */
    protected $jwtKey = "jwtKey";

    /**
     * The config key for JWT prefix
     *
     * @var string
     */
    protected $jwtPrefix = "jwtPrefix";

    /**
     * The config key for JWT leeway
     *
     * @var string
     */
    protected $jwtLeeway = "jwtLeeway";

    /**
     * The config key for HTTP ignore SSL setting
     *
     * @var string
     */
    protected $httpIgnoreSSL = "ignoreSSL";

    /** The demo url. */
    protected const DEMO_URL = "https://onlinedocs.docs.onlyoffice.com/";
    /** The demo security header. */
    protected const DEMO_JWT_HEADER = "AuthorizationJWT";
    /** The demo security key. */
    protected const DEMO_JWT_KEY = "sn2puSUF7muF5Jas";
    /** The demo security prefix. */
    protected const DEMO_JWT_PREFIX = "Bearer ";
    /** The number of days that the demo server can be used. */
    protected const DEMO_TRIAL_PERIOD = 30;

    protected const ENV_SETTINGS_PREFIX = "DOCS_INTEGRATION_SDK";

    public function __construct()
    {
        EnvUtil::loadEnvSettings();
    }

    /**
     * Get status of demo server
     *
     * @return bool
     */
    public function useDemo()
    {
        return $this->getDemoData()["enabled"] === true;
    }

    /**
     * Get demo data
     *
     * @return array
     */
    public function getDemoData()
    {
        $data = $this->getSetting($this->useDemoName);

        if (empty($data)) {
            $data = [
                "available" => true,
                "enabled" => false
            ];
            $this->setSetting($this->useDemoName, json_encode($data), true);
            return $data;
        }
        $data = json_decode($data, true);

        if (isset($data['start'])) {
            $overdue = $data["start"];
            $overdue += 24 * 60 * 60 *$this->getDemoParams()["TRIAL"];
            if ($overdue > time()) {
                $data["available"] = true;
                $data["enabled"] = $data["enabled"] === true;
            } else {
                $data["available"] = false;
                $data["enabled"] = false;
                $this->setSetting($this->useDemoName, json_encode($data));
            }
        }
        return $data;
    }

    /**
     * Switch on demo server
     *
     * @param bool $value - select demo
     *
     * @return bool
     */
    public function selectDemo($value)
    {
        $data = $this->getDemoData();

        if ($value === true && !$data["available"]) {
            return false;
        }

        $data["enabled"] = $value === true;

        if (!isset($data["start"])) {
            $data["start"] = time();
        }
        $this->setSetting($this->useDemoName, json_encode($data));
        return true;
    }

    private function getBaseSettingValue(string $settingKey, string $envKey, string $demoKey = "")
    {
        if ($this->useDemo() && !empty($demoKey)) {
            return $demoKey;
        }

        $settingValue = $this->getSetting($settingKey);
        if (empty($settingValue) && !empty($_ENV[$envKey])) {
            $settingValue = $_ENV[$envKey];
        }

        return $settingValue;
    }

    /**
     * Get the document service address from the application configuration
     *
     * @return string
     */
    public function getDocumentServerUrl()
    {
        $url = $this->getBaseSettingValue(
            $this->documentServerUrl,
            EnvUtil::envKey("DOCUMENT_SERVER_URL"),
            self::DEMO_URL
        );
        $url = !empty($url) ? $this->normalizeUrl($url) : "";
        return (string)$url;
    }

    public function getDocumentServerInternalUrl()
    {
        if ($this->useDemo()) {
            return $this->getDocumentServerUrl();
        }

        $url = $this->getSetting($this->documentServerInternalUrl);
        if (empty($url)) {
            return $this->getDocumentServerUrl();
        }

        return (string)$url;
    }

    public function getStorageUrl()
    {
        $url = $this->getSetting($this->storageUrl);
        return !empty($url) ? $url : "";
    }

    /**
     * Replace domain in document server url with internal address from configuration
     *
     * @param string $url - document server url
     *
     * @return string
     */
    public function replaceDocumentServerUrlToInternal($url)
    {
        $documentServerUrl = $this->getDocumentServerInternalUrl();
        if (!empty($documentServerUrl)) {
            $from = $this->getDocumentServerUrl();

            if (!preg_match("/^https?:\/\//i", $from)) {
                $parsedUrl = parse_url($url);
                $from = $parsedUrl["scheme"].
                "://".
                $parsedUrl["host"].
                (array_key_exists("port", $parsedUrl) ? (":" . $parsedUrl["port"]) : "") . $from;
            }

            $url = $from !== $documentServerUrl ?? str_replace($from, $documentServerUrl, $url);
        }
        return $url;
    }

    private function getDocumentServerCustomUrl($settingKey, $useInternalUrl = false)
    {
        if (!$useInternalUrl) {
            $serverUrl = $this->getDocumentServerUrl();
        } else {
            $serverUrl = $this->getDocumentServerInternalUrl();
        }
        $customUrl = "";

        if (!empty($serverUrl) && !empty($_ENV[EnvUtil::envKey($settingKey)])) {
            $customUrl = $_ENV[EnvUtil::envKey($settingKey)];
            $customUrl = $this->normalizeUrl($serverUrl .= $customUrl);
        }

        return (string)$customUrl;
    }

    /**
     * Get the document server API URL
     *
     * @return string
     */
    public function getDocumentServerApiUrl($useInternalUrl = false)
    {
        return $this->getDocumentServerCustomUrl("DOCUMENT_SERVER_API_URL", $useInternalUrl) ?:
        $this->normalizeUrl($this->getDocumentServerUrl()."/web-apps/apps/api/documents/api.js");
    }

    /**
     * Get the document server preloader url
     *
     * @return string
     */
    public function getDocumentServerPreloaderUrl($useInternalUrl = false)
    {
        return $this->getDocumentServerCustomUrl("DOCUMENT_SERVER_API_PRELOADER_URL", $useInternalUrl) ?:
        $this->normalizeUrl($this->getDocumentServerUrl()."/web-apps/apps/api/documents/cache-scripts.html");
    }

    /**
     * Get the document server healthcheck url
     *
     * @return string
     */
    public function getDocumentServerHealthcheckUrl($useInternalUrl = false)
    {
        return $this->getDocumentServerCustomUrl("DOCUMENT_SERVER_HEALTHCHECK_URL", $useInternalUrl) ?:
        $this->normalizeUrl($this->getDocumentServerUrl()."/healthcheck");
    }

    /**
     * Get the convert service url
     *
     * @return string
     */
    public function getConvertServiceUrl($useInternalUrl = false)
    {
        return $this->getDocumentServerCustomUrl("CONVERT_SERVICE_URL", $useInternalUrl) ?:
        $this->normalizeUrl($this->getDocumentServerUrl()."/ConvertService.ashx");
    }

    /**
     * Get the command service url
     *
     * @return string
     */
    public function getCommandServiceUrl($useInternalUrl = false)
    {
        return $this->getDocumentServerCustomUrl("COMMAND_SERVICE_URL", $useInternalUrl) ?:
        $this->normalizeUrl($this->getDocumentServerUrl()."/coauthoring/CommandService.ashx");
    }

    /**
     * Get the JWT Header
     *
     * @return string
     */
    public function getJwtHeader()
    {
        $jwtHeader = $this->getBaseSettingValue($this->jwtHeader, EnvUtil::envKey("JWT_HEADER"), self::DEMO_JWT_HEADER);
        return (string)$jwtHeader;
    }

    /**
     * Get the JWT secret
     *
     * @return string
     */
    public function getJwtKey()
    {
        $jwtKey = $this->getBaseSettingValue($this->jwtKey, EnvUtil::envKey("JWT_KEY"), self::DEMO_JWT_KEY);
        return (string)$jwtKey;
    }

    /**
     * Get the JWT prefix
     *
     * @return string
     */
    public function getJwtPrefix()
    {
        $jwtPrefix = $this->getBaseSettingValue($this->jwtPrefix, EnvUtil::envKey("JWT_PREFIX"), self::DEMO_JWT_PREFIX);
        return (string)$jwtPrefix;
    }

    /**
     * Get the JWT leeway
     *
     * @return string
     */
    public function getJwtLeeway()
    {
        $jwtLeeway = $this->getBaseSettingValue($this->jwtLeeway, EnvUtil::envKey("JWT_LEEWAY"));
        return (string)$jwtLeeway;
    }

    /**
     * Get the ignore SSL value
     *
     * @return bool
     */
    public function isIgnoreSSL()
    {
        if (!$this->useDemo()) {
            return boolval($this->getBaseSettingValue($this->httpIgnoreSSL, EnvUtil::envKey("HTTP_IGNORE_SSL")))
            === true;
        }

        return false;
    }

    /**
     * Get demo params
     *
     * @return array
     */
    public function getDemoParams()
    {
        return [
            "ADDR" => self::DEMO_URL,
            "HEADER" => self::DEMO_JWT_HEADER,
            "SECRET" => self::DEMO_JWT_KEY,
            "PREFIX" => self::DEMO_JWT_PREFIX,
            "TRIAL" => self::DEMO_TRIAL_PERIOD
        ];
    }

    /**
     * Add backslash to url if it's needed
     *
     * @return string
     */
    public function processUrl($url)
    {
        if ($url !== null && $url !== "/") {
            $url = rtrim($url, "/");
            if (strlen($url) > 0) {
                $url = $url . "/";
            }
        }
        return $url;
    }

    public function normalizeUrl($url)
    {
        $url = preg_replace('/([^:])(\/{2,})/', '$1/', $url);
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return $url;
    }
}
