<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service;

use JsonException;

use const JSON_THROW_ON_ERROR;

/**
 * Conservative data filter for downloadable diagnostic configuration.
 * Do not use this to decide whether a password is safe or valid.
 */
final class ConfigurationDumpSanitizer
{
    private const string REDACTED = '[REDACTED]';

    /**
     * Configuration switches can mention security terms without storing credentials.
     * Only boolean values in these explicitly reviewed settings can be exported.
     */
    private const array PUBLIC_BOOLEAN_SETTINGS = [
        'allow_download_documents_by_api_key',
        'allow_email_editor',
        'allow_email_editor_for_anonymous',
        'allow_lostpassword',
        'allow_users_to_change_email_with_no_password',
        'check_password',
        'email_alert_manager_on_new_quiz',
        'enable_email_open_tracking',
        'force_renew_password_at_first_login',
        'login_is_email',
        'messages_hide_mail_content',
        'send_email_to_admin_when_create_course',
        'show_email_addresses',
        'show_user_email_in_notification',
        'update_users_email_to_dummy_except_admins',
        'user_reset_password',
    ];

    /**
     * Numeric policy limits are non-secret only when their values are plain integers.
     */
    private const array PUBLIC_INTEGER_SETTINGS = [
        'hosting_limit_identical_email',
        'password_rotation_days',
        'user_reset_password_token_limit',
    ];

    /**
     * @return array{value: mixed, redacted: bool}
     */
    public function sanitize(string $key, mixed $value, bool $confidential = false): array
    {
        if ($confidential) {
            return ['value' => self::REDACTED, 'redacted' => true];
        }

        // Reviewed diagnostic switches must match their expected scalar type,
        // even when their name does not match the generic sensitive-key pattern.
        if (self::isReviewedDiagnosticKey($key)) {
            if (!self::isPublicDiagnosticScalar($key, $value)) {
                return ['value' => self::REDACTED, 'redacted' => true];
            }
        } elseif (self::isSensitiveKey($key)) {
            return ['value' => self::REDACTED, 'redacted' => true];
        }

        [$sanitized, $redacted] = $this->sanitizeValue($value);

        return ['value' => $sanitized, 'redacted' => $redacted];
    }

    public static function isSensitiveKey(string $name): bool
    {
        $name = preg_replace('/([a-z\d])([A-Z])/', '$1_$2', $name);
        $name = strtolower((string) preg_replace('/[^a-z\d]+/i', '_', $name));
        $name = trim($name, '_');

        if (preg_match('/(?:^|_)(?:password|passwd|passphrase|secret|token|credential|authorization|authkey|private|privatekey|api_key|apikey|client_secret|clientsecret|smtp|access_key|accesskey|dkim|dsn|webhook|signing|bearer|oauth|jwt|sasl|password_hash|mailgun|sendgrid|stripe|paypal)(?:_|$)/', $name)) {
            return true;
        }

        if (preg_match('/(?:^|_)(?:email|e_mail|mail_address|email_address|recipient_address|sender_address|contact_mail|administrator_mail|admin_mail)(?:_|$)/', $name)) {
            return true;
        }

        return false;
    }

    private static function isReviewedDiagnosticKey(string $key): bool
    {
        return \in_array($key, self::PUBLIC_BOOLEAN_SETTINGS, true)
            || \in_array($key, self::PUBLIC_INTEGER_SETTINGS, true);
    }

    /**
     * A reviewed setting is only safe to expose if its value matches its
     * expected Boolean or integer type. Unexpected values remain redacted.
     */
    private static function isPublicDiagnosticScalar(string $key, mixed $value): bool
    {
        if (\in_array($key, self::PUBLIC_BOOLEAN_SETTINGS, true)) {
            return \is_bool($value)
                || (\is_string($value) && \in_array(strtolower(trim($value)), ['true', 'false', '0', '1'], true))
                || (\is_int($value) && \in_array($value, [0, 1], true));
        }

        if (\in_array($key, self::PUBLIC_INTEGER_SETTINGS, true)) {
            return (\is_int($value) && $value >= 0)
                || (\is_string($value) && 1 === preg_match('/^[0-9]{1,10}$/', trim($value)));
        }

        return false;
    }

    /**
     * @return array{0: mixed, 1: bool}
     */
    private function sanitizeValue(mixed $value): array
    {
        if (null === $value || \is_bool($value) || \is_int($value) || \is_float($value)) {
            return [$value, false];
        }

        if (\is_array($value)) {
            $result = [];
            $redacted = false;

            foreach ($value as $key => $item) {
                // Keys can themselves contain an address or credential.
                if (!\is_int($key) && self::hasSensitiveText($key)) {
                    $result['[REDACTED KEY]'] = self::REDACTED;
                    $redacted = true;

                    continue;
                }

                $filtered = $this->sanitize((string) $key, $item);
                $result[$key] = $filtered['value'];
                $redacted = $redacted || $filtered['redacted'];
            }

            return [$result, $redacted];
        }

        if (!\is_string($value)) {
            return [self::REDACTED, true];
        }

        if ('' !== $value && ('{' === $value[0] || '[' === $value[0])) {
            try {
                $decoded = json_decode($value, true, 64, JSON_THROW_ON_ERROR);
                if (\is_array($decoded)) {
                    return $this->sanitizeValue($decoded);
                }
            } catch (JsonException) {
                // A malformed JSON value still goes through the text filter.
            }
        }

        if (self::hasSensitiveText($value)) {
            return [self::REDACTED, true];
        }

        return [$value, false];
    }

    private static function hasSensitiveText(string $text): bool
    {
        if (preg_match('/[\w.+\-]+@[\w.\-]+\.[a-z]{2,}/i', $text)) {
            return true;
        }

        if (preg_match('/-----BEGIN [A-Z ]*(?:PRIVATE KEY|CERTIFICATE)-----/i', $text)) {
            return true;
        }

        if (preg_match('~(?:https?|smtps?|amqps?|rediss?)://[^\s/@:]+:[^\s/@]+@~i', $text)) {
            return true;
        }

        if (preg_match('/(?:[?&;]|^)(?:token|secret|password|api[_-]?key|access[_-]?key|signature|sig|auth)=\S+/i', $text)) {
            return true;
        }

        if (preg_match('/(?:password|passwd|secret|api[_-]?key|bearer|authorization)\s*[:=]\s*[^\s,;]+/i', $text)) {
            return true;
        }

        if (preg_match('/\b(?:sk|ghp|gho|ghu|ghs|ghr|xoxb|xoxp|xoxa|xoxr)[-_][a-z\d_\-]{12,}/i', $text)) {
            return true;
        }

        if (preg_match('/\bBearer\s+[A-Za-z0-9._~+\/-]{8,}/i', $text)
            || preg_match('/\bAIza[0-9A-Za-z_\-]{30,}/', $text)
            || preg_match('/\beyJ[A-Za-z0-9_\-]+\.eyJ[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+/', $text)
        ) {
            return true;
        }

        // Opaque long tokens are not useful for diagnostics and can hide credentials.
        if (preg_match('/^[A-Za-z0-9+\/=\-_]{64,}$/', $text)) {
            return true;
        }

        return false;
    }
}
