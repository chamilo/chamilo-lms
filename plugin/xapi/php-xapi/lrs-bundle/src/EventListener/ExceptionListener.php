<?php

namespace XApi\LrsBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Converts Experience API specific domain exceptions into proper HTTP responses.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class ExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
    }
}
