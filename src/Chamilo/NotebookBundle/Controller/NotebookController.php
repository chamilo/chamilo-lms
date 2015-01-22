<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\NotebookBundle\Controller;

use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Resource\ResourceRights;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\ToolResourceRights;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\NotebookBundle\Tool\Notebook;
use Doctrine\ORM\QueryBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
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
use Chamilo\NotebookBundle\Entity\NotebookRepository;
use Chamilo\NotebookBundle\Entity\NotebookManager;
use Chamilo\NotebookBundle\Entity\CNotebook;
use Chamilo\CourseBundle\Controller\ToolBaseCrudController;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

/**
 * Class NotebookController
 * @package Chamilo\NotebookBundle\Controller
 */
class NotebookController extends ToolBaseCrudController
{
    /**
     * @Route("/")
     */
    public function indexAction(Request $request)
    {
        /*if (false === $this->get('security.authorization_checker')->isGranted('view', $course)) {
           throw new AccessDeniedException('Unauthorised access!');
        }*/

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

        /** @var NotebookRepository $repository */
        $repository = $this->getRepository();
        $resources = $repository->getResourceByCourse($course);

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
     * @return Response
     */
    public function showAction(Request $request)
    {
        /** @var AbstractResource $resource */

        $resource = $this->findOr404($request);
        $resourceNode = $resource->getResourceNode();

        if (false === $this->isGranted(ResourceNodeVoter::VIEW, $resourceNode)) {
            throw new AccessDeniedException('Unauthorised access!');
        }

        $view = $this
            ->view()
            ->setTemplate($this->config->getTemplate('show.html'))
            ->setTemplateVar($this->config->getResourceName())
            ->setData($resource)
        ;

        return $this->handleView($view);
    }

    /**
     * @param ResourceNode $resourceNode
     * @return ResourceLink|null
     */
    public function detectLink(ResourceNode $resourceNode)
    {
        $user = $this->getUser();
        $session = $this->getSession();
        $course = $this->getCourse();

        /*if ($user->getId() == $resourceNode->getCreator()->getId()) {

        }*/

        $links = $resourceNode->getLinks();

        $linkFound = null;

        if (!empty($links)) {
            /** @var ResourceLink $link */
            foreach ($links as $link) {
                $linkCourse = $link->getCourse();
                $linkSession = $link->getSession();
                $linkUser = $link->getUser();

                if (isset($course) && isset($session)) {
                    if ($linkCourse->getId() == $course->getId() &&
                        $linkSession->getId() == $session->getId()
                    ) {
                        $linkFound = $link;
                        break;
                    }
                }

                if (isset($course) && isset($linkCourse)) {
                    if ($linkCourse->getId() == $course->getId()) {
                        $linkFound = $link;
                        break;
                    }
                }

                if (isset($linkUser)) {
                    if ($linkUser->getId() == $user->getId()) {
                        $linkFound = $link;
                        break;
                    }
                }
            }
        }

        if (empty($linkFound)) {
            throw new NotFoundResourceException('Link not found');
        }
        return $linkFound;
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
            $sharedType = $form->get('shared')->getData();
            $shareList = array();

            switch ($sharedType) {
                case 'this_course':
                    // Default Chamilo behaviour:
                    // Teachers can edit and students can see
                    $shareList = array(
                        array(
                            'sharing' => 'course',
                            'mask' => ToolResourceRights::getReaderMask(),
                            'role' => 'ROLE_STUDENT',
                            'search' => $this->getCourse()->getId()
                        ),
                        array(
                            'sharing' => 'course',
                            'mask' => ToolResourceRights::getEditorMask(),
                            'role' => 'ROLE_TEACHER',
                            'search' => $this->getCourse()->getId()
                        )
                    );
                    break;
                case 'shared':
                    $shareList = $form->get('rights')->getData();
                    break;
                case 'only_me':
                    $shareList = array(
                        array(
                            'sharing' => 'user',
                            'only_me' => true
                        )
                    );
                    break;
            }

            /** @var NotebookRepository $repository */
            $repository = $this->getRepository();

            $resourceNode = $repository->addResourceNode(
                $resource,
                $this->getUser()
            );
            // Loops all sharing options
            foreach ($shareList as $share) {
                $idList = array();
                if (isset($share['search'])) {
                    $idList = explode(',', $share['search']);
                }

                $resourceRight = new ResourceRights();
                $resourceRight->setMask($share['mask']);
                $resourceRight->setRole($share['role']);

                // Build links
                switch ($share['sharing']) {
                    case 'everyone':
                        $repository->addResourceToEveryone(
                            $resourceNode,
                            $resourceRight
                        );
                        break;
                    case 'course':
                        $repository->addResourceToCourse(
                            $resourceNode,
                            $this->getCourse(),
                            $resourceRight
                        );
                        break;
                    case 'session':
                        $repository->addResourceToSession(
                            $resourceNode,
                            $this->getCourse(),
                            $this->getSession(),
                            $resourceRight
                        );
                        break;
                    case 'user':
                        // Only for me
                        if (isset($rights['only_me'])) {
                            $repository->addResourceOnlyToMe(
                                $resourceNode
                            );
                        } else {
                            // To other users
                            $repository->addResourceToUserList(
                                $resourceNode,
                                $idList
                            );
                        }
                        break;
                    case 'group':
                        // @todo
                        break;
                }
            }

            $resource->setResourceNode($resourceNode);

            $resource = $this->domainManager->create($resource);

            if ($this->config->isApiRequest()) {
                return $this->handleView($this->view($resource));
            }

            if (null === $resource) {
                return $this->redirectHandler->redirectToIndex();
            }

            return $this->redirectHandler->redirectTo($resource);
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
    }

    /**
     * @return NotebookManager
     */
    protected function getManager()
    {
        return $this->get('chamilo_notebook.entity.notebook_manager');
    }

}
