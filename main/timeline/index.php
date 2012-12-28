<?php

require_once '../inc/global.inc.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Entity\EntityCTimeline;

class TimeLineController {

    function indexAction(Application $app) {
        $timeline_query = $app['orm.em']->getRepository('Entity\EntityCTimeline')->findAll();
        
        $timeline_query = $app['orm.em']->createQuery('SELECT a FROM Entity\EntityCTimeline a');


        $paginator = new Doctrine\ORM\Tools\Pagination\Paginator($timeline_query, $fetchJoinCollection = true);
        $c = count($paginator);
        $test = null;
        foreach ($paginator as $item) {
            $test .= $item->getHeadline() . "\n";
        }
        $response = $test;
        return new Response($response, 200, array());
    }

    function addAction(Application $app) {
        $timeline = new Timeline();
        $url  = $app['url_generator']->generate('add');
        $form = $timeline->return_item_form($url, 'edit');

        // The validation or display
        if ($form->validate()) {
            $values = $form->exportValues();
            $values['type']     = 0;
            $values['status']   = 0;

            $my_timeline = new EntityCTimeline();
            $my_timeline->setCId(api_get_course_int_id());
            $my_timeline->setHeadline($values['headline']);
            $my_timeline->setType($values['type']);
            $my_timeline->setStartDate($values['start_date']);
            $my_timeline->setEndDate($values['end_date']);
            $my_timeline->setText($values['text']);
            $my_timeline->setMedia($values['media']);
            $my_timeline->setMediaCredit($values['media_credit']);
            $my_timeline->setMediaCaption($values['media_caption']);
            $my_timeline->setTitleSlide($values['title_slide']);
            $my_timeline->setParentId($values['parent_id']);
            $my_timeline->setStatus($values['status']);

            $app['orm.em']->persist($my_timeline);
            $app['orm.em']->flush();

            $message = Display::return_message(sprintf(get_lang('ItemUpdated'), $values['name']), 'confirmation');
            //$app['session']->setFlash('error', $message);

            return $app->redirect($app['url_generator']->generate('view', array('id'=> $my_timeline->getId())), 201);
        } else {
            $actions = '<a href="'.$app['url_generator']->generate('index').'">'.Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';
            $content = $form->return_form();
        }
        $app['template']->assign('content', $content);
        $response = $app['template']->render_layout('layout_1_col.tpl');
        return new Response($response, 200, array());
    }

    function viewAction(Application $app, $id) {
        $timeline = $app['db.orm.em']->find('EntityCTimeline', $id);
        $app['template']->assign('timeline', $timeline);
        $response = $app['template']->render_template('timeline/view.tpl');
        return new Response($response, 200, array());
    }

    function editAction(Application $app, $id) {
        $timeline = $app['db.orm.em']->find('EntityCTimeline', $id);
        $app['template']->assign('timeline', $timeline);
        $content = $app['template']->fetch('default/timeline/edit.tpl');
        $app['template']->assign('content', $content);
        $response = $app['template']->render_layout('layout_1_col.tpl');
        return new Response($response, 200, array());
    }
}

$app->get('/', 'TimeLineController::indexAction')->bind('index');
$app->get('/view/{id}', 'TimeLineController::viewAction')->bind('view');
$app->get('/edit/{id}', 'TimeLineController::editAction')->bind('edit');
$app->match('/add', 'TimeLineController::addAction', 'GET|POST')->bind('add');
$app->run();