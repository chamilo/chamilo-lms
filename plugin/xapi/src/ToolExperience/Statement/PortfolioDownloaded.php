<?php

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\PluginBundle\XApi\ToolExperience\Activity\Portfolio as PortfolioActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User as UserActor;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Downloaded;
use Chamilo\UserBundle\Entity\User;
use Xabbuh\XApi\Model\Statement;

class PortfolioDownloaded extends BaseStatement
{
    /** @var User */
    private $owner;

    public function __construct(User $owner)
    {
        $this->owner = $owner;
    }

    public function generate(): Statement
    {
        $user = api_get_user_entity(api_get_user_id());

        $actor = new UserActor($user);
        $verb = new Downloaded();
        $object = new PortfolioActivity($this->owner);
        $context = $this->generateContext();

        return new Statement(
            $this->generateStatementId('portfolio-item'),
            $actor->generate(),
            $verb->generate(),
            $object->generate(),
            null,
            null,
            api_get_utc_datetime(null, false, true),
            null,
            $context
        );
    }
}
