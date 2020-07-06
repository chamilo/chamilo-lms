<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;

/**
 * Class MigrationMoodleCheckLoginCredentialsHook.
 */
class MigrationMoodleCheckLoginCredentialsHook extends HookObserver implements CheckLoginCredentialsHookObserverInterface
{
    /**
     * MigrationMoodleCheckLoginCredentialsHook constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            'plugin/migrationmoodle/src/MigrationMoodlePlugin.php',
            'migrationmoodle'
        );
    }

    /**
     * @return bool
     */
    public function checkLoginCredentials(CheckLoginCredentialsHookEventInterface $event)
    {
        $data = $event->getEventData();
        /** @var array $userData */
        $userData = $data['user'];
        /** @var array $credentials */
        $credentials = $data['credentials'];

        $extraField = $this->getExtraField();

        if (empty($extraField)) {
            return false;
        }

        $fieldValue = $this->getExtraFieldValue($extraField, $userData);

        if (empty($fieldValue)) {
            return false;
        }

        $isPasswordVerified = password_verify(
            $credentials['password'],
            $fieldValue->getValue()
        );

        if (!$isPasswordVerified) {
            return false;
        }

        return true;
    }

    /**
     * @return ExtraField|null
     */
    private function getExtraField()
    {
        return Database::getManager()
            ->getRepository('ChamiloCoreBundle:ExtraField')
            ->findOneBy(
                [
                    'variable' => 'moodle_password',
                    'extraFieldType' => ExtraField::USER_FIELD_TYPE,
                ]
            );
    }

    /**
     * @return ExtraFieldValues|null
     */
    private function getExtraFieldValue(ExtraField $extraField, array $userData)
    {
        return Database::getManager()
            ->getRepository('ChamiloCoreBundle:ExtraFieldValues')
            ->findOneBy(['field' => $extraField, 'itemId' => $userData['user_id']]);
    }
}
