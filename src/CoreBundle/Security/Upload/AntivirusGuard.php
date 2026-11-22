<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Upload;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

final class AntivirusGuard
{
    public function __construct(
        private readonly SettingsManager $settings,
        private readonly AntivirusScannerInterface $scanner,
        private readonly AntivirusAuditWriter $audit,
        private readonly AntivirusPathScanCache $pathCache,
        private readonly UserHelper $user,
        private readonly RequestStack $requests,
        private readonly LoggerInterface $logger,
    ) {}

    public function isEnabled(): bool
    {
        return \in_array($this->settings->getSetting('security.antivirus_upload_enabled', true), [true, 'true', 1, '1'], true);
    }

    public function assertPath(string $path, string $filename, string $source): void
    {
        if (!$this->isEnabled()) {
            return;
        }
        $stream = @fopen($path, 'rb');

        try {
            if ($this->pathCache->matches($path, $stream)) {
                return;
            }
            $result = $this->scan($stream, $filename, $source);
            $this->pathCache->remember($path, $result);
        } finally {
            if (\is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function assertContent(string $content, string $filename, string $source): void
    {
        if (!$this->isEnabled()) {
            return;
        }
        $stream = fopen('php://temp', 'w+b');

        try {
            if (false !== $stream && fwrite($stream, $content) === \strlen($content) && rewind($stream)) {
                $this->scan($stream, $filename, $source);
            } else {
                $this->scan(false, $filename, $source);
            }
        } finally {
            if (\is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * @param resource|false $stream
     */
    public function assertStream($stream, string $filename, string $source): void
    {
        if ($this->isEnabled()) {
            $this->scan($stream, $filename, $source);
        }
    }

    /**
     * @param resource|false $stream
     */
    private function scan($stream, string $filename, string $source): array
    {
        $start = microtime(true);
        $result = $this->scanner->scan($stream);
        if (null === $this->settings->getUrl()) {
            $this->settings->load('security', 'antivirus_upload_enabled');
        }
        $portal = $this->settings->getUrl()?->getId();
        $request = $this->requests->getMainRequest();
        $route = $request?->attributes->get('_route');
        if (!\is_string($route) || 'legacy' === $route) {
            $route = $request?->getPathInfo();
        }
        // Snapshot the server-resolved context, never raw cid/sid query parameters.
        $course = $request?->hasSession() ? $request->getSession()->get('course') : null;
        $session = $request?->hasSession() ? $request->getSession()->get('session') : null;
        $filename = basename(str_replace('\\', '/', $filename));
        $filename = preg_replace('/[\x00-\x1f\x7f]/u', '', $filename) ?? 'file';

        try {
            if (null === $portal) {
                throw new RuntimeException('Missing antivirus audit portal.');
            }
            $this->audit->record([
                'access_url_id' => $portal,
                'user_id' => $this->user->getCurrent()?->getId(),
                'course_id' => $course instanceof Course ? $course->getId() : null,
                'session_id' => $session instanceof Session ? $session->getId() : null,
                'filename' => mb_substr($filename, 0, 255),
                'file_size' => $result['size'],
                'sha256' => $result['sha256'],
                'scanner' => 'ClamAV',
                'status' => $result['status'],
                'threat_name' => null === $result['threat'] ? null : mb_substr($result['threat'], 0, 255),
                'source' => mb_substr($source.(\is_string($route) ? ':'.$route : ''), 0, 255),
                'scanned_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ]);
        } catch (Throwable) {
            $this->logger->error('Antivirus audit could not be recorded.');

            throw new HttpException(503, 'File upload rejected: antivirus audit is unavailable.');
        }
        if ('infected' === $result['status']) {
            throw new HttpException(422, 'File upload rejected by antivirus.');
        }
        if ('clean' !== $result['status']) {
            throw new HttpException(503, 'File upload rejected: antivirus scan could not be completed.');
        }

        return $result;
    }
}
