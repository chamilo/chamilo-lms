<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Sequence;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\GraphViz\GraphViz;

/**
 * Responses to AJAX calls
 */
require_once __DIR__.'/../global.inc.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
$sequenceId = isset($_REQUEST['sequence_id']) ? $_REQUEST['sequence_id'] : 0;

$em = Database::getManager();
$repository = $em->getRepository('ChamiloCoreBundle:SequenceResource');
switch ($action) {
    case 'graph':
        api_block_anonymous_users();
        api_protect_admin_script();

        switch ($type) {
            case 'session':
                $type = SequenceResource::SESSION_TYPE;

                /** @var Sequence $sequence */
                $sequence = $em->getRepository('ChamiloCoreBundle:Sequence')->find($sequenceId);

                if (empty($sequence)) {
                    exit;
                }

                if ($sequence->hasGraph()) {
                    $graph = $sequence->getUnSerializeGraph();
                    $graph->setAttribute('graphviz.node.fontname', 'arial');
                    $graphviz = new GraphViz();
                    $graphImage = '';
                    try {
                        $graphImage = $graphviz->createImageHtml($graph);
                    } catch (UnexpectedValueException $e) {
                        error_log($e->getMessage().' - Graph could not be rendered in resources sequence because GraphViz command "dot" could not be executed - Make sure graphviz is installed.');
                        $graphImage = '<p class="text-center"><small>'.get_lang('MissingChartLibraryPleaseCheckLog').'</small></p>';
                    }
                    echo $graphImage;
                }
                break;
        }
        break;
    case 'get_icon':
        api_block_anonymous_users();
        api_protect_admin_script();

        $link = '';
        switch ($type) {
            case 'session':
                $type = SequenceResource::SESSION_TYPE;
                $showDelete = isset($_REQUEST['show_delete']) ? $_REQUEST['show_delete'] : false;
                $image = Display::return_icon('item-sequence.png', null, null, ICON_SIZE_LARGE);
                $sessionInfo = api_get_session_info($id);
                if (!empty($sessionInfo)) {
                    $linkDelete = $linkUndo = '';
                    if ($showDelete) {
                        $linkDelete = Display::toolbarButton(
                            get_lang('Delete'),
                            '#',
                            'trash',
                            'default',
                            [
                                'class' => 'delete_vertex btn btn-block btn-xs',
                                'data-id' => $id
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
                                'data-id' => $id
                            ]
                        );
                    }

                    $link = '<div class="parent" data-id="'.$id.'">';
                    $link .= '<div class="big-icon">';
                    $link .= $image;
                    $link .= '<div class="sequence-course">'.$sessionInfo['name'].'</div>';
                    $link .= '<a href="#" class="sequence-id">'.$id.'</a>';
                    $link .= $linkDelete;
                    $link .= $linkUndo;
                    $link .= '</div></div>';
                }
                break;
        }
        echo $link;
        break;
    case 'delete_vertex':
        api_block_anonymous_users();
        api_protect_admin_script();

        $vertexId = isset($_REQUEST['vertex_id']) ? $_REQUEST['vertex_id'] : null;
        $type = SequenceResource::SESSION_TYPE;

        /** @var Sequence $sequence */
        $sequence = $em->getRepository('ChamiloCoreBundle:Sequence')->find($sequenceId);

        if (empty($sequence)) {
            exit;
        }

        /** @var SequenceResource $sequenceResource */
        $sequenceResource = $repository->findOneBy(
            ['resourceId' => $id, 'type' => $type, 'sequence' => $sequence]
        );

        if (empty($sequenceResource)) {
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
                            if (intval($edges->getVertexEnd()->getId()) === intval($id)) {
                                $vertexFromTo = $edges;
                            }

                            if (intval($edges->getVertexStart()->getId()) === intval($vertexId)) {
                                $vertexToFrom = $edges;
                            }
                        }

                        if ($vertexFromTo && !$vertexToFrom) {
                            $_SESSION['sr_vertex'] = true;
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
                            $sequenceResourceToDelete = $repository->findOneBy(
                                [
                                    'resourceId' => $vertexId,
                                    'type' => $type,
                                    'sequence' => $sequence
                                ]
                            );
                            $em->remove($sequenceResourceToDelete);
                        }

                        if (!$vertexToFrom && !$vertexFromTo) {
                            $_SESSION['sr_vertex'] = true;
                            $vertexTo = $graph->getVertex($id);
                            $vertexFrom = $graph->getVertex($vertexId);
                            if ($vertexTo->getVerticesEdgeFrom()->count() > 1) {
                                $vertexFrom->destroy();
                                $sequenceResourceToDelete = $repository->findOneBy(
                                    [
                                        'resourceId' => $vertexId,
                                        'type' => $type,
                                        'sequence' => $sequence
                                    ]
                                );
                                $em->remove($sequenceResourceToDelete);
                            } else {
                                $vertexTo->destroy();
                                $vertexFrom->destroy();
                                $sequenceResourceToDelete = $repository->findOneBy(
                                    [
                                        'resourceId' => $vertexId,
                                        'type' => $type,
                                        'sequence' => $sequence
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
        $sequenceId = isset($_REQUEST['sequence_id']) ? $_REQUEST['sequence_id'] : 0;
        $type = SequenceResource::SESSION_TYPE;

        /** @var Sequence $sequence */
        $sequence = $em->getRepository('ChamiloCoreBundle:Sequence')->find($sequenceId);

        if (empty($sequence)) {
            exit;
        }

        /** @var SequenceResource $sequenceResource */
        $sequenceResource = $repository->findOneBy(
            ['resourceId' => $id, 'type' => $type, 'sequence' => $sequence]
        );

        if (empty($sequenceResource)) {
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
        $sequenceId = isset($_REQUEST['sequence_id']) ? $_REQUEST['sequence_id'] : 0;
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';

        if (empty($parents) || empty($sequenceId) || empty($type)) {
            exit;
        }

        /** @var Sequence $sequence */
        $sequence = $em->getRepository('ChamiloCoreBundle:Sequence')->find($sequenceId);

        if (empty($sequence)) {
            exit;
        }

        if (isset($_SESSION['sr_vertex']) && $_SESSION['sr_vertex']) {
            unset($_SESSION['sr_vertex']);
            echo Display::return_message(get_lang('Saved'), 'success');
            break;
        }

        $parents = str_replace($id, '', $parents);
        $parents = explode(',', $parents);
        $parents = array_filter($parents);

        if ($sequence->hasGraph()) {
            $graph = $sequence->getUnSerializeGraph();
        } else {
            $graph = new Graph();
        }

        switch ($type) {
            case 'session':
                $type = SequenceResource::SESSION_TYPE;
                $sessionInfo = api_get_session_info($id);
                $name = $sessionInfo['name'];

                if ($graph->hasVertex($id)) {
                    $main = $graph->getVertex($id);
                } else {
                    $main = $graph->createVertex($id);
                }

                foreach ($parents as $parentId) {
                    if ($graph->hasVertex($parentId)) {
                        $parent = $graph->getVertex($parentId);
                        if (!$parent->hasEdgeTo($main)) {
                            $parent->createEdgeTo($main);
                        }
                    } else {
                        $parent = $graph->createVertex($parentId);
                        $parent->createEdgeTo($main);
                    }
                }

                foreach ($parents as $parentId) {
                    $sequenceResourceParent = $repository->findOneBy(
                        ['resourceId' => $parentId, 'type' => $type, 'sequence' => $sequence]
                    );

                    if (empty($sequenceResourceParent)) {
                        $sequenceResourceParent = new SequenceResource();
                        $sequenceResourceParent
                            ->setSequence($sequence)
                            ->setType(SequenceResource::SESSION_TYPE)
                            ->setResourceId($parentId);
                        $em->persist($sequenceResourceParent);
                    }
                }

                //$graphviz = new GraphViz();
                //echo $graphviz->createImageHtml($graph);
                /** @var SequenceResource $sequenceResource */
                $sequenceResource = $repository->findOneBy(
                    ['resourceId' => $id, 'type' => $type, 'sequence' => $sequence]
                );

                if (empty($sequenceResource)) {
                    // Create
                    $sequence->setGraphAndSerialize($graph);

                    $sequenceResource = new SequenceResource();
                    $sequenceResource
                        ->setSequence($sequence)
                        ->setType(SequenceResource::SESSION_TYPE)
                        ->setResourceId($id);
                } else {
                    // Update
                    $sequenceResource->getSequence()->setGraphAndSerialize($graph);
                }
                $em->persist($sequenceResource);
                $em->flush();

                echo Display::return_message(get_lang('Saved'), 'success');
                break;
        }
        break;
    case 'get_requirements':
        $userId = api_get_user_id();

        switch ($type) {
            case SequenceResource::SESSION_TYPE:
                $session = api_get_session_info($id);

                $sequences = $repository->getRequirements(
                    $session['id'],
                    $type
                );

                if (count($sequences) === 0) {
                    break;
                }

                $sequenceList = SequenceResourceManager::checkRequirementsForUser($sequences, $type, $userId);
                $allowSubscription = SequenceResourceManager::checkSequenceAreCompleted($sequenceList);

                $courseController = new CoursesController();

                $view = new Template(null, false, false, false, false, false);
                $view->assign('sequences', $sequenceList);
                $view->assign('allow_subscription', $allowSubscription);

                if ($allowSubscription) {
                    $view->assign(
                        'subscribe_button',
                        $courseController->getRegisteredInSessionButton(
                            $session['id'],
                            $session['name'],
                            false
                        )
                    );
                }

                $template = $view->get_template(
                    'sequence_resource/session_requirements.tpl'
                );

                $view->display($template);
                break;
        }
        break;
}
