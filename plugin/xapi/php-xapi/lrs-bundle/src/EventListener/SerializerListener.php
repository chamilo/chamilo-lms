<?php

namespace XApi\LrsBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as BaseSerializerException;
use Xabbuh\XApi\Serializer\StatementSerializerInterface;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class SerializerListener
{
    private $statementSerializer;

    public function __construct(StatementSerializerInterface $statementSerializer)
    {
        $this->statementSerializer = $statementSerializer;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->attributes->has('xapi_lrs.route')) {
            return;
        }

        try {
            switch ($request->attributes->get('xapi_serializer')) {
                case 'statement':
                    $request->attributes->set('statement', $this->statementSerializer->deserializeStatement($request->getContent()));
                    break;
            }
        } catch (BaseSerializerException $e) {
            throw new BadRequestHttpException(sprintf('The content of the request cannot be deserialized into a valid xAPI %s.', $request->attributes->get('xapi_serializer')), $e);
        }
    }
}
