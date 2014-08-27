<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\NotebookBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Export\CSVExport;
use APY\DataGridBundle\Grid\Action\RowAction;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\Request;
use Chamilo\NotebookBundle\Entity\CNotebookRepository;
use Chamilo\NotebookBundle\Entity\CNotebookManager;
use Chamilo\NotebookBundle\Entity\CNotebook;

class CNotebookController extends ResourceController
{
    /**
     * @Route("/")
     * @Template("ChamiloNotebookBundle::index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $source = new Entity('ChamiloNotebookBundle:CNotebook');

        $courseCode = $request->get('course');

        /* @var $grid \APY\DataGridBundle\Grid\Grid */
        $grid = $this->get('grid');
        $grid->setSource($source);
        $grid->hideFilters();

        //$grid->setMaxResults(1);
        $grid->setLimits(1);
        $grid->getColumn('id')->manipulateRenderCell(
            function ($value, $row, $router) use ($courseCode) {
                $router = $this->get('router');
                return $router->generate(
                    'chamilo_notebook_show',
                    array('id' => $row->getField('id'), 'course' => $courseCode)
                );
            }
        );

        $myRowAction = new RowAction('More Info', 'administration', false, '_self', array('class' => 'btn'));
        $grid->addRowAction($myRowAction);
        $grid->addExport(new CSVExport('CSV Export'));

        return $grid->getGridResponse('ChamiloNotebookBundle:Notebook:index.html.twig');
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
        $notebook = $this->getNotebookRepository()->createNewWithCourse($course);
        $user = $this->getUser();

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
