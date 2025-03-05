<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\HookEvent\CheckLoginCredentialsHookEvent;
use Chamilo\CoreBundle\HookEvent\HookEvents;
use Doctrine\ORM\Exception\NotSupported;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MigrationMoodleEventSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            HookEvents::CHECK_LOGIN_CREDENTIALS => 'onCheckLoginCredentials',
        ];
    }

    /**
     * @throws NotSupported
     */
    public function onCheckLoginCredentials(CheckLoginCredentialsHookEvent $event): void
    {
        $userData = $event->getUser();
        $credentials = $event->getCredentials();

        $extraField = $this->getExtraField();

        if (empty($extraField)) {
            return;
        }

        $fieldValue = $this->getExtraFieldValue($extraField, $userData);

        if (empty($fieldValue)) {
            return;
        }

        $isPasswordVerified = password_verify(
            $credentials['password'],
            $fieldValue->getFieldValue()
        );

        if (!$isPasswordVerified) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @throws NotSupported
     */
    private function getExtraField(): ?ExtraField
    {
        return Database::getManager()
            ->getRepository(ExtraField::class)
            ->findOneBy(
                [
                    'variable' => 'moodle_password',
                    'extraFieldType' => ExtraField::USER_FIELD_TYPE,
                ]
            );
    }

    /**
     * @throws NotSupported
     */
    private function getExtraFieldValue(ExtraField $extraField, array $userData): ?ExtraFieldValues
    {
        return Database::getManager()
            ->getRepository(ExtraFieldValues::class)
            ->findOneBy(['field' => $extraField, 'itemId' => $userData['id']]);
    }
}
