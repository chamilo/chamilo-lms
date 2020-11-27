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

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Jérôme Parmentier <jerome.parmentier@acensi.fr>
 */
class AlternateRequestSyntaxListener
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

        if ('POST' !== $request->getMethod()) {
            return;
        }

        if (null === $method = $request->query->get('method')) {
            return;
        }

        if ($request->query->count() > 1) {
            throw new BadRequestHttpException('Including other query parameters than "method" is not allowed. You have to send them as POST parameters inside the request body.');
        }

        $request->setMethod($method);
        $request->query->remove('method');

        if (null !== $content = $request->request->get('content')) {
            $request->request->remove('content');

            $request->initialize(
                $request->query->all(),
                $request->request->all(),
                $request->attributes->all(),
                $request->cookies->all(),
                $request->files->all(),
                $request->server->all(),
                $content
            );
        }

        foreach ($request->request as $key => $value) {
            if (in_array($key, ['Authorization', 'X-Experience-API-Version', 'Content-Type', 'Content-Length', 'If-Match', 'If-None-Match'], true)) {
                $request->headers->set($key, $value);
            } else {
                $request->query->set($key, $value);
            }

            $request->request->remove($key);
        }
    }
}
