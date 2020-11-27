<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\SharedStatement;
use Doctrine\ORM\OptimisticLockException;
use Xabbuh\XApi\Common\Exception\StatementIdAlreadyExistsException;
use Xabbuh\XApi\Model\Context;
use Xabbuh\XApi\Model\ContextActivities;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Serializer\Symfony\Serializer;
use Xabbuh\XApi\Serializer\Symfony\StatementSerializer;

/**
 * Class XApiActivityHookObserver.
 */
abstract class XApiActivityHookObserver extends HookObserver
{
    use XApiStatementTrait;

    /**
     * @var \Chamilo\CoreBundle\Entity\Course
     */
    protected $course;
    /**
     * @var \Chamilo\CoreBundle\Entity\Session|null
     */
    protected $session;
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

        try {
            $em->flush();
        } catch (OptimisticLockException $e) {
            return null;
        }

        return $sharedStmt;
    }

    /**
     * @param \DateTime|null $createdAt
     *
     * @throws \Xabbuh\XApi\Common\Exception\StatementIdAlreadyExistsException
     *
     * @return \Xabbuh\XApi\Model\Statement
     */
    protected function createStatement(DateTime $createdAt = null)
    {
        $id = $this->getId();

        $sharedStmt = Database::getManager()
            ->getRepository(SharedStatement::class)
            ->findOneByUuid($id->getValue());

        if ($sharedStmt) {
            throw new StatementIdAlreadyExistsException($id->getValue());
        }

        return new Statement(
            $id,
            $this->getActor(),
            $this->getVerb(),
            $this->getActivity(),
            $this->getActivityResult(),
            null,
            $createdAt,
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

        $groupingActivities = [
            $this->generateActivityFromSite(),
            $this->generateActivityFromCourse($this->course, $this->session),
        ];

        $context = new Context();

        return $context
            ->withPlatform($platform)
            ->withLanguage(
                api_get_language_isocode()
            )
            ->withContextActivities(
                new ContextActivities(null, $groupingActivities)
            );
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
