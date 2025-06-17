<?php
/* For licensing terms, see /license.txt */

require_once 'OpenAiUrl.php';

class OpenAi
{
    private $model = "gpt-4o"; // See https://platform.openai.com/docs/models for possible models
    private $headers;
    private $contentTypes;
    private $timeout = 0;
    private $streamMethod;

    public function __construct(
        string $apiKey,
        string $organizationId = ''
    ) {
        $this->contentTypes = [
            "application/json" => "Content-Type: application/json",
            "multipart/form-data" => "Content-Type: multipart/form-data",
        ];

        $this->headers = [
            $this->contentTypes["application/json"],
            "Authorization: Bearer $apiKey",
        ];

        if (!empty($organizationId)) {
            $this->headers[] = "OpenAI-Organization: $organizationId";
        }
    }

    /**
     * @return bool|string
     */
    public function listModels()
    {
        $url = OpenAiUrl::fineTuneModel();

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $model
     *
     * @return bool|string
     */
    public function retrieveModel($model)
    {
        $model = "/$model";
        $url = OpenAiUrl::fineTuneModel().$model;

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $opts
     * @param null $stream
     *
     * @return bool|string
     */
    public function completion(array $opts, callable $stream = null)
    {
        if ($stream !== null && isset($opts['stream']) && $opts['stream']) {
            $this->streamMethod = $stream;
        }

        $opts['model'] = $opts['model'] ?? $this->model;
        $url = OpenAiUrl::completionsURL();

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     *
     * @return bool|string
     */
    public function createEdit($opts)
    {
        $url = OpenAiUrl::editsUrl();

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     *
     * @return bool|string
     */
    public function image($opts)
    {
        $url = OpenAiUrl::imageUrl()."/generations";

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     *
     * @return bool|string
     */
    public function imageEdit($opts)
    {
        $url = OpenAiUrl::imageUrl()."/edits";

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     *
     * @return bool|string
     */
    public function createImageVariation($opts)
    {
        $url = OpenAiUrl::imageUrl()."/variations";

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     *
     * @return bool|string
     */
    public function moderation($opts)
    {
        $url = OpenAiUrl::moderationUrl();

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     *
     * @return bool|string
     */
    public function uploadFile($opts)
    {
        $url = OpenAiUrl::filesUrl();

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @return bool|string
     */
    public function listFiles()
    {
        $url = OpenAiUrl::filesUrl();

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $fileId
     *
     * @return bool|string
     */
    public function retrieveFile($fileId)
    {
        $fileId = "/$fileId";
        $url = OpenAiUrl::filesUrl().$fileId;

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $fileId
     *
     * @return bool|string
     */
    public function retrieveFileContent($fileId)
    {
        $fileId = "/$fileId/content";
        $url = OpenAiUrl::filesUrl().$fileId;

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $fileId
     *
     * @return bool|string
     */
    public function deleteFile($fileId)
    {
        $fileId = "/$fileId";
        $url = OpenAiUrl::filesUrl().$fileId;

        return $this->sendRequest($url, 'DELETE');
    }

    /**
     * @param $opts
     *
     * @return bool|string
     */
    public function createFineTune($opts)
    {
        $url = OpenAiUrl::fineTuneUrl();

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @return bool|string
     */
    public function listFineTunes()
    {
        $url = OpenAiUrl::fineTuneUrl();

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $fineTuneId
     *
     * @return bool|string
     */
    public function retrieveFineTune($fineTuneId)
    {
        $fineTuneId = "/$fineTuneId";
        $url = OpenAiUrl::fineTuneUrl().$fineTuneId;

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $fineTuneId
     *
     * @return bool|string
     */
    public function cancelFineTune($fineTuneId)
    {
        $fineTuneId = "/$fineTuneId/cancel";
        $url = OpenAiUrl::fineTuneUrl().$fineTuneId;

        return $this->sendRequest($url, 'POST');
    }

    /**
     * @param $fineTuneId
     *
     * @return bool|string
     */
    public function listFineTuneEvents($fineTuneId)
    {
        $fineTuneId = "/$fineTuneId/events";
        $url = OpenAiUrl::fineTuneUrl().$fineTuneId;

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $fineTuneId
     *
     * @return bool|string
     */
    public function deleteFineTune($fineTuneId)
    {
        $fineTuneId = "/$fineTuneId";
        $url = OpenAiUrl::fineTuneModel().$fineTuneId;

        return $this->sendRequest($url, 'DELETE');
    }

    /**
     * @param $opts
     *
     * @return bool|string
     */
    public function embeddings($opts)
    {
        $url = OpenAiUrl::embeddings();

        return $this->sendRequest($url, 'POST', $opts);
    }

    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    private function sendRequest(string $url, string $method, array $opts = []): string
    {
        $post_fields = json_encode($opts);

        if (isset($opts['file']) || isset($opts['image'])) {
            $this->headers[0] = $this->contentTypes["multipart/form-data"];
            $post_fields = $opts;
        } else {
            $this->headers[0] = $this->contentTypes["application/json"];
        }
        $curl_info = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_HTTPHEADER => $this->headers,
        ];

        if (empty($opts)) {
            unset($curl_info[CURLOPT_POSTFIELDS]);
        }

        if (isset($opts['stream']) && $opts['stream']) {
            $curl_info[CURLOPT_WRITEFUNCTION] = $this->streamMethod;
        }

        $curl = curl_init();

        curl_setopt_array($curl, $curl_info);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $errorMessage = curl_error($curl);
            curl_close($curl);
            throw new Exception("cURL Error: {$errorMessage}");
        }

        curl_close($curl);

        if ($httpCode === 429) {
            throw new Exception("Insufficient quota. Please check your OpenAI account plan and billing details.");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception("HTTP Error: {$httpCode}, Response: {$response}");
        }

        return $response;
    }
}
