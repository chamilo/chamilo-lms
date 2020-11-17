<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Repository\SequenceRepository;
use Chamilo\CoreBundle\Entity\Repository\SequenceResourceRepository;
use Chamilo\CoreBundle\Entity\Sequence;
use Chamilo\CoreBundle\Entity\SequenceResource;
use ChamiloSession as Session;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\GraphViz\GraphViz;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
$sequenceId = isset($_REQUEST['sequence_id']) ? $_REQUEST['sequence_id'] : 0;

$em = Database::getManager();
/** @var SequenceRepository $sequenceRepository */
$sequenceRepository = $em->getRepository('ChamiloCoreBundle:Sequence');
/** @var SequenceResourceRepository $sequenceResourceRepository */
$sequenceResourceRepository = $em->getRepository('ChamiloCoreBundle:SequenceResource');

switch ($action) {
    case 'graph':
        api_block_anonymous_users();

        /** @var Sequence $sequence */
        $sequence = $sequenceRepository->find($sequenceId);

        if (null === $sequence) {
            exit;
        }

        if ($sequence->hasGraph()) {
            $graph = $sequence->getUnSerializeGraph();
            $graph->setAttribute('graphviz.node.fontname', 'arial');
            $graphviz = new GraphViz();
            $graphImage = '';
            try {
                $graphImage = $graphviz->createImageSrc($graph);
                echo Display::img(
                    $graphImage,
                    get_lang('GraphDependencyTree'),
                    ['class' => 'center-block'],
                    false
                );
            } catch (UnexpectedValueException $e) {
                error_log(
                    $e->getMessage()
                    .' - Graph could not be rendered in resources sequence'
                    .' because GraphViz command "dot" could not be executed '
                    .'- Make sure graphviz is installed.'
                );
                echo '<p class="text-center"><small>'.get_lang('MissingChartLibraryPleaseCheckLog')
                    .'</small></p>';
            }
        }

        break;
    case 'get_icon':
        api_block_anonymous_users();
        api_protect_admin_script();

        $showDelete = isset($_REQUEST['show_delete']) ? $_REQUEST['show_delete'] : false;
        $image = Display::return_icon('item-sequence.png', null, null, ICON_SIZE_LARGE);

        if (empty($id)) {
            exit;
        }

        $link = '';
        $linkDelete = $linkUndo = '';
        $resourceName = '';
        switch ($type) {
            case SequenceResource::SESSION_TYPE:
                $resourceData = api_get_session_info($id);
                if ($resourceData) {
                    $resourceName = $resourceData['name'];
                }
                break;
            case SequenceResource::COURSE_TYPE:
                $resourceData = api_get_course_info_by_id($id);
                if ($resourceData) {
                    $resourceName = $resourceData['name'];
                }
                break;
        }

        if (empty($resourceData)) {
            exit;
        }

        if (!empty($resourceData) && $showDelete) {
            $linkDelete = Display::toolbarButton(
                get_lang('Delete'),
                '#',
                'trash',
                'default',
                [
                    'class' => 'delete_vertex btn btn-block btn-xs',
                    'data-id' => $id,
                ]
            );

            $linkUndo = Display::toolbarButton(
                get_lang('Undo'),
                '#',
                'undo',
                'default',
                [
                    'class' => 'undo_delete btn btn-block btn-xs',
                    'style' => 'display: none;',
                    'data-id' => $id,
                ]
            );
        }

        $link = '<div class="parent" data-id="'.$id.'">';
        $link .= '<div class="big-icon">';
        $link .= $image;
        $link .= '<div class="sequence-course">'.$resourceName.'</div>';
        $link .= Display::tag(
            'button',
            $resourceName,
            [
                'class' => 'sequence-id',
                'title' => get_lang('UseAsReference'),
                'type' => 'button',
            ]
        );
        $link .= $linkDelete;
        $link .= $linkUndo;
        $link .= '</div></div>';

        echo $link;
        break;
    case 'delete_vertex':
        api_block_anonymous_users();
        api_protect_admin_script();

        $vertexId = isset($_REQUEST['vertex_id']) ? $_REQUEST['vertex_id'] : null;

        /** @var Sequence $sequence */
        $sequence = $sequenceRepository->find($sequenceId);

        if (null === $sequence) {
            exit;
        }

        /** @var SequenceResource $sequenceResource */
        $sequenceResource = $sequenceResourceRepository->findOneBy(
            ['resourceId' => $id, 'type' => $type, 'sequence' => $sequence]
        );

        if (null === $sequenceResource) {
            exit;
        }

        if ($sequenceResource->getSequence()->hasGraph()) {
            $graph = $sequenceResource->getSequence()->getUnSerializeGraph();
            if ($graph->hasVertex($vertexId)) {
                $edgeIterator = $graph->getEdges()->getIterator();
                $edgeToDelete = null;
                foreach ($edgeIterator as $edge) {
                    if ($edge->getVertexStart()->getId() == $vertexId && $edge->getVertexEnd()->getId() == $id) {
                        $edgeToDelete = $edge;
                        $vertexFromTo = null;
                        $vertexToFrom = null;
                        foreach ($edgeIterator as $edges) {
                            if ((int) $edges->getVertexEnd()->getId() === (int) $id) {
                                $vertexFromTo = $edges;
                            }

                            if ((int) $edges->getVertexStart()->getId() === (int) $vertexId) {
                                $vertexToFrom = $edges;
                            }
                        }

                        if ($vertexFromTo && !$vertexToFrom) {
                            Session::write('sr_vertex', true);
                            $vertex = $graph->getVertex($id);
                            $vertex->destroy();
                            $em->remove($sequenceResource);
                        }

                        if ($vertexToFrom && $vertexFromTo) {
                            $vertex = $graph->getVertex($vertexId);
                            $edgeToDelete->destroy();
                        }

                        if ($vertexToFrom && !$vertexFromTo) {
                            $vertex = $graph->getVertex($vertexId);
                            $vertex->destroy();
                            $sequenceResourceToDelete = $sequenceResourceRepository->findOneBy(
                                [
                                    'resourceId' => $vertexId,
                                    'type' => $type,
                                    'sequence' => $sequence,
                                ]
                            );
                            $em->remove($sequenceResourceToDelete);
                        }

                        if (!$vertexToFrom && !$vertexFromTo) {
                            Session::write('sr_vertex', true);
                            $vertexTo = $graph->getVertex($id);
                            $vertexFrom = $graph->getVertex($vertexId);
                            if ($vertexTo->getVerticesEdgeFrom()->count() > 1) {
                                $vertexFrom->destroy();
                                $sequenceResourceToDelete = $sequenceResourceRepository->findOneBy(
                                    [
                                        'resourceId' => $vertexId,
                                        'type' => $type,
                                        'sequence' => $sequence,
                                    ]
                                );
                                $em->remove($sequenceResourceToDelete);
                            } else {
                                $vertexTo->destroy();
                                $vertexFrom->destroy();
                                $sequenceResourceToDelete = $sequenceResourceRepository->findOneBy(
                                    [
                                        'resourceId' => $vertexId,
                                        'type' => $type,
                                        'sequence' => $sequence,
                                    ]
                                );
                                $em->remove($sequenceResource);
                                $em->remove($sequenceResourceToDelete);
                            }
                        }
                    }
                }

                $sequence->setGraphAndSerialize($graph);
                $em->merge($sequence);
                $em->flush();
            }
        }

        break;
    case 'load_resource':
        api_block_anonymous_users();
        api_protect_admin_script();

        // children or parent
        $loadResourceType = isset($_REQUEST['load_resource_type']) ? $_REQUEST['load_resource_type'] : null;

        /** @var Sequence $sequence */
        $sequence = $sequenceRepository->find($sequenceId);

        if (empty($sequence)) {
            exit;
        }

        /** @var SequenceResource $sequenceResource */
        $sequenceResource = $sequenceResourceRepository->findOneBy(
            ['resourceId' => $id, 'type' => $type, 'sequence' => $sequence]
        );

        if (null === $sequenceResource) {
            exit;
        }

        if ($sequenceResource->hasGraph()) {
            $graph = $sequenceResource->getSequence()->getUnSerializeGraph();

            /** @var Vertex $mainVertice */
            if ($graph->hasVertex($id)) {
                $mainVertex = $graph->getVertex($id);

                if (!empty($mainVertex)) {
                    $vertexList = null;
                    switch ($loadResourceType) {
                        case 'parent':
                            $vertexList = $mainVertex->getVerticesEdgeFrom();

                            break;
                        case 'children':
                            $vertexList = $mainVertex->getVerticesEdgeTo();
                            break;
                    }

                    $list = [];
                    if (!empty($vertexList)) {
                        foreach ($vertexList as $vertex) {
                            $list[] = $vertex->getId();
                        }
                    }

                    if (!empty($list)) {
                        echo implode(',', $list);
                    }
                }
            }
        }
        break;
    case 'save_resource':
        api_block_anonymous_users();
        api_protect_admin_script();

        $parents = isset($_REQUEST['parents']) ? $_REQUEST['parents'] : '';

        if (empty($parents) || empty($sequenceId) || empty($type)) {
            exit;
        }

        /** @var Sequence $sequence */
        $sequence = $sequenceRepository->find($sequenceId);

        if (null === $sequence) {
            exit;
        }

        /*$vertexFromSession = Session::read('sr_vertex');
        if ($vertexFromSession) {
            Session::erase('sr_vertex');
            echo Display::return_message(get_lang('Saved'), 'success');
            break;
        }*/

        $parents = str_replace($id, '', $parents);
        $parents = explode(',', $parents);
        $parents = array_filter($parents);

        if ($sequence->hasGraph()) {
            $graph = $sequence->getUnSerializeGraph();
        } else {
            $graph = new Graph();
        }

        if ($graph->hasVertex($id)) {
            $main = $graph->getVertex($id);
        } else {
            $main = $graph->createVertex($id);
        }

        $item = $sequenceRepository->getItem($id, $type);
        $main->setAttribute('graphviz.shape', 'record');
        $main->setAttribute('graphviz.label', $item->getName());

        foreach ($parents as $parentId) {
            $item = $sequenceRepository->getItem($parentId, $type);
            if ($graph->hasVertex($parentId)) {
                $parent = $graph->getVertex($parentId);
                if (!$parent->hasEdgeTo($main)) {
                    $newEdge = $parent->createEdgeTo($main);
                }
            } else {
                $parent = $graph->createVertex($parentId);
                $newEdge = $parent->createEdgeTo($main);
            }

            $parent->setAttribute('graphviz.shape', 'record');
            $parent->setAttribute('graphviz.label', $item->getName());
        }

        foreach ($parents as $parentId) {
            $sequenceResourceParent = $sequenceResourceRepository->findOneBy(
                ['resourceId' => $parentId, 'type' => $type, 'sequence' => $sequence]
            );

            if (empty($sequenceResourceParent)) {
                $sequenceResourceParent = new SequenceResource();
                $sequenceResourceParent
                    ->setSequence($sequence)
                    ->setType($type)
                    ->setResourceId($parentId);
                $em->persist($sequenceResourceParent);
            }
        }

        /** @var SequenceResource $sequenceResource */
        $sequenceResource = $sequenceResourceRepository->findOneBy(
            ['resourceId' => $id, 'type' => $type, 'sequence' => $sequence]
        );

        if (null === $sequenceResource) {
            // Create
            $sequence->setGraphAndSerialize($graph);
            $sequenceResource = new SequenceResource();
            $sequenceResource
                ->setSequence($sequence)
                ->setType($type)
                ->setResourceId($id);
        } else {
            // Update
            $sequenceResource->getSequence()->setGraphAndSerialize($graph);
        }
        $em->persist($sequenceResource);
        $em->flush();

        echo Display::return_message(get_lang('Saved'), 'success');

        break;
    case 'get_requirements':
        $sessionId = isset($_REQUEST['sid']) ? (int) $_REQUEST['sid'] : 0;
        $userId = api_get_user_id();
        $resourceName = '';
        $template = '';
        switch ($type) {
            case SequenceResource::SESSION_TYPE:
                $resourceData = api_get_session_info($id);
                $resourceName = $resourceData['name'];
                $template = 'session_requirements.tpl';
                break;
            case SequenceResource::COURSE_TYPE:
                $resourceData = api_get_course_info_by_id($id);
                $resourceName = $resourceData['title'];
                $template = 'course_requirements.tpl';
                break;
        }

        if (empty($resourceData) || empty($template)) {
            exit;
        }

        $sequences = $sequenceResourceRepository->getRequirements($id, $type);

        if (empty($sequences)) {
            exit;
        }

        $sequenceList = $sequenceResourceRepository->checkRequirementsForUser($sequences, $type, $userId, $sessionId);
        $allowSubscription = $sequenceResourceRepository->checkSequenceAreCompleted($sequenceList);

        $view = new Template(null, false, false, false, false, false);
        $view->assign('sequences', $sequenceList);
        $view->assign('sequence_type', $type);
        $view->assign('allow_subscription', $allowSubscription);

        if ($allowSubscription) {
            $view->assign(
                'subscribe_button',
                CoursesAndSessionsCatalog::getRegisteredInSessionButton(
                    $id,
                    $resourceName,
                    false
                )
            );
        }

        $view->display($view->get_template('sequence_resource/'.$template));

        break;
}
