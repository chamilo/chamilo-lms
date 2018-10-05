<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class IndexController
 * author Julio Montoya <gugli100@gmail.com>.
 *
 * @Route("/news")
 *
 * @package Chamilo\CoreBundle\Controller
 */
class NewsController extends BaseController
{
    /**
     * @Route("/", name="news_index", methods={"GET"}, options={"expose"=true})
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        $toolBar = '';
        if ($this->isGranted('ROLE_ADMIN')) {
            $actionEdit = \Display::url(
                \Display::return_icon('edit.png', $this->trans('EditSystemAnnouncement'), [], ICON_SIZE_MEDIUM),
                api_get_path(WEB_PATH).'main/admin/system_announcements.php'
            );
            $toolBar = \Display::toolbarAction('toolbar', [$actionEdit]);
        }

        return $this->render(
            '@ChamiloTheme/News/index.html.twig',
            [
                'toolbar' => $toolBar,
            ]
        );
    }

    /**
     * @Route("/{id}", name="news", methods={"GET", "POST"}, options={"expose"=true})
     *
     * @param int $id
     *
     * @return Response
     */
    public function newsAction($id = 0): Response
    {
        $visibility = \SystemAnnouncementManager::getCurrentUserVisibility();

        if (empty($id)) {
            $content = \SystemAnnouncementManager::getAnnouncements($visibility);

            return $this->render(
                '@ChamiloTheme/News/slider.html.twig',
                [
                    'announcements' => $content,
                ]
            );
        } else {
            $content = \SystemAnnouncementManager::getAnnouncement($id, $visibility);

            return $this->render(
                '@ChamiloTheme/News/view.html.twig',
                [
                    'announcement' => $content,
                ]
            );
        }
    }
}
