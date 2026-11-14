<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Service\ConfigurationDumpSanitizer;
use PHPUnit\Framework\TestCase;

final class ConfigurationDumpSanitizerTest extends TestCase
{
    private ConfigurationDumpSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new ConfigurationDumpSanitizer();
    }

    public function testSensitiveNamesAreNeverExported(): void
    {
        foreach (['mailer_dsn', 'messaging_gdc_api_key', 'clientSecret', 'administrator_email', 'auth-token', 'private_key'] as $key) {
            $result = $this->sanitizer->sanitize($key, 'dummy-sensitive-value');
            self::assertTrue($result['redacted'], $key);
            self::assertSame('[REDACTED]', $result['value'], $key);
        }
    }

    public function testExplicitConfidentialFlagOverridesBenignNames(): void
    {
        self::assertSame(
            ['value' => '[REDACTED]', 'redacted' => true],
            $this->sanitizer->sanitize('merchant_reference', 'dont-share-this', true)
        );
    }

    public function testSensitiveNamesCannotBeOverriddenByPublicMetadata(): void
    {
        self::assertSame(
            ['value' => '[REDACTED]', 'redacted' => true],
            $this->sanitizer->sanitize('api_key', 'secret-data', false)
        );
    }

    public function testHarmlessValuesRemainAvailableForDiagnostics(): void
    {
        self::assertSame(
            ['value' => 'true', 'redacted' => false],
            $this->sanitizer->sanitize('registration_enabled', 'true')
        );
        self::assertSame(
            ['value' => 12, 'redacted' => false],
            $this->sanitizer->sanitize('limit', 12)
        );
    }

    public function testReviewedBooleanSettingsStayVisibleOnlyForBooleanValues(): void
    {
        foreach (['check_password', 'allow_lostpassword', 'login_is_email', 'enable_email_open_tracking'] as $key) {
            self::assertSame(
                ['value' => 'false', 'redacted' => false],
                $this->sanitizer->sanitize($key, 'false'),
                $key
            );
            self::assertSame(
                ['value' => '[REDACTED]', 'redacted' => true],
                $this->sanitizer->sanitize($key, 'unexpected-private-data'),
                $key
            );
            self::assertSame(
                ['value' => '[REDACTED]', 'redacted' => true],
                $this->sanitizer->sanitize($key, 'true', true),
                $key
            );
        }
    }

    public function testReviewedIntegerSettingsStayVisibleOnlyForIntegerValues(): void
    {
        self::assertSame(
            ['value' => '90', 'redacted' => false],
            $this->sanitizer->sanitize('password_rotation_days', '90')
        );
        self::assertSame(
            ['value' => '[REDACTED]', 'redacted' => true],
            $this->sanitizer->sanitize('password_rotation_days', 'opaque-secret')
        );
        self::assertSame(
            ['value' => '[REDACTED]', 'redacted' => true],
            $this->sanitizer->sanitize('administrator_email', 'false')
        );
    }

    public function testEmbeddedEmailAndUrlCredentialsAreRedacted(): void
    {
        foreach (['staff@example.org', 'smtp://person:secret@server.invalid:587', 'https://example.invalid?a=1&token=abc'] as $value) {
            $result = $this->sanitizer->sanitize('other', $value);
            self::assertTrue($result['redacted']);
            self::assertSame('[REDACTED]', $result['value']);
        }
    }

    public function testJsonObjectsAndNestedValuesAreFilteredRecursively(): void
    {
        $result = $this->sanitizer->sanitize('options', '{"enabled":true,"apiKey":"secret123","nested":{"contact":"staff@example.org","port":443}}');
        self::assertTrue($result['redacted']);
        self::assertTrue($result['value']['enabled']);
        self::assertSame('[REDACTED]', $result['value']['apiKey']);
        self::assertSame('[REDACTED]', $result['value']['nested']['contact']);
        self::assertSame(443, $result['value']['nested']['port']);
    }

    public function testAdditionalCredentialFormatsAreRedacted(): void
    {
        foreach (['Bearer abcdefghijklmnop', 'AIzaSyDexample123456789012345678901234567', 'https://example.org/?sig=privatevalue', 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxIn0.signature123'] as $value) {
            $result = $this->sanitizer->sanitize('some_config', $value);
            self::assertTrue($result['redacted']);
            self::assertSame('[REDACTED]', $result['value']);
        }
    }

    public function testArrayKeysCannotExposeEmailAddresses(): void
    {
        $result = $this->sanitizer->sanitize('mapping', ['person@example.org' => 'member']);
        self::assertTrue($result['redacted']);
        self::assertArrayNotHasKey('person@example.org', $result['value']);
    }
}
