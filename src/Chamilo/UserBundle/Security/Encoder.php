<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Security;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Class Encoder
 * @package Chamilo\UserBundle\Security
 */
class Encoder implements PasswordEncoderInterface
{
    protected $method;

    /**
     * @param $method
     */
    public function __construct($method)
    {
        $this->method = $method;
    }
    /**
     * @param string $raw
     * @param string $salt
     * @return string
     */
    public function encodePassword($raw, $salt)
    {
        $encrypted = null;
        switch ($this->method) {
            case 'sha1':
                $encrypted = sha1($raw);
                break;
            case 'md5':
                $encrypted = md5($raw);
                break;
            case 'none':
                $encrypted = $raw;
        }

        // Do not use salt here.
        return $encrypted;
    }

    /**
     * @param string $encoded
     * @param string $raw
     * @param string $salt
     * @return bool
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        return $encoded === $this->encodePassword($raw, $salt);
    }
}
