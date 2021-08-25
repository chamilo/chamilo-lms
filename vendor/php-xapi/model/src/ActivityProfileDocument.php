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
 * A {@link Document} that is related to an {@link Activity}.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class ActivityProfileDocument extends Document
{
    private $profile;

    public function __construct(ActivityProfile $profile, DocumentData $data)
    {
        parent::__construct($data);

        $this->profile = $profile;
    }

    public function getActivityProfile(): ActivityProfile
    {
        return $this->profile;
    }
}
