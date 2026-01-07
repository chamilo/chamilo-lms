<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Random\RandomException;

readonly class ScimHelper
{
    public const SCIM_FIELD = 'scim_external_id';

    public function __construct(
        private ExtraFieldRepository $extraFieldRepo,
        private ExtraFieldValuesRepository $extraFieldValuesRepo,
        private UserRepository $userRepo,
    ) {}

    public static function createToken(): string
    {
        try {
            return bin2hex(random_bytes(64));
        } catch (RandomException) {
            return '';
        }
    }

    private function getExtraField(): ExtraField
    {
        return $this->extraFieldRepo->findByVariable(
            ExtraField::USER_FIELD_TYPE,
            self::SCIM_FIELD
        );
    }

    public function findUser(string $externalId): ?User
    {
        $field = $this->getExtraField();

        try {
            $fieldValue = $this->extraFieldValuesRepo->findByVariableAndValue($field, $externalId);
        } catch (NonUniqueResultException) {
            return null;
        }

        if (!$fieldValue) {
            return null;
        }

        return $this->userRepo->find($fieldValue->getItemId());
    }

    public function getExternalId(User $user): ?string
    {
        $fieldValue = $this->extraFieldValuesRepo->getValueByVariableAndItem(
            self::SCIM_FIELD,
            $user->getId(),
            ExtraField::USER_FIELD_TYPE
        );

        return $fieldValue?->getFieldValue();
    }

    public function saveExternalId(string $externalId, User $user): void
    {
        $this->extraFieldValuesRepo->updateItemData(
            $this->getExtraField(),
            $user,
            $externalId
        );
    }
}
