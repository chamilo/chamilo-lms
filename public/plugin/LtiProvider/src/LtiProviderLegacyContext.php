<?php
/* For license terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession as Session;

final class LtiProviderLegacyContext
{
    private const DEBUG = true;

    public static function restoreFromRequest(): bool
    {
        $rawToken = trim((string) ($_REQUEST['lti_provider_token'] ?? ''));

        if ('' === $rawToken) {
            return false;
        }

        $payload = self::parseToken($rawToken);
        if (empty($payload)) {
            self::debug('Invalid or expired LTI provider token.');

            return false;
        }

        $userId = (int) ($payload['user_id'] ?? 0);
        $courseId = (int) ($payload['course_id'] ?? 0);
        $courseCode = trim((string) ($payload['course_code'] ?? ''));
        $toolName = trim((string) ($payload['tool_name'] ?? ''));
        $toolId = trim((string) ($payload['tool_id'] ?? ''));
        $launchId = trim((string) ($payload['launch_id'] ?? ''));

        if ($userId <= 0 || $courseId <= 0 || '' === $courseCode) {
            self::debug('Incomplete LTI provider token payload.', $payload);

            return false;
        }

        $requestLaunchId = trim((string) ($_REQUEST['lti_launch_id'] ?? ''));
        if ('' !== $launchId && '' !== $requestLaunchId && !hash_equals($launchId, $requestLaunchId)) {
            self::debug('Launch ID mismatch while restoring legacy context.', [
                'token_launch_id' => $launchId,
                'request_launch_id' => $requestLaunchId,
            ]);

            return false;
        }

        $userInfo = api_get_user_info($userId);
        if (empty($userInfo) || empty($userInfo['user_id'])) {
            self::debug('User not found while restoring legacy context.', [
                'user_id' => $userId,
            ]);

            return false;
        }

        $courseInfo = api_get_course_info($courseCode);
        if (empty($courseInfo) || empty($courseInfo['real_id'])) {
            self::debug('Course not found while restoring legacy context.', [
                'course_code' => $courseCode,
            ]);

            return false;
        }

        if ((int) $courseInfo['real_id'] !== $courseId) {
            self::debug('Course ID mismatch while restoring legacy context.', [
                'token_course_id' => $courseId,
                'resolved_course_id' => (int) $courseInfo['real_id'],
                'course_code' => $courseCode,
            ]);

            return false;
        }

        if (!CourseManager::is_user_subscribed_in_course($userId, $courseCode)) {
            self::debug('User is not subscribed in target course.', [
                'user_id' => $userId,
                'course_code' => $courseCode,
            ]);

            return false;
        }

        self::ensureSessionStarted();
        self::forceRequestContext($courseId);
        self::writeLegacyContext($userInfo, $courseInfo, $toolName, $toolId, $launchId, $rawToken);

        return true;
    }

    private static function writeLegacyContext(
        array $userInfo,
        array $courseInfo,
        string $toolName,
        string $toolId,
        string $launchId,
        string $rawToken
    ): void {
        self::writeSessionValue('_uid', (int) $userInfo['user_id']);
        self::writeSessionValue('_user', $userInfo);
        self::writeSessionValue('_cid', (string) $courseInfo['code']);
        self::writeSessionValue('_real_cid', (int) $courseInfo['real_id']);
        self::writeSessionValue('_course', $courseInfo);
        self::writeSessionValue('is_allowed_in_course', true);

        self::writeSessionValue('_ltiProvider', [
            'user_id' => (int) $userInfo['user_id'],
            'course_code' => (string) $courseInfo['code'],
            'tool_name' => $toolName,
            'tool_id' => $toolId,
            'lti_launch_id' => $launchId,
            'lti_provider_token' => $rawToken,
        ]);
    }

    private static function forceRequestContext(int $courseId): void
    {
        $_GET['cid'] = (string) $courseId;
        $_REQUEST['cid'] = (string) $courseId;

        if (!isset($_GET['sid'])) {
            $_GET['sid'] = '0';
        }

        if (!isset($_REQUEST['sid'])) {
            $_REQUEST['sid'] = '0';
        }
    }

    private static function writeSessionValue(string $key, $value): void
    {
        $_SESSION[$key] = $value;
        Session::write($key, $value);
    }

    private static function ensureSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    private static function parseToken(string $token): ?array
    {
        $token = trim($token);

        if ('' === $token || !str_contains($token, '.')) {
            return null;
        }

        [$payloadPart, $signaturePart] = explode('.', $token, 2);

        if ('' === $payloadPart || '' === $signaturePart) {
            return null;
        }

        $secret = self::getSecret();
        if ('' === $secret) {
            self::debug('APP secret is empty while parsing LTI provider token.');

            return null;
        }

        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', $payloadPart, $secret, true)
        );

        if (!hash_equals($expectedSignature, $signaturePart)) {
            return null;
        }

        $payloadJson = self::base64UrlDecode($payloadPart);
        if (false === $payloadJson) {
            return null;
        }

        $payload = json_decode($payloadJson, true);

        if (JSON_ERROR_NONE !== json_last_error() || !is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && time() > (int) $payload['exp']) {
            return null;
        }

        return $payload;
    }

    private static function getSecret(): string
    {
        if (!empty($_ENV['APP_SECRET'])) {
            return (string) $_ENV['APP_SECRET'];
        }

        if (!empty($_SERVER['APP_SECRET'])) {
            return (string) $_SERVER['APP_SECRET'];
        }

        try {
            $container = Container::$container;
            if ($container && $container->hasParameter('kernel.secret')) {
                return (string) $container->getParameter('kernel.secret');
            }
        } catch (Throwable $exception) {
            self::debug('Unable to read kernel secret from container.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return '';
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string|false
    {
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }

    private static function debug(string $message, array $context = []): void
    {
        if (!self::DEBUG) {
            return;
        }

        $line = '[LtiProvider legacy] '.$message;

        if (!empty($context)) {
            $json = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (false !== $json) {
                $line .= ' | '.$json;
            }
        }

        error_log($line);
    }
}
