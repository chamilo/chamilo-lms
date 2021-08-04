<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Chamilo\PluginBundle\Entity\XApi\LrsAuth;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        $this->validAuth();

        $version = $this->request->headers->get('X-Experience-API-Version');

        if (null === $version) {
            throw new BadRequestHttpException('The "X-Experience-API-Version" header is required.');
        }

        if (!$this->isValidVersion($version)) {
            throw new BadRequestHttpException("The xAPI version \"$version\" is not supported.");
        }

        $controllerName = $this->getControllerName();
        $methodName = $this->getMethodName();

        if ($controllerName
            && class_exists($controllerName)
            && method_exists($controllerName, $methodName)
        ) {
            /** @var HttpResponse $response */
            $response = call_user_func([new $controllerName(), $methodName]);
        } else {
            $response = HttpResponse::create('Not Found', HttpResponse::HTTP_NOT_FOUND);
        }

        $response->headers->set('X-Experience-API-Version', '1.0.3');

        $response->send();
    }

    /**
     * @return string|null
     */
    private function getControllerName()
    {
        $segments = explode('/', $this->request->getPathInfo());

        if (empty($segments[1])) {
            return null;
        }

        $controllerName = ucfirst($segments[1]).'Controller';

        return "Chamilo\\PluginBundle\\XApi\Lrs\\$controllerName";
    }

    /**
     * @return string
     */
    private function getMethodName()
    {
        $method = $this->request->getMethod();

        return strtolower($method);
    }

    /**
     * @param string $version
     *
     * @return bool
     */
    private function isValidVersion($version)
    {
        if (preg_match('/^1\.0(?:\.\d+)?$/', $version)) {
            if ('1.0' === $version) {
                $this->request->headers->set('X-Experience-API-Version', '1.0.0');
            }

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function validAuth()
    {
        if (!$this->request->headers->has('Authorization')) {
            throw new AccessDeniedHttpException();
        }

        $authHeader = $this->request->headers->get('Authorization');

        $parts = explode('Basic ', $authHeader, 2);

        if (empty($parts[1])) {
            throw new AccessDeniedHttpException();
        }

        $authDecoded = base64_decode($parts[1]);

        $parts = explode(':', $authDecoded, 2);

        if (empty($parts) || count($parts) !== 2) {
            throw new AccessDeniedHttpException();
        }

        list($username, $password) = $parts;

        $auth = \Database::getManager()
            ->getRepository(LrsAuth::class)
            ->findOneBy(
                ['username' => $username, 'password' => $password, 'enabled' => true]
            );

        if (null == $auth) {
            throw new AccessDeniedHttpException();
        }

        return true;
    }
}
