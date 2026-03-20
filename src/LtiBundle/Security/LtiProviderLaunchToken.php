<?php
/* For license terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\LtiBundle\Security;

final class LtiProviderLaunchToken
{
    public function __construct(
        private readonly string $secret
    ) {}

    /**
     * Create a compact signed token for temporary LTI launch context recovery.
     */
    public function createToken(array $payload): string
    {
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if (false === $json) {
            throw new \RuntimeException('Unable to encode LTI provider launch token payload.');
        }

        $payloadPart = $this->base64UrlEncode($json);
        $signaturePart = $this->base64UrlEncode(
            hash_hmac('sha256', $payloadPart, $this->secret, true)
        );

        return $payloadPart.'.'.$signaturePart;
    }

    /**
     * Parse and validate a compact signed token.
     *
     * @return array<string, mixed>|null
     */
    public function parseToken(string $token): ?array
    {
        $token = trim($token);

        if ('' === $token || !str_contains($token, '.')) {
            return null;
        }

        [$payloadPart, $signaturePart] = explode('.', $token, 2);

        if ('' === $payloadPart || '' === $signaturePart) {
            return null;
        }

        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', $payloadPart, $this->secret, true)
        );

        if (!hash_equals($expectedSignature, $signaturePart)) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($payloadPart);

        if (false === $payloadJson) {
            return null;
        }

        $payload = json_decode($payloadJson, true);

        if (JSON_ERROR_NONE !== json_last_error() || !\is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && time() > (int) $payload['exp']) {
            return null;
        }

        return $payload;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string|false
    {
        $padding = \strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}
