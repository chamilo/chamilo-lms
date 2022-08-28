<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Chamilo\PluginBundle\XApi\Lrs\Util\InternalLogUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    /**
     * @var StatementRepository
     */
    private $statementRepository;
    /**
     * @var \Symfony\Component\Serializer\Serializer|\Symfony\Component\Serializer\SerializerInterface
     */
    private $serializer;
    /**
     * @var SerializerFactory
     */
    private $serializerFactory;

    public function __construct(Request $httpRequest)
    {
        parent::__construct($httpRequest);

        $pluginEm = XApiPlugin::getEntityManager();

        $this->statementRepository = new StatementRepository(
            $pluginEm->getRepository(StatementEntity::class)
        );
        $this->serializer = Serializer::createSerializer();
        $this->serializerFactory = new SerializerFactory($this->serializer);
    }

    public function get(): Response
    {
        $getStatementController = new StatementGetController(
            $this->statementRepository,
            $this->serializerFactory->createStatementSerializer(),
            $this->serializerFactory->createStatementResultSerializer(),
            new StatementsFilterFactory(
                new ActorSerializer($this->serializer)
            )
        );

        return $getStatementController->getStatement($this->httpRequest);
    }

    public function head(): Response
    {
        $headStatementController = new StatementHeadController(
            $this->statementRepository,
            $this->serializerFactory->createStatementSerializer(),
            $this->serializerFactory->createStatementResultSerializer(),
            new StatementsFilterFactory(
                new ActorSerializer($this->serializer)
            )
        );

        return $headStatementController->getStatement($this->httpRequest);
    }

    public function put(): Response
    {
        $statement = $this->serializerFactory
            ->createStatementSerializer()
            ->deserializeStatement(
                $this->httpRequest->getContent()
            )
        ;

        InternalLogUtil::saveStatementForInternalLog($statement);

        $putStatementController = new StatementPutController($this->statementRepository);

        return $putStatementController->putStatement($this->httpRequest, $statement);
    }

    public function post(): Response
    {
        $content = $this->httpRequest->getContent();

        if (substr($content, 0, 1) !== '[') {
            $content = "[$content]";
        }

        $statements = $this->serializerFactory
            ->createStatementSerializer()
            ->deserializeStatements($content)
        ;

        foreach ($statements as $statement) {
            InternalLogUtil::saveStatementForInternalLog($statement);
        }

        $postStatementController = new StatementPostController($this->statementRepository);

        return $postStatementController->postStatements($this->httpRequest, $statements);
    }
}
