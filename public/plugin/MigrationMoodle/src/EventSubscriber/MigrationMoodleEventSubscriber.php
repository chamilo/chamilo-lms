<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\LoginCredentialsCheckedEvent;
use Doctrine\ORM\Exception\NotSupported;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MigrationMoodleEventSubscriber implements EventSubscriberInterface
{
    private MigrationMoodlePlugin $plugin;

    public function __construct()
    {
        $this->plugin = MigrationMoodlePlugin::create();
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::LOGIN_CREDENTIALS_CHECKED => 'onLoginCredentialsChecked',
        ];
    }

    /**
     * @throws NotSupported
     */
    public function onLoginCredentialsChecked(LoginCredentialsCheckedEvent $event): void
    {
        if (!$this->plugin->isEnabled()) {
            return;
        }

        $userData = $event->getUser();
        $credentials = $event->getCredentials();

        if (empty($userData['id']) || empty($credentials['password'])) {
            return;
        }

        $extraField = $this->getExtraField();

        if (empty($extraField)) {
            return;
        }

        $fieldValue = $this->getExtraFieldValue($extraField, $userData);

        if (empty($fieldValue) || '' === (string) $fieldValue->getFieldValue()) {
            return;
        }

        $isPasswordVerified = password_verify(
            (string) $credentials['password'],
            (string) $fieldValue->getFieldValue()
        );

        if (!$isPasswordVerified) {
            throw new AccessDeniedException('Invalid Moodle password.');
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
            ->findOneBy([
                'field' => $extraField,
                'itemId' => (int) $userData['id'],
            ]);
    }
}
