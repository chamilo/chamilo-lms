<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use const PHP_URL_PATH;
use const PHP_URL_SCHEME;

final readonly class UpdatePackageDownloader
{
    private TranslatorInterface $translator;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
        private UpdateConfiguration $updateConfiguration,
        ?TranslatorInterface $translator = null,
    ) {
        $this->translator = $translator ?? new IdentityTranslator();
    }

    public function download(string $url, ?string $targetDirectory = null): string
    {
        $this->assertAllowedDownloadUrl($url);

        $targetDirectory ??= $this->projectDir.'/var/update/downloads';
        $this->ensureDirectory($targetDirectory);

        $targetPath = $this->getTargetPath($url, $targetDirectory);

        $response = $this->httpClient->request('GET', $url, [
            'max_redirects' => 0,
        ]);
        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException(\sprintf($this->translator->trans('Unable to download update package. HTTP status: %d'), $statusCode));
        }

        $handle = fopen($targetPath, 'wb');
        if (false === $handle) {
            throw new RuntimeException(\sprintf($this->translator->trans('Unable to open update package target file: %s'), $targetPath));
        }

        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($handle, $chunk->getContent());
        }

        fclose($handle);

        return $targetPath;
    }

    public function findExistingDownload(string $url): ?string
    {
        $this->assertAllowedDownloadUrl($url);

        $targetPath = $this->getTargetPath($url, $this->projectDir.'/var/update/downloads');

        if (!is_file($targetPath) || !is_readable($targetPath)) {
            return null;
        }

        return $targetPath;
    }

    private function assertAllowedDownloadUrl(string $url): void
    {
        $this->assertHttpsUrl($url);

        if (
            !$this->updateConfiguration->allowsDevelopmentUpdateTools()
            && !$this->updateConfiguration->isAllowedOfficialUpdateUrl($url)
        ) {
            throw new InvalidArgumentException(\sprintf($this->translator->trans('Update downloads must use the official update origin %s.'), $this->updateConfiguration->getOfficialManifestOrigin()));
        }
    }

    private function assertHttpsUrl(string $url): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ('https' !== $scheme) {
            throw new InvalidArgumentException($this->translator->trans('The update package URL must use HTTPS.'));
        }
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            if (!is_writable($directory)) {
                throw new RuntimeException(\sprintf($this->translator->trans('Directory is not writable: %s'), $directory));
            }

            return;
        }

        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(\sprintf($this->translator->trans('Unable to create directory: %s'), $directory));
        }
    }

    private function getTargetPath(string $url, string $targetDirectory): string
    {
        return rtrim($targetDirectory, '/').'/'.$this->getSafeFileNameFromUrl($url);
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
