<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\PluginBundle\Entity\ImsLti\LineItem;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * Class LtiResultsResource.
 */
class LtiResultsResource extends LtiAdvantageServiceResource
{
    const URL_TEMPLATE = '/context_id/lineitems/line_item_id/results';

    /**
     * @var LineItem|null
     */
    private $lineItem;

    /**
     * LtiResultsResource constructor.
     *
     * @param int $toolId
     * @param int $courseId
     * @param int $lineItemId
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function __construct($toolId, $courseId, $lineItemId)
    {
        parent::__construct($toolId, $courseId);

        $this->lineItem = Database::getManager()->find('ChamiloPluginBundle:ImsLti\LineItem', (int) $lineItemId);
    }

    /**
     * {@inheritDoc}
     */
    public function validate()
    {
        if (!$this->course) {
            throw new BadRequestHttpException('Course not found.');
        }

        if (!$this->tool) {
            throw new BadRequestHttpException('Tool not found.');
        }

        if ($this->tool->getCourse()->getId() !== $this->course->getId()) {
            throw new AccessDeniedHttpException('Tool not found in course.');
        }

        if ($this->request->server->get('HTTP_ACCEPT') !== LtiAssignmentGradesService::TYPE_RESULT_CONTAINER) {
            throw new UnsupportedMediaTypeHttpException('Unsupported media type.');
        }

        $parentTool = $this->tool->getParent();

        if ($parentTool) {
            $advServices = $parentTool->getAdvantageServices();

            if (LtiAssignmentGradesService::AGS_NONE === $advServices['ags']) {
                throw new AccessDeniedHttpException('Assigment and grade service is not enabled for this tool.');
            }
        }

        if (!$this->lineItem) {
            throw new NotFoundHttpException('Line item not found');
        }

        if ($this->lineItem->getTool()->getId() !== $this->tool->getId()) {
            throw new AccessDeniedHttpException('Line item not found for the tool.');
        }
    }

    public function process()
    {
        switch ($this->request->getMethod()) {
            case Request::METHOD_GET:
                $this->validateToken(
                    [LtiAssignmentGradesService::SCOPE_RESULT_READ]
                );
                $this->processGet();
                break;
            default:
                throw new MethodNotAllowedHttpException([Request::METHOD_GET]);
        }
    }

    private function processGet()
    {
        $limit = $this->request->query->get('limit');
        $page = $this->request->query->get('page');
        $userId = $this->request->query->get('user_id');

        $results = $this->getResults($limit, $userId, $page);

        $data = $this->getGetData($results);

        $this->setLinkHeaderToGet($userId, $limit, $page);

        $this->response->headers->set('Content-Type', LtiAssignmentGradesService::TYPE_RESULT_CONTAINER);
        $this->response->setData($data);
    }

    private function getResults($limit, $userId, $page = 0)
    {
        $em = Database::getManager();

        $limit = (int) $limit;
        $page = (int) $page;

        $dql = 'SELECT r FROM ChamiloCoreBundle:GradebookResult r WHERE r.evaluationId = :id';
        $parameters = ['id' => $this->lineItem->getEvaluation()->getId()];

        if ($userId) {
            $dql .= ' AND r.userId = :user';
            $parameters['user'] = (int) $userId;
        }

        $query = $em->createQuery($dql);

        if ($limit > 0) {
            $query->setMaxResults($limit);

            if ($page > 0) {
                $query->setFirstResult($page * $limit);
            }
        }

        return $query
            ->setParameters($parameters)
            ->getResult();
    }

    /**
     * @param array|GradebookResult[] $results
     *
     * @return array
     */
    private function getGetData(array $results)
    {
        $data = [];

        foreach ($results as $result) {
            $lineItemEndPoint = LtiAssignmentGradesService::getLineItemUrl(
                $this->course->getId(),
                $this->lineItem->getId(),
                $this->tool->getId()
            );

            $data[] = [
                'id' => "$lineItemEndPoint/results/{$result->getId()}",
                'scoreOf' => $lineItemEndPoint,
                'userId' => (string) $result->getUserId(),
                'resultScore' => $result->getScore(),
                'resultMaximum' => $this->lineItem->getEvaluation()->getMax(),
                'comment' => null,
            ];
        }

        return $data;
    }

    /**
     * @param int $userId
     * @param int $limit
     * @param int $page
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function setLinkHeaderToGet($userId, $limit, $page = 0)
    {
        $limit = (int) $limit;
        $page = (int) $page;

        if (!$limit) {
            return;
        }

        $em = Database::getManager();

        $dql = 'SELECT COUNT(r) FROM ChamiloCoreBundle:GradebookResult r WHERE r.evaluationId = :id';
        $parameters = ['id' => $this->lineItem->getEvaluation()->getId()];

        if ($userId) {
            $dql .= ' AND r.userId = :user';
            $parameters['user'] = (int) $userId;
        }

        $count = $em
            ->createQuery($dql)
            ->setParameters($parameters)
            ->getSingleScalarResult();

        $links = [];
        $links['first'] = 0;
        $links['last'] = ceil($count / $limit);
        $links['canonical'] = $page;

        if ($page > 1) {
            $links['prev'] = $page - 1;
        }

        if ($page + 1 < $links['last']) {
            $links['next'] = $page + 1;
        }

        foreach ($links as $rel => $linkPage) {
            $url = LtiAssignmentGradesService::getResultsUrl(
                $this->course->getId(),
                $this->lineItem->getId(),
                $this->tool->getId(),
                ['user_id' => $userId, 'limit' => $limit, 'page' => $linkPage]
            );

            $links[$rel] = '<'.$url.'>; rel="'.$rel.'"';
        }

        $this->response->headers->set(
            'Link',
            implode(', ', $links)
        );
    }
}
