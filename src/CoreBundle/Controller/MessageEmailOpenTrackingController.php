<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Service\Message\MessageEmailOpenTrackingService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MessageEmailOpenTrackingController
{
    private const string TRANSPARENT_GIF_BASE64 = 'R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

    #[Route(
        '/mail/open/{token}.gif',
        name: 'chamilo_core_message_email_open_tracking',
        requirements: ['token' => '[a-f0-9]{64}'],
        methods: ['GET'],
    )]
    public function __invoke(
        string $token,
        MessageEmailOpenTrackingService $trackingService,
    ): Response {
        $trackingService->registerOpen($token);

        $gif = base64_decode(self::TRANSPARENT_GIF_BASE64, true);
        if (false === $gif) {
            $gif = '';
        }

        return new Response(
            $gif,
            Response::HTTP_OK,
            [
                'Content-Type' => 'image/gif',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, private',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
