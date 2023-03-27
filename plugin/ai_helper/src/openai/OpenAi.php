<?php
/* For licensing terms, see /license.txt */

require_once 'OpenAiUrl.php';

class OpenAi
{
    private $model = "text-davinci-003"; // See https://platform.openai.com/docs/models for possible models
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
    public function completion($opts, $stream = null)
    {
        if ($stream != null && array_key_exists('stream', $opts)) {
            if (!$opts['stream']) {
                throw new Exception('Please provide a stream function.');
            }

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

    private function sendRequest(
        string $url,
        string $method,
        array $opts = []
    ) {
        $post_fields = json_encode($opts);

        if (array_key_exists('file', $opts) || array_key_exists('image', $opts)) {
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

        if ($opts == []) {
            unset($curl_info[CURLOPT_POSTFIELDS]);
        }

        if (array_key_exists('stream', $opts) && $opts['stream']) {
            $curl_info[CURLOPT_WRITEFUNCTION] = $this->streamMethod;
        }

        $curl = curl_init();

        curl_setopt_array($curl, $curl_info);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}
