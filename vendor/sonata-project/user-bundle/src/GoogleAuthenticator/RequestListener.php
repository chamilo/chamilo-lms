<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\GoogleAuthenticator;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\SecurityContextInterface;

class RequestListener
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var TokenStorageInterface|SecurityContextInterface
     */
    protected $tokenStorage;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * NEXT_MAJOR: Go back to type hinting check when bumping requirements to SF 2.6+.
     *
     * @param Helper                                         $helper
     * @param TokenStorageInterface|SecurityContextInterface $tokenStorage
     * @param EngineInterface                                $templating
     */
    public function __construct(Helper $helper, $tokenStorage, EngineInterface $templating)
    {
        if (!$tokenStorage instanceof TokenStorageInterface && !$tokenStorage instanceof SecurityContextInterface) {
            throw new \InvalidArgumentException(
                'Argument 2 should be an instance of Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface or Symfony\Component\Security\Core\SecurityContextInterface'
            );
        }

        $this->helper = $helper;
        $this->tokenStorage = $tokenStorage;
        $this->templating = $templating;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onCoreRequest(GetResponseEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return;
        }

        if (!$token instanceof UsernamePasswordToken) {
            return;
        }

        $key = $this->helper->getSessionKey($token);
        $request = $event->getRequest();
        $session = $event->getRequest()->getSession();
        $user = $token->getUser();

        if (!$session->has($key)) {
            return;
        }

        if (true === $session->get($key)) {
            return;
        }

        $state = 'init';
        if ('POST' == $request->getMethod()) {
            if (true == $this->helper->checkCode($user, $request->get('_code'))) {
                $session->set($key, true);

                return;
            }

            $state = 'error';
        }

        $event->setResponse($this->templating->renderResponse('SonataUserBundle:Admin:Security/two_step_form.html.twig', [
            'state' => $state,
         ]));
    }
}
