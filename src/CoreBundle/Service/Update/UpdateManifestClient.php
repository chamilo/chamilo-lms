<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateManifest;
use InvalidArgumentException;
use JsonException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class UpdateManifestClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {}

    public function load(string $source): UpdateManifest
    {
        $rawManifest = $this->readSource($source);

        try {
            $data = json_decode($rawManifest, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Update manifest is not valid JSON: '.$exception->getMessage(), 0, $exception);
        }

        if (!\is_array($data)) {
            throw new InvalidArgumentException('Update manifest JSON must be an object.');
        }

        return UpdateManifest::fromArray($data);
    }

    private function readSource(string $source): string
    {
        $source = trim($source);

        if ('' === $source) {
            throw new InvalidArgumentException('Manifest source cannot be empty.');
        }

        if ($this->isHttpUrl($source)) {
            $this->assertHttpsUrl($source, 'manifest');

            $response = $this->httpClient->request('GET', $source);
            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode >= 300) {
                throw new InvalidArgumentException('Unable to download update manifest. HTTP status: '.(string) $statusCode);
            }

            return $response->getContent();
        }

        if (!is_file($source) || !is_readable($source)) {
            throw new InvalidArgumentException('Manifest file is not readable: '.$source);
        }

        $content = file_get_contents($source);

        if (false === $content) {
            throw new InvalidArgumentException('Unable to read manifest file: '.$source);
        }

        return $content;
    }

    private function isHttpUrl(string $source): bool
    {
        return 1 === preg_match('/^https?:\/\//i', $source);
    }

    private function assertHttpsUrl(string $url, string $label): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ('https' !== $scheme) {
            throw new InvalidArgumentException('The update '.$label.' URL must use HTTPS.');
        }
    }
}
