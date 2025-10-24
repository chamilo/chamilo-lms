<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Helpers\ThemeHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExceptionController extends AbstractController
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private AccessUrlRepository $accessUrlRepository,
        private ThemeHelper $themeHelper,
    ) {}

    public function show(Exception $exception): Response
    {
        // In dev, rethrow the exception so Symfony shows the debug page
        if ('dev' === (string) $this->getParameter('app_env')) {
            throw new HttpException($exception->getCode(), $exception->getMessage());
        }

        // Resolve best template to render for this exception
        $name = 'exception';
        $code = $exception->getCode();
        $format = 'html';
        $loader = $this->container->get('twig')->getLoader();
        $templateToLoad = \sprintf('@ChamiloCore/Exception/%s.html.twig', 'exception_full');

        $candidate = \sprintf('@ChamiloCore/Exception/%s%s.%s.twig', $name, $code, $format);
        if ($loader->exists($candidate)) {
            $templateToLoad = $candidate;
        }

        $candidate = \sprintf('@ChamiloCore/Exception/%s.%s.twig', $name, $format);
        if ($loader->exists($candidate)) {
            $templateToLoad = $candidate;
        }

        return $this->render($templateToLoad, [
            'exception' => $exception,
        ]);
    }

    #[Route(path: '/error')]
    public function error(Request $request): Response
    {
        // Render a generic 500 with an optional message from session
        $message = $request->getSession()->get('error_message', '');
        $exception = new FlattenException();
        $exception->setCode(500);

        $exception->setMessage($message);

        // Resolve best template to render for this exception
        $name = 'exception';
        $code = $exception->getCode();
        $format = 'html';
        $loader = $this->container->get('twig')->getLoader();
        $templateToLoad = \sprintf('@ChamiloCore/Exception/%s.html.twig', 'exception_full');

        $candidate = \sprintf('@ChamiloCore/Exception/%s%s.%s.twig', $name, $code, $format);
        if ($loader->exists($candidate)) {
            $templateToLoad = $candidate;
        }

        $candidate = \sprintf('@ChamiloCore/Exception/%s.%s.twig', $name, $format);
        if ($loader->exists($candidate)) {
            $templateToLoad = $candidate;
        }

        return $this->render($templateToLoad, [
            'exception' => $exception,
        ]);
    }

    #[Route(path: '/error/undefined-url', name: 'undefined_url_error')]
    public function undefinedUrlError(Request $request): Response
    {
        $host = $request->getHost();

        // Try to detect a valid AccessUrl (the first active one as fallback)
        $accessUrl = $this->accessUrlRepository->findOneBy(['active' => 1], ['id' => 'ASC']);

        // Base host to use (fallback to AccessUrl[1] if current host is unknown)
        $baseHost = rtrim($accessUrl?->getUrl() ?? '', '/');

        // Resolve theme-aware asset URLs
        $cssUrl = $this->themeHelper->getThemeAssetUrl('colors.css', true); // still theme-aware
        $logoUrl = $this->themeHelper->getPreferredLogoUrl('header', true);  // absolute but invalid host may persist

        // Fix: if the generated logo URL contains the invalid host, rebuild it with main AccessUrl
        if (str_contains($logoUrl, $host) && !empty($baseHost)) {
            $parsed = parse_url($logoUrl);
            $path = $parsed['path'] ?? '';
            $logoUrl = $baseHost.$path;
        }

        return $this->render('@ChamiloCore/Exception/undefined_url.html.twig', [
            'host' => $host,
            'cssUrl' => $cssUrl,
            'logoUrl' => $logoUrl,
        ]);
    }
}
