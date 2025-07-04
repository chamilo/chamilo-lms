<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends BaseController
{
    #[Route('/', name: 'index', options: ['expose' => true], methods: ['GET', 'POST'])]
    #[Route('/sessions', name: 'sessions')]
    #[Route('/sessions/{extra}', name: 'sessions_options')]
    #[Route('/admin/{vueRouting}', name: 'admin_vue_entrypoint', requirements: ['vueRouting' => '.+'])]
    #[Route('/home', name: 'home', options: ['expose' => true], methods: ['GET', 'POST'])]
    #[Route('/login', name: 'login', options: ['expose' => true], methods: ['GET', 'POST'])]
    #[Route('/faq', name: 'faq', options: ['expose' => true], methods: ['GET', 'POST'])]
    #[Route('/demo', name: 'demo', options: ['expose' => true], methods: ['GET', 'POST'])]
    #[Route('/course/{cid}/home', name: 'chamilo_core_course_home')]
    #[Route('/courses', name: 'courses', options: ['expose' => true], methods: ['GET', 'POST'])]
    #[Route('/catalogue/{slug}', name: 'catalogue', options: ['expose' => true], methods: ['GET', 'POST'])]
    #[Route('/resources/ccalendarevent', name: 'resources_ccalendarevent', methods: ['GET'])]
    #[Route('/resources/document/{nodeId}/manager', name: 'resources_filemanager', methods: ['GET'])]
    #[Route('/account/home', name: 'chamilo_core_account_home', options: ['expose' => true])]
    #[Route('/social', name: 'chamilo_core_socialnetwork', options: ['expose' => true])]
    #[Route('/admin', name: 'admin', options: ['expose' => true])]
    #[Route('/admin-dashboard', name: 'admin_dashboard_entry', options: ['expose' => true])]
    #[Route('/admin-dashboard/{vueRouting}', name: 'admin_dashboard_vue_entry', requirements: ['vueRouting' => '.+'])]
    #[Route('/p/{slug}', name: 'public_page')]
    #[Route('/skill/wheel', name: 'skill_wheel')]
    public function index(): Response
    {
        return $this->render('@ChamiloCore/Layout/no_layout.html.twig', ['content' => '']);
    }

    /**
     * Use only in PHPUnit tests.
     *
     * @param mixed $name
     */
    public function classic($name): Response
    {
        if ('test' !== ($_SERVER['APP_ENV'] ?? '')) {
            exit;
        }

        $rootDir = $this->getParameter('kernel.project_dir');

        $mainPath = $rootDir.'/public/main/';
        $fileToLoad = $mainPath.$name;

        ob_start();

        require_once $fileToLoad;
        $content = ob_get_contents();
        ob_end_clean();

        return $this->render(
            '@ChamiloCore/Layout/layout_one_col.html.twig',
            ['content' => $content]
        );
    }

    /**
     * Toggle the student view action.
     */
    #[Route('/toggle_student_view', methods: ['GET'])]
    #[Security("is_granted('ROLE_TEACHER')")]
    public function toggleStudentView(Request $request, SettingsManager $settingsManager): Response
    {
        if ('true' !== $settingsManager->getSetting('course.student_view_enabled')) {
            throw $this->createAccessDeniedException();
        }

        $studentView = $request->getSession()->get('studentview');

        if (empty($studentView) || 'studentview' === $studentView) {
            $content = 'teacherview';
        } else {
            $content = 'studentview';
        }

        $request->getSession()->set('studentview', $content);

        return new Response($content);
    }
}
