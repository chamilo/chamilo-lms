<?php

namespace ChamiloLMS\NotebookBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Export\CSVExport;
use APY\DataGridBundle\Grid\Action\RowAction;

class CNotebookController extends Controller
{
    /**
     * @Route("/")
     * @Template("ChamiloLMSNotebookBundle::index.html.twig")
     */
    public function indexAction()
    {
        $source = new Entity('ChamiloLMSNotebookBundle:CNotebook');

        /* @var $grid \APY\DataGridBundle\Grid\Grid */
        $grid = $this->get('grid');

        $grid->setSource($source);


        $grid->hideFilters();

        //$grid->setMaxResults(1);
        $grid->setLimits(1);
        $grid->getColumn('iid')->manipulateRenderCell(
            function($value, $row, $router) {
                $router = $this->get('router');
                return $router->generate('administration', array('param' => $row->getField('iid')));
            }
        );

        $myRowAction = new RowAction('More Info', 'administration', false, '_self', array('class' => 'btn'));
        $grid->addRowAction($myRowAction);
        $grid->addExport(new CSVExport('CSV Export'));
        return $grid->getGridResponse('ChamiloLMSNotebookBundle:Notebook:index.html.twig');
    }

    /**
     * @return NotebookManager
     */
    protected function getNotebookManager()
    {
        return $this->get('notebook.manager');
    }
}
