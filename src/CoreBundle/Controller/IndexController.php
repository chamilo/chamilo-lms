<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class IndexController.
 */
class IndexController extends BaseController
{
    /**
     * Index home page.
     *
     * @Route("/", name="home", methods={"GET", "POST"}, options={"expose"=true})
     * @Route("/login", name="login", methods={"GET", "POST"}, options={"expose"=true})
     * @Route("/courses", name="courses", methods={"GET", "POST"}, options={"expose"=true})
     * @Route("/sessions", name="sessions", methods={"GET", "POST"}, options={"expose"=true})
     */
    public function indexAction(): Response
    {
        return $this->render('@ChamiloCore/Index/vue.html.twig');
    }

    /**
     * Toggle the student view action.
     *
     * @Route("/toggle_student_view", methods={"GET"})
     *
     * @Security("has_role('ROLE_TEACHER')")
     */
    public function toggleStudentViewAction(Request $request): Response
    {
        if (!api_is_allowed_to_edit(false, false, false, false)) {
            return '';
        }
        $studentView = $request->getSession()->get('studentview');
        if (empty($studentView) || 'studentview' === $studentView) {
            $request->getSession()->set('studentview', 'teacherview');

            return 'teacherview';
        } else {
            $request->getSession()->set('studentview', 'studentview');

            return 'studentview';
        }
    }
}
