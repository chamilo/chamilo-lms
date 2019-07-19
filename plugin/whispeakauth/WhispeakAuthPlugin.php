<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\UserBundle\Entity\User;

/**
 * Class WhispeakAuthPlugin.
 */
class WhispeakAuthPlugin extends Plugin implements HookPluginInterface
{
    const SETTING_ENABLE = 'enable';
    const SETTING_MAX_ATTEMPTS = 'max_attempts';
    const SETTING_2FA = '2fa';

    const EXTRAFIELD_AUTH_UID = 'whispeak_auth_uid';
    const EXTRAFIELD_LP_ITEM = 'whispeak_lp_item';
    const EXTRAFIELD_QUIZ_QUESTION = 'whispeak_quiz_question';

    const API_URL = 'http://api.whispeak.io:8080/v1.1/';

    const SESSION_FAILED_LOGINS = 'whispeak_failed_logins';
    const SESSION_2FA_USER = 'whispeak_user_id';
    const SESSION_LP_ITEM = 'whispeak_lp_item';
    const SESSION_QUIZ_QUESTION = 'whispeak_quiz_question';
    const SESSION_AUTH_PASSWORD = 'whispeak_auth_password';

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
                self::SETTING_MAX_ATTEMPTS => 'text',
                self::SETTING_2FA => 'boolean',
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

        LpItem::createExtraField(
            self::EXTRAFIELD_LP_ITEM,
            \ExtraField::FIELD_TYPE_CHECKBOX,
            $this->get_lang('MarkForSpeechAuthentication'),
            '0',
            true,
            true
        );

        $extraField = new \ExtraField('question');
        $params = [
            'variable' => self::EXTRAFIELD_QUIZ_QUESTION,
            'field_type' => \ExtraField::FIELD_TYPE_CHECKBOX,
            'display_text' => $this->get_lang('MarkForSpeechAuthentication'),
            'default_value' => '0',
            'changeable' => true,
            'visible_to_self' => true,
            'visible_to_others' => false,
        ];

