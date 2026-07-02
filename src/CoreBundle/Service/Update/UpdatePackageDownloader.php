<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class UpdatePackageDownloader
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
    ) {}

    public function download(string $url, ?string $targetDirectory = null): string
    {
        $this->assertHttpsUrl($url, 'package');

        $targetDirectory ??= $this->projectDir.'/var/update/downloads';
        $this->ensureDirectory($targetDirectory);

        $fileName = $this->getSafeFileNameFromUrl($url);
        $targetPath = $targetDirectory.'/'.$fileName;

        $response = $this->httpClient->request('GET', $url);
        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('Unable to download update package. HTTP status: '.(string) $statusCode);
        }

        $handle = fopen($targetPath, 'wb');
        if (false === $handle) {
            throw new RuntimeException('Unable to open update package target file: '.$targetPath);
        }

        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($handle, $chunk->getContent());
        }

        fclose($handle);

        return $targetPath;
    }

    private function assertHttpsUrl(string $url, string $label): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ('https' !== $scheme) {
            throw new InvalidArgumentException('The update '.$label.' URL must use HTTPS.');
        }
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            if (!is_writable($directory)) {
                throw new RuntimeException('Directory is not writable: '.$directory);
            }

            return;
        }

        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create directory: '.$directory);
        }
    }

    private function getSafeFileNameFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $fileName = \is_string($path) ? basename($path) : '';

        if ('' === $fileName || '.' === $fileName || '..' === $fileName) {
            $fileName = 'chamilo-update-package';
        }

        return preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName) ?: 'chamilo-update-package';
    }
}
