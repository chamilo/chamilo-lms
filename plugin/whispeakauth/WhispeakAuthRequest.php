<?php
/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;

/**
 * Class WhispeakRequest.
 */
class WhispeakAuthRequest
{
    const API_URL = 'http://api.whispeak.io:8080/v1.1/';

    /**
     * @param string       $uri
     * @param array        $headers
     * @param array|string $body
     *
     * @throws Exception
     *
     * @return array
     */
    private static function doPost($uri, array $headers = [], $body = null)
    {
        $ch = curl_init(self::API_URL.$uri);

        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);

        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $result = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);

        if (!empty($error)) {
            throw new Exception($error);
        }

        $result = json_decode($result, true);

        if (!empty($result['error'])) {
            throw new Exception($result['error']);
        }

        return $result;
    }

    /**
     * @param WhispeakAuthPlugin $plugin
     *
     * @throws Exception
     *
     * @return string
     */
    public static function activityId(WhispeakAuthPlugin $plugin)
    {
        $headers = [
            "Authorization: Bearer {$plugin->getAccessToken()}",
            "Content-Type: application/x-www-form-urlencoded",
        ];
        $body = [
            'moderator' => 'ModeratorID',
            'client' => 'ClientID',
        ];

        $result = self::doPost('activityid', $headers, $body);

        if (empty($result['activityid'])) {
            throw new Exception(get_lang('BadFormData'));
        }

        return $result['activityid'];
    }

    /**
     * @param WhispeakAuthPlugin $plugin
     *
     * @throws Exception
     *
     * @return string
     */
    public static function whispeakId(WhispeakAuthPlugin $plugin)
    {
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            "Authorization: Bearer {$plugin->getAccessToken()}",
        ];
        $body = [
            'moderator' => 'ModeratorID',
            'client' => 'ClientID'
        ];

        $result = self::doPost('whispeakid', $headers, $body);

        if (empty($result['wsId'])) {
            throw new Exception(get_lang('BadFormData'));
        }

        return $result['wsId'];
    }

    /**
     * @param WhispeakAuthPlugin $plugin    Plugin instance.
     * @param string             $wsId      User's Whispeak ID.
     * @param bool               $grantGtu  Whether user accepted the General Term of Use.
     * @param bool               $grantAurp Whether user Allow to Use for Research Purpose.
     *
     * @throws Exception
     *
     * @return string
     */
    public static function license(WhispeakAuthPlugin $plugin, $wsId, $grantGtu, $grantAurp)
    {
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            "Authorization: Bearer ".$plugin->getAccessToken(),
        ];
        $metadata = [
            'wsId' => $wsId,
            'GTU' => $grantGtu,
            'AURP' => $grantAurp,
        ];
        $body = [
            'metadata' => json_encode($metadata),
            'moderator' => 'ModeratorID',
            'client' => 'ClientID',
        ];

        $result = self::doPost('license', $headers, $body);

        if (empty($result['wsid'])) {
            throw new Exception(get_lang('BadFormData'));
        }

        return $result['wsid'];
    }

    /**
     * @param WhispeakAuthPlugin $plugin
     *
     * @throws Exception
     *
     * @return string
     */
    public static function enrollmentSentence(WhispeakAuthPlugin $plugin)
    {
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            "Authorization: Bearer ".$plugin->getAccessToken(),
        ];
        $metadata = [
            //'spokenTongue' => WhispeakAuthPlugin::getLanguageIsoCode($user->getLanguage()),
        ];
        $body = [
            'metadata' => json_encode($metadata),
            'moderator' => 'ModeratorID',
            'client' => 'ClientID',
        ];

        $result = self::doPost('enrollmentsentence', $headers, $body);

        if (empty($result['text'])) {
            throw new Exception(get_lang('BadFormData'));
        }

        return $result['text'];
    }

    /**
     * @param WhispeakAuthPlugin $plugin
     * @param User               $user
     * @param string             $wsId
     * @param string             $text     Sentence text used to create the voice file.
     * @param string             $filePath
     *
     * @throws Exception
     *
     * @return array
     */
    public static function enrollment(WhispeakAuthPlugin $plugin, User $user, $wsId, $text, $filePath) {
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            "Authorization: Bearer ".$plugin->getAccessToken(),
        ];
        $metadata = [
            'wsid' => $wsId,
            'audioType' => 'pcm',
            'spokenTongue' => WhispeakAuthPlugin::getLanguageIsoCode($user->getLanguage()),
            'text' => $text,
        ];
        $body = [
            'metadata' => json_encode($metadata),
            'moderator' => 'ModeratorID',
            'client' => 'ClientID',
            'voice' => new CURLFile($filePath),
        ];

        $result = self::doPost('enrollment', $headers, $body);

        if (empty($result)) {
            throw new Exception(get_lang('BadFormData'));
        }

        return $result;
    }

    /**
     * @param WhispeakAuthPlugin $plugin
     *
     * @throws Exception
     *
     * @return string
     */
    public static function authenticateSentence(WhispeakAuthPlugin $plugin)
    {
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            "Authorization: Bearer ".$plugin->getAccessToken(),
        ];
        $body = [
            'metadata' => json_encode([]),
            'moderator' => 'ModeratorID',
            'client' => 'ClientID',
        ];

        $result = self::doPost('authenticatesentence', $headers, $body);

        if (empty($result['text'])) {
            throw new Exception(get_lang('BadFormData'));
        }

        return $result['text'];
    }

    /**
     * @param WhispeakAuthPlugin $plugin
     * @param string             $wsId
     * @param string             $text     Sentence text used to create the voice file.
     * @param string             $filePath
     *
     * @throws Exception
     *
     * @return array
     */
    public static function authentify(WhispeakAuthPlugin $plugin, $wsId, $text, $filePath)
    {
        $headers = [
            "Authorization: Bearer ".$plugin->getAccessToken(),
        ];
        $metadata = [
            'wsid' => $wsId,
            'activityId' => self::activityId($plugin),
            'audioType' => 'pcm',
            'text' => $text,
        ];
        $body = [
            'metadata' => json_encode($metadata),
            'moderator' => 'ModeratorID',
            'client' => 'ClientID',
            'voice' => new CURLFile($filePath),
        ];

        $result = self::doPost('authentify', $headers, $body);

        if (empty($result)) {
            throw new Exception(get_lang('BadFormData'));
        }

        return $result;
    }

    /**
     * @param WhispeakAuthPlugin $plugin
     * @param string             $wsId
     * @param string             $activityId
     * @param DateTime           $date
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getUserInfos(
        WhispeakAuthPlugin $plugin,
        $wsId,
        $activityId,
        DateTime $date = null
    ) {
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            "Authorization: Bearer ".$plugin->getAccessToken(),
        ];
        $metadata = [
            'wsIds' => [$wsId],
            'activityId' => $activityId,
            'audioType' => 'pcm',
        ];

        if ($date) {
            $metadata['year'] = (int) $date->format('Y');
            $metadata['month'] = (int) $date->format('m');
            $metadata['day'] = (int) $date->format('d');
        }

        $body = [
            'metadata' => json_encode($metadata),
            'moderator' => 'ModeratorID',
            'client' => 'ClientID',
        ];

        $result = self::doPost('getusersinfos', $headers, $body);

        if (empty($result)) {
            throw new Exception(get_lang('BadFormData'));
        }

        return $result;
    }
}
