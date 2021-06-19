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
 * Class IndexController
 * author Julio Montoya <gugli100@gmail.com>.
 */
class NewsController extends BaseController
{
    use ControllerTrait;

    /**
     * @Route("/news/list", name="news_index", methods={"GET"}, options={"expose"=true})
     */
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
