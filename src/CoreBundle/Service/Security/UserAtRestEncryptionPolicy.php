<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Security;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Types\TextType;
use RuntimeException;

/**
 * One platform-wide, allowlisted setting controls all supported user profile fields.
 * One DB lookup per service lifetime; encrypted values remain readable when disabled.
 */
final class UserAtRestEncryptionPolicy
{
    public const string SETTING = 'encrypted_user_fields';

    public const array SUPPORTED_FIELDS = ['biography', 'phone', 'address'];

    /**
     * @var list<string>|null
     */
    private ?array $selectedFields = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly AtRestKeyVerifier $keyVerifier,
    ) {}

    public function isEnabled(string $field): bool
    {
        return \in_array($field, $this->enabledFields(), true);
    }

    /**
     * @return list<string>
     */
    public function enabledFields(): array
    {
        if (null !== $this->selectedFields) {
            return $this->selectedFields;
        }

        try {
            // The setting is global; child access URLs must never override this security policy.
            $value = $this->connection->fetchOne(
                'SELECT selected_value FROM settings'
                .' WHERE variable = :variable AND category = :category'
                .' AND access_url = 1 AND subkey IS NULL LIMIT 1',
                ['variable' => self::SETTING, 'category' => 'security'],
            );
        } catch (TableNotFoundException) {
            // The installer can create users before the settings table exists.
            return $this->selectedFields = [];
        }

        if (false === $value || null === $value) {
            return $this->selectedFields = [];
        }

        if (!\is_string($value)) {
            throw new RuntimeException('Invalid stored encryption configuration.');
        }

        return $this->selectedFields = self::parseFields($value);
    }

    /**
     * Reject unknown field names instead of introducing arbitrary column encryption.
     *
     * @return list<string>
     */
    public static function parseFields(string $value): array
    {
        if ('' === trim($value)) {
            return [];
        }

        $fields = explode(',', $value);
        foreach ($fields as $field) {
            if (!\in_array($field, self::SUPPORTED_FIELDS, true)) {
                throw new RuntimeException('Unsupported user encryption field configured.');
            }
        }

        return array_values(array_unique($fields));
    }

    /**
     * Validate an explicit administrative selection before storing it.
     * This must never create or replace an encryption key.
     *
     * @param array<string> $fields
     */
    public function assertFieldsReady(array $fields): void
    {
        if ([] === $fields) {
            return;
        }

        foreach ($fields as $field) {
            if (!\in_array($field, self::SUPPORTED_FIELDS, true)) {
                throw new RuntimeException('Unsupported user encryption field.');
            }
        }

        $columns = $this->connection->createSchemaManager()->listTableColumns('user');
        foreach ($fields as $field) {
            $column = $columns[$field] ?? null;
            if (null === $column || !$column->getType() instanceof TextType) {
                throw new RuntimeException('Apply the profile encryption schema migration first.');
            }
        }

        $this->keyVerifier->assertKeyMatchesStoredUserData();
    }
}
