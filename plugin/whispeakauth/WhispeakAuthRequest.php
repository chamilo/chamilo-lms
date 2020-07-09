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
        ];

        $result = self::doGet('activityid', $headers);

        if (empty($result['activity_id'])) {
            throw new Exception(get_lang('BadFormData'));
        }

        return $result['activity_id'];
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
            "Authorization: Bearer {$plugin->getAccessToken()}",
        ];

        $result = self::doGet('whispeakid', $headers);

        if (empty($result['wsid'])) {
            throw new Exception(get_lang('BadFormData'));
        }

        return $result['wsid'];
    }

    /**
     * @param WhispeakAuthPlugin $plugin    Plugin instance.
     * @param string             $wsId      User's Whispeak ID.
     * @param bool               $grantAurp Whether user Allow to Use for Research Purpose.
     *
     * @throws Exception
     *
     * @return string
     */
    public static function license(WhispeakAuthPlugin $plugin, $wsId, $grantAurp)
    {
        $headers = [
            'Content-Type: application/json',
            "Authorization: Bearer ".$plugin->getAccessToken(),
        ];
        $body = [
            'wsid' => $wsId,
            'GTU' => 'lorem ipsum',
            'AURP' => $grantAurp,
        ];

        $result = self::doPost('license', $headers, json_encode($body));

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
            "Authorization: Bearer ".$plugin->getAccessToken(),
        ];

        $result = self::doGet('enrollmentsentence', $headers);

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
    public static function enrollment(WhispeakAuthPlugin $plugin, User $user, $wsId, $text, $filePath)
    {
        $headers = [
            "Authorization: Bearer ".$plugin->getAccessToken(),
        ];
        $body = [
            'wsid' => $wsId,
            'audioType' => 'pcm',
            'spokenTongue' => WhispeakAuthPlugin::getLanguageIsoCode($user->getLanguage()),
            'text' => $text,
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
            "Authorization: Bearer ".$plugin->getAccessToken(),
        ];

        $result = self::doGet('authenticatesentence', $headers);

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
        if (empty($text)) {
            $text = '';
        }
        $body = [
            'wsid' => $wsId,
            'activityId' => self::activityId($plugin),
            'audioType' => 'pcm',
            'text' => $text,
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
     * @param array              $wsIds
     * @param array              $activityIds
     * @param DateTime           $date
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getUsersInfos(
        WhispeakAuthPlugin $plugin,
        array $wsIds,
        array $activityIds = [],
        DateTime $date = null
    ) {
        $headers = [
            'Content-Type: application/json',
            "Authorization: Bearer ".$plugin->getAccessToken(),
        ];
        $metadata = ['wsids' => $wsIds];

        if ($activityIds) {
            $metadata['activityids'] = $activityIds;
        }

        if ($date) {
            $metadata['date'] = [
                'year' => (int) $date->format('Y'),
                'month' => (int) $date->format('m'),
                'day' => (int) $date->format('d'),
            ];
        }

        $result = self::doPost('getusersinfos', $headers, json_encode($metadata));

        return $result;
    }

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
     * @param string $uri
     * @param array  $headers
     *
     * @throws Exception
     *
     * @return array
     */
    private static function doGet($uri, array $headers = [])
    {
        $ch = curl_init(self::API_URL.$uri);

        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

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
}
