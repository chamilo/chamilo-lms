<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\NotebookBundle\Controller;

use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Doctrine\ORM\QueryBuilder;
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
use Symfony\Component\HttpFoundation\Request;
use Chamilo\NotebookBundle\Entity\CNotebookRepository;
use Chamilo\NotebookBundle\Entity\CNotebookManager;
use Chamilo\NotebookBundle\Entity\CNotebook;
use Chamilo\CourseBundle\Controller\ToolBaseCrudController;

/**
 * Class CNotebookController
 * @package Chamilo\NotebookBundle\Controller
 */
class CNotebookController extends ToolBaseCrudController
{
    /**
     * @Route("/")
     */
    public function indexAction(Request $request)
    {
        $source = new Entity('ChamiloNotebookBundle:CNotebook');

        $course = $this->getCourse();

        /* @var $grid \APY\DataGridBundle\Grid\Grid */
        $grid = $this->get('grid');

        /*$tableAlias = $source->getTableAlias();
        $source->manipulateQuery(function (QueryBuilder $query) use ($tableAlias, $course) {
                $query->andWhere($tableAlias . '.cId = '.$course->getId());
                //$query->resetDQLPart('orderBy');
            }
        );*/

        $resources = $this->getNotebookRepository()->getResourceByCourse($course);

        $source->setData($resources);
        $grid->setSource($source);

        //$grid->hideFilters();
        $grid->setLimits(5);
        //$grid->isReadyForRedirect();

        //$grid->setMaxResults(1);
        //$grid->setLimits(2);
        /*$grid->getColumn('id')->manipulateRenderCell(
            function ($value, $row, $router) use ($course) {
                //$router = $this->get('router');
                return $router->generate(
                    'chamilo_notebook_show',
                    array('id' => $row->getField('id'), 'course' => $course)
                );
            }
        );*/

        //$deleteMassAction = new MassAction('Delete', 'ChamiloNotebookBundle:CNotebook:deleteMass');
        $deleteMassAction = new MassAction(
            'Delete',
            'chamilo.controller.notebook:deleteMassAction',
            true,
            array('course' => $request->get('course'))
        );
        $grid->addMassAction($deleteMassAction);

        $myRowAction = new RowAction(
            'View',
            'chamilo_notebook_show',
            false,
            '_self',
            array('class' => 'btn btn-default')
        );
        $myRowAction->setRouteParameters(array('course' => $course, 'id'));
        $grid->addRowAction($myRowAction);

        $myRowAction = new RowAction(
            'Edit',
            'chamilo_notebook_edit',
            false,
            '_self',
            array('class' => 'btn btn-info')
        );
        $myRowAction->setRouteParameters(array('course' => $course, 'id'));
        $grid->addRowAction($myRowAction);

        $myRowAction = new RowAction(
            'Delete',
            'chamilo_notebook_delete',
            false,
            '_self',
            array('class' => 'btn btn-danger', 'form_delete' => true)
        );
        $myRowAction->setRouteParameters(array('course' => $course, 'id'));
        $grid->addRowAction($myRowAction);

        $grid->addExport(
            new CSVExport(
                'CSV Export', 'export', array('course' => $course)
            )
        );

        $grid->addExport(
            new ExcelExport(
                'Excel Export',
                'export',
                array('course' => $course)
            )
        );

        return $grid->getGridResponse(
            'ChamiloNotebookBundle:Notebook:index.html.twig'
        );
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        /** @var AbstractResource $resource */
        $resource = $this->createNew();
        $form = $this->getForm($resource);

        if ($form->handleRequest($request)->isValid()) {

            $resourceNode = $this->getRepository()->addResourceToCourse(
                $resource,
                $this->getUser(),
                $this->getCourse()
            );

            $resource->setResourceNode($resourceNode);

            $resource = $this->domainManager->create($resource);

            if ($this->config->isApiRequest()) {
                return $this->handleView($this->view($resource));
            }

            if (null === $resource) {
                //return $this->redirectHandler->redirectToIndex();
            }

            //return $this->redirectHandler->redirectTo($resource);
        }

        if ($this->config->isApiRequest()) {
            return $this->handleView($this->view($form));
        }

        $view = $this
            ->view()
            ->setTemplate($this->config->getTemplate('create.html'))
            ->setData(array(
                    $this->config->getResourceName() => $resource,
                    'form'                           => $form->createView()
                ))
        ;

        return $this->handleView($view);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function updateAction(Request $request)
    {
        return parent::updateAction($request);
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
                $this->deleteResource($id);
            }
        }
        return $this->routeRedirectView(
            'chamilo_notebook_index',
            array('course' => $this->getCourse())
        );
    }

    /**
     * @param int $id
     */
    public function deleteResource($id)
    {
        /** @var AbstractResource $resource */
        $resource = $this->getRepository()->find($id);
        $this->domainManager->delete($resource);

        //$this->getManager()->
    }

    /**
     * {@inheritdoc}
     */
    /*public function createNew()
    {
        $notebook = $this->getNotebookRepository()->createNewWithCourse(
            $this->getUser(),
            $this->getCourse()
        );

        return $notebook;
    }*/

    /**
     * @return CNotebookManager
     */
    protected function getManager()
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
