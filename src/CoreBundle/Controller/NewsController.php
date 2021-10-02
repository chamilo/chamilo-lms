<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Repository\SysAnnouncementRepository;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author Julio Montoya <gugli100@gmail.com>.
 */
#[Route('/news')]
class NewsController extends BaseController
{
    use ControllerTrait;

    #[Route('/list', name: 'news_index', methods: ['GET'])]
    public function indexAction(SysAnnouncementRepository $sysAnnouncementRepository): Response
    {
        $user = $this->getUser();

        $list = [];
        if (null !== $user) {
            $list = $sysAnnouncementRepository->getAnnouncements(
                $user,
                $this->getAccessUrl(),
                $this->getRequest()->getLocale()
            );
        }

        return new JsonResponse($list);
    }
}
