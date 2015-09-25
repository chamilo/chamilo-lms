<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Model;

interface UserInterface extends \FOS\UserBundle\Model\UserInterface
{
    const GENDER_FEMALE  = 'f';
    const GENDER_MALE    = 'm';
    const GENDER_MAN     = 'm'; // @deprecated
    const GENDER_UNKNOWN = 'u';

    /**
     * @return string
     */
    public function getTwoStepVerificationCode();

    /**
     * @param string $code
     *
     * @return string
     */
    public function setTwoStepVerificationCode($code);
}
