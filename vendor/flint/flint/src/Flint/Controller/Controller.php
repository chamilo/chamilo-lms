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
     * This will result in a 404 response code.
     * 
     * @param string $message
     * @return Exception
     */
    protected function createNotFoundException($message = 'Not Found')
    {
        return $this->abort(404, $message);
    }

    /**
     * @return Request
     */
    protected function getRequest()
    {
        return $this->get('request');
    }

    /**
     * Get a user from the Security Context
     *
     * @throws \LogicException
     * @return mixed
     */
    public function getUser()
    {
        if (!$this->has('security')) {
            throw new \LogicException('The SecurityServiceProvider is not registered in your application.');
        }

        if (null === $token = $this->get('security')->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param string|FormTypeInterface $type    The built type of the form
     * @param mixed                    $data    The initial data for the form
     * @param array                    $options Options for the form
     *
     * @return \Symfony\Component\Form\Form
     */
    public function createForm($type, $data = null, array $options = array())
    {
        return $this->get('form.factory')->create($type, $data, $options);
    }

    /**
     * Creates and returns a form builder instance
     *
     * @param mixed $data    The initial data for the form
     * @param array $options Options for the form
     * @return \Symfony\Component\Form\FormBuilder
     */
    public function createFormBuilder($data = null, array $options = array())
    {
        return $this->get('form.factory')->createBuilder('form', $data, $options);
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
