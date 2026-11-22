<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Optional CSP for documents rendered through Chamilo's shared application layout.
 * The request owns the policy and nonce, including during the legacy Twig bridge.
 */
final readonly class ContentSecurityPolicy
{
    public const string MODE_DISABLED = 'disabled';
    public const string MODE_REPORT_ONLY = 'report_only';
    public const string MODE_ENFORCE = 'enforce';
    public const string NONCE_PLACEHOLDER = "'nonce-{nonce}'";
    public const int MAX_POLICY_LENGTH = 8192;
    public const string POLICY_PATTERN = '/\A[\x20-\x7E]*\z/D';

    private const string STATE = '_chamilo_content_security_policy';
    private const string ENFORCE_HEADER = 'Content-Security-Policy';
    private const string REPORT_HEADER = 'Content-Security-Policy-Report-Only';

    public function __construct(
        private SettingsManager $settings,
        private LoggerInterface $logger,
    ) {}

    /**
     * Called only by the trusted shared head, before its first script is rendered.
     */
    public function prepareLayout(Request $request): string
    {
        if ($request->attributes->has(self::STATE)) {
            return $this->getNonce($request);
        }

        $request->attributes->set(self::STATE, null);

        try {
            $mode = $this->settings->getSetting('security.content_security_policy_mode', true);
            if (null === $mode || self::MODE_DISABLED === $mode || '' === $mode) {
                return '';
            }
            $setting = match ($mode) {
                self::MODE_REPORT_ONLY => 'security.security_content_policy_report_only',
                self::MODE_ENFORCE => 'security.security_content_policy',
                default => null,
            };
            if (null === $setting) {
                $this->logger->warning('Invalid Content Security Policy mode. No application policy was added.');

                return '';
            }
            $policy = $this->settings->getSetting($setting, true);
        } catch (Throwable) {
            // Do not turn an installation/database failure into another rendering failure.
            $this->logger->warning('Content Security Policy settings could not be read. No application policy was added.');

            return '';
        }

        if (!\is_string($policy) || '' === trim($policy) || !self::isValidHeaderValue($policy)) {
            $this->logger->warning('The selected Content Security Policy is empty or invalid. No application policy was added.');

            return '';
        }

        $nonce = '';
        if (str_contains($policy, self::NONCE_PLACEHOLDER)) {
            $nonce = base64_encode(random_bytes(32));
            $policy = str_replace(self::NONCE_PLACEHOLDER, "'nonce-".$nonce."'", $policy);
            if (!self::isValidHeaderValue($policy)) {
                $this->logger->warning('The expanded Content Security Policy exceeds the header size limit.');

                return '';
            }
        }

        $request->attributes->set(self::STATE, [
            'header' => self::MODE_ENFORCE === $mode ? self::ENFORCE_HEADER : self::REPORT_HEADER,
            'policy' => $policy,
            'nonce' => $nonce,
        ]);

        return $nonce;
    }

    public function getNonce(Request $request): string
    {
        /** @var array{header: string, policy: string, nonce: string}|null $state */
        $state = $request->attributes->get(self::STATE);

        return \is_array($state) ? $state['nonce'] : '';
    }

    /**
     * Do not apply the policy to raw documents, downloads, redirects, API responses,
     * or pages with their own policy. No HTML rewriting or automatic nonce injection.
     */
    public function apply(Request $request, Response $response, bool $hasExistingNativePolicy = false): void
    {
        /** @var array{header: string, policy: string, nonce: string}|null $state */
        $state = $request->attributes->get(self::STATE);
        if (!\is_array($state) || 200 !== $response->getStatusCode() || $response->headers->has('Content-Disposition')) {
            return;
        }

        $contentType = strtolower(trim(explode(';', (string) $response->headers->get('Content-Type'), 2)[0]));
        if (!\in_array($contentType, ['', 'text/html', 'application/xhtml+xml'], true)) {
            return;
        }

        // Even if a specialised policy takes precedence, do not cache a rendered nonce.
        if ('' !== $state['nonce']) {
            $response->headers->set('Cache-Control', 'private, no-store');
            $response->headers->remove('ETag');
            $response->headers->remove('Last-Modified');
        }

        if ($hasExistingNativePolicy || $response->headers->has(self::ENFORCE_HEADER) || $response->headers->has(self::REPORT_HEADER)) {
            return;
        }

        $response->headers->set($state['header'], $state['policy']);
    }

    public static function isValidHeaderValue(string $policy): bool
    {
        // CSP syntax is evaluated by the browser. This validates the HTTP header boundary.
        return \strlen($policy) <= self::MAX_POLICY_LENGTH && 1 === preg_match(self::POLICY_PATTERN, $policy);
    }
}
