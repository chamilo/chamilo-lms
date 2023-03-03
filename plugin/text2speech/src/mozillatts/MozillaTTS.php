<?php

require_once __DIR__.'/../IProvider.php';

class MozillaTTS implements IProvider
{
    private $url;
    private $apiKey;
    private $filePath;

    public function __construct(string $url, string $apiKey, string $filePath)
    {
        $this->url = $url;
        $this->apiKey = $apiKey;
        $this->filePath = $filePath;
    }

    public function convert(string $text): string
    {
        return $this->request($text);
    }

    private function request(string $data): string
    {
        $filename = uniqid().'.wav';
        $filePath = $this->filePath.$filename;
//        $resource = fopen(realpath($filePath), 'w');

        $client = new GuzzleHttp\Client();
        $client->get($this->url.'?api_key='.urlencode($this->apiKey).
            '&text='.str_replace('%0A', '+', urlencode($data)), [
            'headers' => [
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'audio/wav',
            ],
            'sink' => $filePath,
        ]);

        return $filename;
    }
}
