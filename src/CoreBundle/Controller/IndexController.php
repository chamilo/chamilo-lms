<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

//use Chamilo\CoreBundle\Admin\CourseAdmin;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Framework\PageController;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Annotations\Annotation\Attribute;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Attribute\Model\AttributeValue;
use Sylius\Component\Attribute\Model\AttributeValueInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Chamilo\CoreBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Finder\Finder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class IndexController
 * author Julio Montoya <gugli100@gmail.com>
 * @package Chamilo\CoreBundle\Controller
 */
class IndexController extends BaseController
{
    /**
     * The Chamilo index home page
     * @Route("/", name="home")
     * @Method({"GET"})
     *
     * @param string $type courses|sessions|mycoursecategories
     * @param string $filter for the userportal courses page. Only works when setting 'history'
     * @param int $page
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        /** @var \PageController $pageController */
        //$pageController = $this->get('page_controller');
        $pageController = new PageController();
        /*
                if (api_get_setting('display_categories_on_homepage') == 'true') {
                    //$template->assign('course_category_block', $pageController->return_courses_in_categories());
                }
        
                if (!api_is_anonymous()) {
                    if (api_is_platform_admin()) {
                        $pageController->setCourseBlock();
                    } else {
                        $pageController->return_teacher_link();
                    }
                }
        
                // Hot courses & announcements
                $hotCourses         = null;
                $announcementsBlock = null;
        
                // Navigation links
                //$pageController->returnNavigationLinks($template->getNavigationLinks());
                $pageController->returnNotice();
                $pageController->returnHelp();
        
                if (api_is_platform_admin() || api_is_drh()) {
                    $pageController->returnSkillsLinks();
                }*/

        $sessionHandler = $request->getSession();
        $sessionHandler->remove('coursesAlreadyVisited');

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
        $this->get('session')->remove('id_session');

        return $this->render(
            '@ChamiloCore/Index/index.html.twig',
            [
                'content' => '',
                'announcements_block' => $announcementsBlock
                //'home_page_block' => $pageController->returnHomePage()
            ]
        );
    }

    /**
     * Toggle the student view action
     *
     * @Route("/toggle_student_view")
     * @Security("has_role('ROLE_TEACHER')")
     * @Method({"GET"})
     * @param Request $request
     *
     * @return Response
     */
    public function toggleStudentViewAction(Request $request)
    {
        if (!api_is_allowed_to_edit(false, false, false, false)) {
            return '';
        }
        $studentView = $request->getSession()->get('studentview');
        if (empty($studentView) || $studentView == 'studentview') {
            $request->getSession()->set('studentview', 'teacherview');
            return 'teacherview';
        } else {
            $request->getSession()->set('studentview', 'studentview');
            return 'studentview';
        }
    }
}
