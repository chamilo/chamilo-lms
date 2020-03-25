<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

trait ControllerTrait
{
    /**
     * @return Request|null
     */
    public function getRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    /**
     * @param string $message
     *
     * @return NotFoundHttpException
     */
    public function abort($message = '')
    {
        return new NotFoundHttpException($message);
    }

    /**
     * Translator shortcut.
     *
     * @param string $variable
     *
     * @return string
     */
    public function trans($variable)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->container->get('translator');

        return $translator->trans($variable);
    }
}
