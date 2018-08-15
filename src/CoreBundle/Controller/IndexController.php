<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

//use Chamilo\CoreBundle\Admin\CourseAdmin;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Framework\PageController;
use Chamilo\PageBundle\Entity\Block;
use Chamilo\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sonata\PageBundle\Model\Page;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Attribute\Model\AttributeValueInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class IndexController
 * author Julio Montoya <gugli100@gmail.com>.
 *
 * @package Chamilo\CoreBundle\Controller
 */
class IndexController extends BaseController
{
    /**
     * The Chamilo index home page.
     *
     * @Route("/", name="home", options={"expose"=true})
     * @Method({"GET", "POST"})
     *
     * @param string $type   courses|sessions|mycoursecategories
     * @param string $filter for the userportal courses page. Only works when setting 'history'
     * @param int    $page
     *
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        /** @var \PageController $pageController */
        //$pageController = $this->get('page_controller');
        $pageController = new PageController();

        //$sessionHandler = $request->getSession();
        //$sessionHandler->remove('coursesAlreadyVisited');

        $user = $this->getUser();
        $userId = 0;
        if ($user) {
            $userId = $this->getUser()->getId();
        }
        $announcementsBlock = $pageController->getAnnouncements($userId);

        /** @var User $user */
        //$userManager = $this->container->get('fos_user.user_manager');
        //$user = $userManager->find(1);

        //$attribute = $this->container->get('doctrine')->getRepository('ChamiloCoreBundle:ExtraField')->find(1);
        /*
                $attribute = new ExtraField();
                $attribute->setName('size');
                $attribute->setVariable('size');
                $attribute->setType(TextAttributeType::TYPE);
                $attribute->setStorageType(AttributeValueInterface::STORAGE_TEXT);
                $this->getDoctrine()->getManager()->persist($attribute);
                $this->getDoctrine()->getManager()->flush();

                $attributeColor = new ExtraField();
                $attributeColor->setName('color');
                $attributeColor->setVariable('color');
                $attributeColor->setType(TextAttributeType::TYPE);
                $attributeColor->setStorageType(AttributeValueInterface::STORAGE_TEXT);
                $this->getDoctrine()->getManager()->persist($attributeColor);
                $this->getDoctrine()->getManager()->flush();

                $color = new ExtraFieldValues();
                $color->setComment('lol');
                $color->setAttribute($attributeColor);
                $color->setValue('blue');

                $user->addAttribute($color);

                $smallSize = new ExtraFieldValues();
                $smallSize->setComment('lol');
                $smallSize->setAttribute($attribute);
                $smallSize->setValue('S');

                $user->addAttribute($smallSize);
                $userManager->updateUser($user);
        */
        //$this->get('session')->remove('id_session');

        return $this->render(
            '@ChamiloCore/Index/index.html.twig',
            [
                'content' => '',
                'announcements_block' => $announcementsBlock,
                //'home_page_block' => $pageController->returnHomePage()
            ]
        );
    }

    /**
     * Toggle the student view action.
     *
     * @Route("/toggle_student_view")
     * @Security("has_role('ROLE_TEACHER')")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function toggleStudentViewAction(Request $request): Response
    {
        if (!api_is_allowed_to_edit(false, false, false, false)) {
            return '';
        }
        $studentView = $request->getSession()->get('studentview');
        if (empty($studentView) || $studentView === 'studentview') {
            $request->getSession()->set('studentview', 'teacherview');

            return 'teacherview';
        } else {
            $request->getSession()->set('studentview', 'studentview');

            return 'studentview';
        }
    }
}
