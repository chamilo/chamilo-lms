<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Framework\PageController;
use Chamilo\PageBundle\Entity\Block;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * Class IndexController
 * author Julio Montoya <gugli100@gmail.com>.
 * @Route("/news")
 *
 * @package Chamilo\CoreBundle\Controller
 */
class NewsController extends BaseController
{
    /**
     * The Chamilo index home page.
     *
     * @Route("/", name="news_index", methods={"GET", "POST"}, options={"expose"=true})
     *
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $toolBar = '';
        if ($this->isGranted('ROLE_ADMIN')) {
            $actionEdit = \Display::url(
                \Display::return_icon('edit.png', get_lang('EditSystemAnnouncement'), [], ICON_SIZE_MEDIUM),
                api_get_path(WEB_PATH).'main/admin/system_announcements.php'
            );
            $toolBar = \Display::toolbarAction('toolbar', [$actionEdit]);
        }

        return $this->render(
            '@ChamiloCore/News/index.html.twig',
            [
                'toolbar' => $toolBar
            ]
        );
    }

    /**
     * The Chamilo index home page.
     *
     * @Route("/{id}", name="news", methods={"GET", "POST"}, options={"expose"=true})
     *
     * @return Response
     */
    public function newsAction($id = null)
    {
        $visibility = \SystemAnnouncementManager::getCurrentUserVisibility();

        $toolBar = '';
        if (empty($id)) {
            $content = \SystemAnnouncementManager::getAnnouncements($visibility);

            return $this->render(
                '@ChamiloCore/News/slider.html.twig',
                [
                    'announcements' => $content
                ]
            );
        } else {
            $content = \SystemAnnouncementManager::getAnnouncement($id, $visibility);

            return $this->render(
                '@ChamiloCore/News/view.html.twig',
                [
                    'announcement' => $content
                ]
            );
        }
    }
}
