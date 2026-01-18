<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\PushSubscriptionRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[Route('/push-notifications')]
class PushNotificationController extends AbstractController
{
    public function __construct(
        private readonly PushSubscriptionRepository $subscriptionRepository,
        private readonly SettingsManager $settingsManager,
        private readonly UserHelper $userHelper,
        private readonly TranslatorInterface $translator
    ) {}

    #[Route('/send/{userId}', name: 'chamilo_core_push_notification_send', methods: ['GET'])]
    public function send(int $userId, UserRepository $userRepository, Request $request): JsonResponse
    {
        $currentUser = $this->userHelper->getCurrent();

        if (!$currentUser) {
            return new JsonResponse([
                'error' => $this->translator->trans('Current user not found.'),
            ], 403);
        }

        // Check permission (example: only admins)
        if (!$currentUser->isAdmin()) {
            return new JsonResponse([
                'error' => $this->translator->trans('You do not have permission to send notifications to other users.'),
            ], 403);
        }

        // Find target user
        $user = $userRepository->find($userId);

        if (!$user) {
            return new JsonResponse([
                'error' => $this->translator->trans("This user doesn't exist"),
            ], 404);
        }

        $settings = $this->settingsManager->getSetting('platform.push_notification_settings', true);

        if (empty($settings)) {
            return new JsonResponse([
                'error' => $this->translator->trans('No push notification setting configured.'),
            ], 500);
        }

        $decoded = json_decode($settings, true);

        $vapidPublicKey = $decoded['vapid_public_key'] ?? null;
        $vapidPrivateKey = $decoded['vapid_private_key'] ?? null;

        if (!$vapidPublicKey || !$vapidPrivateKey) {
            return new JsonResponse([
                'error' => $this->translator->trans('VAPID keys are missing in the configuration.'),
            ], 500);
        }

        $subscriptions = $this->subscriptionRepository->findByUser($user);

        if (empty($subscriptions)) {
            return new JsonResponse([
                'error' => $this->translator->trans('No push subscriptions found for this user.'),
            ], 404);
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => 'mailto:'.$user->getEmail(),
                'publicKey' => $vapidPublicKey,
                'privateKey' => $vapidPrivateKey,
            ],
        ]);

        $successes = [];
        $failures = [];

        foreach ($subscriptions as $subEntity) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $subEntity->getEndpoint(),
                    'publicKey' => $subEntity->getPublicKey(),
                    'authToken' => $subEntity->getAuthToken(),
                    'contentEncoding' => $subEntity->getContentEncoding() ?? 'aesgcm',
                ]);

                $payload = json_encode([
                    'title' => $this->translator->trans('Push notification test'),
                    'message' => $this->translator->trans("This is a test push notification from this platform to the user's browser or app."),
                    'url' => '/account/edit',
                ]);

                $report = $webPush->sendOneNotification(
                    $subscription,
                    $payload
                );

                if ($report->isSuccess()) {
                    $successes[] = $subEntity->getEndpoint();
                } else {
                    $failures[] = [
                        'endpoint' => $subEntity->getEndpoint(),
                        'reason' => $report->getReason(),
                        'statusCode' => $report->getResponse()->getStatusCode(),
                    ];
                }
            } catch (Throwable $e) {
                $failures[] = [
                    'endpoint' => $subEntity->getEndpoint(),
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return new JsonResponse([
            'message' => $this->translator->trans('Push notifications have been processed.'),
            'success' => $successes,
            'failures' => $failures,
        ]);
    }

    #[Route('/send-gotify', name: '_core_push_notification_send_gotify')]
    public function sendGotify(): JsonResponse
    {
        $user = $this->userHelper->getCurrent();

        if (!$user) {
            return new JsonResponse([
                'error' => $this->translator->trans('User not found.'),
            ], 403);
        }

        $settings = $this->settingsManager->getSetting('platform.push_notification_settings', true);

        if (empty($settings)) {
            return new JsonResponse([
                'error' => $this->translator->trans('No push notification settings configured.'),
            ], 500);
        }

        $decoded = json_decode($settings, true);

        $gotifyUrl = $decoded['gotify_url'] ?? null;
        $gotifyToken = $decoded['gotify_token'] ?? null;

        if (!$gotifyUrl || !$gotifyToken) {
            return new JsonResponse([
                'error' => $this->translator->trans('Gotify configuration is missing.'),
            ], 500);
        }

        // Prepare the payload for Gotify
        $payload = [
            'title' => $user->getEmail(),
            'message' => $this->translator->trans('This is a test notification sent to Gotify from this platform.'),
            'priority' => 5,
        ];

        $client = HttpClient::create();

        try {
            $response = $client->request('POST', rtrim($gotifyUrl, '/').'/message', [
                'headers' => [
                    'X-Gotify-Key' => $gotifyToken,
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            return new JsonResponse([
                'message' => $this->translator->trans('Notification sent to Gotify.'),
                'status' => $statusCode,
                'response' => $content,
            ]);
        } catch (Throwable $e) {
            return new JsonResponse([
                'error' => $this->translator->trans('Error sending notification to Gotify: ').$e->getMessage(),
            ], 500);
        }
    }
}
