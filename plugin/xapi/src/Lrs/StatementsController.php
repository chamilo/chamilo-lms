<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Serializer\Symfony\Serializer;
use XApi\LrsBundle\Controller\StatementPutController;
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
}
