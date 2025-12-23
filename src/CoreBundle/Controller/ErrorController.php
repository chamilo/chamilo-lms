<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ErrorController
{
    public function __construct(
        private readonly Environment $twig,
        #[Autowire(env: 'APP_ENV')]
        private readonly string $environment,
        #[Autowire(service: 'error_renderer')]
        private readonly ErrorRendererInterface $errorRenderer,
    ) {}

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function show(
        Request $request,
        Throwable $exception,
        ?DebugLoggerInterface $logger = null,
    ): Response {
        $statusCode = 500;

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        if (\in_array($this->environment, ['dev', 'test'])) {
            $exception = $this->errorRenderer->render($exception);

            $content = $exception->getAsString();
            $headers = $exception->getHeaders();
        } else {
            $format = $request->getPreferredFormat();

            if ('html' === $format) {
                $content = $this->twig->render(
                    '@ChamiloCore/Layout/no_layout.html.twig',
                    [
                        'exception' => $exception,
                        'content' => '',
                    ]
                );
            } else {
                $exception = $this->errorRenderer->render($exception);

                $content = $exception->getAsString();
            }

            $headers = [];
        }

        return new Response($content, $statusCode, $headers);
    }
}
