<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls
 */

use Chamilo\CoreBundle\Entity\Sequence;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;


require_once '../global.inc.php';

api_block_anonymous_users();
api_protect_admin_script();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
$sequenceId = isset($_REQUEST['sequence_id']) ? $_REQUEST['sequence_id'] : 0;

$em = Database::getManager();
$repository = $em->getRepository('ChamiloCoreBundle:SequenceResource');
switch ($action) {
    case 'graph':
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
                    $graphviz = new \Graphp\GraphViz\GraphViz();
                    echo $graphviz->createImageHtml($graph);
                }
                break;
        }
        break;
    case 'get_icon':
        $link = '';
        switch ($type) {
            case 'session':
                $type = SequenceResource::SESSION_TYPE;
                $showDelete = isset($_REQUEST['show_delete']) ? $_REQUEST['show_delete'] : false;
                $image = Display::return_icon('item-sequence.png', null, null, ICON_SIZE_LARGE);
                $sessionInfo = api_get_session_info($id);
                if (!empty($sessionInfo)) {
                    $linkDelete = '';
                    if ($showDelete) {
                        $linkDelete = Display::url(
                            get_lang('Delete'),
                            'javascript:void(0);',
                            ['class' => 'delete_vertex', 'data-id' => $id]
                        );
                    }

                    $link = '<div class="parent" data-id="' . $id . '">';
                    $link .= '<div class="big-icon">';
                    $link .= $image;
                    $link .= '<div class="sequence-course">' . $sessionInfo['name'] . '</div>';
                    $link .= '<div class="sequence-id">' . $id . '</div>';
                    $link .= '<div class="sequence-deleted">' . $linkDelete . '</div>';
                    $link .= '</div></div>';
                }
                break;
        }
        echo $link;
        break;
    case 'delete_vertex':
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
                $vertex = $graph->getVertex($vertexId);
                $vertex->destroy();

                /** @var SequenceResource $sequenceResource */
                $sequenceResourceToDelete = $repository->findOneBy(
                    [
                        'resourceId' => $vertexId,
                        'type' => $type,
                        'sequence' => $sequence
                    ]
                );

                $em->remove($sequenceResourceToDelete);

                $sequence->setGraphAndSerialize($graph);
                $em->merge($sequence);
                $em->flush();
            }
        }
        break;
    case 'load_resource':
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
                break;
        }
        break;
}
