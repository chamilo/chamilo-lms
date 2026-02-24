<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Chamilo\PluginBundle\XApi\Lrs\Util\InternalLogUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;
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

        $putStatementController = new StatementPutController($this->statementRepository);

        $response = $putStatementController->putStatement($this->httpRequest, $statement);

        $this->saveLog(
            [$this->httpRequest->query->get('statementId')]
        );

        return $response;
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
  
    /**
     * @param array<string> $statementsId
     *
     * @return void
     */
    private function saveLog(array $statementsId)
    {
        foreach ($statementsId as $statementId) {
            try {
                $storedStatement = $this->statementRepository->findStatementById(
                    StatementId::fromString($statementId)
                );

                InternalLogUtil::saveStatementForInternalLog($storedStatement);
            } catch (NotFoundException $e) {
            }
        }
    }
}
