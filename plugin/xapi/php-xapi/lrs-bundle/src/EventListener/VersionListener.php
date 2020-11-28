<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\LrsBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Jérôme Parmentier <jerome.parmentier@acensi.fr>
 */
class VersionListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->attributes->has('xapi_lrs.route')) {
            return;
        }

        if (null === $version = $request->headers->get('X-Experience-API-Version')) {
            throw new BadRequestHttpException('Missing required "X-Experience-API-Version" header.');
        }

        if (preg_match('/^1\.0(?:\.\d+)?$/', $version)) {
            if ('1.0' === $version) {
                $request->headers->set('X-Experience-API-Version', '1.0.0');
            }

            return;
        }

        throw new BadRequestHttpException(sprintf('xAPI version "%s" is not supported.', $version));
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$event->getRequest()->attributes->has('xapi_lrs.route')) {
            return;
        }

        $headers = $event->getResponse()->headers;

        if (!$headers->has('X-Experience-API-Version')) {
            $headers->set('X-Experience-API-Version', '1.0.3');
        }
    }
}
