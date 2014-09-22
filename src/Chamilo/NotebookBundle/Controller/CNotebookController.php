<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\NotebookBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Export\CSVExport;
use APY\DataGridBundle\Grid\Export\ExcelExport;
use APY\DataGridBundle\Grid\Export\PHPExcelPDFExport;
//use APY\DataGridBundle\Grid\Export\XmlExport;

use APY\DataGridBundle\Grid\Action\MassAction;
use APY\DataGridBundle\Grid\Action\RowAction;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\Request;
use Chamilo\NotebookBundle\Entity\CNotebookRepository;
use Chamilo\NotebookBundle\Entity\CNotebookManager;
use Chamilo\NotebookBundle\Entity\CNotebook;

/**
 * Class CNotebookController
 * @package Chamilo\NotebookBundle\Controller
 */
class CNotebookController extends ResourceController
{

    /**
     * @Route("/")
     */
    public function indexAction(Request $request)
    {
        $source = new Entity('ChamiloNotebookBundle:CNotebook');

        $courseCode = $request->get('course');

        /* @var $grid \APY\DataGridBundle\Grid\Grid */
        $grid = $this->get('grid');
        $grid->setSource($source);
        //$grid->hideFilters();
        $grid->setLimits(2);
        //$grid->isReadyForRedirect();

        //$grid->setMaxResults(1);
        //$grid->setLimits(2);
        /*$grid->getColumn('id')->manipulateRenderCell(
            function ($value, $row, $router) use ($courseCode) {
                //$router = $this->get('router');
                return $router->generate(
                    'chamilo_notebook_show',
                    array('id' => $row->getField('id'), 'course' => $courseCode)
                );
            }
        );*/

        //$deleteMassAction = new MassAction('Delete', 'ChamiloNotebookBundle:CNotebook:deleteMass');
        $deleteMassAction = new MassAction('Delete', 'chamilo.controller.notebook:deleteMassAction', true, array('course' => $request->get('course')));
        $grid->addMassAction($deleteMassAction);

        $myRowAction = new RowAction('View', 'chamilo_notebook_show', false, '_self', array('class' => 'btn btn-default'));
        $myRowAction->setRouteParameters(array('course' => $courseCode, 'id'));
        $grid->addRowAction($myRowAction);

        $myRowAction = new RowAction('Edit', 'chamilo_notebook_edit', false, '_self', array('class' => 'btn btn-info'));
        $myRowAction->setRouteParameters(array('course' => $courseCode, 'id'));
        $grid->addRowAction($myRowAction);

        $myRowAction = new RowAction('Delete', 'chamilo_notebook_delete', false, '_self', array('class' => 'btn btn-danger', 'form_delete' => true));
        $myRowAction->setRouteParameters(array('course' => $courseCode, 'id'));
        $grid->addRowAction($myRowAction);

        $grid->addExport(new CSVExport('CSV Export', 'export', array('course' => $courseCode)));
        $grid->addExport(new ExcelExport('Excel Export', 'export', array('course' => $courseCode)));

        return $grid->getGridResponse('ChamiloNotebookBundle:Notebook:index.html.twig');
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function updateAction(Request $request)
    {
        $resource = $this->findOr404($request);

        parent::updateAction($request);
    }

    /**
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function deleteMassAction(Request $request)
    {
        $primaryKeys = $request->get('primaryKeys');
        if (!empty($primaryKeys)) {
            foreach ($primaryKeys as $id) {
                $resource = $this->getRepository()->find($id);
                $this->domainManager->delete($resource);
            }
        }
        return $this->routeRedirectView('chamilo_notebook_index', array('course' => $request->get('course')));
    }

    /**
     * {@inheritdoc}
     */
    public function createNew()
    {
        $request = $this->getRequest();
        $courseCode = $request->get('course');
        $course = $this->get('chamilo_core.manager.course')->findOneByCode($courseCode);
        /** @var CNotebook $notebook */
        $notebook = $this->getNotebookRepository()->createNewWithCourse($this->getUser(), $course);

        //$notebook->setSession();
        return $notebook;
    }

    /**
     * @return CNotebookManager
     */
    protected function getNotebookManager()
    {
        return $this->get('chamilo_notebook.notebook_manager');
    }

    /**
     * @return CNotebookRepository
     */
    protected function getNotebookRepository()
    {
        return $this->get('chamilo.repository.notebook');
    }
}
