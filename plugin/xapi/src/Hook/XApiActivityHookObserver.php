<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\SharedStatement;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Serializer\Symfony\Serializer;
use Xabbuh\XApi\Serializer\Symfony\StatementSerializer;

/**
 * Class XApiActivityHookObserver.
 */
abstract class XApiActivityHookObserver extends HookObserver
{
    /**
     * @var \XApiPlugin
     */
    protected $plugin;

    /**
     * XApiActivityHookObserver constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            'plugin/xapi/src/XApiPlugin.php',
            'xapi'
        );

        $this->plugin = XApiPlugin::create();
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return \Chamilo\PluginBundle\Entity\XApi\SharedStatement|null
     */
    protected function saveSharedStatement(Statement $statement)
    {
        $statementSerialized = $this->serializeStatement($statement);

        $sharedStmt = new SharedStatement(
            json_decode($statementSerialized, true)
        );

        $em = Database::getManager();
        $em->persist($sharedStmt);
        $em->flush();

        return $sharedStmt;
    }

    /**
     * Serialize a statement to JSON.
     *
     * @return string
     */
    private function serializeStatement(Statement $statement)
    {
        $serializer = Serializer::createSerializer();
        $statementSerializer = new StatementSerializer($serializer);

        return $statementSerializer->serializeStatement($statement);
    }
}
