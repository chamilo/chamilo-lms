<?php

require_once '../inc/global.inc.php';

use Silex\Application;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Tools\Pagination\Paginator;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\View\TwitterBootstrapView;
//use Pagerfanta\View\DefaultView;
//use Pages\PagesAdmin;

class PagesController {
    /*
    function indexAction(Application $app, $page) {
        return $this->listAction($app, $page);
    }*/

    function addAction(Application $app) {
        $request = $app['request'];
        $form = $this->getForm($app);

        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);
            if ($form->isValid()) {
                $page = $form->getData();
                $page->setSlug($page->getTitle());
                $em = $app['orm.em'];
                /*$page_data = $form->getData();
                $page->setContent($page_data['content']);
                $page->setSlug($page_data['slug']);
                $page->setTitle($page_data['title']);
                $em->persist($page);*/
                $em->persist($page);
                $em->flush();
                return $app->redirect($app['url_generator']->generate('show', array('id'=> $page->getId())), 201);
            }
        }
        return $app['template']->render_template('pages/add.tpl', array('form' => $form->createView()));
    }

    function editAction(Application $app, $id) {
        $request = $app['request'];
        $page = $app['orm.em']->find('Entity\EntityPages', $id);

        if (empty($page)) {
            $app->abort(404, "Page $id does not exist.");
        }
        $form = $this->getForm($app, $page);

        if ('POST' == $request->getMethod()) {
            $form->bind($request);
            if ($form->isValid()) {
                $em = $app['orm.em'];
                //$page = $form->getData();
                $page->setTitle($page->getTitle());
                $em->persist($page);
                $em->flush();
                return $app->redirect($app['url_generator']->generate('show', array('id'=> $page->getId())), 201);
            }
        }
        return $app['template']->render_template('pages/add.tpl', array('form' => $form->createView()));
    }

    function showAction(Application $app, $id) {
        $page = $app['orm.em']->find('Entity\EntityPages', $id);
        $actions = Display::url(Display::return_icon('list.png', get_lang('Listing'), array(), ICON_SIZE_MEDIUM), $app['url_generator']->generate('index'));
        return $app['template']->render_template('pages/show.tpl', array(
            'page' => $page,
            'actions' => $actions,
        ));
    }

    function deleteAction(Application $app, $id) {
        $em = $app['orm.em'];
        $page = $em->find('Entity\EntityPages', $id);
        $em->remove($page);
        $em->flush();
        return $app->redirect($app['url_generator']->generate('index'), 201);
    }

    function listAction(Application $app, $page = 1) {
        /*
        $source = new Entity('Entity\EntityPages');
        $grid = new Grid();

        // Attach the source to the grid
        $grid->setSource($source);

        // Return the response of the grid to the template
        //return $grid->getGridResponse('MyProjectMyBundle::myGrid.html.twig');
        */



        $em = $app['orm.em'];
        $dql = 'SELECT a FROM Entity\EntityPages a';
        $query = $em->createQuery($dql)->setFirstResult(0)->setMaxResults(100);

        //or using the repository
        //
        //$query = $em->getRepository('Entity\EntityPages')->getLatestPages();

        $adapter = new DoctrineORMAdapter($query);
        $pagerfanta = new Pagerfanta($adapter);

        $routeGenerator = function($page) use ($app) {
            return $app['url_generator']->generate('list', array('page' => $page));
        };
        $page = intval($app['request']->get('page'));

        $pagerfanta->setMaxPerPage(2); // 10 by default
        $pagerfanta->setCurrentPage($page);

        //$view = new DefaultView();
        $view = new TwitterBootstrapView();

        $pagination = $view->render($pagerfanta, $routeGenerator, array(
            'proximity' => 3,
        ));

        $actions = Display::url(Display::return_icon('add.png', get_lang('Add'), array(), ICON_SIZE_MEDIUM), $app['url_generator']->generate('add'));
        //$paginator = new Paginator($query, $fetchJoinCollection = true);

        return $app['template']->render_template('pages/listing.tpl', array(
            //'pages' => $paginator->getIterator(),
            'pages' => $pagerfanta,
            'pagination' => $pagination,
            'actions' => $actions
        ));
    }

    function getForm(Application $app, $entity = null) {
        if (empty($entity)) {
            $entity = new Entity\EntityPages();
        }
        $form = $app['form.factory']->createBuilder('form', $entity);
        $form->add('title', 'text', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\MinLength(5))
        ));
        $form->add('slug', 'text', array(
            //'constraints' => array(new Assert\NotBlank())
        ));
        $form->add('content', 'textarea', array(
           // 'constraints' => array()
        ));
        return $form->getForm();
    }
}

$app->get('/', 'pages.controller:listAction')->bind('index');

$app->get('/page', 'pages.controller:listAction')->bind('list');

$app->get('/show/{id}', 'pages.controller:showAction')
    ->bind('show')
    ->assert('id', '\d+');
$app->get('/delete/{id}', 'pages.controller:deleteAction')
    ->bind('delete')
    ->assert('id', '\d+');
$app->match('/edit/{id}', 'pages.controller:editAction', 'GET|POST')
    ->bind('edit')
    ->assert('id', '\d+');
$app->match('/add', 'pages.controller:addAction', 'GET|POST')->bind('add');
$app->run();