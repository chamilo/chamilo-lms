<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Silex\Application;
use \ChamiloSession as Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserPortalController
 * @package ChamiloLMS\Controller
 * @todo Improve this class in order to use better helpers and not just call the page_controller service
 * @author Julio Montoya <gugli100@gmail.com>
 */
class UserPortalController extends CommonController
{
    /**
     * @param Application $app
     * @param string $type courses|sessions|mycoursecategories
     * @param string $filter for the userportal courses page. Only works when setting 'history'
     * @param int $page
     *
     * @return Response|void
     */
    public function indexAction(Application $app, $type = 'courses', $filter = 'current', $page = 1)
    {
        // @todo Use filters like "after/before|finish" to manage user access
        api_block_anonymous_users();


        // Abort request because the user is not allowed here - @todo use filters
        if ($app['allowed'] == false) {
            return $app->abort(403, 'Not allowed');
        }

        // Main courses and session list
        $items = null;
        $type = str_replace('/', '', $type);

        /** @var \PageController $pageController */
        $pageController = $app['page_controller'];

        switch ($type) {
            case 'sessions':
                $items = $pageController->returnSessions(api_get_user_id(), $filter, $page);
                break;
            case 'sessioncategories':
                $items = $pageController->returnSessionsCategories(api_get_user_id(), $filter, $page);
                break;
            case 'courses':
                $items = $pageController->returnCourses(api_get_user_id(), $filter, $page);
                break;
            case 'mycoursecategories':
                $items = $pageController->returnMyCourseCategories(api_get_user_id(), $filter, $page);
                break;
            case 'specialcourses':
                $items = $pageController->returnSpecialCourses(api_get_user_id(), $filter, $page);
                break;
        }

        //Show the chamilo mascot
        if (empty($items) && empty($filter)) {
            $pageController->return_welcome_to_course_block($app['template']);
        }

        /*
        $app['my_main_menu'] = function($app) {
            $menu = $app['knp_menu.factory']->createItem('root');
            $menu->addChild('Home', array('route' => api_get_path(WEB_CODE_PATH)));
            return $menu;
        };
        $app['knp_menu.menus'] = array('main' => 'my_main_menu');*/
        $app['template']->assign('content', $items);
        $pageController->setCourseSessionMenu();

        $pageController->setProfileBlock();
        $pageController->setUserImageBlock();
        $pageController->setCourseBlock($filter);
        $pageController->setSessionBlock();
        $pageController->return_reservation_block();
        $pageController->returnNavigationLinks($app['template']->getNavigationLinks());

        $app['template']->assign('search_block', $pageController->return_search_block());
        $app['template']->assign('classes_block', $pageController->return_classes_block());
        $pageController->returnSkillsLinks();

        // Deleting the session_id.
        Session::erase('session_id');

        $response = $app['template']->render_template('userportal/index.tpl');

        return new Response($response, 200, array());
    }

    /**
     * Toggle the student view action
     */
    public function toggleStudentViewAction(Application $app)
    {
        if (!api_is_allowed_to_edit(false, false, false, false)) {
            return '';
        }

        /** @var Request $request */
        $request = $app['request'];
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
