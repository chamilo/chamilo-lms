<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\UserBundle\Entity\User;

/**
 * Class WhispeakAuthPlugin.
 */
class WhispeakAuthPlugin extends Plugin
{
    const SETTING_ENABLE = 'enable';
    const SETTING_API_URL = 'api_url';
    const SETTING_TOKEN = 'token';
    const SETTING_INSTRUCTION = 'instruction';

    const EXTRAFIELD_AUTH_UID = 'whispeak_auth_uid';

    /**
     * StudentFollowUpPlugin constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '0.1',
            'Angel Fernando Quiroz',
            [
                self::SETTING_ENABLE => 'boolean',
                self::SETTING_API_URL => 'text',
                self::SETTING_TOKEN => 'text',
                self::SETTING_INSTRUCTION => 'html',
            ]
        );
    }

    /**
     * @return WhispeakAuthPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function install()
    {
        UserManager::create_extra_field(
            self::EXTRAFIELD_AUTH_UID,
            \ExtraField::FIELD_TYPE_TEXT,
            $this->get_lang('Whispeak uid'),
            ''
        );
    }

    public function uninstall()
    {
        $extraField = self::getAuthUidExtraField();

        if (empty($extraField)) {
            return;
        }

        $em = Database::getManager();

        $em->createQuery('DELETE FROM ChamiloCoreBundle:ExtraFieldValues efv WHERE efv.field = :field')
            ->execute(['field' => $extraField]);

        $em->remove($extraField);
        $em->flush();
    }

    /**
     * @return ExtraField
     */
    public static function getAuthUidExtraField()
    {
        $em = Database::getManager();
        $efRepo = $em->getRepository('ChamiloCoreBundle:ExtraField');

        /** @var ExtraField $extraField */
        $extraField = $efRepo->findOneBy(
            [
                'variable' => self::EXTRAFIELD_AUTH_UID,
                'extraFieldType' => ExtraField::USER_FIELD_TYPE,
            ]
        );

        return $extraField;
    }

    /**
     * @param int $userId
     *
     * @return ExtraFieldValues
     */
    public static function getAuthUidValue($userId)
    {
        $extraField = self::getAuthUidExtraField();
        $em = Database::getManager();
        $efvRepo = $em->getRepository('ChamiloCoreBundle:ExtraFieldValues');

        /** @var ExtraFieldValues $value */
        $value = $efvRepo->findOneBy(['field' => $extraField, 'itemId' => $userId]);

        return $value;
    }

    /**
     * @param int $userId
     *
     * @return bool
     */
    public static function checkUserIsEnrolled($userId)
    {
        $value = self::getAuthUidValue($userId);

        if (empty($value)) {
            return false;
        }

        return !empty($value->getValue());
    }

    /**
     * @return string
     */
    public static function getEnrollmentUrl()
    {
        return api_get_path(WEB_PLUGIN_PATH).'whispeakauth/enrollment.php';
    }

    /**
     * @param string $filePath
     *
     * @return array
     */
    public function requestEnrollment(User $user, $filePath)
    {
        $metadata = [
            'motherTongue' => $user->getLanguage(),
            'spokenTongue' => $user->getLanguage(),
            'audioType' => 'pcm',
        ];

        return $this->sendRequest(
            'enrollment',
            $metadata,
            $user,
            $filePath
        );
    }

    /**
     * @param string $uid
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveEnrollment(User $user, $uid)
    {
        $em = Database::getManager();
        $value = self::getAuthUidValue($user->getId());

        if (empty($value)) {
            $ef = self::getAuthUidExtraField();
            $now = new DateTime('now', new DateTimeZone('UTC'));

            $value = new ExtraFieldValues();
            $value
                ->setField($ef)
                ->setItemId($user->getId())
                ->setUpdatedAt($now);
        }

        $value->setValue($uid);

        $em->persist($value);
        $em->flush();
    }

    public function requestAuthentify(User $user, $filePath)
    {
        $value = self::getAuthUidValue($user->getId());

        if (empty($value)) {
            return null;
        }

        $metadata = [
            'uid' => $value->getValue(),
            'audioType' => 'pcm',
        ];

        return $this->sendRequest(
            'authentify',
            $metadata,
            $user,
            $filePath
        );
    }

    /**
     * @return string
     */
    public function getAuthentifySampleText()
    {
        $phrases = [];

        for ($i = 1; $i <= 6; $i++) {
            $phrases[] = $this->get_lang("AuthentifySampleText$i");
        }

        $rand = array_rand($phrases, 1);

        return $phrases[$rand];
    }

    /**
     * @return bool
     */
    public function toolIsEnabled()
    {
        return 'true' === $this->get(self::SETTING_ENABLE);
    }

    /**
     * Access not allowed when tool is not enabled.
     *
     * @param bool $printHeaders Optional. Print headers.
     */
    public function protectTool($printHeaders = true)
    {
        if ($this->toolIsEnabled()) {
            return;
        }

        api_not_allowed($printHeaders);
    }

    /**
     * @return string
     */
    private function getApiUrl()
    {
        $url = $this->get(self::SETTING_API_URL);

        return trim($url, " \t\n\r \v/");
    }

    /**
     * @param string $endPoint
     * @param string $filePath
     *
     * @return array
     */
    private function sendRequest($endPoint, array $metadata, User $user, $filePath)
    {
        $moderator = $user->getCreatorId() ?: $user->getId();
        $apiUrl = $this->getApiUrl()."/$endPoint";
        $headers = [
            //"Content-Type: application/x-www-form-urlencoded",
            "Authorization: Bearer ".$this->get(self::SETTING_TOKEN),
        ];
        $post = [
            'metadata' => json_encode($metadata),
            'moderator' => "moderator_$moderator",
            'client' => base64_encode($user->getUserId()),
            'voice' => new CURLFile($filePath),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);

        if (!empty($result['error'])) {
            return null;
        }

        return json_decode($result, true);
    }
}
