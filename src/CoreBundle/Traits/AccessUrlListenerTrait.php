<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

trait AccessUrlListenerTrait
{
    protected ?AccessUrl $accessUrl = null;

    public function getAccessUrl(EntityManagerInterface $em, RequestStack $request): ?AccessUrl
    {
        if (null === $this->accessUrl) {
            $request = $request->getCurrentRequest();
            if (null === $request) {
                return null;
            }

            $sessionRequest = $request->getSession();
            $id = (int) $sessionRequest->get('access_url_id');
            if (0 !== $id) {
                /** @var AccessUrl $url */
                $url = $em->getRepository(AccessUrl::class)->find($id);

                if (null !== $url) {
                    $this->accessUrl = $url;

                    return $url;
                }
            }

            return null;
        }

        return $this->accessUrl;
    }
}
