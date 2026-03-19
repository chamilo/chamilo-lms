<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Chamilo\CoreBundle\Entity\XApiLrsAuth;
use Chamilo\CoreBundle\Framework\Container;
use Database;
use Exception;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class LrsRequest.
 */
class LrsRequest
{
    private HttpRequest $request;

    public function __construct(?HttpRequest $request = null)
    {
        $this->request = $request ?? Container::getRequest();
    }

    public function send(): void
    {
        $this->handle()->send();
    }

    public function handle(?HttpRequest $request = null): HttpResponse
    {
        if (null !== $request) {
            $this->request = $request;
        }

        try {
            $this->alternateRequestSyntax();

            $controllerName = $this->getControllerName();
            $methodName = $this->getMethodName();

            $response = $this->generateResponse($controllerName, $methodName);
        } catch (HttpException $httpException) {
            $response = HttpResponse::create(
                $httpException->getMessage(),
                $httpException->getStatusCode()
            );
        } catch (Exception $exception) {
            $response = HttpResponse::create(
                $exception->getMessage(),
                HttpResponse::HTTP_BAD_REQUEST
            );
        }

        $response->headers->set('X-Experience-API-Version', '1.0.3');

        return $response;
    }

    private function validateAuth(): bool
    {
        if (!$this->request->headers->has('Authorization')) {
            throw new AccessDeniedHttpException('Missing Authorization header.');
        }

        $authHeader = trim((string) $this->request->headers->get('Authorization'));

        if (!str_starts_with($authHeader, 'Basic ')) {
            throw new AccessDeniedHttpException('Unsupported Authorization scheme.');
        }

        $basicValue = trim(substr($authHeader, 6));

        if ('' === $basicValue) {
            throw new AccessDeniedHttpException('Empty Authorization header.');
        }

        if ($this->isValidLegacyBasicAuth($basicValue)) {
            return true;
        }

        if ($this->isValidCmi5Token($basicValue)) {
            return true;
        }

        throw new AccessDeniedHttpException('Invalid xAPI authorization.');
    }

    private function isValidLegacyBasicAuth(string $basicValue): bool
    {
        $decoded = base64_decode($basicValue, true);

        if (false === $decoded) {
            return false;
        }

        $parts = explode(':', $decoded, 2);

        if (2 !== \count($parts)) {
            return false;
        }

        [$username, $password] = $parts;

        $auth = Database::getManager()
            ->getRepository(XApiLrsAuth::class)
            ->findOneBy(
                [
                    'username' => $username,
                    'password' => $password,
                    'enabled' => true,
                ]
            );

        return null !== $auth;
    }

    private function isValidCmi5Token(string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $storedTokens = $_SESSION['xapi_cmi5_tokens'] ?? [];
        $now = time();

        if (!\is_array($storedTokens)) {
            return false;
        }

        foreach ($storedTokens as $launchSessionId => $tokenData) {
            if (!\is_array($tokenData)) {
                continue;
            }

            $expiresAt = (int) ($tokenData['expires_at'] ?? 0);

            if ($expiresAt > 0 && $expiresAt < $now) {
                unset($_SESSION['xapi_cmi5_tokens'][$launchSessionId]);
                continue;
            }

            $storedToken = (string) ($tokenData['token'] ?? '');

            if ('' !== $storedToken && hash_equals($storedToken, $token)) {
                return true;
            }
        }

        return false;
    }

    private function validateVersion(): void
    {
        $version = $this->request->headers->get('X-Experience-API-Version');

        if (null === $version) {
            throw new BadRequestHttpException('The "X-Experience-API-Version" header is required.');
        }

        if (preg_match('/^1\.0(?:\.\d+)?$/', $version)) {
            if ('1.0' === $version) {
                $this->request->headers->set('X-Experience-API-Version', '1.0.0');
            }

            return;
        }

        throw new BadRequestHttpException("The xAPI version \"$version\" is not supported.");
    }

    private function getControllerName(): string
    {
        $segments = $this->getLrsSegments();

        if (empty($segments)) {
            throw new BadRequestHttpException('Bad request');
        }

        $segments = array_map(
            static function (string $segment): string {
                $segment = preg_replace('/[^a-z0-9]+/i', ' ', $segment) ?? $segment;
                $segment = trim($segment);

                return str_replace(' ', '', ucwords(strtolower($segment)));
            },
            $segments
        );

        $controllerName = implode('', $segments).'Controller';

        return "Chamilo\\PluginBundle\\XApi\\Lrs\\$controllerName";
    }

    private function getMethodName(): string
    {
        return strtolower($this->request->getMethod());
    }

    private function generateResponse(string $controllerName, string $methodName): HttpResponse
    {
        if (!class_exists($controllerName) || !method_exists($controllerName, $methodName)) {
            throw new NotFoundHttpException();
        }

        if (AboutController::class !== $controllerName) {
            $this->validateAuth();
            $this->validateVersion();
        }

        /** @var HttpResponse $response */
        $response = \call_user_func(
            [
                new $controllerName($this->request),
                $methodName,
            ]
        );

        return $response;
    }

    private function alternateRequestSyntax(): void
    {
        if ('POST' !== $this->request->getMethod()) {
            return;
        }

        $method = $this->request->query->get('method');

        if (null === $method) {
            return;
        }

        if ($this->request->query->count() > 1) {
            throw new BadRequestHttpException(
                'Including other query parameters than "method" is not allowed. You have to send them as POST parameters inside the request body.'
            );
        }

        $this->request->setMethod($method);
        $this->request->query->remove('method');

        $content = $this->request->request->get('content');
        if (null !== $content) {
            $this->request->request->remove('content');

            $this->request->initialize(
                $this->request->query->all(),
                $this->request->request->all(),
                $this->request->attributes->all(),
                $this->request->cookies->all(),
                $this->request->files->all(),
                $this->request->server->all(),
                $content
            );
        }

        $headerNames = [
            'Authorization',
            'X-Experience-API-Version',
            'Content-Type',
            'Content-Length',
            'If-Match',
            'If-None-Match',
        ];

        foreach ($this->request->request->all() as $key => $value) {
            if (\in_array($key, $headerNames, true)) {
                $this->request->headers->set($key, (string) $value);
            } else {
                $this->request->query->set($key, $value);
            }

            $this->request->request->remove($key);
        }
    }

    /**
     * @return string[]
     */
    private function getLrsSegments(): array
    {
        $segments = explode('/', $this->request->getPathInfo());
        $segments = array_values(array_filter($segments, static fn ($value): bool => '' !== trim((string) $value)));

        $markerIndex = array_search('lrs', $segments, true);

        if (false === $markerIndex) {
            $markerIndex = array_search('lrs.php', $segments, true);
        }

        if (false !== $markerIndex) {
            $segments = array_slice($segments, $markerIndex + 1);
        }

        return array_values(
            array_filter(
                $segments,
                static fn ($segment): bool => \is_string($segment) && '' !== trim($segment)
            )
        );
    }
}