        return $extraField->save($params);
    }

    public function uninstall()
    {
        $this->uninstallHook();

        $em = Database::getManager();

        $authIdExtrafield = self::getAuthUidExtraField();

        if (!empty($authIdExtrafield)) {
            $em
                ->createQuery('DELETE FROM ChamiloCoreBundle:ExtraFieldValues efv WHERE efv.field = :field')
                ->execute(['field' => $authIdExtrafield]);

            $em->remove($authIdExtrafield);
            $em->flush();
        }

        $lpItemExtrafield = self::getLpItemExtraField();

        if (!empty($lpItemExtrafield)) {
            $em
                ->createQuery('DELETE FROM ChamiloCoreBundle:ExtraFieldValues efv WHERE efv.field = :field')
                ->execute(['field' => $lpItemExtrafield]);

            $em->remove($lpItemExtrafield);
            $em->flush();
        }

        $quizQuestionExtrafield = self::getQuizQuestionExtraField();

        if (!empty($quizQuestionExtrafield)) {
            $em
                ->createQuery('DELETE FROM ChamiloCoreBundle:ExtraFieldValues efv WHERE efv.field = :field')
                ->execute(['field' => $quizQuestionExtrafield]);

            $em->remove($quizQuestionExtrafield);
            $em->flush();
        }
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        $token = file_get_contents(__DIR__.'/tokenTest');

        return trim($token);
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
     * @return ExtraField
     */
    public static function getLpItemExtraField()
    {
        $efRepo = Database::getManager()->getRepository('ChamiloCoreBundle:ExtraField');

        /** @var ExtraField $extraField */
        $extraField = $efRepo->findOneBy(
            [
                'variable' => self::EXTRAFIELD_LP_ITEM,
                'extraFieldType' => ExtraField::LP_ITEM_FIELD_TYPE,
            ]
        );

        return $extraField;
    }

    /**
     * @return ExtraField
     */
    public static function getQuizQuestionExtraField()
    {
        $efRepo = Database::getManager()->getRepository('ChamiloCoreBundle:ExtraField');

        /** @var ExtraField $extraField */
        $extraField = $efRepo->findOneBy(
            [
                'variable' => self::EXTRAFIELD_QUIZ_QUESTION,
                'extraFieldType' => ExtraField::QUESTION_FIELD_TYPE,
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
     * Get the whispeak_lp_item value for a LP item ID.
     *
     * @param int $lpItemId
     *
     * @return array|false
     */
    public static function getLpItemValue($lpItemId)
    {
        $efv = new ExtraFieldValue('lp_item');
        $value = $efv->get_values_by_handler_and_field_variable($lpItemId, self::EXTRAFIELD_LP_ITEM);

        return $value;
    }

    /**
     * @param int $lpItemId
     *
     * @return bool
     */
    public static function isLpItemMarked($lpItemId)
    {
        if (!self::create()->isEnabled()) {
            return false;
        }

        $value = self::getLpItemValue($lpItemId);

        return !empty($value) && !empty($value['value']);
    }

    /**
     * Get the whispeak_quiz_question value for a quiz question ID.
     *
     * @param int $questionId
     *
     * @return array|false
     */
    public static function getQuizQuestionValue($questionId)
    {
        $efv = new ExtraFieldValue('question');
        $value = $efv->get_values_by_handler_and_field_variable($questionId, self::EXTRAFIELD_QUIZ_QUESTION);

        return $value;
    }

    /**
     * @param int $questionId
     *
     * @return bool
     */
    public static function isQuizQuestionMarked($questionId)
    {
        if (!self::create()->isEnabled()) {
            return false;
        }

        $value = self::getQuizQuestionValue($questionId);

        return !empty($value) && !empty($value['value']);
    }

    /**
     * @param int $questionId
     *
     * @return bool
     */
    public static function questionRequireAuthentify($questionId)
    {
        $isMarked = self::isQuizQuestionMarked($questionId);

        if (!$isMarked) {
            return false;
        }

        $questionInfo = ChamiloSession::read(self::SESSION_QUIZ_QUESTION, []);

        if (empty($questionInfo)) {
            return true;
        }

        if ((int) $questionId !== $questionInfo['question']) {
            return true;
        }

        if (false === $questionInfo['passed']) {
            return true;
        }

        return false;
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
     * @param User   $user
     * @param string $uid
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveEnrollment(User $user, $uid)
    {
        $em = Database::getManager();
        $extraFieldValue = self::getAuthUidValue($user->getId());

        if (empty($extraFieldValue)) {
            $extraField = self::getAuthUidExtraField();
            $now = new DateTime('now', new DateTimeZone('UTC'));

            $extraFieldValue = new ExtraFieldValues();
            $extraFieldValue
                ->setField($extraField)
                ->setItemId($user->getId())
                ->setUpdatedAt($now);
        }

        $extraFieldValue->setValue($uid);

        $em->persist($extraFieldValue);
        $em->flush();
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
     * @throws Exception
     *
     * @return array
     */
    public function generateWsid()
    {
        $headers = [
            "Authorization: Bearer ".$this->getAccessToken(),
        ];

        $ch = curl_init(self::API_URL.'whispeakid');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);

        if (!empty($result['error'])) {
            throw new Exception($result['error']);
        }

        return $result;
    }

    /**
     * @param strging $wsid
     * @param bool    $researchPermission
     *
     * @throws Exception
     *
     * @return array
     */
    public function license($wsid, $researchPermission = false)
    {
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer ".$this->getAccessToken(),
        ];

        $body = [
            'wsid' => $wsid,
            'license' => 1,
            'researchPermission' => $researchPermission,
        ];

        $ch = curl_init(self::API_URL.'license');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);

        if (!empty($result['error'])) {
            throw new Exception($result['error']);
        }

        return $result;
    }

    /**
     * Convert the language name to ISO-639-2 code (3 characters).
     *
     * @param string $languageName
     *
     * @return string
     */
    public static function getLanguageIsoCode($languageName)
    {
        $listIso3 = [
            'ab' => 'abk',
            'aa' => 'aar',
            'af' => 'afr',
            'ak' => 'aka',
            'sq' => 'sqi',
            'am' => 'amh',
            'ar' => 'ara',
            'an' => 'arg',
            'hy' => 'hye',
            'as' => 'asm',
            'av' => 'ava',
            'ae' => 'ave',
            'ay' => 'aym',
            'az' => 'aze',
            'bm' => 'bam',
            'ba' => 'bak',
            'eu' => 'eus',
            'be' => 'bel',
            'bn' => 'ben',
            'bh' => 'bih',
            'bi' => 'bis',
            'bs' => 'bos',
            'br' => 'bre',
            'bg' => 'bul',
            'my' => 'mya',
            'ca' => 'cat',
            'ch' => 'cha',
            'ce' => 'che',
            'ny' => 'nya',
            'zh' => 'zho',
            'cv' => 'chv',
            'kw' => 'cor',
            'co' => 'cos',
            'cr' => 'cre',
            'hr' => 'hrv',
            'cs' => 'ces',
            'da' => 'dan',
            'dv' => 'div',
            'nl' => 'nld',
            'dz' => 'dzo',
            'en' => 'eng',
            'eo' => 'epo',
            'et' => 'est',
            'ee' => 'ewe',
            'fo' => 'fao',
            'fj' => 'fij',
            'fi' => 'fin',
            'fr' => 'fra',
            'ff' => 'ful',
            'gl' => 'glg',
            'ka' => 'kat',
            'de' => 'deu',
            'el' => 'ell',
            'gn' => 'grn',
            'gu' => 'guj',
            'ht' => 'hat',
            'ha' => 'hau',
            'he' => 'heb',
            'hz' => 'her',
            'hi' => 'hin',
            'ho' => 'hmo',
            'hu' => 'hun',
            'ia' => 'ina',
            'id' => 'ind',
            'ie' => 'ile',
            'ga' => 'gle',
            'ig' => 'ibo',
            'ik' => 'ipk',
            'io' => 'ido',
            'is' => 'isl',
            'it' => 'ita',
            'iu' => 'iku',
            'ja' => 'jpn',
            'jv' => 'jav',
            'kl' => 'kal',
            'kn' => 'kan',
            'kr' => 'kau',
            'ks' => 'kas',
            'kk' => 'kaz',
            'km' => 'khm',
            'ki' => 'kik',
            'rw' => 'kin',
            'ky' => 'kir',
            'kv' => 'kom',
            'kg' => 'kon',
            'ko' => 'kor',
            'ku' => 'kur',
            'kj' => 'kua',
            'la' => 'lat',
            'lb' => 'ltz',
            'lg' => 'lug',
            'li' => 'lim',
            'ln' => 'lin',
            'lo' => 'lao',
            'lt' => 'lit',
            'lu' => 'lub',
            'lv' => 'lav',
            'gv' => 'glv',
            'mk' => 'mkd',
            'mg' => 'mlg',
            'ms' => 'msa',
            'ml' => 'mal',
            'mt' => 'mlt',
            'mi' => 'mri',
            'mr' => 'mar',
            'mh' => 'mah',
            'mn' => 'mon',
            'na' => 'nau',
            'nv' => 'nav',
            'nd' => 'nde',
            'ne' => 'nep',
            'ng' => 'ndo',
            'nb' => 'nob',
            'nn' => 'nno',
            'no' => 'nor',
            'ii' => 'iii',
            'nr' => 'nbl',
            'oc' => 'oci',
            'oj' => 'oji',
            'cu' => 'chu',
            'om' => 'orm',
            'or' => 'ori',
            'os' => 'oss',
            'pa' => 'pan',
            'pi' => 'pli',
            'fa' => 'fas',
            'pl' => 'pol',
            'ps' => 'pus',
            'pt' => 'por',
            'qu' => 'que',
            'rm' => 'roh',
            'rn' => 'run',
            'ro' => 'ron',
            'ru' => 'rus',
            'sa' => 'san',
            'sc' => 'srd',
            'sd' => 'snd',
            'se' => 'sme',
            'sm' => 'smo',
            'sg' => 'sag',
            'sr' => 'srp',
            'gd' => 'gla',
            'sn' => 'sna',
            'si' => 'sin',
            'sk' => 'slk',
            'sl' => 'slv',
            'so' => 'som',
            'st' => 'sot',
            'es' => 'spa',
            'su' => 'sun',
            'sw' => 'swa',
            'ss' => 'ssw',
            'sv' => 'swe',
            'ta' => 'tam',
            'te' => 'tel',
            'tg' => 'tgk',
            'th' => 'tha',
            'ti' => 'tir',
            'bo' => 'bod',
            'tk' => 'tuk',
            'tl' => 'tgl',
            'tn' => 'tsn',
            'to' => 'ton',
            'tr' => 'tur',
            'ts' => 'tso',
            'tt' => 'tat',
            'tw' => 'twi',
            'ty' => 'tah',
            'ug' => 'uig',
            'uk' => 'ukr',
            'ur' => 'urd',
            'uz' => 'uzb',
            've' => 'ven',
            'vi' => 'vie',
            'vo' => 'vol',
            'wa' => 'wln',
            'cy' => 'cym',
            'wo' => 'wol',
            'fy' => 'fry',
            'xh' => 'xho',
            'yi' => 'yid',
            'yo' => 'yor',
            'za' => 'zha',
            'zu' => 'zul',
        ];

        $iso2 = api_get_language_isocode($languageName);
        $iso3 = isset($listIso3[$iso2]) ? $listIso3[$iso2] : $listIso3['en'];

        return $iso3;
    }

    /**
     * @param string $wsid
     * @param User   $user
     * @param string $filePath
     *
     * @throws Exception
     *
     * @return array
     */
    public function enrollment($wsid, User $user, $filePath)
    {
        $headers = [
            "Authorization: Bearer ".$this->getAccessToken(),
        ];

        $formData = [
            'wsid' => $wsid,
            'audioType' => 'pcm',
            'spokenTongue' => self::getLanguageIsoCode($user->getLanguage()),
            'voice' => new CURLFile($filePath),
        ];

        $ch = curl_init(self::API_URL.'enrollment');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formData);
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);

        if (!empty($result['error'])) {
            throw new Exception($result['error']);
        }

        return $result;
    }

    /**
     * @param string $wsid
     * @param string $filePath
     *
     * @throws Exception
     *
     * @return array
     */
    public function authentify($wsid, $filePath)
    {
        $headers = [
            "Authorization: Bearer ".$this->getAccessToken(),
        ];

        $formData = [
            'wsid' => $wsid,
            'audioType' => 'pcm',
            'voice' => new CURLFile($filePath),
        ];

        $ch = curl_init(self::API_URL.'authentify');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formData);
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);

        if (!empty($result['error'])) {
            throw new Exception($result['error']);
        }

        return $result;
    }

    /**
     * Get the max_attemtps option.
     *
     * @return int
     */
    public function getMaxAttempts()
    {
        return (int) $this->get(self::SETTING_MAX_ATTEMPTS);
    }

    /**
     * Install hook when saving the plugin configuration.
     *
     * @return WhispeakAuthPlugin
     */
    public function performActionsAfterConfigure()
    {
        if ('true' === $this->get(self::SETTING_2FA)) {
            $this->installHook();
        } else {
            $this->uninstallHook();
        }

        return $this;
    }

    /**
     * This method will call the Hook management insertHook to add Hook observer from this plugin.
     */
    public function installHook()
    {
        $observer = WhispeakConditionalLoginHook::create();

        HookConditionalLogin::create()->attach($observer);
    }

    /**
     * This method will call the Hook management deleteHook to disable Hook observer from this plugin.
     */
    public function uninstallHook()
    {
        $observer = WhispeakConditionalLoginHook::create();

        HookConditionalLogin::create()->detach($observer);
    }

    /**
     * @param int $userId
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return bool
     */
    public static function deleteEnrollment($userId)
    {
        $extraFieldValue = self::getAuthUidValue($userId);

        if (empty($extraFieldValue)) {
            return false;
        }

        $em = Database::getManager();
        $em->remove($extraFieldValue);
        $em->flush();

        return true;
    }

    /**
     * Check if the WhispeakAuth plugin is installed and enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return parent::isEnabled() && 'true' === api_get_plugin_setting('whispeakauth', self::SETTING_ENABLE);
    }

    /**
     * @param int $lpItemId
     *
     * @return bool
     */
    public static function isAllowedToSaveLpItem($lpItemId)
    {
        if (!self::isLpItemMarked($lpItemId)) {
            return true;
        }

        $markedItem = ChamiloSession::read(self::SESSION_LP_ITEM, []);

        if (empty($markedItem)) {
            return true;
        }

        if ((int) $lpItemId !== (int) $markedItem['id']) {
            return true;
        }

        return false;
    }

    public static function displayNotAllowedMessage()
    {
        echo Display::return_message(get_lang('NotAllowed'), 'error', false);

        exit;
    }

    /**
     * @param int      $questionId
     * @param Exercise $exercise
     *
     * @throws Exception
     *
     * @return string
     */
    public static function quizQuestionAuthentify($questionId, Exercise $exercise)
    {
        ChamiloSession::write(
            self::SESSION_QUIZ_QUESTION,
            [
                'exercise' => (int) $exercise->iId,
                'question' => (int) $questionId,
                'url_params' => $_SERVER['QUERY_STRING'],
                'passed' => false,
            ]
        );

        $template = new Template('', false, false, false, true, false, false);
        $template->assign('question', $questionId);
        $template->assign('exercise', $exercise->iId);
        $content = $template->fetch('whispeakauth/view/quiz_question.html.twig');

        echo $content;
    }
}
