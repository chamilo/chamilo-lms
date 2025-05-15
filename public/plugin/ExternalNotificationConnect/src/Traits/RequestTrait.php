<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExternalNotificationConnect\Traits\RequestTrait;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use ExternalNotificationConnectPlugin;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

trait RequestTrait
{
    /**
     * @throws Exception
     */
    protected function doCreateRequest($json): ?array
    {
        try {
            $token = $this->plugin->getAccessToken();
        } catch (OptimisticLockException|ORMException|Exception $e) {
            throw new Exception($e->getMessage());
        }

        $options = [
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
            'json' => $json,
        ];

        $client = new Client();

        try {
            $response = $client->post(
                $this->plugin->get(ExternalNotificationConnectPlugin::SETTING_NOTIFICATION_URL),
                $options
            );
        } catch (ClientException|ServerException $e) {
            if (!$e->hasResponse()) {
                throw new Exception($e->getMessage());
            }

            $response = $e->getResponse();
        }

        $json = json_decode((string) $response->getBody(), true);

        if (isset($json['status']) && 500 === $json['status']) {
            throw new Exception($json['message']);
        }

        if (isset($json['validation_errors']) && $json['validation_errors']) {
            $messageError = implode(
                '<br>',
                array_column($json['errors'], 'message')
            );

            throw new Exception($messageError);
        }

        return $json;
    }

    /**
     * @throws Exception
     */
    protected function doEditRequest(array $json): array
    {
        try {
            $token = $this->plugin->getAccessToken();
        } catch (OptimisticLockException|ORMException|Exception $e) {
            throw new Exception($e->getMessage());
        }

        $url = $this->plugin->get(ExternalNotificationConnectPlugin::SETTING_NOTIFICATION_URL)
            .'/'.$json['content_id'].'/'.$json['content_type'];

        $options = [
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
            'json' => $json,
        ];

        $client = new Client();

        try {
            $response = $client->post($url, $options);
        } catch (ClientException|ServerException $e) {
            if (!$e->hasResponse()) {
                throw new Exception($e->getMessage());
            }

            $response = $e->getResponse();
        }

        $json = json_decode((string) $response->getBody(), true);

        if (isset($json['status']) && 500 === $json['status']) {
            throw new Exception($json['message']);
        }

        return $json;
    }

    /**
     * @throws Exception
     */
    protected function doVisibilityRequest(array $data)
    {
        try {
            $token = $this->plugin->getAccessToken();
        } catch (OptimisticLockException|ORMException|Exception $e) {
            throw new Exception($e->getMessage());
        }

        $options = [
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
            'json' => $data,
        ];

        $client = new Client();

        try {
            $response = $client->post(
                $this->plugin->get(ExternalNotificationConnectPlugin::SETTING_NOTIFICATION_URL).'/visibility',
                $options
            );
        } catch (ClientException|ServerException $e) {
            if (!$e->hasResponse()) {
                throw new Exception($e->getMessage());
            }

            $response = $e->getResponse();
        }

        $json = json_decode((string) $response->getBody(), true);

        if (isset($json['status']) && 500 === $json['status']) {
            throw new Exception($json['message']);
        }

        return $json;
    }

    /**
     * @throws Exception
     */
    protected function doDeleteRequest(int $contentId, string $contentType): array
    {
        try {
            $token = $this->plugin->getAccessToken();
        } catch (OptimisticLockException|ORMException|Exception $e) {
            throw new Exception($e->getMessage());
        }

        $url = $this->plugin->get(ExternalNotificationConnectPlugin::SETTING_NOTIFICATION_URL)."/$contentId/$contentType";

        $options = [
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
        ];

        $client = new Client();

        try {
            $response = $client->delete($url, $options);
        } catch (ClientException|ServerException $e) {
            if (!$e->hasResponse()) {
                throw new Exception($e->getMessage());
            }

            $response = $e->getResponse();
        }

        $json = json_decode((string) $response->getBody(), true);

        if (isset($json['status']) && 500 === $json['status']) {
            throw new Exception($json['message']);
        }

        return $json;
    }
}
