<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Sequence;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Repository\SequenceRepository;
use Chamilo\CoreBundle\Repository\SequenceResourceRepository;
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
$sequenceRepository = $em->getRepository(Sequence::class);
/** @var SequenceResourceRepository $sequenceResourceRepository */
$sequenceResourceRepository = $em->getRepository(SequenceResource::class);

switch ($action) {
    case 'graph':
        api_block_anonymous_users();
        // Close the session as we don't need it any further
        session_write_close();

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
                    get_lang('Dependency tree graph'),
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
                echo '<p class="text-center"><small>'.get_lang('Missing chart library, please check web server logs.')
                    .'</small></p>';
            }
        }

        break;
    case 'get_icon':
        api_block_anonymous_users();
        api_protect_admin_script();

        $showDelete = isset($_REQUEST['show_delete']) ? $_REQUEST['show_delete'] : false;

        if (empty($id)) {
            exit;
        }

        $resourceName = '';
        switch ($type) {
            case SequenceResource::SESSION_TYPE:
                $resourceData = api_get_session_info($id);
                if ($resourceData) {
                    $resourceName = $resourceData['title'];
                }
                break;
            case SequenceResource::COURSE_TYPE:
                $resourceData = api_get_course_info_by_id($id);
                if ($resourceData) {
                    $resourceName = $resourceData['title'];
                }
                break;
        }
        // Close the session as we don't need it any further
        session_write_close();

        if (empty($resourceData)) {
            exit;
        }

        $linkDelete = '';
        $linkUndo = '';
        if (!empty($resourceData) && $showDelete) {
            $linkDelete = Display::tag(
                'button',
                get_lang('Delete'),
                [
                    'class' => 'delete_vertex text-xs text-red-600 hover:underline',
                    'data-id' => $id,
                ]
            );

            $linkUndo = Display::tag(
                'button',
                get_lang('Undo'),
                [
                    'class' => 'undo_delete text-xs text-blue-600 hover:underline hidden',
                    'data-id' => $id,
                ]
            );
        }

        $image = Display::getMdiIcon('notebook', 'text-blue-500', null, ICON_SIZE_LARGE);

        $link = '<div class="parent" data-id="'.$id.'">';
        $link .= '<div class="bg-yellow-100 border border-yellow-300 rounded-lg p-4 shadow-sm flex flex-col items-center justify-center space-y-2 text-center">';
        $link .= $image;
        $link .= Display::tag(
            'button',
            $resourceName,
            [
                'class' => 'sequence-id text-gray-800 font-semibold text-sm hover:underline',
                'type' => 'button',
                'title' => get_lang('Use as reference'),
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
                // Close the session as we don't need it any further
                session_write_close();

                $sequence->setGraphAndSerialize($graph);
                $em->persist($sequence);
                $em->flush();
            }
        }

        break;
    case 'load_resource':
        api_block_anonymous_users();
        api_protect_admin_script();
        // Close the session as we don't need it any further
        session_write_close();

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
        // Close the session as we don't need it any further
        session_write_close();

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
        $main->setAttribute('graphviz.label', $item->getTitle());

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
            $parent->setAttribute('graphviz.label', $item->getTitle());
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
                $resourceName = $resourceData['title'];
                $template = 'session_requirements.tpl';
                break;
            case SequenceResource::COURSE_TYPE:
                $resourceData = api_get_course_info_by_id($id);
                $resourceName = $resourceData['title'];
                $template = 'course_requirements.tpl';
                break;
        }
        // Close the session as we don't need it any further
        session_write_close();

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

        $template = $view->get_template('sequence_resource/'.$template);

        $view->display($template);

        break;
    case 'get_initial_resource':
        api_block_anonymous_users();
        api_protect_admin_script();
        // Close the session as we don't need it any further
        session_write_close();

        /** @var Sequence $sequence */
        $sequence = $sequenceRepository->find($sequenceId);

        if (null === $sequence || !$sequence->hasGraph()) {
            exit;
        }

        $graph = $sequence->getUnSerializeGraph();

        foreach ($graph->getVertices() as $vertex) {
            $vertexId = $vertex->getId();
            if (!empty($vertexId)) {
                echo (int) $vertexId;
                exit;
            }
        }

        exit;
}
