<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\Portfolio as PortfolioActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User as UserActor;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Downloaded;

/**
 * Class PortfolioDownloaded.
 */
class PortfolioDownloaded extends BaseStatement
{
    private User $owner;

    public function __construct(User $owner)
    {
        $this->owner = $owner;
    }

    public function generate(): array
    {
        $user = api_get_user_entity(api_get_user_id());

        $actor = new UserActor($user);
        $verb = new Downloaded();
        $object = new PortfolioActivity($this->owner);
        $context = $this->generateContext();

        return [
            'id' => $this->generateStatementId('portfolio-item'),
            'actor' => $actor->generate(),
            'verb' => $verb->generate(),
            'object' => $object->generate(),
            'timestamp' => api_get_utc_datetime(null, false, true)->format(DATE_ATOM),
            'context' => $context,
        ];
    }
}
