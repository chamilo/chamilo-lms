<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Traits\ControllerTrait;
use Display;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use SystemAnnouncementManager;

/**
 * Class IndexController
 * author Julio Montoya <gugli100@gmail.com>.
 *
 * @Route("/news")
 */
class NewsController extends BaseController
{
    use ControllerTrait;

    /**
     * @Route("/", name="news_index", methods={"GET"}, options={"expose"=true})
     */
    public function indexAction(): Response
    {
        $toolBar = '';
        if ($this->isGranted('ROLE_ADMIN')) {
            $actionEdit = Display::url(
                Display::return_icon('edit.png', $this->trans('EditSystemAnnouncement'), [], ICON_SIZE_MEDIUM),
                api_get_path(WEB_PATH).'main/admin/system_announcements.php'
            );
            $toolBar = Display::toolbarAction('toolbar', [$actionEdit]);
        }

        return $this->render(
            '@ChamiloCore/News/index.html.twig',
            [
                'toolbar' => $toolBar,
            ]
        );
    }

    /**
     * @Route("/{id}", name="news", methods={"GET", "POST"}, options={"expose"=true})
     */
    public function newsAction(int $id = 0): Response
    {
        $visibility = SystemAnnouncementManager::getCurrentUserVisibility();

        if (empty($id)) {
            $content = SystemAnnouncementManager::getAnnouncements($visibility);

            return $this->render(
                '@ChamiloCore/News/slider.html.twig',
                [
                    'announcements' => $content,
                ]
            );
        }
        $content = SystemAnnouncementManager::getAnnouncement($id, $visibility);

        return $this->render(
            '@ChamiloCore/News/view.html.twig',
            [
                'announcement' => $content,
            ]
        );
    }
}
