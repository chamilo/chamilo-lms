<?php
/* For licensing terms, see /license.txt */

namespace Application\Sonata\UserBundle\Security;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Class Encoder
 * @package Application\Sonata\UserBundle\Security
 */
class Encoder implements PasswordEncoderInterface
{
    public function encodePassword($raw, $salt)
    {
        // Do not use salt here.
        return sha1($raw);
    }

    public function isPasswordValid($encoded, $raw, $salt)
    {
        return $encoded === $this->encodePassword($raw, $salt);
    }
}
