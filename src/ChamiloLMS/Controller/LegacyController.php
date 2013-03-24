<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * Manages the chamilo pages starting with Display::display_header and $tpl = new Template();
 */
class LegacyController// extends Controller
{
    public $section;
    public $language_files = array('courses', 'index', 'admin');

    /**
     * Handles default Chamilo scripts handled by Display::display_header() and display_footer()
     *
     * @param \Silex\Application $app
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|void
     */
    public function classicAction(Application $app)
    {
        //User is not allowed
        if ($app['allowed'] == false) {
            return $app->abort(403);
        }
        //Rendering page
        $response = $app['twig']->render($app['default_layout']);

        //Classic style
        if ($app['classic_layout'] == true) {
            //assign('content', already done in display::display_header() and display_footer()

        } else {
           return $app->redirect('index');
        }

        return new Response($response, 200, array());
    }
}