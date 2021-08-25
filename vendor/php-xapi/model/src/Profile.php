<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Model;

/**
 * A profile.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
abstract class Profile
{
    private $profileId;

    public function __construct(string $profileId)
    {
        $this->profileId = $profileId;
    }

    public function getProfileId(): string
    {
        return $this->profileId;
    }
}
