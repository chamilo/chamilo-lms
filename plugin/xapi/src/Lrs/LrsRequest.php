<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Chamilo\PluginBundle\Entity\XApi\LrsAuth;
use Database;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Xabbuh\XApi\Common\Exception\AccessDeniedException;
use Xabbuh\XApi\Common\Exception\XApiException;

/**
 * Class LrsRequest.
 *
 * @package Chamilo\PluginBundle\XApi\Lrs
 */
class LrsRequest
{
    /**
     * @var HttpRequest
     */
    private $request;

    /**
     * LrsRequest constructor.
     */
    public function __construct()
    {
        $this->request = HttpRequest::createFromGlobals();
    }

    public function send()
    {
        $controllerName = $this->getControllerName();
        $methodName = $this->getMethodName();

        try {
            $response = $this->generateResponse($controllerName, $methodName);
        } catch (XApiException $xApiException) {
            $response = HttpResponse::create('', HttpResponse::HTTP_BAD_REQUEST);
        } catch (HttpException $httpException) {
            $response = HttpResponse::create(
                $httpException->getMessage(),
                $httpException->getStatusCode()
            );
        } catch (\Exception $exception) {
            $response = HttpResponse::create('', HttpResponse::HTTP_BAD_REQUEST);
        }

        $response->headers->set('X-Experience-API-Version', '1.0.3');

        $response->send();
    }

    /**
     * @throws \Xabbuh\XApi\Common\Exception\AccessDeniedException
     *
     * @return bool
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

        if (empty($parts) || count($parts) !== 2) {
            throw new AccessDeniedException();
        }

        list($username, $password) = $parts;

        $auth = Database::getManager()
            ->getRepository(LrsAuth::class)
            ->findOneBy(
                ['username' => $username, 'password' => $password, 'enabled' => true]
            );

        if (null == $auth) {
            throw new AccessDeniedException();
        }

        return true;
    }

    private function validateVersion()
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
            return null;
        }

        $segments = array_map('ucfirst', $segments);
        $controllerName = implode('', $segments).'Controller';

        return "Chamilo\\PluginBundle\\XApi\Lrs\\$controllerName";
    }

    private function getMethodName(): string
    {
        $method = $this->request->getMethod();

        return strtolower($method);
    }

    /**
     * @param string $controllerName
     * @param string $methodName
     *
     * @throws \Xabbuh\XApi\Common\Exception\AccessDeniedException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function generateResponse(string $controllerName, string $methodName): HttpResponse
    {
        if (!class_exists($controllerName)
            || !method_exists($controllerName, $methodName)
        ) {
            throw new NotFoundHttpException();
        }

        if ($controllerName !== AboutController::class) {
            $this->validateAuth();
            $this->validateVersion();
        }

        /** @var HttpResponse $response */
        $response = call_user_func([new $controllerName(), $methodName]);

        return $response;
    }
}
