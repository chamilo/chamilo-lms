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
}
