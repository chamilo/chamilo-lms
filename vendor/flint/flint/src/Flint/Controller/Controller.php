<?php

namespace Flint\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @package Flint
 */
abstract class Controller extends \Flint\PimpleAware
{
    /**
     * Creates a normal response with the given text and statusCode
     *
     * @param  string   $text
     * @param  integer  $statusCode
     * @param  array    $headers
     * @return Response
     */
    public function text($text, $statusCode = 200, array $headers = array())
    {
        return new Response($text, $statusCode, $headers);
    }

    /**
     * @see Symfony\Component\Routing\RouterInterface::generate()
     */
    public function generateUrl($name, array $parameters = array(), $reference = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->pimple['router']->generate($name, $parameters, $reference);
    }

    /**
     * @see Twig_Environment::render();
     */
    public function render($name, array $context = array())
    {
        return $this->pimple['twig']->render($name, $context);
    }

    /**
     * @see Silex\Application::redirect()
     */
    public function redirect($url, $statusCode = 302)
    {
        return $this->pimple->redirect($url, $statusCode);
    }

    /**
     * @see Silex\Application::abort()
     */
    public function abort($statusCode, $message = '', array $headers = array())
    {
        return $this->pimple->abort($statusCode, $message, $headers);
    }

    /**
     * @param  string  $id
     * @return boolean
     */
    public function has($id)
    {
        return isset($this->pimple[$id]);
    }

    /**
     * @param  string $id
     * @return mixed
     */
    public function get($id)
    {
        return $this->pimple[$id];
    }
}
