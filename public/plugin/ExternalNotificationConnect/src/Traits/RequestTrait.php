<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExternalNotificationConnect\Traits\RequestTrait;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use ExternalNotificationConnectPlugin;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;

trait RequestTrait
{
    /**
     * @throws GuzzleException
     * @throws Exception
     */
    protected function doCreateRequest(array $json): ?array
    {
        return $this->sendNotificationRequest(
            'POST',
            $this->plugin->get(ExternalNotificationConnectPlugin::SETTING_NOTIFICATION_URL),
            $json
        );
    }

    /**
     * @throws Exception
     */
    protected function doEditRequest(array $json): array
    {
        $url = rtrim((string) $this->plugin->get(ExternalNotificationConnectPlugin::SETTING_NOTIFICATION_URL), '/')
            .'/'.$json['content_id'].'/'.$json['content_type'];

        return $this->sendNotificationRequest('POST', $url, $json);
    }

    /**
     * @throws Exception
     */
    protected function doVisibilityRequest(array $data): array
    {
        $url = rtrim((string) $this->plugin->get(ExternalNotificationConnectPlugin::SETTING_NOTIFICATION_URL), '/')
            .'/visibility';

        return $this->sendNotificationRequest('POST', $url, $data);
    }

    /**
     * @throws Exception
     */
    protected function doDeleteRequest(int $contentId, string $contentType): array
    {
        $url = rtrim((string) $this->plugin->get(ExternalNotificationConnectPlugin::SETTING_NOTIFICATION_URL), '/')
            .'/'.$contentId.'/'.$contentType;

        return $this->sendNotificationRequest('DELETE', $url);
    }

    /**
     * @throws Exception
     */
    private function sendNotificationRequest(string $method, string $url, array $json = []): array
    {
        if ('' === trim($url)) {
            throw new Exception('Notification endpoint is not configured.');
        }

        try {
            $token = $this->plugin->getAccessToken();
        } catch (OptimisticLockException|ORMException|Exception $e) {
            throw new Exception($e->getMessage());
        }

        $options = [
            'connect_timeout' => ExternalNotificationConnectPlugin::HTTP_CONNECT_TIMEOUT,
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
            'http_errors' => false,
            'timeout' => ExternalNotificationConnectPlugin::HTTP_TIMEOUT,
        ];

        if ([] !== $json) {
            $options['json'] = $json;
        }

        $client = new Client();

        try {
            $response = $client->request($method, $url, $options);
        } catch (ClientException|ServerException $e) {
            if (!$e->hasResponse()) {
                throw new Exception($e->getMessage());
            }

            $response = $e->getResponse();
        } catch (GuzzleException $e) {
            throw new Exception($e->getMessage());
        }

        $responseJson = json_decode((string) $response->getBody(), true);

        if (!\is_array($responseJson)) {
            throw new Exception('Notification endpoint returned an invalid JSON response.');
        }

        if (isset($responseJson['status']) && 500 === (int) $responseJson['status']) {
            throw new Exception((string) ($responseJson['message'] ?? 'Notification endpoint returned an error.'));
        }

        if (!empty($responseJson['validation_errors'])) {
            $errors = \is_array($responseJson['errors'] ?? null) ? $responseJson['errors'] : [];
            $messages = array_filter(array_map(
                static fn ($error): string => \is_array($error) ? (string) ($error['message'] ?? '') : '',
                $errors
            ));

            throw new Exception([] === $messages ? 'Notification endpoint returned validation errors.' : implode('<br>', $messages));
        }

        return $responseJson;
    }
}
