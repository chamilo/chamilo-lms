<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\PushSubscriptionRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\HttpClient\HttpClient;

class PushNotificationHelper
{
    public function __construct(
        private readonly PushSubscriptionRepository $subscriptionRepository,
        private readonly SettingsManager $settingsManager
    ) {
    }

    public function sendNotification(User $user, string $title, string $message, string $url = '/'): void
    {
        $settings = $this->settingsManager->getSetting('platform.push_notification_settings');

        if (empty($settings)) {
            return;
        }

        $decoded = json_decode($settings, true);

        $gotifyUrl = $decoded['gotify_url'] ?? null;
        $gotifyToken = $decoded['gotify_token'] ?? null;
        $enabled = $decoded['enabled'] ?? false;

        if (!$enabled || empty($gotifyUrl) || empty($gotifyToken)) {
            return;
        }

        $subscriptions = $this->subscriptionRepository->findByUser($user);

        if (empty($subscriptions)) {
            return;
        }

        $client = HttpClient::create();

        $client->request('POST', $gotifyUrl.'/message', [
            'headers' => [
                'X-Gotify-Key' => $gotifyToken,
            ],
            'json' => [
                'title' => $title,
                'message' => $message,
                'priority' => 5,
                'extras' => [
                    'client::notification' => [
                        'click' => [
                            'url' => $url,
                        ],
                    ],
                ],
            ],
        ]);
    }
}
