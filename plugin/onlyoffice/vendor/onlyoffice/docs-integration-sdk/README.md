# ONLYOFFICE Docs Integration PHP SDK

ONLYOFFICE Docs Integration PHP SDK provides common interfaces and default implementations for integrating ONLYOFFICE Document Server into your own website or application on PHP.

## Prerequisites
* **PHP**: version 7.4.0 and higher

### Managers

| Manager                       | Description                                                             | Default implementation           |
| ----------------------------- | ----------------------------------------------------------------------- | -------------------------------- |
| DocumentManagerInterface | This manager is used for working with files, and string data associated with documents. | DocumentManager (abstract) |
| FormatsManagerInterface | This manager is used for working with document formats. | FormatsManager |
| JwtManagerInterface | This manager is used for generating and verifying authorization tokens. | JwtManager (abstract) |
| SettingsManagerInterface | This manager is used to manage integration application settings. | SettingsManager (abstract) |

### Services

| Service                       | Description                                                             | Default implementation           |
| ----------------------------- | ----------------------------------------------------------------------- | -------------------------------- |
| CallbackServiceInterface | This service is used for processing the response of the Document Server. | CallbackService (abstract) |
| DocEditorConfigServiceInterface | This configuration generation service is used for opening the document editor. | DocEditorConfigService |
| RequestServiceInterface | This service is used to make requests to the ONLYOFFICE Document Server. | RequestService (abstract) |

## Usage
1. Implement the methods of the abstract **DocumentManager** class:
    ```php
    public function getDocumentKey(string $fileId, bool $embedded = false)
    {
        return self::generateRevisionId($fileId);
    }

    public function getDocumentName(string $fileId)
    {
        return "sample.docx";
    }

    public static function getLangMapping()
    {
        return null;
    }

    public static function getFileUrl(string $fileId)
    {
        return "https://example-server.example/fileId/download/";
    }

    public static function getCallbackUrl(string $fileId)
    {
        return "https://example-server.example/callback";
    }

    public static function getGobackUrl(string $fileId)
    {
        return "https://example-server.example/filelist";
    }

    public static function getCreateUrl(string $fileId)
    {
        return "https://example-server.example/fileId";
    }
    ```
2. Implement the methods of the abstract **JwtManager** class (use third-party libraries for JWT encoding and decoding, whichever is convenient for you):
    ```php
    public function encode($token, $key, $algorithm = "HS256")
    {
        return "SOME.JWT.STRING";
    }

    public function decode($token, $key, $algorithm = "HS256")
    {
        return json_encode([]);
    }
    ```
3. Implement the methods of the abstract **SettingsManager** class:
    ```php
    public function getServerUrl()
    {
        return "https://example-server.example/";
    }

    public function getSetting($settingName)
    {
        return null;
    }

    public function setSetting($settingName, $value, $createSetting = false)
    {
        // if ($createSetting === true) {
            // $this->yourMethodForCreateNewSetting($settingName, $value);
            // return;
        // }
        // $this->yourMethodForSetNewValueForSetting($settingName, $value);
    }
    ```
4. Implement the methods of the abstract **SettingsManager** class:
    ```php
    public function processTrackerStatusEditing()
    {
        // $someTrackResult["error"] = 0;
        // return json_encode($someTrackResult);
    }

    public function processTrackerStatusMustsave()
    {
        // $someTrackResult["error"] = 0;
        // return json_encode($someTrackResult);
    }

    public function processTrackerStatusCorrupted()
    {
        // $someTrackResult["error"] = 0;
        // return json_encode($someTrackResult);
    }

    public function processTrackerStatusClosed()
    {
        // $someTrackResult["error"] = 0;
        // return json_encode($someTrackResult);
    }

    public function processTrackerStatusForcesave()
    {
        // $someTrackResult["error"] = 0;
        // return json_encode($someTrackResult);
    }
    ```
5. Create a class that implements the **HttpClientInterface** interface (use PHP Client URL Library or any other third-party library to make requests):
    ```php
    class YourHttpClient implements HttpClientInterface
    {
        public function __construct()
        {
            $this->responseStatusCode = null;
            $this->responseBody = null;
        }

        public function getStatusCode()
        {
            return $this->responseStatusCode;
        }

        public function getBody()
        {
            return $this->responseBody;
        }

        public function request($url, $method = 'GET', $opts = [])
        {
            $this->responseStatusCode = 200;
            $this->responseBody = "{\"status\": \"OK\"}";
        }
    }
    ```
6. Implement the method of the abstract **RequestService** class:
    ```php
    public function getFileUrlForConvert()
    {
        return "https://example-server.example/file-url-for-check-convert";
    }
    ```
7. Use DocEditorConfigService to create a config model for the editors in your own controllers.