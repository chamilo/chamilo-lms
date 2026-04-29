<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Serializer;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class UserWebserviceFieldNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'chamilo_user_webservice_field_normalizer_already_called';

    /** @var array<string, int|null> */
    private array $extraFieldIdCache = [];

    /** @var array<int, array<string, string|null>> */
    private array $extraFieldValueCache = [];

    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly RequestStack $requestStack,
        private readonly Connection $connection
    ) {}

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|\ArrayObject|bool|float|int|string|null
    {
        $context[self::ALREADY_CALLED] = true;

        $data = $this->normalizer->normalize($object, $format, $context);

        if (!$object instanceof User || !is_array($data)) {
            return $data;
        }

        $fieldName = trim((string) $this->settingsManager->getSetting('webservice.webservice_return_user_field'));

        if ('' === $fieldName || 'false' === strtolower($fieldName)) {
            return $data;
        }

        $value = $this->resolveUserWebserviceField($object, $fieldName);

        if (null !== $value && '' !== $value) {
            $data['webserviceUserId'] = $value;
        }

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        if (!$data instanceof User) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();

        return null !== $request
            && true === $request->attributes->get('_chamilo_webservice_api_key');
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            User::class => false,
        ];
    }

    private function resolveUserWebserviceField(User $user, string $fieldName): ?string
    {
        return match ($fieldName) {
            'id' => null !== $user->getId() ? (string) $user->getId() : null,
            'username' => (string) $user->getUsername(),
            'email' => (string) $user->getEmail(),
            'official_code', 'officialCode' => method_exists($user, 'getOfficialCode')
                ? (string) $user->getOfficialCode()
                : null,
            default => $this->resolveExtraUserFieldValue($user, $fieldName),
        };
    }

    private function resolveExtraUserFieldValue(User $user, string $fieldName): ?string
    {
        $userId = (int) $user->getId();

        if ($userId <= 0) {
            return null;
        }

        if (array_key_exists($fieldName, $this->extraFieldValueCache[$userId] ?? [])) {
            return $this->extraFieldValueCache[$userId][$fieldName];
        }

        $fieldId = $this->resolveExtraFieldId($fieldName);

        if (null === $fieldId) {
            $this->extraFieldValueCache[$userId][$fieldName] = null;

            return null;
        }

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['extra_field_values'])) {
            $this->extraFieldValueCache[$userId][$fieldName] = null;

            return null;
        }

        $columns = array_map(
            static fn ($column): string => $column->getName(),
            $schemaManager->listTableColumns('extra_field_values')
        );

        $valueColumn = null;

        if (in_array('value', $columns, true)) {
            $valueColumn = 'value';
        } elseif (in_array('field_value', $columns, true)) {
            $valueColumn = 'field_value';
        }

        if (
            null === $valueColumn
            || !in_array('field_id', $columns, true)
            || !in_array('item_id', $columns, true)
        ) {
            $this->extraFieldValueCache[$userId][$fieldName] = null;

            return null;
        }

        $value = $this->connection->fetchOne(
            sprintf(
                'SELECT %s FROM extra_field_values WHERE field_id = :fieldId AND item_id = :userId',
                $this->connection->quoteIdentifier($valueColumn)
            ),
            [
                'fieldId' => $fieldId,
                'userId' => $userId,
            ]
        );

        $this->extraFieldValueCache[$userId][$fieldName] = false !== $value ? (string) $value : null;

        return $this->extraFieldValueCache[$userId][$fieldName];
    }

    private function resolveExtraFieldId(string $fieldName): ?int
    {
        if (array_key_exists($fieldName, $this->extraFieldIdCache)) {
            return $this->extraFieldIdCache[$fieldName];
        }

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['extra_field'])) {
            $this->extraFieldIdCache[$fieldName] = null;

            return null;
        }

        $columns = array_map(
            static fn ($column): string => $column->getName(),
            $schemaManager->listTableColumns('extra_field')
        );

        if (!in_array('id', $columns, true) || !in_array('variable', $columns, true)) {
            $this->extraFieldIdCache[$fieldName] = null;

            return null;
        }

        $fieldId = $this->connection->fetchOne(
            'SELECT id FROM extra_field WHERE variable = :variable ORDER BY id ASC',
            ['variable' => $fieldName]
        );

        $this->extraFieldIdCache[$fieldName] = false !== $fieldId ? (int) $fieldId : null;

        return $this->extraFieldIdCache[$fieldName];
    }
}
