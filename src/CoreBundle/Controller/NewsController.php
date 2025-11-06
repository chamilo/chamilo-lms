<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\SysAnnouncementRepository;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author Julio Montoya <gugli100@gmail.com>.
 */
#[Route('/news')]
class NewsController extends BaseController
{
    use ControllerTrait;

    public function __construct(
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly UserHelper $userHelper,
    ) {}

    #[Route('/list', name: 'news_index', methods: ['GET'])]
    public function index(SysAnnouncementRepository $sysAnnouncementRepository): Response
    {
        $user = $this->userHelper->getCurrent();

        if (null === $user) {
            $anon = new User();
            $anon->setRoles(['ROLE_ANONYMOUS']);
            $user = $anon;
        }

        $list = $sysAnnouncementRepository->getAnnouncements(
            $user,
            $this->accessUrlHelper->getCurrent(),
            $this->getRequest()->getLocale()
        );

        return new JsonResponse($list);
    }
}
