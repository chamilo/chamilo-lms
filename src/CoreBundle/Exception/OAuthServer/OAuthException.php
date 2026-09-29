<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Exception\OAuthServer;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * An OAuth 2.1 protocol error (RFC 6749 §5.2, RFC 7591 §3.2.2), carrying the
 * RFC error code and the HTTP status a controller should respond with.
 *
 * Deliberately generic across every /oauth/* endpoint's error responses so
 * they all share one JSON shape: {"error": "...", "error_description": "..."}.
 *
 * Constructed only via the named static factories below, never as a service —
 * #[Exclude] keeps the Chamilo\CoreBundle\: services.yml glob from tripping
 * over the private constructor.
 */
#[Exclude]
final class OAuthException extends RuntimeException
{
    /**
     * @param array<string, string> $extraHeaders
     */
    private function __construct(
        private readonly string $errorCode,
        string $errorDescription,
        private readonly int $httpStatusCode,
        private readonly array $extraHeaders = [],
    ) {
        parent::__construct($errorDescription);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * @return array<string, string>
     */
    public function getExtraHeaders(): array
    {
        return $this->extraHeaders;
    }

    public static function invalidClientMetadata(string $description): self
    {
        return new self('invalid_client_metadata', $description, 400);
    }

    public static function invalidRedirectUri(string $description): self
    {
        return new self('invalid_redirect_uri', $description, 400);
    }

    public static function invalidRequest(string $description): self
    {
        return new self('invalid_request', $description, 400);
    }

    public static function invalidGrant(string $description = 'The provided authorization grant is invalid.'): self
    {
        return new self('invalid_grant', $description, 400);
    }

    public static function invalidTarget(string $description = 'The requested resource is invalid.'): self
    {
        return new self('invalid_target', $description, 400);
    }

    /**
     * @param array<string, string> $extraHeaders
     */
    public static function invalidClient(
        string $description = 'Client authentication failed.',
        array $extraHeaders = [],
    ): self {
        return new self('invalid_client', $description, 401, $extraHeaders);
    }

    public static function unsupportedGrantType(): self
    {
        return new self('unsupported_grant_type', 'The requested grant type is not supported.', 400);
    }

    public static function unsupportedResponseType(): self
    {
        return new self('unsupported_response_type', 'Only the "code" response type is supported.', 400);
    }

    public static function accessDenied(): self
    {
        return new self('access_denied', 'The resource owner denied the request.', 400);
    }
}
