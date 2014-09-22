<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chamilo\SettingsBundle\Controller;

use Sylius\Bundle\SettingsBundle\Controller\SettingsController as SyliusSettingsController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Settings controller.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
class SettingsController extends SyliusSettingsController
{
    /**
     * Edit configuration with given namespace.
     *
     * @param Request $request
     * @param string  $namespace
     *
     * @return Response
     */
    public function updateAction(Request $request, $namespace)
    {
        $manager = $this->getSettingsManager();
        $settings = $manager->loadSettings($namespace);

        $form = $this
            ->getSettingsFormFactory()
            ->create($namespace)
        ;

        $form->setData($settings);

        if ($form->handleRequest($request)->isValid()) {
            $messageType = 'success';
            try {
                $manager->saveSettings($namespace, $form->getData());
                $message = $this->getTranslator()->trans('sylius.settings.update', array(), 'flashes');
            } catch (ValidatorException $exception) {
                $message = $this->getTranslator()->trans($exception->getMessage(), array(), 'validators');
                $messageType = 'error';
            }
            $request->getSession()->getBag('flashes')->add($messageType, $message);

            if ($request->headers->has('referer')) {
                return $this->redirect($request->headers->get('referer'));
            }
        }

        return $this->render(
            $request->attributes->get('template', 'SyliusSettingsBundle:Settings:update.html.twig'), array(
            'settings' => $settings,
            'form'     => $form->createView()
            ));
    }
}
