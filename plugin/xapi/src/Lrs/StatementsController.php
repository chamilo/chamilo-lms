<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Chamilo\PluginBundle\XApi\Lrs\Util\InternalLogUtil;
use Symfony\Component\HttpFoundation\Response;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Serializer\Symfony\ActorSerializer;
use Xabbuh\XApi\Serializer\Symfony\Serializer;
use Xabbuh\XApi\Serializer\Symfony\SerializerFactory;
use XApi\LrsBundle\Controller\StatementGetController;
use XApi\LrsBundle\Controller\StatementHeadController;
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
    public function get(): Response
    {
        $pluginEm = XApiPlugin::getEntityManager();

        $serializer = Serializer::createSerializer();
        $factory = new SerializerFactory($serializer);

        $getStatementController = new StatementGetController(
            new StatementRepository(
                $pluginEm->getRepository(StatementEntity::class)
            ),
            $factory->createStatementSerializer(),
            $factory->createStatementResultSerializer(),
            new StatementsFilterFactory(
                new ActorSerializer($serializer)
            )
        );

        return $getStatementController->getStatement($this->httpRequest);
    }

    public function head(): Response
    {
        $pluginEm = XApiPlugin::getEntityManager();

        $serializer = Serializer::createSerializer();
        $factory = new SerializerFactory($serializer);

        $headStatementController = new StatementHeadController(
            new StatementRepository(
                $pluginEm->getRepository(StatementEntity::class)
            ),
            $factory->createStatementSerializer(),
            $factory->createStatementResultSerializer(),
            new StatementsFilterFactory(
                new ActorSerializer($serializer)
            )
        );

        return $headStatementController->getStatement($this->httpRequest);
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

        InternalLogUtil::saveStatementForInternalLog($statement);

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

        $content = $this->httpRequest->getContent();

        if (substr($content, 0, 1) !== '[') {
            $content = "[$content]";
        }

        $statements = $this->deserializeStatements($content);

        foreach ($statements as $statement) {
            InternalLogUtil::saveStatementForInternalLog($statement);
        }

        return $postStatementController->postStatements($this->httpRequest, $statements);
    }

    private function deserializeStatement(string $content = ''): Statement
    {
        $factory = new SerializerFactory(Serializer::createSerializer());

        return $factory->createStatementSerializer()->deserializeStatement($content);
    }

    private function deserializeStatements(string $content = ''): array
    {
        $factory = new SerializerFactory(Serializer::createSerializer());

        return $factory->createStatementSerializer()->deserializeStatements($content);
    }
}
