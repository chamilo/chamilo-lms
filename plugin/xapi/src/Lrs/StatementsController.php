<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Symfony\Component\HttpFoundation\Response;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementResult;
use Xabbuh\XApi\Serializer\Symfony\ActorSerializer;
use Xabbuh\XApi\Serializer\Symfony\Serializer;
use Xabbuh\XApi\Serializer\Symfony\StatementResultSerializer;
use Xabbuh\XApi\Serializer\Symfony\StatementSerializer;
use XApi\LrsBundle\Controller\StatementGetController;
use XApi\LrsBundle\Controller\StatementPostController;
use XApi\LrsBundle\Controller\StatementPutController;
use XApi\LrsBundle\Model\StatementsFilterFactory;
use XApi\Repository\Doctrine\Mapping\Statement as StatementEntity;
use XApi\Repository\Doctrine\Repository\StatementRepository;
use XApiPlugin;

/**
 * Class StatementsController.
 *
 * @package Chamilo\PluginBundle\XApi\Lrs
 */
class StatementsController extends BaseController
{
    public function get()
    {
        $pluginEm = XApiPlugin::getEntityManager();

        $serializer = Serializer::createSerializer();

        $getStatementController = new StatementGetController(
            new StatementRepository(
                $pluginEm->getRepository(StatementEntity::class)
            ),
            new StatementSerializer($serializer),
            new StatementResultSerializer($serializer),
            new StatementsFilterFactory(
                new ActorSerializer($serializer)
            )
        );

        return $getStatementController->getStatement($this->httpRequest);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function put()
    {
        $pluginEm = XApiPlugin::getEntityManager();

        $putStatementController = new StatementPutController(
            new StatementRepository(
                $pluginEm->getRepository(StatementEntity::class)
            )
        );

        $statement = $this->deserializeStatement(
            $this->httpRequest->getContent()
        );

        return $putStatementController->putStatement($this->httpRequest, $statement);
    }

    public function post(): Response
    {
        $pluginEm = XApiPlugin::getEntityManager();

        $postStatementController = new StatementPostController(
            new StatementRepository(
                $pluginEm->getRepository(StatementEntity::class)
            )
        );

        $statements = $this->deserializeStatements(
            $this->httpRequest->getContent()
        );

        return $postStatementController->postStatements($this->httpRequest, $statements);
    }

    /**
     * @param string $content
     *
     * @return \Xabbuh\XApi\Model\Statement
     */
    private function deserializeStatement($content)
    {
        $serializer = Serializer::createSerializer();

        return $serializer->deserialize($content, Statement::class, 'json');
    }

    private function deserializeStatements($content): array
    {
        $serializer = Serializer::createSerializer();

        return $serializer->deserialize($content, Statement::class.'[]', 'json');
    }
}
