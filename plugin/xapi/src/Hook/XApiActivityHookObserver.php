<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\SharedStatement;
use Xabbuh\XApi\Common\Exception\ConflictException;
use Xabbuh\XApi\Common\Exception\XApiException;
use Xabbuh\XApi\Model\Context;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;

/**
 * Class XApiActivityHookObserver.
 */
abstract class XApiActivityHookObserver extends HookObserver
{
    /**
     * @var \Chamilo\UserBundle\Entity\User
     */
    protected $user;
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
     * @param \Xabbuh\XApi\Model\Statement $statement
     *
     * @throws \Exception
     *
     * @return \Xabbuh\XApi\Model\Statement
     */
    protected function sendStatementToLrs(Statement $statement)
    {
        $client = XApiPlugin::create()->getXApiStatementClient();

        try {
            return $client->storeStatement($statement);
        } catch (ConflictException $e) {
            throw new Exception($e->getMessage());
        } catch (XApiException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param \Xabbuh\XApi\Model\StatementId $uuid
     * @param string                         $dataType
     * @param int                            $dataId
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return \Chamilo\PluginBundle\Entity\XApi\SharedStatement
     */
    protected function saveSharedStatement(StatementId $uuid, $dataType, $dataId)
    {
        $sharedStmt = new SharedStatement();
        $sharedStmt
            ->setUuid($uuid->getValue())
            ->setDataType($dataType)
            ->setDataId($dataId);

        $em = Database::getManager();
        $em->persist($sharedStmt);
        $em->flush();

        return $sharedStmt;
    }

    /**
     * @return \Xabbuh\XApi\Model\Statement
     */
    protected function createStatement()
    {
        return new Statement(
            $this->getId(),
            $this->getActor(),
            $this->getVerb(),
            $this->getActivity(),
            $this->getActivityResult(),
            null,
            null,
            null,
            $this->getContext()
        );
    }

    /**
     * @return \Xabbuh\XApi\Model\StatementId
     */
    abstract protected function getId();

    /**
     * @return \Xabbuh\XApi\Model\Agent
     */
    abstract protected function getActor();

    /**
     * @return \Xabbuh\XApi\Model\Verb
     */
    abstract protected function getVerb();

    /**
     * @return \Xabbuh\XApi\Model\Activity
     */
    abstract protected function getActivity();

    /**
     * @return \Xabbuh\XApi\Model\Result|null
     */
    abstract protected function getActivityResult();

    /**
     * @return \Xabbuh\XApi\Model\Context
     */
    protected function getContext()
    {
        $platform = api_get_setting('Institution').' - '.api_get_setting('siteName');

        $context = new Context();

        return $context->withPlatform($platform);
    }
}
