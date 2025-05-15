<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Chamilo\CoreBundle\Entity\XApiLrsAuth;
use Database;
use Exception;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Xabbuh\XApi\Common\Exception\AccessDeniedException;
use Xabbuh\XApi\Common\Exception\XApiException;

/**
 * Class LrsRequest.
 */
class LrsRequest
{
    /**
     * @var HttpRequest
     */
    private $request;

    public function __construct()
    {
        $this->request = HttpRequest::createFromGlobals();
    }

    public function send(): void
    {
        try {
            $this->alternateRequestSyntax();

            $controllerName = $this->getControllerName();
            $methodName = $this->getMethodName();

            $response = $this->generateResponse($controllerName, $methodName);
        } catch (XApiException $xApiException) {
            $response = HttpResponse::create('', HttpResponse::HTTP_BAD_REQUEST);
        } catch (HttpException $httpException) {
            $response = HttpResponse::create(
                $httpException->getMessage(),
                $httpException->getStatusCode()
            );
        } catch (Exception $exception) {
            $response = HttpResponse::create($exception->getMessage(), HttpResponse::HTTP_BAD_REQUEST);
        }

        $response->headers->set('X-Experience-API-Version', '1.0.3');

        $response->send();
    }

    /**
     * @throws AccessDeniedException
     */
    private function validateAuth(): bool
    {
        if (!$this->request->headers->has('Authorization')) {
            throw new AccessDeniedException();
        }

        $authHeader = $this->request->headers->get('Authorization');

        $parts = explode('Basic ', $authHeader, 2);

        if (empty($parts[1])) {
            throw new AccessDeniedException();
        }

        $authDecoded = base64_decode($parts[1]);

        $parts = explode(':', $authDecoded, 2);

        if (empty($parts) || 2 !== \count($parts)) {
            throw new AccessDeniedException();
        }

        list($username, $password) = $parts;

        $auth = Database::getManager()
            ->getRepository(XApiLrsAuth::class)
            ->findOneBy(
                ['username' => $username, 'password' => $password, 'enabled' => true]
            )
        ;

        if (null == $auth) {
            throw new AccessDeniedException();
        }

        return true;
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

    private function getControllerName(): ?string
    {
        $segments = explode('/', $this->request->getPathInfo());
        $segments = array_filter($segments);
        $segments = array_values($segments);

        if (empty($segments)) {
            throw new BadRequestHttpException('Bad request');
        }

        $segments = array_map('ucfirst', $segments);
        $controllerName = implode('', $segments).'Controller';

        return "Chamilo\\PluginBundle\\XApi\\Lrs\\$controllerName";
    }

    private function getMethodName(): string
    {
        $method = $this->request->getMethod();

        return strtolower($method);
    }

    /**
     * @throws AccessDeniedException
     */
    private function generateResponse(string $controllerName, string $methodName): HttpResponse
    {
        if (!class_exists($controllerName)
            || !method_exists($controllerName, $methodName)
        ) {
            throw new NotFoundHttpException();
        }

        if (AboutController::class !== $controllerName) {
            $this->validateAuth();
            $this->validateVersion();
        }

        /** @var HttpResponse $response */
        return \call_user_func(
            [
                new $controllerName($this->request),
                $methodName,
            ]
        );
    }

    private function alternateRequestSyntax(): void
    {
        if ('POST' !== $this->request->getMethod()) {
            return;
        }

        if (null === $method = $this->request->query->get('method')) {
            return;
        }

        if ($this->request->query->count() > 1) {
            throw new BadRequestHttpException('Including other query parameters than "method" is not allowed. You have to send them as POST parameters inside the request body.');
        }

        $this->request->setMethod($method);
        $this->request->query->remove('method');

        if (null !== $content = $this->request->request->get('content')) {
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

        foreach ($this->request->request as $key => $value) {
            if (\in_array($key, $headerNames, true)) {
                $this->request->headers->set($key, $value);
            } else {
                $this->request->query->set($key, $value);
            }

            $this->request->request->remove($key);
        }
    }
}
