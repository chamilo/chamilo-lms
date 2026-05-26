<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt. */

namespace Chamilo\CoreBundle\Controller\Plugin;

use PensProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/plugin/pens', name: 'chamilo_core_plugin_pens_')]
final class PensController extends AbstractController
{
    #[Route('/collect', name: 'collect', methods: ['POST'])]
    public function collect(Request $request): Response
    {
        require_once __DIR__.'/../../../../public/plugin/Pens/lib/PensProcessor.php';

        try {
            $payload = $request->request->all();

            if (empty($payload)) {
                parse_str((string) $request->getContent(), $payload);
            }

            $processor = new PensProcessor();
            $content = $processor->handle($payload);
        } catch (Throwable $exception) {
            error_log('[Pens][collect] '.$exception->getMessage());

            $content = "error=1432\n";
            $content .= "error-text=Internal package error\n";
            $content .= "version=1.0.0\n";
            $content .= "pens-data=\n";
        }

        return new Response(
            $content,
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]
        );
    }
}
