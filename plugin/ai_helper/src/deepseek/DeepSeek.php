<?php
/* For license terms, see /license.txt */

require_once 'DeepSeekUrl.php';

class DeepSeek
{
    private $apiKey;
    private $headers;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->headers = [
            'Content-Type: application/json',
            "Authorization: Bearer {$this->apiKey}",
        ];
    }

    /**
     * Generate questions using the DeepSeek API.
     *
     * @param array $payload Data to send to the API
     *
     * @throws Exception If an error occurs during the request
     *
     * @return string Decoded response from the API
     */
    public function generateQuestions(array $payload): string
    {
        $url = DeepSeekUrl::completionsUrl();
        $response = $this->sendRequest($url, 'POST', $payload);

        if (empty($response)) {
            throw new Exception('The DeepSeek API returned no response.');
        }

        $result = json_decode($response, true);

        // Validate errors returned by the API
        if (isset($result['error'])) {
            throw new Exception("DeepSeek API Error: {$result['error']['message']}");
        }

        // Ensure the response contains the expected "choices" field
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception('Unexpected response format from the DeepSeek API.');
        }

        return $result['choices'][0]['message']['content'];
    }

    /**
     * Send a request to the DeepSeek API.
     *
     * @param string $url    Endpoint to send the request to
     * @param string $method HTTP method (e.g., GET, POST)
     * @param array  $data   Data to send as JSON
     *
     * @throws Exception If a cURL error occurs
     *
     * @return string Raw response from the API
     */
    private function sendRequest(string $url, string $method, array $data = []): string
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $errorMessage = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: {$errorMessage}");
        }

        curl_close($ch);

        // Validate HTTP status codes
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception("Request to DeepSeek failed with HTTP status code: {$httpCode}");
        }

        return $response;
    }
}
