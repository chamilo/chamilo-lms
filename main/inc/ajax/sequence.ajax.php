<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls
 */

use Chamilo\CoreBundle\Entity\SequenceResource;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\GraphViz\GraphViz;

require_once '../global.inc.php';

api_protect_admin_script();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
$manager = Database::getManager();
$repository = $manager->getRepository('ChamiloCoreBundle:SequenceResource');
switch ($action) {
    case 'get_icon':
        $link = '';
        switch ($type) {
            case 'session':
                $showDelete = isset($_REQUEST['show_delete']) ? $_REQUEST['show_delete'] : false;
                $image = Display::return_icon('window_list.png');
                $sessionInfo = api_get_session_info($id);
                if (!empty($sessionInfo)) {


                $linkDelete = '';
                if ($showDelete) {
                    $linkDelete = Display::url(
                        get_lang('Delete'),
                        '#',
                        ['class' => 'delete_vertex', 'data-id' => $id]
                    );
                }

                $link = '<div class="parent" data-id="'.$id.'">'.
                    $image.' '.$sessionInfo['name'].$linkDelete.
                    '</div>';
                }
                break;
        }
        echo $link;
        break;
    case 'delete_vertex':
        $vertexId = isset($_REQUEST['vertex_id']) ? $_REQUEST['vertex_id'] : null;
        /** @var SequenceResource $resource */
        $resource = $repository->findOneByResourceId($id);

        if (empty($resource)) {
            exit;
        }

        $graph = $resource->getGraph();

        if (!empty($graph)) {
            /** @var Graph $graph */
            $graph = unserialize($graph);
            if ($graph->hasVertex($vertexId)) {
                $vertex = $graph->getVertex($vertexId);
                $vertex->destroy();

                $resource->setGraph(serialize($graph));

                $manager->persist($resource);
                $manager->flush();
            }
        }

        break;
    case 'load_resource':
        // children or parent
        $loadResourceType = isset($_REQUEST['load_resource_type']) ? $_REQUEST['load_resource_type'] : null;
        /** @var SequenceResource $resource */
        $resource = $repository->findOneByResourceId($id);

        if (empty($resource)) {
            exit;
        }

        $graph = $resource->getGraph();

        if (!empty($graph)) {
            /** @var Graph $graph */
            $graph = unserialize($graph);

            $graphviz = new GraphViz();
            //echo $graphviz->createImageHtml($graph);

            /** @var Vertex $mainVertice */
            if ($graph->hasVertex($id)) {
                $mainVertice = $graph->getVertex($id);

                if (!empty($mainVertice)) {
                    $list = [];
                    switch ($loadResourceType) {
                        case 'parent':
                            $verticeList = $mainVertice->getVerticesEdgeFrom();

                            break;
                        case 'children':
                            $verticeList = $mainVertice->getVerticesEdgeTo();
                            break;
                    }
                    foreach ($verticeList as $vertice) {
                        $list[] = $vertice->getId();
                    }

                    if (!empty($list)) {
                        echo implode(',', $list);
                    }
                }
            }
        }
        break;
    case 'save_resource':
        $parents = isset($_REQUEST['parents']) ? $_REQUEST['parents'] : null;
        $parents = str_replace($id, '', $parents);
        $parents = explode(',', $parents);
        $parents = array_filter($parents);

        $graph = new Graph();

        switch ($type) {
            case 'session':
                $sessionInfo = api_get_session_info($id);
                $name = $sessionInfo['name'];

                $main = $graph->createVertex($id);

                foreach ($parents as $parentId) {
                    $parent = $graph->createVertex($parentId);
                    // Check if parent Id exists in the DB
                    /** @var SequenceResource $resource */
                    $resource = $repository->findOneByResourceId($parentId);
                    if ($resource) {
                        $parentGraph = $resource->getGraph();

                        if (!empty($parentGraph)) {
                            /** @var Graph $parentGraph */
                            $parentGraph = unserialize($parentGraph);
                            try {
                                $vertex = $parentGraph->getVertex($parentId);
                                $parentMain = $parentGraph->createVertex($id);
                                $vertex->createEdgeTo($parentMain);
                                $resource->setGraph(serialize($parentGraph));

                                $manager->persist($resource);
                                $manager->flush();
/*
                                $graphviz = new GraphViz();
                                echo $graphviz->createImageHtml($parentGraph);*/
                            } catch (Exception $e) {

                            }
                        }
                    }

                    $parent->createEdgeTo($main);
                }

                $graphviz = new GraphViz();
                //echo $graphviz->createImageHtml($graph);

                /** @var SequenceResource $sequence */
                $sequence = $repository->findOneByResourceId($id);
                if (empty($sequence)) {
                    $sequence = new SequenceResource();
                    $sequence
                        ->setGraph(serialize($graph))
                        ->setType(SequenceResource::SESSION_TYPE)
                        ->setResourceId($id);
                } else {
                    $sequence->setGraph(serialize($graph));
                }
                $manager->persist($sequence);
                $manager->flush();
                break;
        }


        break;
}
