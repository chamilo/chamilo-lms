<?php

/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;

class HTML_QuickForm_Rule_NoSameCurrentPassword extends HTML_QuickForm_Rule
{
    public function validate($value, $options)
    {
        /** @var User $user */
        $user = $options;

        return !UserManager::isPasswordValid($user->getPassword(), $value, $user->getSalt());
    }
}
