<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Actor;

use Chamilo\CoreBundle\Entity\User as UserEntity;

/**
 * Class User.
 */
class User extends BaseActor
{
    public function __construct(private UserEntity $user)
    {
    }

    public function generate(): array
    {
        $fullName = $this->normalizeString($this->user->getFullName());
        $email = $this->normalizeString($this->user->getEmail());

        if ('' !== $email) {
            return [
                'objectType' => 'Agent',
                'name' => $fullName,
                'mbox' => 'mailto:'.$email,
            ];
        }

        $homePage = rtrim(api_get_path(WEB_PATH), '/').'/';
        $accountName = method_exists($this->user, 'getUsername')
            ? $this->normalizeString($this->user->getUsername())
            : (string) $this->user->getId();

        return [
            'objectType' => 'Agent',
            'name' => $fullName,
            'account' => [
                'homePage' => $homePage,
                'name' => $accountName,
            ],
        ];
    }
}
